<?php

namespace App\Modules\Reportes\Providers;

use App\Modules\Reportes\Services\ReporteService;
use Illuminate\Support\ServiceProvider;

/**
 * Proveedor del módulo Reportes.
 *
 * El servicio de reportes se resuelve por autowired; el proveedor solo fija el
 * registro como singleton, siguiendo el patrón de los demás módulos.
 */
class ReportesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ReporteService::class);
    }

    public function boot(): void
    {
        //
    }
}
