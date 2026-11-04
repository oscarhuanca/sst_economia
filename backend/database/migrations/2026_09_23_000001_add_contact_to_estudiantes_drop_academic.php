<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajustes de la tabla `estudiantes` para el registro del propio estudiante.
 *
 *  - Se añaden `email` y `telefono` (datos de contacto que aporta el propio
 *    estudiante al auto-registrarse desde el login).
 *  - Se eliminan `plan_estudios` y `fecha_conclusion_plan`: la modalidad de
 *    graduación y su avance se gestionan a través del trámite, no del perfil.
 *  - `promedio_global` permanece OPCIONAL (nullable): no es obligatorio para
 *    registrarse y lo completa la instancia académica si corresponde.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->string('email')->nullable()->after('fecha_nacimiento');
            $table->string('telefono')->nullable()->after('email');
        });

        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropColumn(['plan_estudios', 'fecha_conclusion_plan']);
        });
    }

    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->string('plan_estudios')->nullable()->after('fecha_nacimiento');
            $table->date('fecha_conclusion_plan')->nullable()->after('plan_estudios');
        });

        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropColumn(['email', 'telefono']);
        });
    }
};