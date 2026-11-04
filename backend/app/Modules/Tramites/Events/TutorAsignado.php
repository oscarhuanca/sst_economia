<?php

namespace App\Modules\Tramites\Events;

use App\Models\Docente;
use App\Models\Tramite;

/**
 * Evento de dominio: se asignó (o reasignó) un docente tutor a un trámite.
 */
class TutorAsignado
{
    public function __construct(
        public readonly Tramite $tramite,
        public readonly Docente $tutor,
    ) {}
}
