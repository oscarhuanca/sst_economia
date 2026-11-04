<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Credenciales de acceso sobre la tabla `users`.
 *
 * Habilita el rediseño de autenticación:
 *  - `username`: identificador de login único, autogenerado para los usuarios
 *    existentes con la regla `primer_nombre_ci` (minúsculas, sin espacios).
 *  - `estudiante_id`: vínculo OPCIONAL con el perfil aislado de `estudiantes`
 *    (solo para usuarios con rol estudiante; los demás quedan independientes).
 *  - `email` pasa a ser NO obligatorio: el inicio de sesión ya no se basa en él.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('email');
            $table->unsignedBigInteger('estudiante_id')->nullable()->after('username');
        });

        // Re-genera el username de los usuarios existentes siguiendo la misma
        // regla que usa el alta automática (`primer_nombre_ci` en minúsculas).
        $usuarios = DB::table('users')->get(['id_usuario', 'ci', 'nombres', 'email']);

        foreach ($usuarios as $usuario) {
            $primerNombre = mb_strtolower(Str::ascii(trim(explode(' ', (string) $usuario->nombres)[0] ?? '')));
            $ci = mb_strtolower(preg_replace('/[^a-z0-9]/u', '', Str::ascii((string) $usuario->ci)) ?? '');

            $base = ($primerNombre !== '' ? $primerNombre : 'usuario').'_'.($ci !== '' ? $ci : Str::lower(Str::random(5)));
            $username = $base;
            $sufijo = 2;

            while (DB::table('users')->where('username', $username)->exists()) {
                $username = $base.'_'.$sufijo++;
            }

            DB::table('users')->where('id_usuario', $usuario->id_usuario)->update(['username' => $username]);
        }

        // Los usuarios con rol estudiante quedan vinculados a su perfil aislado
        // mediante la coincidencia de CI (los datos se migraron en la migración previa).
        if (Schema::hasColumn('estudiantes', 'ci')) {
            DB::statement(
                "UPDATE users u JOIN estudiantes e ON e.ci = u.ci
                 SET u.estudiante_id = e.id_estudiante
                 WHERE u.rol = 'estudiante'"
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('username')->nullable(false)->change();

            $table->unique('username');
            $table->unique('estudiante_id');
            $table->foreign('estudiante_id')
                ->references('id_estudiante')
                ->on('estudiantes')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['estudiante_id']);
            $table->dropConstrainedForeignId('estudiante_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
            $table->string('email')->nullable(false)->unique()->change();
        });
    }
};
