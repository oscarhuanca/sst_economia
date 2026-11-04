<?php

namespace App\Modules\Tramites\Providers;

use App\Modules\Tramites\Events\EstadoTramiteCambiado;
use App\Modules\Tramites\Listeners\AplicarHitosFlujoTitulacion;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Provider del módulo de Trámites.
 *
 * Punto de cableado del dominio de trámites de titulación. Los servicios de
 * este módulo (`TramiteService`, `TramiteStateService`,
 * `FlujoTitulacionEstudianteService`) se inyectan por dependencia; los eventos
 * que emite (`Tramites\Events\*`) los consumen los listeners declarados por
 * NOTIFICACIONES, que es el módulo dependiente (desacoplamiento por eventos).
 * La única excepción es el listener de hitos, que ES parte de este dominio:
 * alimenta los `hitos` del propio trámite.
 */
class TramitesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(EstadoTramiteCambiado::class, AplicarHitosFlujoTitulacion::class);
    }
}
