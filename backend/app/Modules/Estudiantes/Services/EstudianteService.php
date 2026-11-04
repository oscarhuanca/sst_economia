<?php

namespace App\Modules\Estudiantes\Services;

use App\Models\Estudiante;
use App\Models\User;
use App\Support\BasePaginadoService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Casos de uso del perfil de estudiante.
 *
 * Centraliza:
 *  - REGISTRO del estudiante: el propio aspirante se registra desde el login
 *    (auto-registro público de su perfil) y el administrador también puede
 *    crear perfiles (gestión administrativa).
 *  - Gestión ADMINISTRATIVA del perfil: listados (con o sin cuenta de acceso
 *    creada en `users` para el CREAR USUARIO).
 *  - CONSULTA (solo lectura) del perfil del estudiante autenticado.
 *
 * El estudiante ya NO actualiza su perfil: una vez generadas sus credenciales
 * de acceso, solo consulta sus datos y utiliza los módulos de modalidades de
 * graduación.
 */
class EstudianteService extends BasePaginadoService
{
    /** Columnas estrictas expuestas en los listados administrativos. */
    private const COLUMNAS = [
        'id_estudiante', 'ci', 'nombres', 'apellidos', 'fecha_nacimiento',
        'email', 'telefono', 'registro_universitario', 'promedio_global',
        'created_at', 'updated_at',
    ];

    /**
     * Lista paginada de estudiantes (solo admin), con búsqueda opcional.
     *
     * @return array{data: array, meta: array<string, int|bool>}
     */
    public function index(?int $perPage = 20, ?string $q = null): array
    {
        return $this->paginar(
            $this->queryBase($q),
            $this->porPagina($perPage)
        );
    }

    /**
     * Lista paginada de estudiantes SIN cuenta de acceso aún (solo admin).
     *
     * Alimenta la pantalla de alta de credenciales (CREAR USUARIO): la
     * instancia académica selecciona a un estudiante ya registrado en el
     * sistema (incluido el auto-registro desde el login).
     *
     * @return array{data: array, meta: array<string, int|bool>}
     */
    public function sinUsuario(?int $perPage = 20, ?string $q = null): array
    {
        return $this->paginar(
            $this->queryBase($q)->doesntHave('user'),
            $this->porPagina($perPage)
        );
    }

    /**
     * Registra el perfil del estudiante (alto administrativo o auto-registro
     * público desde el login). Las credenciales de acceso se generan después
     * mediante el flujo CREAR USUARIO.
     */
    public function crear(array $datos): Estudiante
    {
        return Estudiante::create($datos);
    }

    /**
     * Devuelve el perfil del estudiante autenticado (o null si aún no existe).
     */
    public function perfilDe(User $user): ?Estudiante
    {
        $perfil = $user->estudiante;

        return $perfil?->load('user:id_usuario,email');
    }

    /**
     * Consulta base de estudiantes con selección estricta y búsqueda por
     * nombre, apellido, CI o registro universitario.
     */
    private function queryBase(?string $q = null): Builder
    {
        $query = Estudiante::query()
            ->select(self::COLUMNAS)
            ->with('user:id_usuario,username,rol,activo')
            ->orderByDesc('created_at');

        if ($q !== null && trim($q) !== '') {
            $like = '%'.trim($q).'%';
            $query->where(function ($busqueda) use ($like) {
                $busqueda
                    ->where('ci', 'like', $like)
                    ->orWhere('nombres', 'like', $like)
                    ->orWhere('apellidos', 'like', $like)
                    ->orWhere('registro_universitario', 'like', $like);
            });
        }

        return $query;
    }
}