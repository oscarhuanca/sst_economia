<?php

namespace App\Modules\Auth\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Provider del módulo de Auth (login/logout con Sanctum).
 *
 * Punto de cableado del dominio de autenticación. Si el módulo necesita
 * inyectar dependencias, configuración o middleware propios, se declara aquí
 * y es auto-registrado por `App\Providers\ModulesServiceProvider`.
 */
class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}