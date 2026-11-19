<?php

namespace App\Modules\Estudiantes\Events;

use App\Models\Estudiante;

/**
 * Evento de dominio: un postulante registró su perfil en el sistema.
 *
 * Lo escucha el módulo de Notificaciones para avisar a los roles de gestión
 * cuántos postulantes están esperando la generación de credenciales.
 */
class EstudianteRegistrado
{
    public function __construct(public readonly Estudiante $estudiante) {}
}
