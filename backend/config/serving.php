<?php

/**
 * Configuración del servidor de desarrollo PHP (`php artisan serve`).
 *
 * Nota: `illuminate/foundation` ya lee `SERVER_HOST` y `SERVER_PORT` del
 * entorno de forma nativa para `artisan serve`. Este archivo centraliza y
 * documenta esos valores con tipado seguro.
 */
return [
    'host' => env('SERVER_HOST', '127.0.0.1'),

    'port' => (int) env('SERVER_PORT', 8000),

    'tries' => (int) env('SERVER_TRIES', 10),
];