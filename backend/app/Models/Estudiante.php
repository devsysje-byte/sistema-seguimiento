<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modelo Eloquent de la tabla `estudiantes`.
 *
 * Representa el PERFIL del estudiante: datos personales, institucionales y de
 * contacto (`ci`, `nombres`, `apellidos`, `fecha_nacimiento`, `email`,
 * `telefono`, `registro_universitario`) y, opcionalmente, su `promedio_global`.
 * El estudiante puede auto-registrarse desde el login; la cuenta de acceso
 * (`users`) se vincula de forma independiente mediante
 * `estudiantes → users.estudiante_id` cuando la instancia académica genera las
 * credenciales (CREAR USUARIO).
 */
class Estudiante extends Model
{
    protected $primaryKey = 'id_estudiante';

    protected $fillable = [
        'ci', 'nombres', 'apellidos', 'fecha_nacimiento', 'email', 'telefono',
        'registro_universitario', 'promedio_global',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
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