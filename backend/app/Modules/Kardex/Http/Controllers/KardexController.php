<?php

namespace App\Modules\Kardex\Http\Controllers;

use App\Modules\Kardex\Http\Requests\AsignarModalidadRequest;
use App\Modules\Kardex\Http\Requests\BuscarPostulanteRequest;
use App\Modules\Kardex\Http\Requests\ConsultarPostulanteRequest;
use App\Modules\Kardex\Services\KardexService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Controlador del Dashboard de KARDEX.
 *
 * Endpoints consumidos por el módulo frontal `kardex`: búsqueda de postulantes
 * por CI/RU, asignación de modalidad con generación de credenciales y consulta
 * del flujo actual del postulante bajo la modalidad asignada.
 */
class KardexController extends Controller
{
    public function __construct(
        private readonly KardexService $kardex,
    ) {}

    /**
     * Busca un postulante por CI o registro universitario.
     */
    public function buscar(BuscarPostulanteRequest $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            ...$this->kardex->buscarPostulante($request->validated('identificador')),
        ]);
    }

    /**
     * Asigna la modalidad y genera las credenciales de acceso (una sola vez).
     */
    public function asignarModalidad(AsignarModalidadRequest $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            ...$this->kardex->asignarModalidad($request->validated(), $request->user()),
        ], 201);
    }

    /**
     * Consulta el flujo actual del postulante (trámite más reciente).
     */
    public function consultar(ConsultarPostulanteRequest $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            ...$this->kardex->consultar($request->validated('identificador')),
        ]);
    }

    /**
     * Resumen estadístico del dashboard: totales por modalidad, estudiantes y
     * tutores para la vista principal del KARDEX.
     */
    public function resumen(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'resumen' => $this->kardex->resumen(),
        ]);
    }
}