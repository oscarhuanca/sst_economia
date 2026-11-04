<?php

namespace App\Modules\Tramites\Services;

use App\Models\Docente;
use App\Models\EstadoTramite;
use App\Models\Modalidad;
use App\Models\Tramite;
use App\Models\User;
use App\Modules\Kardex\Services\VerificacionTituloService;
use App\Modules\Tramites\Events\EstadoTramiteCambiado;
use App\Modules\Tramites\Events\TramiteCreado;
use App\Modules\Tramites\Events\TramiteRevisado;
use App\Modules\Tramites\Events\TutorAsignado;
use App\Modules\Tramites\Exceptions\TransicionNoPermitidaException;
use App\Support\BasePaginadoService;
use App\Support\DocumentoAdjuntoService;
use App\Support\Roles;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Casos de uso de trámites de titulación.
 *
 * Orquesta el ciclo de vida completo de un trámite: creación por el estudiante,
 * revisión de la documentación inicial, transiciones de estado, asignación de
 * tutor, paneles de consulta y estadísticas. Las notificaciones NO se envían
 * aquí directamente: se disparan eventos de dominio que el módulo de
 * Notificaciones escucha (desacoplamiento por eventos).
 */
class TramiteService extends BasePaginadoService
{
    /** Columnas estrictas del DETALLE de un trámite (prohibido `select *`). */
    private const DETALLE_COLUMNAS = [
        'id_tramite', 'id_estudiante', 'id_modalidad', 'id_tutor',
        'estado_actual', 'observaciones', 'hitos', 'created_at', 'updated_at',
    ];

    /** Columnas estrictas de los LISTADOS (solo lo que pinta el tablero). */
    private const LISTA_COLUMNAS = [
        'id_tramite', 'id_estudiante', 'id_modalidad', 'id_tutor',
        'estado_actual', 'hitos', 'created_at', 'updated_at',
    ];

    /** Eager-loads del detalle con proyección de columnas (ni contraseñas ni sobrantes). */
    private const DETALLE_CARGAS = [
        'modalidad:id_modalidad,nombre,descripcion,requisitos_minimos',
        'estudiante:id_estudiante,ci,nombres,apellidos,registro_universitario,email,telefono,promedio_global,numero_intentos',
        'estudiante.user:id_usuario,estudiante_id,nombres,apellidos,email,rol',
        'documentos:id_documento,id_tramite,tipo_documento,nombre_archivo,ruta_archivo,tamanio_kb,created_at',
        'estados:id_estado,id_tramite,nombre_estado,descripcion,observaciones,id_usuario_responsable,created_at',
        'estados.responsable:id_usuario,nombres,apellidos,rol',
        'tutor:id_docente,nombre,apellidos,email,telefono,materia,ci',
    ];

    /** Eager-loads mínimos de los listados (tablero de revisión). */
    private const LISTA_CARGAS = [
        'modalidad:id_modalidad,nombre',
        'estudiante:id_estudiante,ci,nombres,apellidos,registro_universitario,promedio_global',
        'estudiante.user:id_usuario,estudiante_id,nombres,apellidos',
        'tutor:id_docente,nombre,apellidos',
        'documentos:id_documento,id_tramite,tipo_documento,ruta_archivo',
    ];

    /**
     * Módulos del flujo de titulación y el estado objetivo al que avanza cada
     * uno al aprobarse (espejo del frontend).
     *
     * El primer módulo (el del perfil) cambia de identificador según la
     * modalidad (`perfil_tesis` / `perfil_proyecto`), por eso el mapa se arma
     * en {@see self::modulosFlujoDe()} a partir de este catálogo común y del
     * identificador registrado para cada modalidad.
     *
     * @var array<string, string>
     */
    private const MODULOS_FLUJO_TITULACION = [
        'aprobacion_tema' => 'tema_aprobado',
        'tribunal_revisor' => 'tribunal_asignado',
        'defensa' => 'defensa_aprobada',
        'reporte' => 'titulado',
        'publicacion' => 'publicado',
    ];

    /**
     * Estado al que pasa el trámite cuando KARDEX RECHAZA el perfil: el flujo
     * queda a la espera de que el estudiante presente otro perfil con un título
     * diferente.
     */
    private const PERFIL_RECHAZADO = 'perfil_rechazado';

    /**
     * Estado terminal al que pasa el trámite cuando la nota de la defensa de
     * tesis queda por debajo del mínimo de aprobación.
     */
    private const DEFENSA_REPROBADA = 'reprobado';

    /** Documento opcional que designa al docente como tutor del trabajo. */
    private const DOC_RESOLUCION_TUTOR = 'resolucion_designacion_tutor';

    /** Documento opcional que designa al tribunal revisor del trabajo. */
    private const DOC_RESOLUCION_TRIBUNAL = 'resolucion_designacion_tribunal';

    /**
     * Porcentaje de avance por estado (incluye estados heredados) para saber si
     * un módulo avanza el flujo o ya fue superado. Espejo del frontend.
     *
     * @var array<string, int>
     */
    private const PCT_POR_ESTADO = [
        'solicitud_presentada' => 0,
        'pendiente_concejo_universitario' => 5,
        'perfil_rechazado' => 5,
        'perfil_aprobado' => 20,
        'tutor_asignado' => 30,
        'tema_aprobado' => 40,
        'investigacion_en_desarrollo' => 45,
        'documento_final_presentado' => 48,
        'comision_revisora' => 50,
        'insuficiente' => 50,
        'correcciones_90_dias' => 50,
        'suficiente' => 52,
        'solicitud_fecha_defensa' => 55,
        'defensa_programada' => 57,
        'defensa_en_curso' => 62,
        'tribunal_asignado' => 60,
        'defensa_aprobada' => 75,
        'reporte_generado' => 90,
        'publicado' => 90,
        'aprobado' => 100,
        'titulado' => 100,
        'reprobado' => 75,

        'rechazado' => 20,
    ];

    public function __construct(
        private readonly TramiteStateService $stateService,
        private readonly DocumentoAdjuntoService $documentos,
        private readonly VerificacionTituloService $verificacionTitulo,
    ) {}

    /**
     * Trámite activo (más reciente) del estudiante autenticado.
     */
    public function activoDe(User $user): ?array
    {
        $estudiante = $user->estudiante;

        if (! $estudiante) {
            return null;
        }

        $tramite = Tramite::query()
            ->select(self::DETALLE_COLUMNAS)
            ->with(self::DETALLE_CARGAS)
            ->where('id_estudiante', $estudiante->id_estudiante)
            ->orderByDesc('created_at')
            ->limit(1)
            ->first();

        if (! $tramite) {
            return null;
        }

        // El seguimiento del estudiante muestra las actualizaciones de Kardex y
        // los intentos del Examen de Grado. Solo ese flujo tiene historial
        // especializado, así que se carga únicamente cuando la modalidad aplica.
        if ($tramite->modalidad->nombre === TramiteStateService::MODALIDAD_EXAMEN) {
            $tramite->load(
                'historialExamen:id_historial_examen,id_tramite,nombre_estado,etapa,resultado,numero_resolucion,fecha_resolucion,tema,fecha_examen,nota,numero_intentos,observaciones,created_at'
            );
        }

        return $this->formatear($tramite);
    }

    /**
     * Crea un trámite: registra el estado inicial, sube los documentos y
     * notifica a los roles de gestión vía evento de dominio.
     */
    public function crear(array $validated, User $user): Tramite
    {
        $estudiante = $user->estudiante;

        if (! $estudiante) {
            throw new \DomainException('Debe completar su perfil de estudiante primero');
        }

        // Un estudiante que ya aprobó una modalidad no puede iniciar más trámites.
        // Se cuentan los dos estados de cierre del flujo de Examen de Grado:
        // `aprobado` (ambos exámenes rendidos, pendiente el reporte) y `titulado`
        // (reporte emitido). Reprobar o ser rechazado sí permite volver a
        // postular, y en Examen consume un intento.
        $aprobada = Tramite::query()
            ->where('id_estudiante', $estudiante->id_estudiante)
            ->whereIn('estado_actual', ['titulado', 'aprobado'])
            ->exists();

        if ($aprobada) {
            throw new \DomainException('Ya aprobó su modalidad de titulación. No puede iniciar más solicitudes de trámite.');
        }

        $tramite = DB::transaction(function () use ($estudiante, $user, $validated) {
            $tramite = Tramite::create([
                'id_estudiante' => $estudiante->id_estudiante,
                'id_modalidad' => $validated['id_modalidad'],
                'estado_actual' => 'solicitud_presentada',
            ]);

            $this->contarIntentoExamen($tramite);

            EstadoTramite::create([
                'id_tramite' => $tramite->id_tramite,
                'nombre_estado' => 'solicitud_presentada',
                'descripcion' => 'Solicitud presentada por el estudiante',
                'id_usuario_responsable' => $user->id_usuario,
                'observaciones' => 'Documentación inicial enviada.',
            ]);

            foreach (($validated['documentos'] ?? []) as $documento) {
                $this->documentos->guardar(
                    $tramite->id_tramite,
                    $user->id_usuario,
                    $documento['archivo'],
                    $documento['tipo'],
                );
            }

            return $tramite->load('modalidad', 'documentos', 'estados');
        });

        event(new TramiteCreado($tramite, $user));

        return $tramite;
    }

    /**
     * Solicitudes en proceso (estados no terminales) para los roles de gestión.
     *
     * Paginado por defecto y con proyección estricta de columnas; soporta
     * búsqueda (q) sobre estudiante, código, modalidad y estado.
     *
     * @return array{data: array, meta: array<string, int|bool>}
     */
    public function pendientes(?int $perPage = 20, ?string $q = null): array
    {
        return $this->paginar(
            $this->listadoBase($q)
                ->where(function (Builder $w) {
                    $w->whereNotIn('estado_actual', TramiteStateService::terminales())
                        // Examen: la ausencia y la aprobación aún esperan
                        // reapertura y reporte, pese a ser terminales en el resto.
                        ->orWhereIn('estado_actual', TramiteStateService::noTerminalesDeExamen());
                })
                ->orderByDesc('created_at'),
            $this->porPagina($perPage)
        );
    }

    /**
     * Trámites que ya finalizaron su flujo (concluidos), paginado por defecto.
     *
     * @return array{data: array, meta: array<string, int|bool>}
     */
    public function concluidos(?int $perPage = 20, ?string $q = null): array
    {
        return $this->paginar(
            $this->listadoBase($q)
                ->whereIn('estado_actual', TramiteStateService::terminales())
                ->whereNotIn('estado_actual', TramiteStateService::noTerminalesDeExamen())
                ->orderByDesc('updated_at'),
            $this->porPagina($perPage)
        );
    }

    /**
     * Consulta base de listados con columnas estrictas, eager-loads mínimos y
     * búsqueda opcional delegada a subconsultas que usan los índices de FKs.
     */
    private function listadoBase(?string $q): Builder
    {
        $query = Tramite::query()
            ->select(self::LISTA_COLUMNAS)
            ->with(self::LISTA_CARGAS);

        if ($q !== null && trim($q) !== '') {
            $like = '%'.mb_strtolower(trim($q)).'%';

            $query->where(function (Builder $sub) use ($like) {
                $sub->orWhereRaw('LOWER(tramites.estado_actual) LIKE ?', [$like])
                    ->orWhereHas('modalidad', fn (Builder $m) => $m->whereRaw('LOWER(modalidades.nombre) LIKE ?', [$like]))
                    ->orWhereHas('estudiante', fn (Builder $e) => $e
                        ->whereRaw('LOWER(estudiantes.nombres) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(estudiantes.apellidos) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(estudiantes.registro_universitario) LIKE ?', [$like]));
            });
        }

        return $query;
    }

    /**
     * Resumen estadístico de aprobados/reprobados por modalidad.
     *
     * Agrega en SQL (GROUP BY sobre índices) en vez de cargar todas las filas
     * a PHP y agruparlas en memoria. La clasificación respeta los estados
     * terminales de cada modalidad: `titulado` cuenta como aprobado y el
     * `aprobado` de Examen de Grado sigue en proceso (aún debe emitir reporte).
     */
    public function estadisticas(): array
    {
        $conteos = DB::table('tramites')
            ->join('modalidades', 'modalidades.id_modalidad', '=', 'tramites.id_modalidad')
            ->selectRaw('modalidades.id_modalidad, tramites.estado_actual, COUNT(*) AS cantidad')
            ->groupBy('modalidades.id_modalidad', 'tramites.estado_actual')
            ->get();

        $fallos = TramiteStateService::fallos();

        $porModalidad = Modalidad::query()
            ->orderBy('nombre')
            ->get(['id_modalidad', 'nombre'])
            ->map(function (Modalidad $modalidad) use ($conteos, $fallos) {
                $filas = $conteos->where('id_modalidad', $modalidad->id_modalidad);
                $exitosos = TramiteStateService::terminalesExitososDe($modalidad->nombre);

                return [
                    'id_modalidad' => $modalidad->id_modalidad,
                    'nombre' => $modalidad->nombre,
                    'aprobados' => (int) $filas->whereIn('estado_actual', $exitosos)->sum('cantidad'),
                    'reprobados' => (int) $filas->whereIn('estado_actual', $fallos)->sum('cantidad'),
                    'total' => (int) $filas->sum('cantidad'),
                ];
            })
            ->all();

        return [
            'totales' => [
                'aprobados' => array_sum(array_column($porModalidad, 'aprobados')),
                'reprobados' => array_sum(array_column($porModalidad, 'reprobados')),
                'total' => array_sum(array_column($porModalidad, 'total')),
            ],
            'porModalidad' => $porModalidad,
        ];
    }

    /**
     * Detalle completo de un trámite. Un estudiante solo puede ver los suyos.
     */
    public function mostrar(int $id, User $actor): array
    {
        $tramite = Tramite::query()
            ->select(self::DETALLE_COLUMNAS)
            ->with(self::DETALLE_CARGAS)
            ->findOrFail($id);

        if ($actor->rol === Roles::ESTUDIANTE) {
            if (! $tramite->perteneceA($actor)) {
                throw new AuthorizationException('No autorizado');
            }
        }

        return $this->formatear($tramite);
    }

    /**
     * Revisa la documentación inicial (`aprobar` o `rechazar`) y notifica el
     * resultado al estudiante vía evento de dominio.
     */
    public function revisar(int $id, string $accion, ?string $observaciones, User $actor): array
    {
        if ($accion === 'aprobar') {
            $siguientes = $this->stateService->getSiguientesEstados(
                Tramite::with('modalidad')->findOrFail($id)
            );

            $nuevoEstado = $siguientes[0] ?? null;

            if (! $nuevoEstado) {
                throw new \RuntimeException('No se pudo determinar el siguiente estado');
            }
        } else {
            $nuevoEstado = 'rechazado';
        }

        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if ($tramite->estado_actual !== 'solicitud_presentada') {
            throw new \RuntimeException('Este trámite ya fue revisado o avanza por otra vía');
        }

        $this->stateService->transicionar(
            $tramite,
            $nuevoEstado,
            $observaciones ?? ($accion === 'aprobar'
                ? 'Documentación inicial aprobada.'
                : 'Solicitud rechazada.'),
            $actor->id_usuario
        );

        $tramite->load(self::DETALLE_CARGAS);

        event(new TramiteRevisado($tramite, $accion, $observaciones));

        return $this->formatear($tramite);
    }

    /**
     * Avanza un trámite a un nuevo estado del flujo de su modalidad y notifica
     * al estudiante vía evento de dominio.
     */
    public function transicionar(int $id, string $nuevoEstado, ?string $observaciones, User $actor): array
    {
        $tramite = Tramite::with('modalidad')->findOrFail($id);

        // La investigación en desarrollo solo comienza con tutor asignado:
        // Kardex/Secretaría debe asignarlo en el paso "Tutor Asignado" antes de avanzar.
        if ($nuevoEstado === 'investigacion_en_desarrollo' && $tramite->id_tutor === null) {
            throw ValidationException::withMessages([
                'id_tutor' => 'Debe asignar un tutor antes de pasar a Investigación en Desarrollo.',
            ]);
        }

        $this->stateService->transicionar(
            $tramite,
            $nuevoEstado,
            (string) $observaciones,
            $actor->id_usuario
        );

        $tramite->refresh()->load(self::DETALLE_CARGAS);

        event(new EstadoTramiteCambiado($tramite, $nuevoEstado, $observaciones));

        return $this->formatear($tramite);
    }

    /**
     * Asigna (o reasigna) el docente tutor de un trámite desde el registro
     * académico de docentes.
     */
    public function asignarTutor(int $id, int $idTutor): array
    {
        $tutor = Docente::findOrFail($idTutor);

        $tramite = Tramite::with('modalidad')->findOrFail($id);
        $tramite->update(['id_tutor' => $tutor->id_docente]);

        $tramite->load(self::DETALLE_CARGAS);

        event(new TutorAsignado($tramite, $tutor));

        return $this->formatear($tramite);
    }

    /**
     * Guarda un módulo del flujo de titulación de la Tesis de Grado.
     *
     * Valida los datos del formulario del módulo, los registra en
     * `hitos.modulos`, asigna el tutor cuando corresponde (módulo de tema) y
     * avanza el trámite al estado objetivo del módulo a través de la máquina de
     * estados (incluye puentes con el flujo heredado).
     *
     * El módulo del PERFIL es el único que admite una decisión: con
     * `accion: rechazar` el trámite pasa a `perfil_rechazado` y queda a la
     * espera de que el estudiante presente otro perfil con un título diferente;
     * con `accion: aprobar` (por defecto) avanza a `perfil_aprobado` y solo si
     * el título propuesto no está repetido en otro trabajo de grado.
     *
     * El módulo de tema puede incluir opcionalmente el PDF de la Resolución
     * que designa al docente como tutor; el módulo del tribunal puede incluir
     * el PDF de la Resolución que designa al tribunal y los PDFs de las Notas
     * de Suficiencia del tutor y de cada miembro. Todos se guardan como
     * documentos adjuntos y sus metadatos quedan registrados en el módulo.
     *
     * @return array Trámite formateado (con siguientes_estados y secuencia).
     *
     * @throws ValidationException
     */
    public function guardarModuloFlujo(
        int $id,
        string $modulo,
        array $datos,
        User $actor,
        array $adjuntos = [],
    ): array {
        $tramite = Tramite::with('modalidad')->findOrFail($id);

        $modalidad = $tramite->modalidad->nombre;

        if (! FlujoTitulacionModalidad::esTitulacion($modalidad)) {
            throw ValidationException::withMessages([
                'modulo' => ['El flujo de módulos solo aplica a las modalidades del flujo de titulación.'],
            ]);
        }

        $modulosFlujo = $this->modulosFlujoDe($modalidad);

        if (! array_key_exists($modulo, $modulosFlujo)) {
            throw ValidationException::withMessages([
                'datos' => ['El módulo del flujo no es reconocido.'],
            ]);
        }

        if (in_array($tramite->estado_actual, TramiteStateService::terminales(), true)) {
            throw ValidationException::withMessages([
                'datos' => ['El trámite finalizó; no es posible registrar más módulos.'],
            ]);
        }

        // La defensa se registra en TRES pasos: el paso 1 confirma la entrega
        // de los 8 empastados y el CD; el paso 2 guarda la Resolución de
        // Aprobación Final y la fecha; el paso 3 cierra el módulo con la nota
        // final. Sin `paso` se interpreta como guardado completo.
        $paso = (int) ($datos['paso'] ?? 3);

        // Módulos con decisión: el perfil se aprueba o se rechaza.
        $rechaza = $this->rechazaModulo($modulo, $datos, $modalidad);

        $this->validarDatosModulo($modulo, $datos, $paso, $rechaza, $tramite, $modalidad);

        // La defensa se aprueba con la nota mínima reglamentaria; con menos la
        // nota deja el trámite REPROBADO (estado terminal) y el estudiante
        // deberá volver a solicitar la modalidad de graduación.
        $reprobada = $this->defensaReprobada($modulo, $datos, $paso, $modalidad);

        $objetivo = $rechaza
            ? self::PERFIL_RECHAZADO
            : ($reprobada ? self::DEFENSA_REPROBADA : $modulosFlujo[$modulo]);
        $pctActual = self::PCT_POR_ESTADO[$tramite->estado_actual] ?? 0;
        $pctObjetivo = self::PCT_POR_ESTADO[$objetivo] ?? 0;

        // Los pasos 1 y 2 de la defensa solo registran los datos: el trámite
        // avanza al estado objetivo cuando la nota final se guarda (paso 3).
        $avanza = ! ($modulo === 'defensa' && $paso < 3);
        $transiciono = false;

        // El rechazo y la reprobación de la defensa son decisiones de cierre o
        // reversión (mismo nivel de avance que el estado actual), por lo que se
        // ejecutan siempre; el resto de módulos solo transicionan si aún no
        // fueron superados.
        $debeTransicionar = $avanza && ($pctObjetivo > $pctActual || $rechaza || $reprobada);

        // Observación con la que se registra el cambio en el historial: el
        // rechazo usa la que escribió KARDEX y la reprobación de la defensa
        // documenta la nota obtenida frente al mínimo reglamentario.
        $observacion = $rechaza
            ? $this->observacionDeRechazo($datos, $modalidad)
            : ($reprobada
                ? "Defensa reprobada con nota {$datos['nota_final']} "
                    .'(mínimo de aprobación: '.$this->notaMinimaDefensa($modalidad).'). '
                    .'El flujo de titulación finaliza; el estudiante deberá '
                    .'volver a solicitar la modalidad de graduación.'
                : "Módulo '{$modulo}' del flujo de titulación completado.");

        if ($debeTransicionar) {
            try {
                $this->stateService->transicionar($tramite, $objetivo, $observacion, $actor->id_usuario);
                $transiciono = true;
            } catch (TransicionNoPermitidaException $e) {
                throw ValidationException::withMessages([
                    'datos' => ['No es posible registrar este módulo en el estado actual del trámite.'],
                ]);
            }
        }

        // `paso` y `accion` son controles del formulario, no datos del módulo.
        unset($datos['paso'], $datos['accion']);

        // Los PDFs opcionales del módulo se guardan como documentos del trámite
        // y sus metadatos quedan en `hitos.modulos` para mostrarlos en el flujo.
        $datos = $this->guardarAdjuntosDelModulo($tramite, $actor, $modulo, $datos, $rechaza ? [] : $adjuntos);

        $hitos = $tramite->hitos ?? [];
        $soloHitos = ['hitos' => $hitos];

        // Al rechazar el perfil se conserva lo que el estudiante presentó (su
        // título y los documentos ya verificados): solo se registra la decisión.
        if (! $rechaza) {
            $modulos = $hitos['modulos'] ?? [];
            $modulos[$modulo] = array_replace($modulos[$modulo] ?? [], $datos);
            $hitos['modulos'] = $modulos;
            $soloHitos['hitos'] = $hitos;
        }

        if ($avanza) {
            $soloHitos['estado_actual'] = $objetivo;
        }

        // El módulo de tema asigna el tutor designado en el formulario.
        if ($modulo === 'aprobacion_tema' && ! empty($datos['tutor_id'])) {
            $tutor = Docente::find($datos['tutor_id']);
            if ($tutor === null) {
                throw ValidationException::withMessages([
                    'datos' => ['El tutor seleccionado no es un docente válido.'],
                ]);
            }
            $soloHitos['id_tutor'] = $tutor->id_docente;
        }

        $tramite->update($soloHitos);
        $tramite->refresh()->load(self::DETALLE_CARGAS);

        // Solo se notifica un cambio de estado si realmente hubo transición;
        // el paso 1 de la defensa guarda datos sin avanzar el trámite.
        if ($transiciono) {
            event(new EstadoTramiteCambiado($tramite, $objetivo, ($rechaza || $reprobada) ? $observacion : null));
        }

        return $this->formatear($tramite);
    }

    /**
     * Guarda los PDFs opcionales del módulo y agrega sus metadatos a `datos`.
     *
     * @param  array<string, UploadedFile>  $adjuntos  Archivos del request por campo.
     * @return array<string, mixed>
     */
    private function guardarAdjuntosDelModulo(
        Tramite $tramite,
        User $actor,
        string $modulo,
        array $datos,
        array $adjuntos,
    ): array {
        if ($modulo === 'aprobacion_tema' && isset($adjuntos['resolucion_tutor'])) {
            $datos['resolucion_tutor'] = $this->guardarMetadatosDocumento(
                $tramite,
                $actor,
                $adjuntos['resolucion_tutor'],
                self::DOC_RESOLUCION_TUTOR,
            );
        }

        if ($modulo !== 'tribunal_revisor') {
            return $datos;
        }

        if (isset($adjuntos['resolucion_tribunal'])) {
            $datos['resolucion_tribunal'] = $this->guardarMetadatosDocumento(
                $tramite,
                $actor,
                $adjuntos['resolucion_tribunal'],
                self::DOC_RESOLUCION_TRIBUNAL,
            );
        }

        if (isset($adjuntos['nota_suficiencia_tutor'])) {
            $datos['nota_suficiencia_tutor'] = $this->guardarMetadatosDocumento(
                $tramite,
                $actor,
                $adjuntos['nota_suficiencia_tutor'],
                'nota_suficiencia_tutor',
            );
        }

        foreach (['presidente', 'vocal', 'secretario'] as $rol) {
            $campo = "nota_suficiencia_{$rol}";

            if (! isset($adjuntos[$campo])) {
                continue;
            }

            $metadatos = $this->guardarMetadatosDocumento($tramite, $actor, $adjuntos[$campo], $campo);

            foreach ($datos['tribunal'] ?? [] as $indice => $miembro) {
                if (($miembro['rol'] ?? null) === $rol) {
                    $datos['tribunal'][$indice]['nota_suficiencia'] = $metadatos;
                    break;
                }
            }
        }

        return $datos;
    }

    /**
     * Guarda un PDF del módulo y devuelve los metadatos que se registran en los hitos.
     *
     * @return array{nombre: string, tamanio: mixed, ruta: string}
     */
    private function guardarMetadatosDocumento(
        Tramite $tramite,
        User $actor,
        UploadedFile $archivo,
        string $tipoDocumento,
    ): array {
        $documento = $this->documentos->reemplazar(
            $tramite->id_tramite,
            $actor->id_usuario,
            $archivo,
            $tipoDocumento,
        );

        return [
            'nombre' => $documento->nombre_archivo,
            'tamanio' => $documento->tamanio_kb,
            'ruta' => $documento->ruta_archivo,
        ];
    }

    /**
     * Módulos del flujo de titulación de una modalidad y el estado objetivo al
     * que avanza cada uno.
     *
     * El identificador del módulo del perfil depende de la modalidad
     * (`perfil_tesis` / `perfil_proyecto`); el resto es común.
     *
     * @return array<string, string>
     */
    private function modulosFlujoDe(string $modalidad): array
    {
        $perfil = FlujoTitulacionModalidad::perfilDe($modalidad);

        return array_merge([$perfil => 'perfil_aprobado'], self::MODULOS_FLUJO_TITULACION);
    }

    /**
     * Indica si KARDEX está rechazando (en lugar de aprobar) el módulo enviado.
     *
     * En el flujo de titulación el único módulo con decisión es el del perfil.
     *
     * @param  string  $modulo  Identificador del módulo.
     * @param  array<string, mixed>  $datos  Datos del formulario del módulo.
     * @param  string  $modalidad  Nombre de la modalidad del trámite.
     */
    private function rechazaModulo(string $modulo, array $datos, string $modalidad): bool
    {
        if ($modulo !== FlujoTitulacionModalidad::perfilDe($modalidad)) {
            return false;
        }

        return ($datos['accion'] ?? 'aprobar') === 'rechazar';
    }

    /**
     * Nota mínima para aprobar la defensa de la modalidad.
     */
    private function notaMinimaDefensa(string $modalidad): int
    {
        return (int) config(FlujoTitulacionModalidad::configDe($modalidad).'.nota_minima_defensa', 51);
    }

    /**
     * Indica si la nota final de la defensa está por debajo del mínimo de
     * aprobación: el trámite queda REPROBADO (estado terminal) y el flujo de
     * titulación termina en este acto.
     *
     * @param  string  $modulo  Identificador del módulo.
     * @param  array<string, mixed>  $datos  Datos del formulario del módulo.
     * @param  int  $paso  Paso del módulo de defensa (3 = nota final).
     * @param  string  $modalidad  Nombre de la modalidad del trámite.
     */
    private function defensaReprobada(string $modulo, array $datos, int $paso, string $modalidad): bool
    {
        if ($modulo !== 'defensa' || $paso < 3) {
            return false;
        }

        $nota = $datos['nota_final'] ?? null;
        if ($nota === null || $nota === '' || ! is_numeric($nota)) {
            return false;
        }

        return (float) $nota < $this->notaMinimaDefensa($modalidad);
    }

    /**
     * Observación con la que se registra el rechazo del perfil: la que KARDEX
     * escribió (obligatoria) o un texto por defecto que explica la causa.
     *
     * @param  array<string, mixed>  $datos  Datos del formulario del módulo.
     * @param  string  $modalidad  Nombre de la modalidad del trámite.
     */
    private function observacionDeRechazo(array $datos, string $modalidad): string
    {
        $observacion = trim((string) ($datos['observaciones'] ?? ''));

        return $observacion !== ''
            ? $observacion
            : ucfirst(FlujoTitulacionModalidad::etiquetaPerfilDe($modalidad)).' rechazado por KARDEX.';
    }

    /**
     * Reglas de validación de cada módulo del flujo (espejo del frontend).
     *
     * @param  string  $modulo  Identificador del módulo.
     * @param  array<string, mixed>  $datos  Datos enviados desde el formulario del módulo.
     * @param  int  $paso  Paso del módulo de defensa (1 = empastados y CD, 2 = resolución + fecha, 3 = nota).
     * @param  bool  $rechaza  true cuando KARDEX rechaza el módulo en lugar de aprobarlo.
     * @param  Tramite|null  $tramite  Trámite que se está procesando (para no
     *                                 comparar el título consigo mismo).
     * @param  string  $modalidad  Nombre de la modalidad del trámite.
     *
     * @throws ValidationException
     */
    private function validarDatosModulo(
        string $modulo,
        array $datos,
        int $paso = 2,
        bool $rechaza = false,
        ?Tramite $tramite = null,
        string $modalidad = FlujoTitulacionModalidad::MODALIDAD_TESIS,
    ): void {
        $val = static fn ($v) => is_string($v) && trim($v) !== '';
        $errores = [];

        switch ($modulo) {
            case 'perfil_tesis':
            case 'perfil_proyecto':
                $etiqueta = FlujoTitulacionModalidad::etiquetaTituloDe($modalidad);

                // Rechazar el perfil exige el motivo: es lo que el estudiante
                // verá en su flujo para corregir y volver a presentar.
                if ($rechaza) {
                    if (! $val($datos['observaciones'] ?? null)) {
                        $errores[] = 'Indique la observación del rechazo del perfil.';
                    }
                    break;
                }

                $titulo = trim((string) ($datos['titulo'] ?? ''));
                if ($titulo === '') {
                    $errores[] = 'Ingrese el título de '.$etiqueta.' presentado por el estudiante.';
                } elseif ($this->verificacionTitulo->duplicado($titulo, $tramite?->id_tramite, $tramite?->id_estudiante) !== null) {
                    $errores[] = 'Ya existe un trabajo de grado registrado con el mismo título de '.$etiqueta.'. '
                        .'Rechace el perfil para que el estudiante presente un título diferente.';
                }

                // Los documentos verificados son los que el estudiante adjuntó
                // en su solicitud (ver `config/<modalidad>.php`): la verificación
                // es obligatoria, pero el PDF adjunto es opcional.
                foreach (array_keys(FlujoTitulacionModalidad::tiposDocumentoDe($modalidad)) as $tipo) {
                    $item = $datos['documentos'][$tipo] ?? null;
                    if (empty($item['marcado'])) {
                        $errores[] = "Marque la verificación del documento: {$tipo}.";
                    }
                }
                break;

            case 'aprobacion_tema':
                if (! $val($datos['numero_resolucion'] ?? null)) {
                    $errores[] = 'Ingrese el número de Resolución del HCC.';
                }
                if (! $val($datos['fecha_resolucion'] ?? null)) {
                    $errores[] = 'Seleccione la fecha de la Resolución.';
                }
                if (! $val($datos['tema_investigacion'] ?? null) || mb_strlen(trim((string) $datos['tema_investigacion'])) < 5) {
                    $errores[] = 'Ingrese el tema de investigación (mínimo 5 caracteres).';
                }
                if (empty($datos['tutor_id'])) {
                    $errores[] = 'Seleccione el tutor asignado.';
                }
                break;

            case 'tribunal_revisor':
                if (empty($datos['nota_suficiencia_tutor_entregada'])) {
                    $errores[] = 'Confirme que el tutor entregó la Nota de Suficiencia.';
                }
                if (empty($datos['resolucion_tribunal_entregada'])) {
                    $errores[] = 'Confirme que se cuenta con la Resolución de designación del tribunal revisor.';
                }
                if (! $val($datos['numero_resolucion'] ?? null)) {
                    $errores[] = 'Ingrese el número de Resolución del HCC.';
                }
                if (! $val($datos['fecha_resolucion'] ?? null)) {
                    $errores[] = 'Seleccione la fecha de la Resolución.';
                }
                foreach (['presidente', 'vocal', 'secretario'] as $rol) {
                    $miembro = collect($datos['tribunal'] ?? [])->first(fn ($t) => ($t['rol'] ?? null) === $rol);
                    if (! $val($miembro['nombre'] ?? null)) {
                        $errores[] = 'Complete el nombre del '.ucfirst($rol).'.';
                    }
                    if (empty($miembro['nota_suficiencia_entregada'])) {
                        $errores[] = 'Confirme que el '.ucfirst($rol).' entregó su Nota de Suficiencia.';
                    }
                }
                break;

            case 'defensa':
                if (empty($datos['empastados_cd_entregados'])) {
                    $errores[] = 'Confirme que el estudiante presentó los 8 empastados y el CD de su tesis de grado.';
                }
                if ($paso >= 2 && ! $val($datos['numero_resolucion'] ?? null)) {
                    $errores[] = 'Ingrese el número de Resolución de Aprobación Final.';
                }
                if ($paso >= 2 && ! $val($datos['fecha_defensa'] ?? null)) {
                    $errores[] = 'Seleccione la fecha de la defensa.';
                }
                if ($paso >= 3) {
                    $nota = $datos['nota_final'] ?? null;
                    if ($nota === null || $nota === '') {
                        $errores[] = 'Ingrese la nota final de la defensa.';
                    } elseif (! is_numeric($nota) || (float) $nota < 0 || (float) $nota > 100) {
                        $errores[] = 'La nota debe estar entre 0 y 100.';
                    }
                }
                break;

            case 'reporte':
                break;

            case 'publicacion':
                // El trabajo de investigación (PDF) lo sube el estudiante tras
                // aprobar la defensa; KARDEX solo valida que esté adjunto para
                // cerrar el flujo (`publicado`).
                $tieneDocumento = $tramite !== null
                    && $tramite->documentos()
                        ->where('tipo_documento', 'trabajo_publicacion')
                        ->exists();

                if (! $tieneDocumento) {
                    $errores[] = 'El estudiante aún no adjuntó su trabajo de investigación.';
                }
                break;

            default:
                $errores[] = 'Módulo no reconocido.';
        }

        if ($errores !== []) {
            throw ValidationException::withMessages(['datos' => $errores]);
        }
    }

    /**
     * Trámites donde el usuario autenticado figura como tutor (panel docente),
     * paginado y con proyección estricta.
     *
     * @return array{data: array, meta: array<string, int|bool>}
     */
    public function tutoriasDe(User $user, ?int $perPage = 20): array
    {
        $docente = Docente::query()->where('ci', $user->ci)->first();

        $query = Tramite::query()
            ->select(self::DETALLE_COLUMNAS)
            ->with(self::DETALLE_CARGAS);

        if ($docente === null) {
            $query->whereRaw('1 = 0');
        } else {
            $query->where('id_tutor', $docente->id_docente);
        }

        return $this->paginar(
            $query->orderByDesc('created_at'),
            $this->porPagina($perPage),
            fn (Tramite $t) => $this->formatear($t)
        );
    }

    /**
     * Suma un intento al contador del estudiante cuando la modalidad es Examen de
     * Grado.
     *
     * El módulo 1 del flujo (`verificacion_intentos`) compara este contador contra
     * `examen.maximo_intentos` para admitir o rechazar la solicitud, y
     * `ExamenService::reabrirSolicitud()` lo vuelve a subir cuando el estudiante
     * re-solicita el examen. Solo el Examen de Grado tiene intentos limitados.
     */
    private function contarIntentoExamen(Tramite $tramite): void
    {
        $estudiante = $tramite->estudiante()->first();

        if ($estudiante === null || $tramite->modalidad->nombre !== TramiteStateService::MODALIDAD_EXAMEN) {
            return;
        }

        $estudiante->increment('numero_intentos');
    }

    /**
     * Formatea un trámite enriqueciéndolo con los siguientes estados permitidos
     * y la secuencia completa de la modalidad (para la línea de tiempo).
     */
    public function formatear(Tramite $tramite): array
    {
        $datos = $tramite->toArray();
        $datos['siguientes_estados'] = $this->stateService->getSiguientesEstados($tramite);
        $datos['secuencia'] = $this->stateService->getSecuenciaEstados($tramite->modalidad->nombre);

        return $datos;
    }
}
