<?php

/**
 * Parámetros de la modalidad de titulación "Examen de Grado".
 *
 * Concentra las reglas de negocio que el módulo de KARDEX aplica al flujo por
 * tarjetas: cuántos intentos de postulación admite un estudiante, la nota
 * mínima de aprobación y el rango de notas válido. El frontend replica estos
 * valores en `modules/examen/utils/flujoExamen.js` para validar antes de enviar.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Intentos de postulación
    |--------------------------------------------------------------------------
    |
    | Cantidad máxima de veces que un estudiante puede presentar una solicitud
    | de Examen de Grado. El módulo 1 del flujo ("Verificación de intentos")
    | rechaza la solicitud cuando `numero_intentos >= maximo_intentos`.
    |
    */

    'maximo_intentos' => (int) env('EXAMEN_MAXIMO_INTENTOS', 3),

    /*
    |--------------------------------------------------------------------------
    | Calificación
    |--------------------------------------------------------------------------
    |
    | Escala de 0 a 100 puntos. `nota_minima` define la aprobación: igual o
    | superior aprueba, inferior reprueba. Los módulos 3 y 5 solo registran el
    | sorteo (asistencia, tema y fecha); el módulo 4 registra la Resolución que
    | aprueba el resultado del teórico, el módulo 6 registra la Resolución de
    | asignación de notas y la nota se asigna en el módulo 7 ("Asignación de
    | notas"); el sistema deriva de ella el estado (`..._aprobado` o
    | `..._reprobado`) sin que KARDEX elija el resultado a mano.
    |
    */

    'nota_minima' => (int) env('EXAMEN_NOTA_MINIMA', 51),

    'rango_nota' => [0, 100],

];
