<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registrador automático de providers de módulos.
 *
 * Cada módulo de dominio vive en `app/Modules/{Modulo}/` y puede declarar sus
 * propios service providers en `app/Modules/{Modulo}/Providers/`. Aquí se
 * descubren y registran SOLOS, replicando el patrón de `routes/api.php` (que
 * ya auto-carga las rutas de cada módulo).
 *
 * Añadir un módulo nuevo solo requiere:
 *   1. crear su carpeta `app/Modules/{Modulo}/`,
 *   2. opcionalmente un `Routes/api.php` con sus endpoints y
 *   3. opcionalmente un `Providers/XxServiceProvider.php` con su cableado.
 * Nada más se toca: `bootstrap/providers.php` solo declara este provider y
 * `AppServiceProvider`, que son la composición raíz de la aplicación.
 */
class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->discoverModuleProviders();
    }

    /**
     * Registra todos los ServiceProvider declarados dentro de cada módulo.
     *
     * Descubre los archivos `*ServiceProvider.php` de la subcarpeta `Providers`
     * de cada módulo y deriva su FQCN a partir de la ruta, respetando el
     * namespace de Laravel (`App\Modules\<Modulo>\Providers\<Clase>`).
     */
    private function discoverModuleProviders(): void
    {
        $mask = __DIR__.'/../Modules/*/Providers/*ServiceProvider.php';

        foreach (glob($mask) ?: [] as $providerFile) {
            $moduleName = basename(dirname(dirname($providerFile)));
            $shortClass = pathinfo($providerFile, PATHINFO_FILENAME);

            $providerClass = sprintf(
                'App\\Modules\\%s\\Providers\\%s',
                $moduleName,
                $shortClass,
            );

            $this->app->register($providerClass);
        }
    }
}