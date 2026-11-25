<?php

use App\Modules\Excelencia\Http\Controllers\ExcelenciaController;
use App\Support\Roles;

// Flujo del estudiante en el Módulo de Excelencia Académica.
Route::middleware(['auth:sanctum', 'role:'.Roles::ESTUDIANTE])->group(function () {
    // Presenta la solicitud adjuntando los tres PDF de rigor.
    Route::post('/excelencia/solicitud', [ExcelenciaController::class, 'store']);

    // Declara o corrige el promedio mientras KARDEX aún no lo verifica.
    Route::put('/excelencia/solicitud/{id}/promedio', [ExcelenciaController::class, 'actualizarPromedio']);
});

// Configuración del módulo (promedio mínimo y rango válido).
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/excelencia/config', [ExcelenciaController::class, 'config']);
});

// Módulos del flujo gestionados por KARDEX: designación del tribunal, informe
// de evaluación, Resolución del HCC y reporte final.
Route::middleware(['auth:sanctum', 'role:'.implode(',', Roles::GESTION)])->group(function () {
    // La ruta estática va ANTES de /{modulo} para que no se capture como módulo.
    Route::post('/tramites/{id}/excelencia/recepcion-documentos', [ExcelenciaController::class, 'verificarDocumentos']);
    Route::post('/tramites/{id}/excelencia/{modulo}', [ExcelenciaController::class, 'guardarModulo']);
});
