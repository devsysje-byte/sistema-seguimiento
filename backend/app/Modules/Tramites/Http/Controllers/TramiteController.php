<?php

namespace App\Modules\Tramites\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Tramites\Http\Requests\AsignarTutorRequest;
use App\Modules\Tramites\Http\Requests\CrearTramiteRequest;
use App\Modules\Tramites\Http\Requests\RevisarTramiteRequest;
use App\Modules\Tramites\Http\Requests\TransicionarTramiteRequest;
use App\Modules\Tramites\Services\TramiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de trámites de titulación.
 *
 * Expone el API de trámites: creación, revisión, transiciones, asignación de
 * tutor, paneles de consulta y estadísticas. Capa delgada: toda la lógica vive
 * en \App\Modules\Tramites\Services\TramiteService y la autorización por rol
 * está declarada en las rutas.
 */
class TramiteController extends Controller
{
    public function __construct(private readonly TramiteService $tramiteService)
    {
    }

    /**
     * GET /api/estudiante/tramite-activo
     */
    public function tramiteActivo(Request $request): JsonResponse
    {
        return response()->json($this->tramiteService->activoDe($request->user()));
    }

    /**
     * POST /api/tramites
     */
    public function store(CrearTramiteRequest $request): JsonResponse
    {
        try {
            $tramite = $this->tramiteService->crear($request->validated(), $request->user());

            return response()->json($tramite, 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /api/tramites/pendientes (roles de gestión)
     */
    public function pendientes(): JsonResponse
    {
        return response()->json($this->tramiteService->pendientes());
    }

    /**
     * GET /api/tramites/estadisticas (roles de gestión)
     */
    public function estadisticas(): JsonResponse
    {
        return response()->json($this->tramiteService->estadisticas());
    }

    /**
     * POST /api/tramites/{id}/revisar (roles de gestión)
     */
    public function revisar(RevisarTramiteRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        return response()->json(
            $this->tramiteService->revisar(
                $id,
                $validated['accion'],
                $validated['observaciones'] ?? null,
                $request->user()
            )
        );
    }

    /**
     * POST /api/tramites/{id}/transicionar (roles de gestión + concejo)
     */
    public function transicionar(TransicionarTramiteRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        return response()->json(
            $this->tramiteService->transicionar(
                $id,
                $validated['nuevo_estado'],
                $validated['observaciones'] ?? null,
                $request->user()
            ),
            201
        );
    }

    /**
     * POST /api/tramites/{id}/asignar-tutor (roles de gestión)
     */
    public function asignarTutor(AsignarTutorRequest $request, int $id): JsonResponse
    {
        return response()->json(
            $this->tramiteService->asignarTutor($id, $request->validated()['id_tutor'])
        );
    }

    /**
     * GET /api/tutorias (solo docente)
     */
    public function tutorias(Request $request): JsonResponse
    {
        return response()->json($this->tramiteService->tutoriasDe($request->user()));
    }

    /**
     * GET /api/tramites/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json($this->tramiteService->mostrar($id, $request->user()));
    }
}