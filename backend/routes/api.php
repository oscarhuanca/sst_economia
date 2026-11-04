<?php

/*
|--------------------------------------------------------------------------
| Agregador de rutas por módulo
|--------------------------------------------------------------------------
|
| Cada módulo de la aplicación declara su propio archivo `Routes/api.php` bajo
| `app/Modules/{Modulo}/`. Aquí se cargan automáticamente todos los archivos
| existentes, manteniendo el prefijo `/api` y el grupo de middleware `api`
| configurados en `bootstrap/app.php`.
|
| Para añadir un nuevo módulo solo hay que crear su carpeta y su archivo de
| rutas: se registrará solo, sin tocar esta ni otras rutas.
*/
foreach (glob(__DIR__ . '/../app/Modules/*/Routes/api.php') ?: [] as $moduleRoutes) {
    require $moduleRoutes;
}