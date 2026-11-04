<?php

use App\Modules\Notificaciones\Http\Controllers\NotificacionController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notificaciones', [NotificacionController::class, 'index']);
    Route::patch('/notificaciones/{id}', [NotificacionController::class, 'marcarLeida']);
    Route::post('/notificaciones/leer-todas', [NotificacionController::class, 'marcarTodasLeidas']);
});