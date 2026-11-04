<?php

namespace App\Modules\Tramites\Events;

use App\Models\Tramite;

/**
 * Evento de dominio: un trámite avanzó a un nuevo estado dentro del flujo de
 * su modalidad.
 */
class EstadoTramiteCambiado
{
    public function __construct(
        public readonly Tramite $tramite,
        public readonly string $nuevoEstado,
        public readonly ?string $observaciones,
    ) {
    }
}