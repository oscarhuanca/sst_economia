<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fecha de la Resolución del HCC en el historial del Examen de Grado.
 *
 * El módulo de habilitación captura el número de resolución y la fecha en que
 * el Consejo Universitario la emitió, pero solo el número quedaba en
 * `historial_estados_examen`. La fecha es un dato queKARDEX necesita mostrar en
 * la tarjeta y en el reporte PDF, así que se guarda en su propia columna en vez
 * de sobrecargar `fecha_examen`, que es la fecha en que se rindió el examen.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('historial_estados_examen', function (Blueprint $table) {
            $table->date('fecha_resolucion')->nullable()->after('numero_resolucion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historial_estados_examen', function (Blueprint $table) {
            $table->dropColumn('fecha_resolucion');
        });
    }
};
