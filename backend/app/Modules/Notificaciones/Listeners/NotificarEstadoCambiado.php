<?php

namespace App\Modules\Notificaciones\Listeners;

use App\Modules\Notificaciones\Services\NotificacionService;
use App\Modules\Tramites\Events\EstadoTramiteCambiado;
use App\Modules\Tramites\Services\FlujoTitulacionModalidad;

/**
 * Listener del evento \App\Modules\Tramites\Events\EstadoTramiteCambiado.
 *
 * Notifica al estudiante del avance de un trámite. Para
 * las modalidades que comparten el flujo de titulación (Tesis de Grado y
 * Trabajo Dirigido) personaliza el mensaje al estudiante en los estados clave
 * del flujo (rechazo del perfil, veredicto suficiente, correcciones de 90
 * días, aprobación y reprobación de la defensa).
 */
class NotificarEstadoCambiado
{
    public function handle(EstadoTramiteCambiado $event): void
    {
        $event->tramite->loadMissing('estudiante.user', 'modalidad');
        $estudiante = $event->tramite->estudiante;
        $cuentaEstudiante = $estudiante?->user;

        $modalidad = $event->tramite->modalidad->nombre ?? null;

        if ($estudiante && $cuentaEstudiante) {
            $mensaje = $modalidad !== null && FlujoTitulacionModalidad::esTitulacion($modalidad)
                ? static::mensajeTitulacion($event, $modalidad)
                : null;

            if ($mensaje) {
                NotificacionService::paraUsuario(
                    $cuentaEstudiante->id_usuario,
                    $mensaje['tipo'],
                    $mensaje['titulo'],
                    $mensaje['mensaje'],
                    FlujoTitulacionModalidad::rutaDe($modalidad)
                );
            } else {
                NotificacionService::paraUsuario(
                    $cuentaEstudiante->id_usuario,
                    'cambio_estado',
                    'Actualización en su trámite',
                    "Su trámite de {$modalidad} avanzó al estado: {$event->nuevoEstado}.",
                    '/estudiante'
                );
            }
        }
    }

    /**
     * Mensaje personalizado para el estudiante en los estados clave del flujo de
     * titulación.
     *
     * @return array{tipo:string, titulo:string, mensaje:string}|null
     */
    private static function mensajeTitulacion(EstadoTramiteCambiado $event, string $modalidad): ?array
    {
        $prefijo = FlujoTitulacionModalidad::prefijoDe($modalidad);
        $etiqueta = FlujoTitulacionModalidad::etiquetaTituloDe($modalidad);
        $etiquetaPerfil = FlujoTitulacionModalidad::etiquetaPerfilDe($modalidad);

        return match ($event->nuevoEstado) {
            'perfil_rechazado' => [
                'tipo' => $prefijo.'_perfil_rechazado',
                'titulo' => ucfirst($etiquetaPerfil).' rechazado',
                'mensaje' => 'Kardex rechazó su '.$etiquetaPerfil.'. '.static::detalleRechazo($event, $etiqueta),
            ],
            'suficiente' => [
                'tipo' => $prefijo.'_resultado',
                'titulo' => '¡Trabajo Aprobado!',
                'mensaje' => 'La Comisión Revisora calificó su '.$etiqueta.' como suficiente. Ya puede solicitar la fecha de su defensa.',
            ],
            'correcciones_90_dias' => [
                'tipo' => $prefijo.'_correcciones',
                'titulo' => 'Defensa no aprobada en esta oportunidad',
                'mensaje' => 'Cuenta con 90 días para corregir su trabajo y volver a solicitar una fecha de defensa.',
            ],
            'aprobado' => [
                'tipo' => $prefijo.'_aprobada',
                'titulo' => '¡FELICIDADES APROBADO!',
                'mensaje' => 'Su defensa de '.$etiqueta.' fue aprobada. Ha culminado su modalidad de titulación.',
            ],
            'reprobado' => [
                'tipo' => $prefijo.'_reprobado',
                'titulo' => 'Resultado no aprobado',
                'mensaje' => 'Su '.$etiqueta.' fue reprobada. Podrá volver a presentar su solicitud para optar por una modalidad cuando se cumplan los plazos reglamentarios.',
            ],
            default => null,
        };
    }

    /**
     * Explicación que acompaña al rechazo del perfil: la observación que KARDEX
     * registró o, si no la escribió, el motivo por defecto.
     */
    private static function detalleRechazo(EstadoTramiteCambiado $event, string $etiqueta): string
    {
        $observacion = trim((string) $event->observaciones);

        return $observacion !== ''
            ? $observacion.' Debe presentar su perfil nuevamente con un título de '.$etiqueta.' diferente.'
            : 'Debe presentar su perfil nuevamente con un título de '.$etiqueta.' diferente.';
    }
}
