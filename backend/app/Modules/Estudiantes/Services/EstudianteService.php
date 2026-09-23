<?php

namespace App\Modules\Estudiantes\Services;

use App\Models\Estudiante;
use App\Models\User;
use App\Support\BasePaginadoService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Casos de uso del perfil de estudiante.
 *
 * Centraliza:
 *  - Gestión ADMINISTRATIVA del perfil aislado: alta de estudiantes y listados
 *    (con o sin cuenta de acceso creada en `users`).
 *  - AUTO-GESTIÓN del estudiante autenticado: consulta de su perfil y
 *    actualización de los campos académicos que mantiene por su cuenta
 *    (`plan_estudios`, `fecha_conclusion_plan`, `promedio_global`). Los datos
 *    personales e institucionales solo los modifica el administrador.
 */
class EstudianteService extends BasePaginadoService
{
    /** Columnas estrictas expuestas en los listados administrativos. */
    private const COLUMNAS = [
        'id_estudiante', 'ci', 'nombres', 'apellidos', 'registro_universitario',
        'fecha_nacimiento', 'plan_estudios', 'fecha_conclusion_plan',
        'promedio_global', 'created_at', 'updated_at',
    ];

    /**
     * Lista paginada de estudiantes (solo admin), con búsqueda opcional.
     *
     * @return array{data: array, meta: array<string, int|bool>}
     */
    public function index(?int $perPage = 20, ?string $q = null): array
    {
        return $this->paginar(
            $this->queryBase($q),
            $this->porPagina($perPage)
        );
    }

    /**
     * Lista paginada de estudiantes SIN cuenta de acceso aún (solo admin).
     *
     * Alimenta la pantalla de alta de credenciales (CREAR USUARIO): el
     * administrador selecciona a un estudiante ya registrado en el sistema.
     *
     * @return array{data: array, meta: array<string, int|bool>}
     */
    public function sinUsuario(?int $perPage = 20, ?string $q = null): array
    {
        return $this->paginar(
            $this->queryBase($q)->doesntHave('user'),
            $this->porPagina($perPage)
        );
    }

    /**
     * Registra administrativamente el perfil aislado del estudiante (solo admin).
     */
    public function crear(array $datos): Estudiante
    {
        return Estudiante::create($datos);
    }

    /**
     * Devuelve el perfil del estudiante autenticado (o null si aún no existe).
     */
    public function perfilDe(User $user): ?Estudiante
    {
        $perfil = $user->estudiante;

        return $perfil?->load('user:id_usuario,email');
    }

    /**
     * Actualiza los campos académicos del estudiante autenticado.
     *
     * El perfil debió ser creado previamente por el administrador: si el
     * estudiante de sesión aún no tiene registro, no puede auto-gestionarlo.
     *
     * @throws \DomainException Si el estudiante no tiene perfil registrado.
     */
    public function guardarPerfil(User $user, array $datos): Estudiante
    {
        $perfil = $user->estudiante;

        if ($perfil === null) {
            throw new \DomainException(
                'Tu perfil de estudiante aún no ha sido registrado por el administrador.'
            );
        }

        $perfil->update($datos);

        return $perfil->fresh();
    }

    /**
     * Consulta base de estudiantes con selección estricta y búsqueda por
     * nombre, apellido, CI o registro universitario.
     */
    private function queryBase(?string $q = null): Builder
    {
        $query = Estudiante::query()
            ->select(self::COLUMNAS)
            ->with('user:id_usuario,username,rol,activo')
            ->orderByDesc('created_at');

        if ($q !== null && trim($q) !== '') {
            $like = '%'.trim($q).'%';
            $query->where(function ($busqueda) use ($like) {
                $busqueda
                    ->where('ci', 'like', $like)
                    ->orWhere('nombres', 'like', $like)
                    ->orWhere('apellidos', 'like', $like)
                    ->orWhere('registro_universitario', 'like', $like);
            });
        }

        return $query;
    }
}
