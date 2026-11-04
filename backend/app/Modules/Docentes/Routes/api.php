<?php

use App\Modules\Docentes\Http\Controllers\DocenteController;
use App\Support\Roles;

// CRUD del registro académico de docentes: únicamente el rol admin.
Route::middleware(['auth:sanctum', 'role:' . Roles::ADMIN])->group(function () {
    Route::get('/docentes', [DocenteController::class, 'index']);
    Route::post('/docentes', [DocenteController::class, 'store']);
    Route::match(['put', 'patch'], '/docentes/{id}', [DocenteController::class, 'update']);
    Route::delete('/docentes/{id}', [DocenteController::class, 'destroy']);
});

// Catálogo compacto de docentes para roles de gestión (selección de tutores).
Route::middleware(['auth:sanctum', 'role:' . implode(',', Roles::GESTION)])->group(function () {
    Route::get('/docentes/catalogo', [DocenteController::class, 'catalogo']);
});