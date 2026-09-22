<?php

namespace App\Modules\Tesis\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tesis\Http\Requests\ReenviarDocumentoRequest;
use App\Modules\Tesis\Http\Requests\ReenviarPerfilRequest;
use App\Modules\Tesis\Http\Requests\SolicitarFechaDefensaRequest;
use App\Modules\Tesis\Http\Requests\SolicitudTesisRequest;
use App\Modules\Tesis\Services\TesisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador del flujo del estudiante en el Módulo de Tesis de Grado.
 *
 * Capa delgada: solicitud con 3 documentos, reenvío del perfil y consulta de
 * configuración del módulo. La lógica vive en TesisService y la autorización
 * por rol está en las rutas y en cada FormRequest.
 */
class TesisController extends Controller
{
    public function __construct(private readonly TesisService $tesisService)
    {
    }

    /**
     * POST /api/tesis/solicitud
     */
    public function store(SolicitudTesisRequest $request): JsonResponse
    {
        try {
            $tramite = $this->tesisService->crearSolicitud($request->validated(), $request->user());

            return response()->json($tramite, 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * POST /api/tesis/{id}/reenviar
     */
    public function reenviar(ReenviarPerfilRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        try {
            $tramite = $this->tesisService->reenviar(
                $id,
                $request->user(),
                $validated['perfil'] ?? null,
                $validated['observaciones'] ?? null
            );

            return response()->json($tramite);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'No autorizado'], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first()], 422);
        }
    }

    /**
     * POST /api/tesis/{id}/reenviar-documento
     */
    public function reenviarDocumento(ReenviarDocumentoRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        try {
            $tramite = $this->tesisService->reenviarDocumentoFinal(
                $id,
                $request->user(),
                $validated['documento_final'] ?? null,
                $validated['observaciones'] ?? null
            );

            return response()->json($tramite);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'No autorizado'], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first()], 422);
        }
    }

    /**
     * POST /api/tesis/{id}/solicitar-fecha-defensa
     */
    public function solicitarFechaDefensa(SolicitarFechaDefensaRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        try {
            $tramite = $this->tesisService->solicitarFechaDefensa(
                $id,
                $request->user(),
                $validated['fecha_sugerida'] ?? null
            );

            return response()->json($tramite);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => 'No autorizado'], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first()], 422);
        }
    }

    /**
     * GET /api/tesis/config
     */
    public function config(Request $request): JsonResponse
    {
        return response()->json([
            'plazo_presentacion_meses' => config('tesis.plazo_presentacion_meses'),
            'dias_correccion' => config('tesis.dias_correccion'),
            'dias_remodalidad' => config('tesis.dias_remodalidad'),
            'tipos_documento' => config('tesis.tipos_documento'),
            'estado_reenvio' => $this->tesisService->estadoReenvio(),
        ]);
    }
}