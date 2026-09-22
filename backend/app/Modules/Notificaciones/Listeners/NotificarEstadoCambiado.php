<?php

namespace App\Modules\Notificaciones\Listeners;

use App\Modules\Notificaciones\Services\NotificacionService;
use App\Modules\Tramites\Events\EstadoTramiteCambiado;

/**
 * Listener del evento \App\Modules\Tramites\Events\EstadoTramiteCambiado.
 *
 * Notifica al estudiante y al tutor (si existe) del avance de un trámite.
 */
class NotificarEstadoCambiado
{
    public function handle(EstadoTramiteCambiado $event): void
    {
        $event->tramite->loadMissing('estudiante.user', 'modalidad');

        $estudiante = $event->tramite->estudiante;

        if ($estudiante) {
            NotificacionService::paraUsuario(
                $estudiante->id_usuario,
                'cambio_estado',
                'Actualización en su trámite',
                "Su trámite de {$event->tramite->modalidad->nombre} avanzó al estado: {$event->nuevoEstado}.",
                '/estudiante'
            );
        }

        if ($event->tramite->id_tutor && $estudiante?->user) {
            NotificacionService::paraUsuario(
                $event->tramite->id_tutor,
                'cambio_estado',
                'Actualización en su tutoría',
                "El trámite del estudiante {$estudiante->user->nombres} {$estudiante->user->apellidos} avanzó al estado: {$event->nuevoEstado}.",
                '/docente'
            );
        }
    }
}