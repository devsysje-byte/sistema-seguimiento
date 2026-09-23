<?php

namespace App\Modules\Notificaciones\Listeners;

use App\Modules\Notificaciones\Services\NotificacionService;
use App\Modules\Tramites\Events\EstadoTramiteCambiado;

/**
 * Listener del evento \App\Modules\Tramites\Events\EstadoTramiteCambiado.
 *
 * Notifica al estudiante y al tutor (si existe) del avance de un trámite. Para
 * la modalidad Tesis de Grado personaliza el mensaje al estudiante en los
 * estados clave del flujo (veredicto suficiente, correcciones de 90 días,
 * aprobación y reprobación de la defensa).
 */
class NotificarEstadoCambiado
{
    public function handle(EstadoTramiteCambiado $event): void
    {
        $event->tramite->loadMissing('estudiante.user', 'modalidad');

        $estudiante = $event->tramite->estudiante;
        $cuentaEstudiante = $estudiante?->user;

        if ($estudiante && $cuentaEstudiante) {
            $mensaje = ($event->tramite->modalidad->nombre ?? null) === 'Tesis de Grado'
                ? static::mensajeTesis($event->nuevoEstado)
                : null;

            if ($mensaje) {
                NotificacionService::paraUsuario(
                    $cuentaEstudiante->id_usuario,
                    $mensaje['tipo'],
                    $mensaje['titulo'],
                    $mensaje['mensaje'],
                    '/estudiante/tesis'
                );
            } else {
                NotificacionService::paraUsuario(
                    $cuentaEstudiante->id_usuario,
                    'cambio_estado',
                    'Actualización en su trámite',
                    "Su trámite de {$event->tramite->modalidad->nombre} avanzó al estado: {$event->nuevoEstado}.",
                    '/estudiante'
                );
            }
        }

        if ($event->tramite->id_tutor && $estudiante) {
            NotificacionService::paraUsuario(
                $event->tramite->id_tutor,
                'cambio_estado',
                'Actualización en su tutoría',
                "El trámite del estudiante {$estudiante->nombres} {$estudiante->apellidos} avanzó al estado: {$event->nuevoEstado}.",
                '/docente'
            );
        }
    }

    /**
     * Mensaje personalizado para el estudiante en los estados clave de la tesis.
     *
     * @return array{tipo:string, titulo:string, mensaje:string}|null
     */
    private static function mensajeTesis(string $estado): ?array
    {
        return match ($estado) {
            'suficiente' => [
                'tipo' => 'tesis_resultado',
                'titulo' => '¡Trabajo Aprobado!',
                'mensaje' => 'La Comisión Revisora calificó su tesis como suficiente. Ya puede solicitar la fecha de su defensa.',
            ],
            'correcciones_90_dias' => [
                'tipo' => 'tesis_correcciones',
                'titulo' => 'Defensa no aprobada en esta oportunidad',
                'mensaje' => 'Cuenta con 90 días para corregir su trabajo y volver a solicitar una fecha de defensa.',
            ],
            'aprobado' => [
                'tipo' => 'tesis_aprobada',
                'titulo' => '¡FELICIDADES APROBADO!',
                'mensaje' => 'Su defensa de tesis fue aprobada. Ha culminado su modalidad de titulación.',
            ],
            'reprobado' => [
                'tipo' => 'tesis_reprobado',
                'titulo' => 'Resultado no aprobado',
                'mensaje' => 'Su tesis fue reprobada. Podrá volver a presentar su solicitud para optar por una modalidad cuando se cumplan los plazos reglamentarios.',
            ],
            default => null,
        };
    }
}
