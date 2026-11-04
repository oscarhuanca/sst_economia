<?php

use App\Modules\Tramites\Http\Controllers\ModalidadController;
use App\Modules\Tramites\Http\Controllers\TramiteController;
use App\Support\Roles;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/modalidades', [ModalidadController::class, 'index']);
    Route::get('/estudiante/tramite-activo', [TramiteController::class, 'tramiteActivo']);
    Route::post('/tramites', [TramiteController::class, 'store']);
});

// Rutas estáticas del prefijo /tramites. IMPORTANTE: se declaran ANTES de
// /tramites/{id} para que el comodín no capture "pendientes"/"estadisticas"
// (provocaría TypeError en show(int $id) → HTTP 500).
// Paneles de gestión (roles de gestión).
Route::middleware(['auth:sanctum', 'role:'.implode(',', Roles::GESTION)])->group(function () {
    Route::get('/tramites/pendientes', [TramiteController::class, 'pendientes']);
    Route::get('/tramites/concluidos', [TramiteController::class, 'concluidos']);
    Route::get('/tramites/estadisticas', [TramiteController::class, 'estadisticas']);
    Route::post('/tramites/{id}/revisar', [TramiteController::class, 'revisar']);
    Route::post('/tramites/{id}/asignar-tutor', [TramiteController::class, 'asignarTutor']);
});

// Transición de estados: la realiza la gestión del trámite (secretaría/kardex).
Route::middleware(['auth:sanctum', 'role:'.implode(',', Roles::GESTION)])->group(function () {
    Route::post('/tramites/{id}/transicionar', [TramiteController::class, 'transicionar']);
    Route::post('/tramites/{id}/flujo/{modulo}', [TramiteController::class, 'guardarModuloFlujo']);

    // Flujo de Examen de Grado (KARDEX). IMPORTANTE: la ruta estática
    // /examen/reabrir se declara ANTES de /examen/{modulo} para que el
    // comodín no la capture como si "reabrir" fuera el nombre de un módulo.
    Route::post('/tramites/{id}/examen/reabrir', [TramiteController::class, 'reabrirExamen']);
    Route::post('/tramites/{id}/examen/{modulo}', [TramiteController::class, 'guardarModuloExamen']);
});

// Panel del docente tutor.
Route::middleware(['auth:sanctum', 'role:'.Roles::DOCENTE])->group(function () {
    Route::get('/tutorias', [TramiteController::class, 'tutorias']);
});

// Detalle individual de un trámite (se registra al final para no opacar las
// rutas estáticas /tramites/pendientes y /tramites/estadisticas).
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tramites/{id}', [TramiteController::class, 'show']);
});
