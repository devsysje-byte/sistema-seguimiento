<?php

namespace App\Modules\Notificaciones\Listeners;

use App\Modules\Notificaciones\Services\NotificacionService;
use App\Modules\Tramites\Events\TutorAsignado;

/**
 * Listener del evento \App\Modules\Tramites\Events\TutorAsignado.
 *
 * Notifica al docente tutor de su nueva asignación.
 */
class NotificarTutorAsignado
{
    public function handle(TutorAsignado $event): void
    {
        $event->tramite->loadMissing('estudiante.user', 'modalidad');

        $estudiante = $event->tramite->estudiante;

        NotificacionService::paraUsuario(
            $event->tutor->id_usuario,
            'asignacion_tutor',
            'Tutoría asignada',
            "Le asignaron la tutoría del trámite de {$estudiante->nombres} {$estudiante->apellidos} ({$event->tramite->modalidad->nombre}).",
            '/docente'
        );
    }
}
