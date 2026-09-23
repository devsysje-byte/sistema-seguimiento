<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modelo Eloquent de la tabla `estudiantes`.
 *
 * Representa el PERFIL AISLADO del estudiante: datos personales e
 * institucionales básicos (`ci`, `nombres`, `apellidos`, `registro_universitario`,
 * `fecha_nacimiento`) más los campos académicos que el propio estudiante
 * mantiene por su cuenta (`plan_estudios`, `fecha_conclusion_plan`,
 * `promedio_global`). Es el registro oficial del estudiante; la cuenta de acceso
 * (`users`) solo se vincula de forma opcional mediante `estudiantes → users.estudiante_id`.
 */
class Estudiante extends Model
{
    protected $primaryKey = 'id_estudiante';

    protected $fillable = [
        'ci', 'nombres', 'apellidos', 'registro_universitario', 'fecha_nacimiento',
        'plan_estudios', 'fecha_conclusion_plan', 'promedio_global',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_conclusion_plan' => 'date',
            'promedio_global' => 'decimal:2',
        ];
    }

    /**
     * Relación opcional con la cuenta de acceso del estudiante.
     *
     * Es la relación inversa de `User::estudiante()`: un estudiante puede
     * tener (o no) una cuenta creada en `users`.
     *
     * @return HasOne Cuenta `\App\Models\User`
     *                vinculada a este perfil (o null si aún no tiene cuenta).
     */
    public function user()
    {
        return $this->hasOne(User::class, 'estudiante_id', 'id_estudiante');
    }

    /**
     * Relación uno a muchos con los trámites del estudiante.
     *
     * @return HasMany Trámites `\App\Models\Tramite`
     *                 presentados por este estudiante.
     */
    public function tramites()
    {
        return $this->hasMany(Tramite::class, 'id_estudiante', 'id_estudiante');
    }
}
