<?php

namespace App\Modules\Tesis\Providers;

use App\Modules\Tesis\Listeners\AplicarHitosTesis;
use App\Modules\Tramites\Events\EstadoTramiteCambiado;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Provider del Módulo de Tesis de Grado.
 *
 * Fusiona su configuración del entorno (`.env`) y registra el listener que
 * alimenta los hitos del flujo de la tesis. El módulo de Trámites NO conoce a
 * este módulo: Tesis es un módulo adherente que reacciona a su evento de
 * dominio (desacoplamiento por eventos). La auto-detección de rutas y providers
 * ocurre en la composición raíz (routes/api.php y ModulesServiceProvider).
 */
class TesisServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/tesis.php', 'tesis');
    }

    public function boot(): void
    {
        Event::listen(EstadoTramiteCambiado::class, AplicarHitosTesis::class);
    }
}