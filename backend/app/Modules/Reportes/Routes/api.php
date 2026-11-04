<?php

use App\Modules\Reportes\Http\Controllers\ReporteController;
use App\Support\Roles;

// Descarga del reporte PDF de un trámite: la realiza la gestión académica.
Route::middleware(['auth:sanctum', 'role:'.implode(',', Roles::GESTION)])->group(function () {
    Route::get('/tramites/{id}/reporte', [ReporteController::class, 'descargar']);
});
