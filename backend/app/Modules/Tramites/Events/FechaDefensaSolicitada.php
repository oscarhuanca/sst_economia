<?php

namespace App\Modules\Tramites\Events;

use App\Models\Tramite;
use App\Models\User;

/**
 * Evento de dominio: un estudiante solicitó una fecha para su defensa.
 *
 * Lo escucha el módulo de Notificaciones para avisar a los roles de gestión
 * (Kardex, Secretaría, Dirección, Admin) para que programen la defensa. Vive
 * en el módulo de Trámites porque lo emiten todas las modalidades que
 * comparten el flujo de titulación por módulos (Tesis de Grado y Trabajo
 * Dirigido).
 */
class FechaDefensaSolicitada
{
    public function __construct(
        public readonly Tramite $tramite,
        public readonly User $estudiante,
        public readonly ?string $fechaSugerida,
    ) {}
}
