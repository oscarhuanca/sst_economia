<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño de la tabla `estudiantes` como perfil AISLADO del estudiante.
 *
 * La tabla deja de depender de `users` y pasa a ser el registro oficial de los
 * datos personales e institucionales básicos del estudiante: `ci`, `nombres`,
 * `apellidos`, `registro_universitario`, `fecha_nacimiento`. Los campos
 * académicos (`plan_estudios`, `fecha_conclusion_plan`, `promedio_global`)
 * quedan inicialmente ANULABLES porque los completa el propio estudiante al
 * iniciar sesión (auto-gestión de perfil), no el administrador.
 *
 * Además transfiere los datos personales que hoy viven en `users` (ci, nombres,
 * apellidos) hacia `estudiantes` para eliminar la dependencia `id_usuario`.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Nuevas columnas de identidad del estudiante sobre `estudiantes`.
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->string('ci')->nullable()->after('id_estudiante');
            $table->string('nombres')->nullable()->after('ci');
            $table->string('apellidos')->nullable()->after('nombres');
            $table->date('fecha_nacimiento')->nullable()->after('apellidos');
        });

        // 2. Migración de datos: copia ci/nombres/apellidos del usuario ligado
        //    antes de eliminar la columna `id_usuario`.
        $estudiantes = DB::table('estudiantes')
            ->join('users', 'users.id_usuario', '=', 'estudiantes.id_usuario')
            ->select([
                'estudiantes.id_estudiante',
                'users.ci',
                'users.nombres',
                'users.apellidos',
            ])
            ->get();

        foreach ($estudiantes as $estudiante) {
            DB::table('estudiantes')
                ->where('id_estudiante', $estudiante->id_estudiante)
                ->update([
                    'ci' => $estudiante->ci,
                    'nombres' => $estudiante->nombres,
                    'apellidos' => $estudiante->apellidos,
                ]);
        }

        // 3. Reconstruye el esquema final del perfil aislado.
        Schema::table('estudiantes', function (Blueprint $table) {
            // `ci` pasa a ser obligatorio y único (índice para búsquedas rápidas).
            $table->string('ci')->nullable(false)->change();
            $table->unique('ci');

            // `codigo_universitario` -> `registro_universitario` (conserva índice único).
            $table->renameColumn('codigo_universitario', 'registro_universitario');

            // Campos académicos: el estudiante los completa por su cuenta.
            $table->string('plan_estudios')->nullable()->change();
            $table->date('fecha_conclusion_plan')->nullable()->change();
            $table->decimal('promedio_global', 5, 2)->nullable()->change();

            // Se descartan elementos ajenos al perfil aislado.
            $table->dropColumn('estado');
            $table->dropConstrainedForeignId('id_usuario');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Nota: la reversión intenta restaurar el esquema anterior; los datos
     * personales ya copiados en `estudiantes` no se pueden devolver de forma
     * confiable a `users`, por lo que aquí solo se restaura la estructura.
     */
    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropUnique(['ci']);
            $table->dropColumn(['ci', 'nombres', 'apellidos', 'fecha_nacimiento']);

            $table->renameColumn('registro_universitario', 'codigo_universitario');

            $table->string('codigo_universitario')->nullable(false)->change();
            $table->string('plan_estudios')->nullable(false)->change();
            $table->date('fecha_conclusion_plan')->nullable(false)->change();
            $table->decimal('promedio_global', 5, 2)->nullable(false)->change();

            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->foreignId('id_usuario')->nullable()->after('id_estudiante')
                ->constrained('users', 'id_usuario')->onDelete('cascade');
        });

        // Restaura los enlaces previos (id_usuario) desde la relación inversa
        // que quedó en users.estudiante_id, si la columna existiera.
        if (Schema::hasColumn('users', 'estudiante_id')) {
            DB::table('estudiantes')
                ->join('users', 'users.estudiante_id', '=', 'estudiantes.id_estudiante')
                ->update([
                    'estudiantes.id_usuario' => DB::raw('users.id_usuario'),
                ]);
        }
    }
};
