<?php

namespace App\Modules\Usuarios\Services;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Casos de uso de gestión de usuarios.
 *
 * Centraliza el CRUD de usuarios (alta, consulta, actualización y baja lógica)
 * y el listado de docentes. La autorización por rol se resuelve en las rutas
 * vía el middleware `role`, por lo que el servicio asume permiso verificado.
 */
class UserService
{
    /**
     * Lista los usuarios activos con su perfil de estudiante (solo admin).
     */
    public function index(): Collection
    {
        return User::with('estudiante')->where('activo', true)->get();
    }

    /**
     * Lista los docentes activos ordenados por nombre (roles de gestión).
     */
    public function docentes(): Collection
    {
        return User::where('rol', Roles::DOCENTE)
            ->where('activo', true)
            ->orderBy('nombres')
            ->get();
    }

    /**
     * Crea un usuario cifrando su contraseña.
     */
    public function crear(array $datos): User
    {
        $datos['password'] = Hash::make($datos['password']);

        return User::create($datos);
    }

    /**
     * Actualiza un usuario; cifra password si se envía y sincroniza el perfil
     * de estudiante (normalizando vacíos a null) cuando el rol es estudiante.
     */
    public function actualizar(int $id, array $datos): User
    {
        $user = User::findOrFail($id);

        $perfil = $datos['estudiante'] ?? [];

        $campos = collect($datos)->except(['password', 'estudiante'])->all();
        if (! empty($datos['password'])) {
            $campos['password'] = Hash::make($datos['password']);
        }

        $user->update($campos);

        if (! empty($perfil) && ($campos['rol'] ?? $user->rol) === Roles::ESTUDIANTE) {
            $perfilNormalizado = collect($perfil)
                ->map(fn ($valor) => ($valor === '' || $valor === null) ? null : $valor)
                ->all();

            $user->estudiante()->updateOrCreate([], $perfilNormalizado);
        }

        return $user->load('estudiante');
    }

    /**
     * Da de baja lógica a un usuario (activo = false).
     */
    public function desactivar(int $id): void
    {
        User::findOrFail($id)->update(['activo' => false]);
    }
}