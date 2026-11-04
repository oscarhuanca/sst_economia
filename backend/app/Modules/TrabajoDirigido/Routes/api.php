<?php

use App\Modules\TrabajoDirigido\Http\Controllers\TrabajoDirigidoController;
use App\Support\Roles;

// Flujo del estudiante en el Módulo de Trabajo Dirigido.
Route::middleware(['auth:sanctum', 'role:'.Roles::ESTUDIANTE])->group(function () {
    // Presenta la solicitud con el título y los documentos PDF opcionales (multipart).
    Route::post('/trabajo-dirigido/solicitud', [TrabajoDirigidoController::class, 'store']);

    // Corrige el título y sube los PDFs opcionales del perfil de proyecto
    // mientras la solicitud sigue en verificación (multipart). Se usa POST (no
    // PUT) porque PHP no parsea el cuerpo `multipart/form-data` en peticiones
    // PUT: el título y los archivos llegarían vacíos al servidor.
    Route::post('/trabajo-dirigido/{id}/perfil', [TrabajoDirigidoController::class, 'actualizarPerfil']);

    // Reenvía el perfil de proyecto corregido tras su rechazo.
    Route::post('/trabajo-dirigido/{id}/reenviar', [TrabajoDirigidoController::class, 'reenviar']);

    // Reenvía el documento final corregido tras su calificación como insuficiente.
    Route::post('/trabajo-dirigido/{id}/reenviar-documento', [TrabajoDirigidoController::class, 'reenviarDocumento']);

    // Sube el trabajo dirigido (publicación) tras aprobar la defensa.
    Route::post('/trabajo-dirigido/{id}/publicacion', [TrabajoDirigidoController::class, 'subirPublicacion']);

    // Solicita una fecha para su defensa (queda pendiente de programación por Kardex).
    Route::post('/trabajo-dirigido/{id}/solicitar-fecha-defensa', [TrabajoDirigidoController::class, 'solicitarFechaDefensa']);
});

// Configuración pública del módulo (plazos y catálogo de documentos).
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/trabajo-dirigido/config', [TrabajoDirigidoController::class, 'config']);
});
