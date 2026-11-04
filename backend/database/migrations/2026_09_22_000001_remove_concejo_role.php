<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Elimina el rol `concejo` del sistema: la actualización del trámite
     * (evaluación del perfil y avances) la realizan secretaría y kardex.
     */
    public function up(): void
    {
        // Reasigna los usuarios que tenían el rol concejo a secretaria.
        DB::table('users')->where('rol', 'concejo')->update(['rol' => 'secretaria']);

        // Quita el valor 'concejo' del enum.
        DB::statement("ALTER TABLE users MODIFY rol ENUM('admin', 'estudiante', 'docente', 'kardex', 'secretaria', 'direccion') NOT NULL DEFAULT 'estudiante'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY rol ENUM('admin', 'estudiante', 'docente', 'kardex', 'secretaria', 'direccion', 'concejo') NOT NULL DEFAULT 'estudiante'");
    }
};