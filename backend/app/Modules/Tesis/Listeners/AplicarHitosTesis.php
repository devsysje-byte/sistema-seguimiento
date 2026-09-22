<?php

namespace App\Modules\Tesis\Listeners;

use App\Modules\Tramites\Events\EstadoTramiteCambiado;
use Carbon\Carbon;

/**
 * Alimenta los `hitos` de una tesis cada vez que avanza de estado.
 *
 * Registra la fecha de cada estado alcanzado y calcula las fechas clave del
 * flujo oficial: plazo de presentación del documento (3-12 meses desde la
 * aprobación del perfil), plazo de correcciones (90 días) y fecha de defensa.
 * Solo actúa sobre trámites de la modalidad Tesis de Grado; el resto no se
 * ve afectado (desacoplamiento por evento, el módulo de Tesis es adherente).
 */
class AplicarHitosTesis
{
    public function handle(EstadoTramiteCambiado $event): void
    {
        $tramite = $event->tramite->loadMissing('modalidad');

        if (($tramite->modalidad->nombre ?? null) !== 'Tesis de Grado') {
            return;
        }

        $hitos = $tramite->hitos ?? [];
        $hoy = Carbon::now()->startOfDay();

        $hitos[$event->nuevoEstado] = $hoy->toDateString();

        if ($event->nuevoEstado === 'perfil_aprobado') {
            $hitos['limite_presentacion'] = $hoy->copy()
                ->addMonths((int) config('tesis.plazo_presentacion_meses'))
                ->toDateString();
        }

        if ($event->nuevoEstado === 'correcciones_90_dias') {
            $hitos['limite_correccion'] = $hoy->copy()
                ->addDays((int) config('tesis.dias_correccion'))
                ->toDateString();
        }

        if ($event->nuevoEstado === 'suficiente' || $event->nuevoEstado === 'insuficiente') {
            $hitos['resultado_comision'] = $hoy->toDateString();
        }

        if ($event->nuevoEstado === 'defensa_programada') {
            $hitos['fecha_defensa'] = $this->fechaDefensa($event->observaciones, $hoy);
        }

        $tramite->update(['hitos' => $hitos]);
    }

    /**
     * Intenta leer la fecha de defensa desde las observaciones (formato Y-m-d o
     * Y-m-d H:i:s); si no, programa por defecto 30 días después.
     */
    private function fechaDefensa(?string $observaciones, Carbon $hoy): string
    {
        if ($observaciones) {
            try {
                $posible = Carbon::parse($observaciones);

                if ($posible->gte($hoy)) {
                    return $posible->toDateString();
                }
            } catch (\Throwable) {
                // Si las observaciones no son una fecha, se usa el valor por defecto.
            }
        }

        return $hoy->copy()->addDays(30)->toDateString();
    }
}