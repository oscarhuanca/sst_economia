<?php

use App\Modules\Usuarios\Http\Controllers\UserController;
use App\Support\Roles;

// Alta automática de credenciales de un estudiante registrado (CREAR USUARIO):
// la realiza la instancia académica (admin o kardex). El sistema genera el
// username y la contraseña y los devuelve una única vez.
Route::middleware(['auth:sanctum', 'role:'.implode(',', [Roles::ADMIN, Roles::KARDEX])])->group(function () {
    Route::post('/usuarios/alta', [UserController::class, 'alta']);
});

// CRUD de usuarios: únicamente el rol admin.
Route::middleware(['auth:sanctum', 'role:'.Roles::ADMIN])->group(function () {
    Route::get('/usuarios/estadisticas', [UserController::class, 'estadisticas']);
    Route::get('/usuarios', [UserController::class, 'index']);
    Route::post('/usuarios', [UserController::class, 'store']);
    Route::match(['put', 'patch'], '/usuarios/{id}', [UserController::class, 'update']);
    Route::delete('/usuarios/{id}', [UserController::class, 'destroy']);
});

// Catálogo de docentes: roles de gestión (para asignación de tutores).
Route::middleware(['auth:sanctum', 'role:'.implode(',', Roles::GESTION)])->group(function () {
    Route::get('/usuarios/docentes', [UserController::class, 'docentes']);
});
