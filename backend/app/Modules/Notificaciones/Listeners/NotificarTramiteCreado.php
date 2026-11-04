<?php

namespace App\Modules\Notificaciones\Listeners;

use App\Modules\Notificaciones\Services\NotificacionService;
use App\Modules\Tramites\Events\TramiteCreado;
use App\Support\Roles;

/**
 * Listener del evento \App\Modules\Tramites\Events\TramiteCreado.
 *
 * Avisa a los roles de gestión que un estudiante presentó un nuevo trámite.
 */
class NotificarTramiteCreado
{
    public function handle(TramiteCreado $event): void
    {
        $event->tramite->loadMissing('modalidad');

        NotificacionService::paraUsuarios(
            Roles::GESTION,
            'tramite_nuevo',
            'Nuevo trámite presentado',
            "{$event->estudiante->nombres} {$event->estudiante->apellidos} presentó una solicitud de {$event->tramite->modalidad->nombre}.",
            "/tramites/{$event->tramite->id_tramite}/gestion"
        );
    }
}