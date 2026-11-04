<?php

namespace App\Modules\Estudiantes\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Provider del módulo de Estudiantes (perfil y estadísticas del estudiante).
 *
 * Punto de cableado del dominio de estudiantes. Si el módulo necesita
 * inyectar dependencias, configuración o middleware propios, se declara aquí
 * y es auto-registrado por `App\Providers\ModulesServiceProvider`.
 */
class EstudiantesServiceProvider extends ServiceProvider
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