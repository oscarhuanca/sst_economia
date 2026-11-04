<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tramites', function (Blueprint $table) {
            $table->foreignId('id_tutor')->nullable()
                ->after('id_modalidad')
                ->constrained('users', 'id_usuario')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tramites', function (Blueprint $table) {
            $table->dropForeign(['id_tutor']);
            $table->dropColumn('id_tutor');
        });
    }
};