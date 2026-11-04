<?php

use App\Providers\ModulesServiceProvider;
use App\Providers\AppServiceProvider;

/**
 * Composición raíz de la aplicación.
 *
 * Solo se declaran los providers del núcleo. Los service providers de cada
 * módulo (por ejemplo `NotificacionesServiceProvider`) NO se listan aquí: los
 * registra automáticamente ModulesServiceProvider descubriendo la carpeta
 * `Providers` de cada módulo bajo `app/Modules`.
 */
return [
    AppServiceProvider::class,
    ModulesServiceProvider::class,
];