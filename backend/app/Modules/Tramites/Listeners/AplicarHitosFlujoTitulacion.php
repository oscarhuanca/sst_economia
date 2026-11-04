<?php

namespace App\Modules\Tramites\Listeners;

use App\Modules\Tramites\Events\EstadoTramiteCambiado;
use App\Modules\Tramites\Services\FlujoTitulacionModalidad;
use Carbon\Carbon;

/**
 * Alimenta los `hitos` de los trámites del flujo de titulación cada vez que
 * avanzan de estado.
 *
 * Registra la fecha de cada estado alcanzado y calcula las fechas clave del
 * flujo oficial: plazo de presentación del documento (3-12 meses desde la
 * aprobación del perfil), plazo de correcciones (90 días) y fecha de defensa.
 * Solo actúa sobre trámites de las modalidades que comparten el flujo de
 * titulación por módulos (Tesis de Grado y Trabajo Dirigido); el resto no se
 * ve afectado (desacoplamiento por evento).
 */
class AplicarHitosFlujoTitulacion
{
    public function handle(EstadoTramiteCambiado $event): void
    {
        $tramite = $event->tramite->loadMissing('modalidad');

        $modalidad = $tramite->modalidad->nombre ?? null;

        if ($modalidad === null || ! FlujoTitulacionModalidad::esTitulacion($modalidad)) {
            return;
        }

        $config = FlujoTitulacionModalidad::configDe($modalidad);

        $hitos = $tramite->hitos ?? [];
        $hoy = Carbon::now()->startOfDay();

        $hitos[$event->nuevoEstado] = $hoy->toDateString();

        if ($event->nuevoEstado === 'perfil_aprobado') {
            $hitos['limite_presentacion'] = $hoy->copy()
                ->addMonths((int) config($config.'.plazo_presentacion_meses'))
                ->toDateString();
        }

        if ($event->nuevoEstado === 'correcciones_90_dias') {
            $hitos['limite_correccion'] = $hoy->copy()
                ->addDays((int) config($config.'.dias_correccion'))
                ->toDateString();
        }

        if ($event->nuevoEstado === 'suficiente' || $event->nuevoEstado === 'insuficiente') {
            $hitos['resultado_comision'] = $hoy->toDateString();
        }

        if ($event->nuevoEstado === 'defensa_programada') {
            $hitos['fecha_defensa'] = $this->fechaDefensa($event->observaciones, $hoy);
        }

        $tramite->update(['hitos' => $hitos]);
    }

    /**
     * Intenta leer la fecha de defensa desde las observaciones (formato Y-m-d o
     * Y-m-d H:i:s); si no, programa por defecto 30 días después.
     */
    private function fechaDefensa(?string $observaciones, Carbon $hoy): string
    {
        if ($observaciones) {
            try {
                $posible = Carbon::parse($observaciones);

                if ($posible->gte($hoy)) {
                    return $posible->toDateString();
                }
            } catch (\Throwable) {
                // Si las observaciones no son una fecha, se usa el valor por defecto.
            }
        }

        return $hoy->copy()->addDays(30)->toDateString();
    }
}
