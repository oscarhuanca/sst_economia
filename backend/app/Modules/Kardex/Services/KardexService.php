<?php

namespace App\Modules\Kardex\Services;

use App\Models\Estudiante;
use App\Models\Modalidad;
use App\Models\Tramite;
use App\Models\User;
use App\Modules\Tramites\Services\TramiteService;
use App\Modules\Tramites\Services\TramiteStateService;
use App\Support\CredencialesEstudiante;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Casos de uso del Dashboard de KARDEX.
 *
 * Orquesta la búsqueda de postulantes por CI o registro universitario, la
 * asignación de la modalidad de titulación con generación automática de
 * credenciales (usuario + contraseña temporal, entregadas una única vez) y la
 * consulta del flujo actual del postulante. La autorización por rol se resuelve
 * en las rutas vía el middleware `role`; aquí solo se asume permiso verificado.
 */
class KardexService
{
    /** Eager-loads del detalle de trámite para la consulta del postulante. */
    private const DETALLE_CARGAS = [
        'modalidad:id_modalidad,nombre,descripcion,requisitos_minimos',
        'estudiante:id_estudiante,ci,nombres,apellidos,registro_universitario,email,telefono,promedio_global,numero_intentos',
        'estudiante.user:id_usuario,estudiante_id,nombres,apellidos,email,rol',
        'documentos:id_documento,id_tramite,tipo_documento,nombre_archivo,ruta_archivo,tamanio_kb,created_at',
        'estados:id_estado,id_tramite,nombre_estado,descripcion,observaciones,id_usuario_responsable,created_at',
        'estados.responsable:id_usuario,nombres,apellidos,rol',
        'tutor:id_docente,nombre,apellidos,email,telefono,materia,ci',
        // Historial especializado del Examen de Grado: las tarjetas del panel
        // reconstruyen los módulos completados a partir de estas filas, porque
        // tras un reintento el estado actual no refleja lo ya superado.
        'historialExamen:id_historial_examen,id_tramite,nombre_estado,etapa,resultado,numero_resolucion,fecha_resolucion,tema,fecha_examen,nota,numero_intentos,observaciones,created_at',
    ];

    public function __construct(
        private readonly TramiteService $tramites,
    ) {}

    /**
     * Busca un postulante por CI o registro universitario.
     *
     * @return array{postulante: array<string, mixed>}
     *
     * @throws ValidationException Si no existe un perfil de estudiante con el
     *                             identificador indicado.
     */
    public function buscarPostulante(string $identificador): array
    {
        return [
            'postulante' => $this->postulante($identificador, true),
        ];
    }

    /**
     * Asigna la modalidad de titulación al postulante.
     *
     * Crea la cuenta de acceso automáticamente si el postulante aún no tiene
     * una (usuario + contraseña temporal) y registra el trámite en su estado
     * inicial. Los pasos se ejecutan en una sola transacción: si falla la
     * asignación no queda una cuenta huérfana.
     *
     * @param  array<string, mixed>  $validated
     * @return array{postulante: array<string, mixed>, credenciales: array<string, mixed>, tramite: array<string, mixed>}
     *
     * @throws ValidationException
     * @throws \DomainException
     */
    public function asignarModalidad(array $validated, User $actor): array
    {
        $identificador = (string) $validated['identificador'];
        $idModalidad = (int) $validated['id_modalidad'];

        $estudiante = Estudiante::query()
            ->where('ci', $identificador)
            ->orWhere('registro_universitario', $identificador)
            ->first();

        if ($estudiante === null) {
            throw ValidationException::withMessages([
                'identificador' => 'No se encontró un postulante con ese CI o registro universitario.',
            ]);
        }

        $modalidad = Modalidad::query()
            ->whereKey($idModalidad)
            ->where('activo', true)
            ->first();

        if ($modalidad === null) {
            throw ValidationException::withMessages([
                'id_modalidad' => 'La modalidad seleccionada no está disponible.',
            ]);
        }

        $this->asegurarModalidadDisponible($estudiante, $idModalidad);

        [$credenciales, $usuario] = DB::transaction(function () use ($estudiante) {
            if ($estudiante->user !== null) {
                return [
                    [
                        'generadas' => false,
                        'ya_existia' => true,
                        'username' => $estudiante->user->username,
                        'password' => null,
                    ],
                    $estudiante->user,
                ];
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
                [
                    'generadas' => true,
                    'ya_existia' => false,
                    'username' => $username,
                    'password' => $password,
                ],
                $usuario,
            ];
        });

        $tramite = $this->tramites->crear([
            'id_modalidad' => $idModalidad,
            'documentos' => [],
        ], $usuario);

        return [
            'postulante' => $this->postulante($estudiante->ci, false),
            'credenciales' => $credenciales,
            'tramite' => $this->tramites->formatear($tramite),
        ];
    }

    /**
     * Consulta el flujo actual del postulante: datos del perfil y su trámite
     * más reciente (o null si aún no tiene uno asignado).
     *
     * @return array{postulante: array<string, mixed>, tramite: array<string, mixed>|null}
     *
     * @throws ValidationException
     */
    public function consultar(string $identificador): array
    {
        $estudiante = Estudiante::query()
            ->where('ci', $identificador)
            ->orWhere('registro_universitario', $identificador)
            ->first();

        if ($estudiante === null) {
            throw ValidationException::withMessages([
                'identificador' => 'No se encontró un postulante con ese CI o registro universitario.',
            ]);
        }

        $tramite = Tramite::query()
            ->select([
                'id_tramite', 'id_estudiante', 'id_modalidad', 'id_tutor',
                'estado_actual', 'observaciones', 'hitos', 'created_at', 'updated_at',
            ])
            ->with(self::DETALLE_CARGAS)
            ->where('id_estudiante', $estudiante->id_estudiante)
            ->orderByDesc('created_at')
            ->limit(1)
            ->first();

        return [
            'postulante' => $this->postulante($estudiante->ci, false),
            'tramite' => $tramite !== null ? $this->tramites->formatear($tramite) : null,
        ];
    }

    /**
     * Resumen estadístico del Dashboard de KARDEX.
     *
     * Agrega en SQL los totales por modalidad, estudiantes y tutores para la
     * vista principal del dashboard (panel general).
     *
     * El verde (`aprobados`) cuenta los trámites cuyo flujo YA culminó de forma
     * aprobatoria según su modalidad: `titulado` en todas y `aprobado` en las
     * que cierran ahí. En Examen de Grado `aprobado` todavía espera el reporte,
     * así que no cuenta como finalizado y permanece en curso.
     *
     * @return array{modalidades: array<int, array<string, mixed>>, estudiantes: array<string, int>, tutores: array<string, int>, totales: array<string, int>}
     */
    public function resumen(): array
    {
        $porModalidad = Modalidad::query()
            ->withCount('tramites')
            ->withCount(['tramites as aprobados_count' => fn (Builder $q) => $q->where('estado_actual', 'aprobado')])
            ->withCount(['tramites as titulados_count' => fn (Builder $q) => $q->where('estado_actual', 'titulado')])
            ->withCount(['tramites as fallos_count' => fn (Builder $q) => $q->whereIn('estado_actual', TramiteStateService::fallos())])
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(function (Modalidad $modalidad) {
                $exitosos = TramiteStateService::terminalesExitososDe($modalidad->nombre);
                $aprobados = (int) $modalidad->titulados_count;
                if (in_array('aprobado', $exitosos, true)) {
                    $aprobados += (int) $modalidad->aprobados_count;
                }
                $total = (int) $modalidad->tramites_count;
                $fallos = (int) $modalidad->fallos_count;

                return [
                    'id_modalidad' => $modalidad->id_modalidad,
                    'nombre' => $modalidad->nombre,
                    'total' => $total,
                    'aprobados' => $aprobados,
                    'en_curso' => max($total - $aprobados - $fallos, 0),
                ];
            })
            ->all();

        $totalTramites = (int) Tramite::query()->count();
        $aprobados = (int) Tramite::query()
            ->where(function (Builder $q) {
                $q->where('estado_actual', 'titulado')
                    ->orWhere(fn (Builder $sub) => $sub
                        ->where('estado_actual', 'aprobado')
                        ->whereHas('modalidad', fn (Builder $m) => $m->where('nombre', '!=', TramiteStateService::MODALIDAD_EXAMEN)));
            })
            ->count();
        $fallos = (int) Tramite::query()->whereIn('estado_actual', TramiteStateService::fallos())->count();

        return [
            'modalidades' => $porModalidad,
            'estudiantes' => [
                'total' => (int) Estudiante::query()->count(),
                'con_cuenta' => (int) User::query()->where('rol', Roles::ESTUDIANTE)->count(),
                'con_tramite' => (int) Tramite::query()->distinct()->count('id_estudiante'),
            ],
            'tutores' => [
                'total' => (int) User::query()->where('rol', Roles::DOCENTE)->count(),
                'activos' => (int) Tramite::query()->whereNotNull('id_tutor')->distinct()->count('id_tutor'),
            ],
            'totales' => [
                'tramites' => $totalTramites,
                'aprobados' => $aprobados,
                'en_curso' => max($totalTramites - $aprobados - $fallos, 0),
            ],
        ];
    }

    /**
     * Perfil del postulante listo para la vista:
     * incluye si ya posee cuenta de acceso y su username (si existe).
     *
     * @return array<string, mixed>
     */
    private function postulante(string $identificador, bool $conUsuario): array
    {
        $estudiante = Estudiante::query()
            ->when($conUsuario, fn ($q) => $q->with('user:id_usuario,username,nombres,apellidos,rol'))
            ->where('ci', $identificador)
            ->orWhere('registro_universitario', $identificador)
            ->firstOrFail();

        return [
            'id_estudiante' => $estudiante->id_estudiante,
            'ci' => $estudiante->ci,
            'nombres' => $estudiante->nombres,
            'apellidos' => $estudiante->apellidos,
            'registro_universitario' => $estudiante->registro_universitario,
            'fecha_nacimiento' => $estudiante->fecha_nacimiento?->format('Y-m-d'),
            'email' => $estudiante->email,
            'telefono' => $estudiante->telefono,
            'promedio_global' => $estudiante->promedio_global !== null
                ? round((float) $estudiante->promedio_global, 2)
                : null,
            'tiene_cuenta' => $estudiante->user !== null,
            'username' => $estudiante->user?->username,
        ];
    }

    /**
     * Genera reporte estadístico filtrable por modalidad, mes, año o rango de fechas.
     */
    public function reportes(array $filtros = []): array
    {
        $query = Tramite::query()
            ->with([
                'modalidad:id_modalidad,nombre',
                'estudiante:id_estudiante,ci,nombres,apellidos',
                'tutor:id_docente,nombre,apellidos',
            ])
            ->select('tramites.*');

        if (! empty($filtros['id_modalidad'])) {
            $query->where('id_modalidad', (int) $filtros['id_modalidad']);
        }

        if (! empty($filtros['anio'])) {
            $query->whereYear('created_at', (int) $filtros['anio']);
        }

        if (! empty($filtros['mes'])) {
            $query->whereMonth('created_at', (int) $filtros['mes']);
        }

        if (! empty($filtros['desde'])) {
            $query->whereDate('created_at', '>=', $filtros['desde']);
        }

        if (! empty($filtros['hasta'])) {
            $query->whereDate('created_at', '<=', $filtros['hasta']);
        }

        $tramites = $query->orderByDesc('created_at')->get();

        $total = $tramites->count();
        $fallosSet = TramiteStateService::fallos();

        // Un trámite está finalizado cuando alcanzó un estado terminal exitoso
        // para su modalidad (en Examen de Grado `aprobado` aún espera el reporte).
        $esFinalizado = fn (Tramite $t): bool => in_array(
            $t->estado_actual,
            TramiteStateService::terminalesExitososDe($t->modalidad?->nombre ?? ''),
            true,
        );

        $finalizados = $tramites->filter($esFinalizado)->count();
        $fallos = $tramites->filter(fn ($t) => in_array($t->estado_actual, $fallosSet, true))->count();
        $enProceso = max($total - $finalizados - $fallos, 0);

        $porModalidad = $tramites->groupBy('id_modalidad')->map(function ($grupo) use ($fallosSet, $esFinalizado) {
            $modalidad = $grupo->first()->modalidad;
            $gTotal = $grupo->count();
            $gFinal = $grupo->filter($esFinalizado)->count();
            $gFallos = $grupo->filter(fn ($t) => in_array($t->estado_actual, $fallosSet, true))->count();
            $gProc = max($gTotal - $gFinal - $gFallos, 0);

            return [
                'id_modalidad' => $modalidad?->id_modalidad,
                'nombre' => $modalidad?->nombre ?? 'Sin modalidad',
                'total' => $gTotal,
                'en_proceso' => $gProc,
                'finalizados' => $gFinal,
                'fallos' => $gFallos,
                'porcentaje_finalizados' => $gTotal > 0 ? round(($gFinal / $gTotal) * 100, 1) : 0,
            ];
        })->values()->all();

        $porEstado = $tramites->groupBy('estado_actual')->map(function ($grupo, $estado) use ($total) {
            $cant = $grupo->count();

            return [
                'nombre_estado' => $estado,
                'cantidad' => $cant,
                'porcentaje' => $total > 0 ? round(($cant / $total) * 100, 1) : 0,
            ];
        })->sortByDesc('cantidad')->values()->all();

        $porMes = [];
        if ($total > 0) {
            $base = $tramites->groupBy(fn ($t) => Carbon::parse($t->created_at)->format('Y-m'));
            $max = $base->max(fn ($g) => $g->count()) ?: 1;
            $porMes = $base->map(function ($grupo, $clave) use ($max) {
                $fecha = Carbon::parse($grupo->first()->created_at);
                $cant = $grupo->count();

                return [
                    'clave' => $clave,
                    'label' => $fecha->translatedFormat('M Y'),
                    'cantidad' => $cant,
                    'porcentaje' => round(($cant / $max) * 100, 1),
                ];
            })->sortBy('clave')->values()->all();
        }

        $listado = $tramites->take(200)->map(function ($t) {
            $e = $t->estudiante;
            $tu = $t->tutor;
            $nombreEst = trim(($e->nombres ?? '').' '.($e->apellidos ?? ''));

            return [
                'id_tramite' => $t->id_tramite,
                'estudiante' => $nombreEst !== '' ? $nombreEst : '—',
                'ci' => $e->ci ?? '—',
                'modalidad' => $t->modalidad?->nombre ?? '—',
                'estado_actual' => $t->estado_actual,
                'tutor' => $tu ? trim(($tu->nombre ?? '').' '.($tu->apellidos ?? '')) : null,
                'created_at' => $t->created_at,
            ];
        })->all();

        return [
            'totales' => [
                'total' => $total,
                'en_proceso' => $enProceso,
                'finalizados' => $finalizados,
                'fallos' => $fallos,
            ],
            'por_modalidad' => $porModalidad,
            'por_estado' => $porEstado,
            'por_mes' => $porMes,
            'listado' => $listado,
        ];
    }

    /**
     * Evita asignar una modalidad repetida: no se permite un trámite activo o
     * ya aprobado en la misma modalidad.
     */
    private function asegurarModalidadDisponible(Estudiante $estudiante, int $idModalidad): void
    {
        $existeActivo = Tramite::query()
            ->where('id_estudiante', $estudiante->id_estudiante)
            ->where('id_modalidad', $idModalidad)
            ->whereNotIn('estado_actual', ['aprobado'])
            ->exists();

        if ($existeActivo) {
            throw ValidationException::withMessages([
                'id_modalidad' => 'El postulante ya tiene un trámite en curso para esta modalidad.',
            ]);
        }

        $aprobada = Tramite::query()
            ->where('id_estudiante', $estudiante->id_estudiante)
            ->where('id_modalidad', $idModalidad)
            ->where('estado_actual', 'aprobado')
            ->exists();

        if ($aprobada) {
            throw ValidationException::withMessages([
                'id_modalidad' => 'El postulante ya aprobó su titulación en esta modalidad.',
            ]);
        }
    }
}
