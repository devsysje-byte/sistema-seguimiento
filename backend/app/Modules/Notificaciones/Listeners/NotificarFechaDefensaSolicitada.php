<?php

namespace App\Modules\Notificaciones\Listeners;

use App\Modules\Notificaciones\Services\NotificacionService;
use App\Modules\Tesis\Events\FechaDefensaSolicitada;
use App\Support\Roles;

/**
 * Listener del evento \App\Modules\Tesis\Events\FechaDefensaSolicitada.
 *
 * Avisa a los roles de gestión que un estudiante solicitó una fecha para su
 * defensa de tesis, con la fecha sugerida (si la indicó).
 */
class NotificarFechaDefensaSolicitada
{
    public function handle(FechaDefensaSolicitada $event): void
    {
        $event->tramite->loadMissing('modalidad');

        $mensaje = "{$event->estudiante->nombres} {$event->estudiante->apellidos} solicitó una fecha para su defensa de tesis.";

        if ($event->fechaSugerida) {
            $mensaje .= ' Fecha sugerida: ' . $event->fechaSugerida . '.';
        }

        NotificacionService::paraUsuarios(
            Roles::GESTION,
            'tramite_nuevo',
            'Solicitud de fecha de defensa',
            $mensaje,
            "/tramites/{$event->tramite->id_tramite}/gestion"
        );
    }
}