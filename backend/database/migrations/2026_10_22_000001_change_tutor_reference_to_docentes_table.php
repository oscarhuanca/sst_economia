<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El tutor de un trámite es un docente del registro académico (`docentes`), no
 * una cuenta de acceso (`users`). Se repunta la clave foránea de
 * `tramites.id_tutor` hacia `docentes.id_docente`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tramites', function (Blueprint $table) {
            $table->dropForeign(['id_tutor']);
        });

        DB::table('tramites')->update(['id_tutor' => null]);

        Schema::table('tramites', function (Blueprint $table) {
            $table->foreign('id_tutor')
                ->references('id_docente')
                ->on('docentes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tramites', function (Blueprint $table) {
            $table->dropForeign(['id_tutor']);
        });

        DB::table('tramites')->update(['id_tutor' => null]);

        Schema::table('tramites', function (Blueprint $table) {
            $table->foreign('id_tutor')
                ->references('id_usuario')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
