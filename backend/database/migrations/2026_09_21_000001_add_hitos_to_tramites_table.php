<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la columna `hitos` a la tabla `tramites`.
     *
     * Los hitos son fechas clave del flujo de la tesis calculadas por el módulo
     * de Tesis (aprobación del perfil, plazo de presentación, fecha de defensa,
     * plazo de correcciones), almacenadas como JSON `{ estado: 'YYYY-MM-DD' }`.
     * El módulo: TesisGrado los alimenta; el frontend los consume para el
     * seguimiento y la cuenta regresiva.
     */
    public function up(): void
    {
        Schema::table('tramites', function (Blueprint $table) {
            $table->json('hitos')->nullable()->after('observaciones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tramites', function (Blueprint $table) {
            $table->dropColumn('hitos');
        });
    }
};