<?php

namespace App\Modules\Notificaciones\Listeners;

use App\Modules\Notificaciones\Services\NotificacionService;
use App\Modules\Tramites\Events\TramiteRevisado;

/**
 * Listener del evento \App\Modules\Tramites\Events\TramiteRevisado.
 *
 * Notifica al estudiante del resultado de la revisión de su documentación
 * inicial (aprobada o rechazada).
 */
class NotificarTramiteRevisado
{
    public function handle(TramiteRevisado $event): void
    {
        $event->tramite->loadMissing('estudiante.user', 'modalidad');

        $estudiante = $event->tramite->estudiante;

        if (! $estudiante) {
            return;
        }

        $cuentaEstudiante = $estudiante->user;

        if (! $cuentaEstudiante) {
            return;
        }

        if ($event->accion === 'aprobar') {
            NotificacionService::paraUsuario(
                $cuentaEstudiante->id_usuario,
                'cambio_estado',
                'Su documentación fue aprobada',
                'Su solicitud de modalidad fue aprobada y continúa en evaluación.',
                '/estudiante'
            );

            return;
        }

        NotificacionService::paraUsuario(
            $cuentaEstudiante->id_usuario,
            'cambio_estado',
            'Su solicitud fue rechazada',
            $event->observaciones ?? 'La documentación presentada fue rechazada.',
            '/estudiante'
        );
    }
}
