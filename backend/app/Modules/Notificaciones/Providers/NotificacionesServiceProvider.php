<?php

namespace App\Modules\Notificaciones\Providers;

use App\Modules\Notificaciones\Listeners\NotificarEstadoCambiado;
use App\Modules\Notificaciones\Listeners\NotificarFechaDefensaSolicitada;
use App\Modules\Notificaciones\Listeners\NotificarTramiteCreado;
use App\Modules\Notificaciones\Listeners\NotificarTramiteRevisado;
use App\Modules\Notificaciones\Listeners\NotificarTutorAsignado;
use App\Modules\Tesis\Events\FechaDefensaSolicitada;
use App\Modules\Tramites\Events\EstadoTramiteCambiado;
use App\Modules\Tramites\Events\TramiteCreado;
use App\Modules\Tramites\Events\TramiteRevisado;
use App\Modules\Tramites\Events\TutorAsignado;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Provider del módulo de Notificaciones.
 *
 * Registra los listeners que reaccionan a los eventos de dominio del módulo de
 * Trámites. De este modo el módulo de Trámites no conoce a Notificaciones
 * (desacoplamiento por eventos).
 */
class NotificacionesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(TramiteCreado::class, NotificarTramiteCreado::class);
        Event::listen(TramiteRevisado::class, NotificarTramiteRevisado::class);
        Event::listen(EstadoTramiteCambiado::class, NotificarEstadoCambiado::class);
        Event::listen(TutorAsignado::class, NotificarTutorAsignado::class);
        Event::listen(FechaDefensaSolicitada::class, NotificarFechaDefensaSolicitada::class);
    }
}