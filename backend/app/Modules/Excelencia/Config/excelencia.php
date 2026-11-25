<?php

/**
 * Configuración del Módulo de Excelencia Académica.
 *
 * Parámetros del flujo de titulación por promedio sobresaliente:
 *
 *   - `nota_minima`: promedio general mínimo del estudiante para optar por la
 *     modalidad. KARDEX no puede aceptar una solicitud con un promedio inferior;
 *     si el resultado es RECHAZADO, el trámite se cierra y el estudiante debe
 *     optar por otra modalidad. Configurable con `EXCELENCIA_NOTA_MINIMA`
 *     (por defecto 80).
 *   - `rango_nota`: rango válido en el que se puede registrar un promedio
 *     (0 a 100). El backend vuelve a validarlo por si el formulario se manipula.
 *   - `tipos_documento`: PDFs que el estudiante adjunta al presentar la
 *     solicitud. KARDEX los recepciona antes de verificar la nota.
 */
return [
    'nota_minima' => max(1, min(100, (int) env('EXCELENCIA_NOTA_MINIMA', 80))),

    'rango_nota' => [0, 100],

    'tipos_documento' => [
        'nota_solicitud' => 'Nota de Solicitud',
        'certificado_calificaciones' => 'Certificado de Calificaciones',
        'record_academico' => 'Récord Académico',
    ],
];
