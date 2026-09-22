<?php

namespace App\Modules\Estudiantes\Services;

use App\Models\Estudiante;
use App\Models\User;

/**
 * Casos de uso del perfil de estudiante.
 *
 * Centraliza la consulta y la creación/actualización (upsert) del perfil
 * académico del estudiante autenticado.
 */
class EstudianteService
{
    /**
     * Devuelve el perfil del estudiante autenticado (o null si no existe).
     */
    public function perfilDe(User $user): ?Estudiante
    {
        return Estudiante::where('id_usuario', $user->id_usuario)->first();
    }

    /**
     * Crea o actualiza (upsert) el perfil académico del usuario autenticado.
     */
    public function guardarPerfil(User $user, array $datos): Estudiante
    {
        return $user->estudiante()
            ->updateOrCreate(
                ['id_usuario' => $user->id_usuario],
                $datos
            )
            ->fresh();
    }
}