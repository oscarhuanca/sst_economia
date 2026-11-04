<?php

namespace App\Modules\Tramites\Events;

use App\Models\Tramite;

/**
 * Evento de dominio: la documentación inicial de un trámite fue revisada
 * (aprobada o rechazada) por un rol de gestión.
 */
class TramiteRevisado
{
    public function __construct(
        public readonly Tramite $tramite,
        public readonly string $accion,
        public readonly ?string $observaciones,
    ) {
    }
}