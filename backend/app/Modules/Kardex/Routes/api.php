<?php

use App\Modules\Kardex\Http\Controllers\KardexController;
use App\Support\Roles;

// Dashboard de KARDEX (roles de gestión académica).
// Registro de postulantes en dos etapas (generación de credenciales y
// verificación + asignación de modalidad) y consulta del flujo de trámites.
Route::middleware(['auth:sanctum', 'role:'.implode(',', Roles::GESTION)])->group(function () {
    // Busca un postulante por CI o registro universitario.
    Route::post('/kardex/postulantes/buscar', [KardexController::class, 'buscar']);

    // Etapa 1: genera las credenciales de acceso (usuario + contraseña temporal).
    // Devuelve la contraseña una única vez y no crea ningún trámite.
    Route::post('/kardex/postulantes/generar-credenciales', [KardexController::class, 'generarCredenciales']);

    // Etapa 2: verifica que el postulante ya tenga cuenta y asigna la modalidad.
    Route::post('/kardex/postulantes/asignar-modalidad', [KardexController::class, 'asignarModalidad']);

    // Consulta el estado actual del flujo del postulante (trámite más reciente).
    Route::get('/kardex/postulantes/consultar', [KardexController::class, 'consultar']);

    // Resumen estadístico del dashboard (modalidades, estudiantes y tutores).
    Route::get('/kardex/resumen', [KardexController::class, 'resumen']);

    // Verifica la unicidad del título de una tesis de grado: indica si ya existe
    // otro trámite con el mismo título y lista los trabajos más parecidos.
    Route::get('/kardex/titulos-tesis', [KardexController::class, 'verificarTitulo']);

    // Publica el documento final de tesis (PDF) desde KARDEX.
    Route::post('/kardex/tramites/{id}/publicacion-final', [KardexController::class, 'publicarDocumentoFinal']);

    // Reportes estadísticos por modalidad, mes y año.
    Route::get('/kardex/reportes', [KardexController::class, 'reportes']);
});
