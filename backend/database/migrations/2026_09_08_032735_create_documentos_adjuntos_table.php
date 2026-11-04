<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('documentos_adjuntos', function (Blueprint $table) {
        $table->id('id_documento');

        // FORMA ESTÁNDAR: Laravel buscará automáticamente la tabla 'tramites' y su clave primaria
        $table->foreignId('id_tramite')->constrained('tramites', 'id_tramite')->onDelete('cascade');

        // Relación con usuarios corregida
        $table->foreignId('id_usuario_subio')->constrained('users', 'id_usuario');

        $table->string('tipo_documento');
        $table->string('nombre_archivo');
        $table->string('ruta_archivo');
        $table->integer('tamanio_kb');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos_adjuntos');
    }
};
