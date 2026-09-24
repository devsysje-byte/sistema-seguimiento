<?php

namespace App\Modules\Kardex\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Proveedor del módulo Kardex.
 *
 * Los controladores y servicios del módulo se resuelven por autowired
 * (constructor injection); no se requiere cableado adicional. El proveedor se
 * auto-descubre mediante el patrón de proveedores de módulos de la aplicación.
 */
class KardexServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Modules\Kardex\Services\KardexService::class);
    }

    public function boot(): void
    {
        //
    }
}