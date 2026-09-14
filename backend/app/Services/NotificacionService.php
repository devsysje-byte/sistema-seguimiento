<?php
namespace App\Services;
use App\Models\Notificacion;
use App\Models\User;

class NotificacionService
{
    /**
     * Crear una notificación para todos los usuarios con los roles indicados.
     */
    public static function paraUsuarios(array $roles, string $tipo, string $titulo, ?string $mensaje = null, ?string $enlace = null): void
    {
        $ids = User::whereIn('rol', $roles)->pluck('id_usuario');
        static::insertar($ids, $tipo, $titulo, $mensaje, $enlace);
    }

    /**
     * Crear una notificación para un usuario concreto.
     */
    public static function paraUsuario(int $idUsuario, string $tipo, string $titulo, ?string $mensaje = null, ?string $enlace = null): void
    {
        static::insertar(collect([$idUsuario]), $tipo, $titulo, $mensaje, $enlace);
    }

    private static function insertar($ids, string $tipo, string $titulo, ?string $mensaje, ?string $enlace): void
    {
        foreach ($ids as $id) {
            Notificacion::create([
                'id_usuario' => $id,
                'tipo' => $tipo,
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'enlace' => $enlace,
            ]);
        }
    }
}