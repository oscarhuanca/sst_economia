<?php

namespace App\Modules\Tesis\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Provider del Módulo de Tesis de Grado.
 *
 * Fusiona su configuración del entorno (`.env`). El módulo de Trámites NO
 * conoce a este módulo: Tesis es un módulo adherente que se apoya en los
 * servicios genéricos del flujo de titulación. La auto-detección de rutas y
 * providers ocurre en la composición raíz (routes/api.php y
 * ModulesServiceProvider).
 */
class TesisServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/tesis.php', 'tesis');
    }

    public function boot(): void
    {
        //
    }
}
