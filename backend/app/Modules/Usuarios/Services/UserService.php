<?php

namespace App\Modules\Usuarios\Services;

use App\Models\Estudiante;
use App\Models\User;
use App\Support\BasePaginadoService;
use App\Support\CredencialesEstudiante;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Casos de uso de gestión de usuarios.
 *
 * Centraliza el CRUD de usuarios (alta, consulta, actualización y baja lógica),
 * el alta automática de cuentas para estudiantes (CREAR USUARIO) y el listado
 * de docentes. La autorización por rol se resuelve en las rutas vía el
 * middleware `role`, por lo que el servicio asume permiso verificado.
 */
class UserService extends BasePaginadoService
{
    /** Columnas estrictas expuestas del panel de administración (nunca password). */
    private const LISTA_COLUMNAS = [
        'id_usuario', 'ci', 'nombres', 'apellidos', 'email', 'telefono',
        'username', 'rol', 'activo', 'created_at', 'updated_at',
    ];

    /** Columnas del perfil aislado expuestas junto al usuario. */
    private const PERFIL_ESTUDIANTE_COLUMNAS = [
        'id_estudiante', 'ci', 'nombres', 'apellidos', 'registro_universitario',
        'fecha_nacimiento', 'email', 'telefono', 'promedio_global',
    ];

    /**
     * Lista paginada de usuarios activos con su perfil de estudiante (solo admin).
     *
     * @return array{data: array, meta: array<string, int|bool>}
     */
    public function index(?int $perPage = 20): array
    {
        return $this->paginar(
            User::query()
                ->select(self::LISTA_COLUMNAS)
                ->with('estudiante:id_estudiante,ci,nombres,apellidos,registro_universitario,fecha_nacimiento,email,telefono,promedio_global')
                ->where('activo', true)
                ->orderByDesc('created_at'),
            $this->porPagina($perPage)
        );
    }

    /**
     * Totales de usuarios activos para las tarjetas del panel de administración.
     *
     * Se calculan en SQL sobre toda la tabla (no sobre la página visible), de
     * modo que las tarjetas no cambien al paginar la matriz de actores.
     *
     * @return array{total: int, estudiantes: int, docentes: int, personal_academico: int, por_rol: array<string, int>}
     */
    public function estadisticas(): array
    {
        $porRol = User::query()
            ->select('rol', DB::raw('COUNT(*) AS total'))
            ->where('activo', true)
            ->groupBy('rol')
            ->pluck('total', 'rol')
            ->map(fn ($total) => (int) $total)
            ->all();

        $total = array_sum($porRol);
        $estudiantes = (int) ($porRol[Roles::ESTUDIANTE] ?? 0);
        $docentes = (int) ($porRol[Roles::DOCENTE] ?? 0);

        return [
            'total' => $total,
            'estudiantes' => $estudiantes,
            'docentes' => $docentes,
            'personal_academico' => $total - $estudiantes - $docentes,
            'por_rol' => $porRol,
        ];
    }

    /**
     * Lista los docentes activos ordenados por nombre (roles de gestión).
     * Catálogo pequeño: solo columnas usadas por el selector de tutor.
     */
    public function docentes(): Collection
    {
        return User::query()
            ->select(['id_usuario', 'nombres', 'apellidos', 'email'])
            ->where('rol', Roles::DOCENTE)
            ->where('activo', true)
            ->orderBy('nombres')
            ->get();
    }

    /**
     * Crea un usuario cifrando su contraseña. El `username` lo asigna el
     * administrador en el formulario de alta (CREAR ESTUDIANTE / CREAR USUARIO
     * son casos independientes; los estudiantes obtienen su username automático
     * con altaEstudiante()).
     */
    public function crear(array $datos): User
    {
        $datos['password'] = Hash::make($datos['password']);

        return User::create($datos);
    }

    /**
     * Da de alta automáticamente la cuenta de acceso de un estudiante ya
     * registrado en el sistema (CREAR USUARIO).
     *
     * Genera `username` y contraseña temporal según las reglas centralizadas
     * (ver CredencialesEstudiante), vincula el perfil aislado con
     * `estudiante_id` y devuelve las credenciales generadas una única vez para
     * que el administrador las entregue al estudiante.
     *
     *
     * @return array{usuario: User, username: string, password: string}
     *
     * @throws ValidationException Si el estudiante no
     *                             existe o ya posee una cuenta de acceso.
     * @throws \DomainException Si no se puede generar la contraseña
     *                          (estudiante sin fecha de nacimiento).
     */
    public function altaEstudiante(string $identificador): array
    {
        $estudiante = Estudiante::query()
            ->where('ci', $identificador)
            ->orWhere('registro_universitario', $identificador)
            ->first();

        if ($estudiante === null) {
            throw ValidationException::withMessages([
                'identificador' => 'No se encontró un estudiante con ese CI o registro universitario.',
            ]);
        }

        if ($estudiante->user !== null) {
            throw ValidationException::withMessages([
                'identificador' => 'El estudiante ya tiene una cuenta de acceso creada.',
            ]);
        }

        $username = CredencialesEstudiante::usernameUnico($estudiante);
        $password = CredencialesEstudiante::passwordDe($estudiante);

        try {
            $usuario = User::create([
                'ci' => $estudiante->ci,
                'nombres' => $estudiante->nombres,
                'apellidos' => $estudiante->apellidos,
                'email' => CredencialesEstudiante::emailDisponible($estudiante),
                'telefono' => $estudiante->telefono,
                'username' => $username,
                'password' => Hash::make($password),
                'rol' => Roles::ESTUDIANTE,
                'activo' => true,
                'estudiante_id' => $estudiante->id_estudiante,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'identificador' => 'No se pudo crear la cuenta de acceso: el CI o el usuario ya está registrado.',
            ]);
        }

        return [
            'usuario' => $usuario->load('estudiante'),
            'username' => $username,
            'password' => $password,
        ];
    }

    /**
     * Actualiza un usuario; cifra password si se envía. El perfil de estudiante
     * ya no se sincroniza aquí: el estudiante mantiene sus campos académicos
     * por su cuenta y el administrador gestiona el perfil aislado aparte.
     */
    public function actualizar(int $id, array $datos): User
    {
        $user = User::findOrFail($id);

        $campos = collect($datos)->except(['password'])->all();
        if (! empty($datos['password'])) {
            $campos['password'] = Hash::make($datos['password']);
        }

        $user->update($campos);

        return $user->load('estudiante:'.implode(',', self::PERFIL_ESTUDIANTE_COLUMNAS));
    }

    /**
     * Da de baja lógica a un usuario (activo = false).
     */
    public function desactivar(int $id): void
    {
        User::findOrFail($id)->update(['activo' => false]);
    }
}
