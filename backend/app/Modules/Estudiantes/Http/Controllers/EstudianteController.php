<?php

namespace App\Modules\Estudiantes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Estudiantes\Http\Requests\PerfilEstudianteRequest;
use App\Modules\Estudiantes\Http\Requests\StoreEstudianteRequest;
use App\Modules\Estudiantes\Services\EstudianteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador del perfil de estudiante.
 *
 * Expone tanto la gestión administrativa del perfil aislado (crear estudiantes,
 * listarlos y consultar quiénes aún no tienen cuenta de acceso) como la
 * auto-gestión del estudiante autenticado. La lógica se delega en
 * \App\Modules\Estudiantes\Services\EstudianteService.
 */
class EstudianteController extends Controller
{
    public function __construct(private readonly EstudianteService $estudianteService) {}

    /**
     * GET /api/estudiantes (solo admin) — listado paginado de estudiantes.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->estudianteService->index(
                $request->integer('per_page', 20),
                $request->string('q')?->toString()
            )
        );
    }

    /**
     * POST /api/estudiantes (solo admin) — alta del perfil aislado del estudiante.
     */
    public function store(StoreEstudianteRequest $request): JsonResponse
    {
        $estudiante = $this->estudianteService->crear($request->validated());

        return response()->json($estudiante, 201);
    }

    /**
     * GET /api/estudiantes/sin-usuario (solo admin) — candidatos a CREAR USUARIO.
     */
    public function sinUsuario(Request $request): JsonResponse
    {
        return response()->json(
            $this->estudianteService->sinUsuario(
                $request->integer('per_page', 20),
                $request->string('q')?->toString()
            )
        );
    }

    /**
     * GET /api/estudiante/perfil (autenticado).
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($this->estudianteService->perfilDe($request->user()));
    }

    /**
     * PUT /api/estudiante/perfil (solo rol estudiante) — auto-gestión académica.
     */
    public function updatePerfil(PerfilEstudianteRequest $request): JsonResponse
    {
        $perfil = $this->estudianteService->guardarPerfil(
            $request->user(),
            $request->validated()
        );

        return response()->json($perfil);
    }
}
