<?php

namespace App\Modules\Notificaciones\Listeners;

use App\Modules\Notificaciones\Services\NotificacionService;
use App\Modules\Tramites\Events\FechaDefensaSolicitada;
use App\Modules\Tramites\Services\FlujoTitulacionModalidad;
use App\Support\Roles;

/**
 * Listener del evento \App\Modules\Tramites\Events\FechaDefensaSolicitada.
 *
 * Avisa a los roles de gestión que un estudiante solicitó una fecha para su
 * defensa, con la fecha sugerida (si la indicó).
 */
class NotificarFechaDefensaSolicitada
{
    public function handle(FechaDefensaSolicitada $event): void
    {
        $event->tramite->loadMissing('modalidad');

        $modalidad = $event->tramite->modalidad->nombre ?? null;

        $etiqueta = $modalidad !== null && FlujoTitulacionModalidad::esTitulacion($modalidad)
            ? FlujoTitulacionModalidad::etiquetaTituloDe($modalidad)
            : 'tesis';

        $mensaje = "{$event->estudiante->nombres} {$event->estudiante->apellidos} solicitó una fecha para su {$etiqueta}.";

        if ($event->fechaSugerida) {
            $mensaje .= ' Fecha sugerida: '.$event->fechaSugerida.'.';
        }

        NotificacionService::paraUsuarios(
            Roles::GESTION,
            'tramite_nuevo',
            'Solicitud de fecha de defensa',
            $mensaje,
            "/tramites/{$event->tramite->id_tramite}/gestion"
        );
    }
}
