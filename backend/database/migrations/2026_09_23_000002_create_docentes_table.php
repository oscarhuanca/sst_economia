<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `docentes`: registro académico de los docentes de la carrera.
 *
 * Es una tabla independiente del catálogo de accesos (`users`): almacena los
 * datos institucionales del docente (nombre, apellidos, CI, teléfono, email y
 * la materia que imparte, opcional). `ci` es único y se usa como identificador
 * natural del docente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docentes', function (Blueprint $table) {
            $table->id('id_docente');
            $table->string('ci')->unique();
            $table->string('nombre');
            $table->string('apellidos');
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('materia')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docentes');
    }
};