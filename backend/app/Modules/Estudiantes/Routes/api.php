<?php

use App\Modules\Estudiantes\Http\Controllers\EstudianteController;

// Auto-registro del estudiante desde el login (endpoint público).
Route::post('/estudiantes/registro', [EstudianteController::class, 'registro']);

// Gestión administrativa del perfil aislado de estudiantes.
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/estudiantes', [EstudianteController::class, 'index']);
    Route::post('/estudiantes', [EstudianteController::class, 'store']);
    Route::get('/estudiantes/sin-usuario', [EstudianteController::class, 'sinUsuario']);
});

// Consulta del perfil del estudiante autenticado (solo lectura: el estudiante
// ya no actualiza su perfil, solo consulta sus datos y las modalidades).
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/estudiante/perfil', [EstudianteController::class, 'me']);
});