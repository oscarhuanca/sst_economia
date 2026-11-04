<?php

namespace App\Modules\TrabajoDirigido\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Provider del Módulo de Trabajo Dirigido.
 *
 * Fusiona su configuración del entorno (`.env`). El módulo de Trámites NO
 * conoce a este módulo: Trabajo Dirigido es un módulo adherente que se apoya en
 * los servicios genéricos del flujo de titulación. La auto-detección de rutas y
 * providers ocurre en la composición raíz (routes/api.php y
 * ModulesServiceProvider).
 */
class TrabajoDirigidoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/trabajodirigido.php', 'trabajodirigido');
    }

    public function boot(): void
    {
        //
    }
}
