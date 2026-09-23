<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo Eloquent de la tabla `users`.
 *
 * Representa a un usuario del sistema. Utiliza la clave primaria `id_usuario`
 * (personalizada) y soporta autenticación por tokens de Sanctum. Campo `rol`
 * disponible: admin, estudiante, docente, kardex, secretaria y direccion.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'ci', 'nombres', 'apellidos', 'email', 'telefono', 'password', 'rol', 'activo'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Definición de casts de los atributos del modelo.
     *
     * @return array<string, string> Mapa de atributo => tipo de cast:
     *         `activo` (booleano) y `password` (hash automático nativo de Laravel 11+).
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'password' => 'hashed', // Laravel 11/12/13 casteo nativo
        ];
    }

    /**
     * Relación uno a uno con el perfil de estudiante.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne Perfil `\App\Models\Estudiante`
     *         asociado a este usuario (puede ser `null` si no es estudiante o no completó su perfil).
     */
    public function estudiante() { return $this->hasOne(Estudiante::class, 'id_usuario', 'id_usuario'); }

    /**
     * Relación uno a muchos con las notificaciones del usuario.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany Notificaciones `\App\Models\Notificacion`
     *         dirigidas a este usuario.
     */
    public function notificaciones() { return $this->hasMany(Notificacion::class, 'id_usuario', 'id_usuario'); }
}
