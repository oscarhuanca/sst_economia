<?php

namespace App\Modules\Excelencia\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Provider del Módulo de Excelencia Académica.
 *
 * Fusiona su configuración del entorno (`.env`). El módulo de Trámites NO
 * conoce a este módulo: la lógica de Excelencia Académica vive aquí y se
 * apoya en el motor genérico de trámites (TramiteService/TramiteStateService)
 * a través de su servicio. La auto-detección de rutas y providers ocurre en la
 * composición raíz (routes/api.php y ModulesServiceProvider).
 */
class ExcelenciaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/excelencia.php', 'excelencia');
    }
}
