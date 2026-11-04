<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de estados del Examen de Grado.
 *
 * Registro especializado de la modalidad: a diferencia de `estados_tramite`
 * (línea de tiempo genérica compartida por todas las modalidades), cada fila
 * guarda el detalle propio del examen — etapa del flujo, asistencia al sorteo,
 * tema sorteado, fecha del examen, nota y número de resolución del HCC — con
 * columnas tipadas y consultables.
 *
 * La línea de tiempo genérica (`estados_tramite`) se sigue escribiendo en
 * paralelo para que el seguimiento del estudiante no cambie.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('historial_estados_examen', function (Blueprint $table) {
            $table->id('id_historial_examen');
            $table->foreignId('id_tramite')
                ->constrained('tramites', 'id_tramite')
                ->cascadeOnDelete();

            // Estado interno alcanzado (ver TramiteStateService::TRANSITIONS).
            $table->string('nombre_estado');

            // Etapa/módulo del flujo que originó la transición.
            $table->string('etapa', 60)->nullable();

            // Resultado de la evaluación: aprobado | reprobado | no_asistio | rechazado.
            $table->string('resultado', 30)->nullable();

            // Datos propios del examen.
            $table->string('numero_resolucion')->nullable();
            $table->string('tema', 500)->nullable();
            $table->date('fecha_examen')->nullable();
            $table->decimal('nota', 5, 2)->nullable();

            // Intentos que llevaba el estudiante al registrar la transición.
            $table->unsignedTinyInteger('numero_intentos')->nullable();

            $table->text('observaciones')->nullable();
            $table->foreignId('id_usuario_responsable')->constrained('users', 'id_usuario');
            $table->timestamps();

            $table->index(['id_tramite', 'created_at']);
            $table->index(['id_tramite', 'nombre_estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_estados_examen');
    }
};
