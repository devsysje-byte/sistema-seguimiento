<?php

namespace App\Modules\Tramites\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Provider del módulo de Trámites.
 *
 * Punto de cableado del dominio de trámites de titulación. Los servicios de
 * este módulo (`TramiteService`, `TramiteStateService`) se inyectan por
 * dependencia; los eventos que emite (`Tramites\Events\*`) NO se registran
 * aquí: los consumen los listeners declarados por NOTIFICACIONES, que es el
 * módulo dependiente (desacoplamiento por eventos). Este provider queda listo
 * para cualquier otro cableado propio del dominio.
 */
class TramitesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}