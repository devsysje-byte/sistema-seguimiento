<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'password' => 'hashed', // Laravel 11/12/13 casteo nativo
        ];
    }
}
