<?php

namespace App\Modules\TrabajoDirigido\Services;

use App\Modules\Tramites\Services\FlujoTitulacionEstudianteService;
use App\Modules\Tramites\Services\FlujoTitulacionModalidad;

/**
 * Casos de uso del lado del ESTUDIANTE en el Módulo de Trabajo Dirigido.
 *
 * El comportamiento del flujo (solicitud con el título del trabajo dirigido y
 * documentos opcionales, corrección y reenvío del perfil, reenvío del documento
 * final insuficiente, publicación y solicitud de fecha de defensa) vive en
 * {@see FlujoTitulacionEstudianteService}; esta clase solo fija los datos
 * propios de la modalidad de Trabajo Dirigido, cuyo primer módulo del flujo es
 * el PERFIL DE PROYECTO (en lugar del perfil de tesis).
 */
class TrabajoDirigidoService extends FlujoTitulacionEstudianteService
{
    /**
     * Modalidad que atiende este servicio.
     */
    protected function modalidad(): string
    {
        return FlujoTitulacionModalidad::MODALIDAD_TRABAJO_DIRIGIDO;
    }
}
