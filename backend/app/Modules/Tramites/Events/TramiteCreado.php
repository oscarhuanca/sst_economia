<?php

namespace App\Modules\Tramites\Events;

use App\Models\Tramite;
use App\Models\User;

/**
 * Evento de dominio: un estudiante presentó un nuevo trámite.
 *
 * Lo escucha el módulo de Notificaciones para avisar a los roles de gestión.
 */
class TramiteCreado
{
    public function __construct(
        public readonly Tramite $tramite,
        public readonly User $estudiante,
    ) {
    }
}