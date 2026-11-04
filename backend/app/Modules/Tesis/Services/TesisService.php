<?php

namespace App\Modules\Tesis\Services;

use App\Modules\Tramites\Services\FlujoTitulacionEstudianteService;
use App\Modules\Tramites\Services\FlujoTitulacionModalidad;

/**
 * Casos de uso del lado del ESTUDIANTE en el Módulo de Tesis de Grado.
 *
 * El comportamiento del flujo (solicitud con el título de tesis y documentos
 * opcionales, corrección y reenvío del perfil, reenvío del documento final
 * insuficiente, publicación y solicitud de fecha de defensa) vive en
 * {@see FlujoTitulacionEstudianteService}; esta clase solo fija los datos
 * propios de la modalidad de Tesis de Grado.
 */
class TesisService extends FlujoTitulacionEstudianteService
{
    /**
     * Modalidad que atiende este servicio.
     */
    protected function modalidad(): string
    {
        return FlujoTitulacionModalidad::MODALIDAD_TESIS;
    }
}
