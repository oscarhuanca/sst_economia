<?php

use App\Modules\Tesis\Http\Controllers\TesisController;
use App\Support\Roles;

// Flujo del estudiante en el Módulo de Tesis de Grado.
Route::middleware(['auth:sanctum', 'role:'.Roles::ESTUDIANTE])->group(function () {
    // Presenta la solicitud con el título y los documentos PDF opcionales (multipart).
    Route::post('/tesis/solicitud', [TesisController::class, 'store']);

    // Corrige el título y sube los PDFs opcionales del perfil mientras la
    // solicitud sigue en verificación (multipart). Se usa POST (no PUT) porque
    // PHP no parsea el cuerpo `multipart/form-data` en peticiones PUT: el
    // título y los archivos llegarían vacíos al servidor.
    Route::post('/tesis/{id}/perfil', [TesisController::class, 'actualizarPerfil']);

    // Reenvía el perfil corregido tras su rechazo por el Consejo Universitario.
    Route::post('/tesis/{id}/reenviar', [TesisController::class, 'reenviar']);

    // Reenvía el documento final corregido tras su calificación como insuficiente.
    Route::post('/tesis/{id}/reenviar-documento', [TesisController::class, 'reenviarDocumento']);

    // Sube el trabajo de investigación (publicación) tras aprobar la defensa.
    Route::post('/tesis/{id}/publicacion', [TesisController::class, 'subirPublicacion']);

    // Solicita una fecha para su defensa (queda pendiente de programación por Kardex).
    Route::post('/tesis/{id}/solicitar-fecha-defensa', [TesisController::class, 'solicitarFechaDefensa']);
});

// Configuración pública del módulo (plazos y catálogo de documentos).
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tesis/config', [TesisController::class, 'config']);
});
