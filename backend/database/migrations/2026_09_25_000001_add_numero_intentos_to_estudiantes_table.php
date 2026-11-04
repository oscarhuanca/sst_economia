<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade `numero_intentos` al perfil del estudiante.
 *
 * Contador de postulaciones a la modalidad Examen de Grado. Se incrementa
 * automáticamente en cada nueva postulación (`KardexService::asignarModalidad`)
 * y en cada reapertura de una solicitud reprobada o con ausencia
 * (`ExamenService::reabrirSolicitud`).
 *
 * El módulo 1 del flujo ("Verificación de intentos") lo lee y lo compara con
 * `examen.maximo_intentos`: con `numero_intentos >= maximo_intentos` la solicitud
 * se rechaza por haber agotado los intentos permitidos.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->unsignedTinyInteger('numero_intentos')
                ->default(0)
                ->after('promedio_global')
                ->comment('Postulaciones realizadas a la modalidad Examen de Grado.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropColumn('numero_intentos');
        });
    }
};
