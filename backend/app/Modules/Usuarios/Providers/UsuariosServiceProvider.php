<?php

namespace App\Modules\Usuarios\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Provider del módulo de Usuarios (gestión y administración de usuarios).
 *
 * Punto de cableado del dominio de usuarios. Si el módulo necesita inyectar
 * dependencias, configuración o middleware propios, se declara aquí y es
 * auto-registrado por `App\Providers\ModulesServiceProvider`.
 */
class UsuariosServiceProvider extends ServiceProvider
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