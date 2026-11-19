<?php

namespace App\Modules\Notificaciones\Listeners;

use App\Models\Estudiante;
use App\Modules\Estudiantes\Events\EstudianteRegistrado;
use App\Modules\Notificaciones\Services\NotificacionService;
use App\Support\Roles;

/**
 * Avisa a los roles de gestión que hay postulantes esperando credenciales.
 *
 * Cada nuevo registro dispara una notificación con el total acumulado de
 * perfiles sin cuenta de acceso y un enlace al panel general del KARDEX, donde
 * se lista cada postulante pendiente.
 */
class NotificarNuevoRegistroEstudiante
{
    public function handle(EstudianteRegistrado $event): void
    {
        $estudiante = $event->estudiante;
        $pendientes = (int) Estudiante::query()->doesntHave('user')->count();
        $nombre = trim($estudiante->nombres.' '.$estudiante->apellidos);

        NotificacionService::paraUsuarios(
            Roles::GESTION,
            'nuevo_registro_estudiante',
            'Nuevo postulante registrado',
            "{$nombre} (CI {$estudiante->ci}) se registró. Hay {$pendientes} ".
                ($pendientes === 1 ? 'postulante en espera' : 'postulantes en espera').' de credenciales.',
            '/kardex',
        );
    }
}
