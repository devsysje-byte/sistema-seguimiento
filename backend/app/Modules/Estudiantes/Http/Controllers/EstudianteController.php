<?php

namespace App\Modules\Estudiantes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Estudiantes\Http\Requests\PerfilEstudianteRequest;
use App\Modules\Estudiantes\Services\EstudianteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador del perfil de estudiante.
 *
 * Consulta y registro/actualización del perfil académico del usuario
 * autenticado. La lógica se delega en \App\Modules\Estudiantes\Services\EstudianteService.
 */
class EstudianteController extends Controller
{
    public function __construct(private readonly EstudianteService $estudianteService)
    {
    }

    /**
     * GET /api/estudiante/perfil
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($this->estudianteService->perfilDe($request->user()));
    }

    /**
     * POST /api/estudiante/perfil (solo rol estudiante)
     */
    public function store(PerfilEstudianteRequest $request): JsonResponse
    {
        $perfil = $this->estudianteService->guardarPerfil(
            $request->user(),
            $request->validated()
        );

        return response()->json($perfil);
    }
}