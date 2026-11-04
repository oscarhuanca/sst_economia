<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id('id_notificacion');
            $table->foreignId('id_usuario')->constrained('users', 'id_usuario')->onDelete('cascade');
            $table->string('tipo');        // Ej: 'tramite_nuevo', 'cambio_estado', 'asignacion_tutor'
            $table->string('titulo');
            $table->text('mensaje')->nullable();
            $table->string('enlace')->nullable(); // Ruta interna del frontend, ej: '/kardex'
            $table->boolean('leida')->default(false);
            $table->timestamps();

            $table->index(['id_usuario', 'leida']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};