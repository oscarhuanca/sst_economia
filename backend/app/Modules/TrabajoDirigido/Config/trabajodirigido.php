<?php

/**
 * Configuración del Módulo de Trabajo Dirigido.
 *
 * Parámetros del flujo de titulación por módulos que comparte con la Tesis de
 * Grado:
 *
 *   - `plazo_presentacion_meses`: cuántos meses tiene el estudiante para
 *     presentar el documento final desde la aprobación del perfil. Configurable
 *     con `TRABAJO_DIRIGIDO_PLAZO_PRESENTACION_MESES` (se acota siempre entre 3
 *     y 12).
 *   - `dias_correccion`: días para corregir el documento cuando la revisión es
 *     insuficiente o la defensa no fue aprobada. Configurable con
 *     `TRABAJO_DIRIGIDO_DIAS_CORRECCION` (por defecto 90, según el reglamento).
 *   - `dias_remodalidad`: días que el estudiante reprobado debe esperar para
 *     volver a presentar su solicitud. Configurable con
 *     `TRABAJO_DIRIGIDO_DIAS_REMODALIDAD` (por defecto 365).
 *   - `nota_minima_defensa`: nota mínima para aprobar la defensa del trabajo
 *     dirigido; con menos el trámite queda REPROBADO y el flujo termina.
 *     Configurable con `TRABAJO_DIRIGIDO_NOTA_MINIMA_DEFENSA` (por defecto 51).
 *   - `tipos_documento`: catálogo de los documentos de la fase de solicitud
 *     (Nota de Solicitud, Certificado de Notas y Perfil de Proyecto). Son
 *     opcionales: el estudiante puede enviar la solicitud solo con el título.
 *   - `tipos_documento_publicacion`: catálogo de la publicación del trabajo.
 */
return [
    'plazo_presentacion_meses' => max(3, min(12, (int) env('TRABAJO_DIRIGIDO_PLAZO_PRESENTACION_MESES', 12))),

    'dias_correccion' => max(1, (int) env('TRABAJO_DIRIGIDO_DIAS_CORRECCION', 90)),

    'dias_remodalidad' => max(1, (int) env('TRABAJO_DIRIGIDO_DIAS_REMODALIDAD', 365)),

    'nota_minima_defensa' => max(1, min(100, (int) env('TRABAJO_DIRIGIDO_NOTA_MINIMA_DEFENSA', 51))),

    'tipos_documento' => [
        'nota_solicitud' => 'Nota de Solicitud',
        'certificado_notas' => 'Certificado de Notas',
        'perfil_proyecto' => 'Perfil de Proyecto',
    ],

    'tipos_documento_publicacion' => [
        'trabajo_publicacion' => 'Trabajo Dirigido',
    ],
];
