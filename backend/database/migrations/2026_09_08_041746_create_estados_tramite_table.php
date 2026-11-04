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
    Schema::create('estados_tramite', function (Blueprint $table) {
        $table->id('id_estado');
        $table->foreignId('id_tramite')->constrained('tramites', 'id_tramite')->onDelete('cascade');
        $table->string('nombre_estado'); // Ej: 'perfil_en_evaluacion'
        $table->text('descripcion')->nullable();
        $table->foreignId('id_usuario_responsable')->constrained('users', 'id_usuario');
        $table->text('observaciones')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estados_tramite');
    }
};
