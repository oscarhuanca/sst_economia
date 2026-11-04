<?php

use App\Modules\Excelencia\Http\Controllers\ExcelenciaController;
use App\Support\Roles;

// Flujo del estudiante en el Módulo de Excelencia Académica.
Route::middleware(['auth:sanctum', 'role:'.Roles::ESTUDIANTE])->group(function () {
    // Presenta la solicitud declarando su promedio general.
    Route::post('/excelencia/solicitud', [ExcelenciaController::class, 'store']);

    // Declara o corrige el promedio mientras KARDEX aún no lo verifica.
    Route::put('/excelencia/solicitud/{id}/promedio', [ExcelenciaController::class, 'actualizarPromedio']);
});

// Configuración del módulo (promedio mínimo y rango válido).
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/excelencia/config', [ExcelenciaController::class, 'config']);
});

// Módulos del flujo gestionados por KARDEX: verificación del promedio,
// Resolución del HCC y reporte final.
Route::middleware(['auth:sanctum', 'role:'.implode(',', Roles::GESTION)])->group(function () {
    Route::post('/tramites/{id}/excelencia/{modulo}', [ExcelenciaController::class, 'guardarModulo']);
});
