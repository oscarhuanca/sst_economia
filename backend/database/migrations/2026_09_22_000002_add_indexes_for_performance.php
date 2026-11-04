<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices de rendimiento para las tablas de trámites/tesis/estudiantes.
 *
 * Las FKs creadas con `foreignId()->constrained()` ya generan su índice en la
 * migración original; aquí se añaden los índices sobre campos de búsqueda y
 * filtrado frecuente (estado, fechas, roles activos) para que los listados,
 * estadísticas y contadores eviten escaneos completos (full scans).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Listados y estadísticas filtran por estado y ordenan por fecha.
        Schema::table('tramites', function (Blueprint $table) {
            $table->index('estado_actual', 'idx_tramites_estado');
            $table->index(['id_estudiante', 'estado_actual'], 'idx_tramites_estudiante_estado');
            $table->index('created_at', 'idx_tramites_created_at');
        });

        // Línea de tiempo del trámite + orden de historial.
        Schema::table('estados_tramite', function (Blueprint $table) {
            $table->index('created_at', 'idx_estados_tramite_created_at');
        });

        // Vínculos de documentos por trámite y tipo.
        Schema::table('documentos_adjuntos', function (Blueprint $table) {
            $table->index('tipo_documento', 'idx_documentos_tipo');
        });

        // Catálogo de docentes y auditoría por rol activo.
        Schema::table('users', function (Blueprint $table) {
            $table->index(['rol', 'activo'], 'idx_users_rol_activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tramites', function (Blueprint $table) {
            $table->dropIndex('idx_tramites_estado');
            $table->dropIndex('idx_tramites_estudiante_estado');
            $table->dropIndex('idx_tramites_created_at');
        });

        Schema::table('estados_tramite', function (Blueprint $table) {
            $table->dropIndex('idx_estados_tramite_created_at');
        });

        Schema::table('documentos_adjuntos', function (Blueprint $table) {
            $table->dropIndex('idx_documentos_tipo');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_rol_activo');
        });
    }
};