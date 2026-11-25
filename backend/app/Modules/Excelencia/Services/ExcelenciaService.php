<?php

namespace App\Modules\Excelencia\Services;

use App\Models\Docente;
use App\Models\EstadoTramite;
use App\Models\Modalidad;
use App\Models\Tramite;
use App\Models\User;
use App\Modules\Tramites\Events\EstadoTramiteCambiado;
use App\Modules\Tramites\Exceptions\TransicionNoPermitidaException;
use App\Modules\Tramites\Services\TramiteService;
use App\Modules\Tramites\Services\TramiteStateService;
use App\Support\DocumentoAdjuntoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Flujo de titulación de la modalidad Excelencia Académica.
 *
 * El estudiante presenta su solicitud adjuntando la nota, el certificado de
 * calificaciones y el récord académico en PDF. KARDEX recepciona los documentos
 * y, desde el panel de Excelencia, registra la designación del tribunal, su
 * informe de evaluación, la lectura del acta y, por último, emite el acta.
 *
 * Son CUATRO módulos gestionados uno a uno desde el panel de KARDEX:
 *
 *  1. `designacion_tribunal` — registra el número y la fecha de la Resolución
 *     del HCC que designa al Tribunal de Evaluación y su PDF opcional; avanza a
 *     `tribunal_designado`.
 *  2. `informe_tribunal` — registra el Informe de Evaluación con el Tribunal 1
 *     y el Tribunal 2 elegidos del catálogo de docentes, y su PDF opcional;
 *     avanza a `informe_tribunal_registrado`.
 *  3. `resolucion_hcc` — registra la Resolución del HCC de lectura del acta, la
 *     fecha de lectura ante los tribunales y el PDF opcional del informe;
 *     avanza a `resolucion_hcc_registrada`.
 *  4. `reporte` — genera el acta de Excelencia Académica y titula al
 *     estudiante (`titulado`).
 *
 * El detalle de cada módulo (resoluciones con sus fechas, tribunales, fecha de
 * lectura del acta e informes adjuntos) se conserva en `hitos.modulos`, que es
 * lo que consultan el seguimiento del estudiante y el acta de titulación.
 */
class ExcelenciaService
{
    /**
     * Nombre de la modalidad de titulación por Excelencia Académica.
     *
     * Es la clave con la que se registra su flujo en la máquina de estados y
     * la que usan los servicios para distinguirla del resto de modalidades.
     */
    public const MODALIDAD = 'Excelencia Académica';

    /**
     * Módulo de verificación del promedio general del estudiante (heredado).
     *
     * Ya no forma parte del flujo: se conserva para leer solicitudes antiguas.
     */
    public const MODULO_VERIFICACION = 'verificacion_promedio';

    /**
     * Módulo de designación del Tribunal de Evaluación.
     */
    public const MODULO_DESIGNACION = 'designacion_tribunal';

    /**
     * Módulo del Informe de Evaluación del Tribunal.
     */
    public const MODULO_INFORME = 'informe_tribunal';

    /**
     * Módulo de registro de la Resolución del HCC.
     */
    public const MODULO_RESOLUCION = 'resolucion_hcc';

    /**
     * Módulo del reporte final (acta de titulación).
     */
    public const MODULO_REPORTE = 'reporte';

    /** Resultados permitidos para el Informe de Evaluación del Tribunal. */
    public const RESULTADOS_EVALUACION = [
        'correcto' => 'Aprueba la Titulación por Excelencia del postulante',
        'faltante' => 'Observado por falta de documentacion',
        'incorrecto' => 'El promedio calculado no es mayor o igual a 80 puntos',
    ];

    /** PDF opcional con la resolución HCC de designación del tribunal. */
    private const DOC_RESOLUCION_DESIGNACION = 'resolucion_designacion_tribunal_excelencia';

    /** PDF opcional con el informe de evaluación del tribunal. */
    private const DOC_INFORME_EVALUACION = 'informe_evaluacion_tribunal_excelencia';

    /** Documento opcional con el informe de verificación de notas (heredado). */
    private const DOC_INFORME_VERIFICACION = 'informe_verificacion_excelencia';

    /** Documento opcional con el informe de lectura del acta. */
    private const DOC_INFORME_LECTURA_ACTA = 'informe_lectura_acta_excelencia';

    /**
     * Estado objetivo de cada módulo cuando se registra satisfactoriamente.
     *
     * @var array<string, string>
     */
    private const MODULOS = [
        self::MODULO_DESIGNACION => 'tribunal_designado',
        self::MODULO_INFORME => 'informe_tribunal_registrado',
        self::MODULO_RESOLUCION => 'resolucion_hcc_registrada',
        self::MODULO_REPORTE => 'titulado',
    ];

    /**
     * Estados terminales que permiten volver a solicitar la modalidad.
     *
     * @var array<int, string>
     */
    private const TERMINALES = ['rechazado', 'titulado'];

    public function __construct(
        private readonly TramiteService $tramites,
        private readonly TramiteStateService $stateService,
        private readonly DocumentoAdjuntoService $documentos,
    ) {}

    /**
     * Crea la solicitud de Excelencia Académica del estudiante.
     *
     * Los PDF adjuntos quedan registrados como documentos del trámite para que
     * KARDEX los recepcione antes de registrar la designación del tribunal.
     *
     * @param  User  $user  Estudiante que presenta la solicitud.
     * @param  array<int, array<string, mixed>>  $documentos  PDF validados.
     * @param  string|null  $observaciones  Observación opcional del estudiante.
     *
     * @throws \DomainException Si la modalidad no está configurada, el
     *                          estudiante no tiene perfil o ya tiene una
     *                          solicitud de Excelencia Académica en curso.
     */
    public function crearSolicitud(User $user, array $documentos = [], ?string $observaciones = null): Tramite
    {
        $modalidad = Modalidad::query()
            ->where('nombre', self::MODALIDAD)
            ->where('activo', true)
            ->first();

        if ($modalidad === null) {
            throw new \DomainException('La modalidad '.self::MODALIDAD.' no está configurada en el sistema.');
        }

        $estudiante = $user->estudiante;

        if ($estudiante === null) {
            throw new \DomainException('Debe completar su perfil de estudiante primero.');
        }

        $enCurso = Tramite::query()
            ->where('id_estudiante', $estudiante->id_estudiante)
            ->where('id_modalidad', $modalidad->id_modalidad)
            ->whereNotIn('estado_actual', self::TERMINALES)
            ->exists();

        if ($enCurso) {
            throw new \DomainException('Ya tienes una solicitud de Excelencia Académica en curso. Avanza desde el seguimiento de tu trámite.');
        }

        $tramite = $this->tramites->crear(
            [
                'id_modalidad' => (int) $modalidad->id_modalidad,
                'documentos' => $documentos,
            ],
            $user
        );

        $this->registrarSolicitud($tramite, $observaciones);

        return $tramite;
    }

    /**
     * Actualiza el promedio declarado por el estudiante antes de la
     * verificación de KARDEX.
     *
     * Sirve tanto para declarar la nota cuando KARDEX asignó la modalidad
     * (trámite sin promedio declarado) como para corregirla mientras siga
     * pendiente de verificación. Una vez verificado, el valor queda cerrado.
     *
     * @param  array<string, mixed>  $validated  Datos validados de la solicitud.
     * @return array Trámite formateado.
     *
     * @throws ValidationException
     */
    public function actualizarPromedioDeclarado(int $id, array $validated, User $user): array
    {
        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if ($tramite->modalidad->nombre !== self::MODALIDAD) {
            throw ValidationException::withMessages([
                'modulo' => ['El flujo de Excelencia Académica solo aplica a la modalidad Excelencia Académica.'],
            ]);
        }

        $estudiante = $user->estudiante;

        if ($estudiante === null || (int) $tramite->id_estudiante !== (int) $estudiante->id_estudiante) {
            throw ValidationException::withMessages([
                'datos' => ['Solo el estudiante titular del trámite puede declarar su promedio.'],
            ]);
        }

        if ($tramite->estado_actual !== 'solicitud_presentada') {
            throw ValidationException::withMessages([
                'datos' => ['El promedio ya fue verificado por KARDEX; no es posible modificarlo.'],
            ]);
        }

        $hitos = $tramite->hitos ?? [];
        $modulos = $hitos['modulos'] ?? [];
        $verificacion = $modulos[self::MODULO_VERIFICACION] ?? [];
        $verificacion['promedio_declarado'] = (float) $validated['promedio'];
        $verificacion['nota_minima'] = self::notaMinima();
        $modulos[self::MODULO_VERIFICACION] = $verificacion;
        $hitos['modulos'] = $modulos;

        $tramite->update(['hitos' => $hitos]);

        return $this->formatear($tramite);
    }

    /**
     * Acusa recibido el PDF presentado por el estudiante y registra la
     * verificación documental de KARDEX.
     *
     * El estado del trámite se mantiene en `solicitud_presentada`: el cambio
     * al primer módulo (`tribunal_designado`) lo hace el guardado de la
     * designación. Igual que en Examen de Grado, la revisión se conserva en
     * `hitos.recepcion_documentos` y en el historial de estados.
     *
     * @param  array<string, mixed>  $datos
     *
     * @throws ValidationException
     */
    public function verificarDocumentos(int $id, array $datos, User $actor): array
    {
        $tramite = Tramite::with('modalidad', 'documentos')->findOrFail($id);

        if ($tramite->modalidad->nombre !== self::MODALIDAD) {
            throw ValidationException::withMessages([
                'tramite' => ['La verificación documental solo aplica a la modalidad Excelencia Académica.'],
            ]);
        }

        if ($tramite->estado_actual !== 'solicitud_presentada') {
            throw ValidationException::withMessages([
                'estado_actual' => ['La solicitud ya fue revisada; no es posible acusar la documentación nuevamente.'],
            ]);
        }

        $marcados = $datos['documentos'] ?? [];
        $documentosPorTipo = $tramite->documentos->keyBy('tipo_documento');
        $errores = [];
        $registro = [];

        foreach (config('excelencia.tipos_documento', []) as $tipo => $etiqueta) {
            if (empty($marcados[$tipo])) {
                $errores[] = "Marque {$etiqueta} como verificado.";

                continue;
            }

            $documento = $documentosPorTipo->get($tipo);

            if ($documento === null) {
                $errores[] = "Falta el PDF de {$etiqueta}.";

                continue;
            }

            $registro[$tipo] = [
                'marcado' => true,
                'id_documento' => (int) $documento->id_documento,
            ];
        }

        if ($errores !== []) {
            throw ValidationException::withMessages(['datos' => $errores]);
        }

        DB::transaction(function () use ($tramite, $registro, $datos, $actor) {
            $hitos = $tramite->hitos ?? [];
            $hitos['recepcion_documentos'] = [
                'verificado' => true,
                'verificado_en' => Carbon::now()->toIso8601String(),
                'id_usuario_verifico' => (int) $actor->id_usuario,
                'observaciones' => $this->opcional($datos['observaciones'] ?? null),
                'documentos' => $registro,
            ];

            $tramite->update(['hitos' => $hitos]);

            EstadoTramite::create([
                'id_tramite' => $tramite->id_tramite,
                'nombre_estado' => 'solicitud_presentada',
                'descripcion' => 'Documentación de Excelencia Académica verificada por KARDEX',
                'id_usuario_responsable' => $actor->id_usuario,
                'observaciones' => $this->opcional($datos['observaciones'] ?? null),
            ]);
        });

        $tramite->refresh()->load(['modalidad', 'estudiante', 'documentos']);

        return $this->tramites->formatear($tramite);
    }

    /**
     * Registra un módulo del flujo de Excelencia Académica y avanza el estado
     * del trámite al que corresponde.
     *
     * @param  string  $modulo  Identificador del módulo (ver `self::MODULOS`).
     * @param  array<string, mixed>  $datos  Datos del formulario del módulo.
     * @param  array<string, UploadedFile>  $adjuntos  PDFs opcionales.
     * @return array Trámite formateado (con siguientes estados y secuencia).
     *
     * @throws ValidationException
     */
    public function guardarModulo(int $id, string $modulo, array $datos, User $actor, array $adjuntos = []): array
    {
        // La transición y el registro en `hitos` van juntos: un fallo a mitad
        // del guardado no debe dejar el trámite en un estado sin su registro.
        return DB::transaction(fn () => $this->resolverModulo($id, $modulo, $datos, $actor, $adjuntos));
    }

    /**
     * Cuerpo del guardado del módulo, ejecutado dentro de una transacción.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<string, UploadedFile>  $adjuntos
     *
     * @throws ValidationException
     */
    private function resolverModulo(int $id, string $modulo, array $datos, User $actor, array $adjuntos = []): array
    {
        if (! array_key_exists($modulo, self::MODULOS)) {
            throw ValidationException::withMessages([
                'datos' => ['El módulo del flujo de Excelencia Académica no es reconocido.'],
            ]);
        }

        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if ($tramite->modalidad->nombre !== self::MODALIDAD) {
            throw ValidationException::withMessages([
                'modulo' => ['El flujo de Excelencia Académica solo aplica a la modalidad Excelencia Académica.'],
            ]);
        }

        if ($this->stateService->esTerminal($tramite)) {
            throw ValidationException::withMessages([
                'datos' => ['El trámite finalizó; no es posible registrar más módulos.'],
            ]);
        }

        $destino = match ($modulo) {
            self::MODULO_DESIGNACION => $this->destinoDesignacion($datos),
            self::MODULO_INFORME => $this->destinoInforme($datos),
            self::MODULO_RESOLUCION => $this->destinoResolucion($datos),
            self::MODULO_REPORTE => $this->destinoReporte($tramite),
        };

        $this->transicionar($tramite, $destino['estado'], $destino['observacion'], $actor);

        // `accion` es un control del formulario, no un dato del módulo.
        unset($datos['accion']);

        $this->persistirModulo($tramite, $modulo, $datos, $destino, $actor, $adjuntos);

        event(new EstadoTramiteCambiado($tramite, $destino['estado'], $destino['observacion']));

        return $this->formatear($tramite);
    }

    /**
     * Módulo 1 — designación del Tribunal de Evaluación por Resolución del HCC.
     *
     * @param  array<string, mixed>  $datos
     * @return array{estado:string, observacion:string}
     *
     * @throws ValidationException
     */
    private function destinoDesignacion(array $datos): array
    {
        $errores = [];

        if (! $this->informado($datos['numero_resolucion'] ?? null)) {
            $errores[] = 'Ingrese el número de Resolución del HCC de designación del Tribunal.';
        }
        if (! $this->informado($datos['fecha_resolucion'] ?? null)) {
            $errores[] = 'Seleccione la fecha de la Resolución de designación.';
        }
        if (! $this->confirmado($datos['confirmado'] ?? null)) {
            $errores[] = 'Marque la verificación de la designación del Tribunal.';
        }

        if ($errores !== []) {
            throw ValidationException::withMessages(['datos' => $errores]);
        }

        $resolucion = trim((string) $datos['numero_resolucion']);
        $fecha = trim((string) $datos['fecha_resolucion']);

        return [
            'estado' => 'tribunal_designado',
            'observacion' => "Resolución N.º {$resolucion} del HCC de designación del Tribunal de Evaluación registrada con fecha {$fecha}.",
        ];
    }

    /**
     * Módulo 2 — Informe de Evaluación del Tribunal.
     *
     * Los dos miembros se eligen del catálogo de docentes por `id_docente`, se
     * guarda también su nombre legible y se exige que sean personas distintas.
     *
     * @param  array<string, mixed>  $datos
     * @return array{estado:string, observacion:string, tribunal:array<string, array{id_docente:int, nombre:string}>, resultado_evaluacion:string}
     *
     * @throws ValidationException
     */
    private function destinoInforme(array $datos): array
    {
        $errores = [];
        $tribunal = [];

        foreach (['tribunal_1', 'tribunal_2'] as $clave) {
            $miembro = $datos[$clave] ?? null;
            $etiqueta = $clave === 'tribunal_1' ? 'Tribunal 1' : 'Tribunal 2';

            if (! is_array($miembro)) {
                $errores[] = "Seleccione el {$etiqueta} del catálogo de docentes.";

                continue;
            }

            $id = $miembro['id_docente'] ?? null;
            $nombre = $this->opcional($miembro['nombre'] ?? null);

            if ($id === null || ! is_numeric($id)) {
                $errores[] = "Seleccione un docente válido para {$etiqueta}.";

                continue;
            }

            if ($nombre === null) {
                $errores[] = "Falta el nombre del docente seleccionado para {$etiqueta}.";

                continue;
            }

            $tribunal[$clave] = [
                'id_docente' => (int) $id,
                'nombre' => $nombre,
            ];
        }

        if (isset($tribunal['tribunal_1'], $tribunal['tribunal_2'])
            && $tribunal['tribunal_1']['id_docente'] === $tribunal['tribunal_2']['id_docente']) {
            $errores[] = 'Tribunal 1 y Tribunal 2 deben ser docentes distintos.';
        }

        if (count($tribunal) === 2) {
            $ids = array_column($tribunal, 'id_docente');
            $existentes = Docente::query()
                ->whereIn('id_docente', $ids)
                ->pluck('id_docente')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (count($existentes) < count($ids)) {
                $errores[] = 'Uno de los docentes seleccionados no existe en el catálogo.';
            }
        }

        $resultado = $this->opcional($datos['resultado_evaluacion'] ?? null);

        if ($resultado === null || ! array_key_exists($resultado, self::RESULTADOS_EVALUACION)) {
            $errores[] = 'Seleccione el resultado de la evaluación del tribunal.';
        }

        if (! $this->confirmado($datos['confirmado'] ?? null)) {
            $errores[] = 'Marque la verificación del Informe de Evaluación del Tribunal.';
        }

        if ($errores !== []) {
            throw ValidationException::withMessages(['datos' => $errores]);
        }

        return [
            'estado' => 'informe_tribunal_registrado',
            'observacion' => 'Informe de Evaluación del Tribunal registrado. '
                ."Tribunal 1: {$tribunal['tribunal_1']['nombre']}. "
                ."Tribunal 2: {$tribunal['tribunal_2']['nombre']}. "
                .'Resultado: '.self::RESULTADOS_EVALUACION[$resultado],
            'tribunal' => $tribunal,
            'resultado_evaluacion' => $resultado,
        ];
    }

    /**
     * Módulo 3 — Resolución del HCC y lectura del acta ante los tribunales.
     *
     * Registra la Resolución que fija la lectura del acta, su fecha y la fecha
     * en que se realizó la lectura ante los tribunales. KARDEX confirma
     * expresamente la revisión de estos datos y del informe PDF opcional.
     *
     * @param  array<string, mixed>  $datos
     * @return array{estado:string, observacion:string}
     *
     * @throws ValidationException
     */
    private function destinoResolucion(array $datos): array
    {
        $errores = [];

        if (! $this->informado($datos['numero_resolucion'] ?? null)) {
            $errores[] = 'Ingrese el número de Resolución del HCC.';
        }
        if (! $this->informado($datos['fecha_resolucion'] ?? null)) {
            $errores[] = 'Seleccione la fecha de la Resolución.';
        }
        if (! $this->informado($datos['fecha_lectura_acta'] ?? null)) {
            $errores[] = 'Seleccione la fecha de lectura del acta.';
        }
        if (! $this->confirmado($datos['confirmado'] ?? null)) {
            $errores[] = 'Marque la verificación de la Resolución y de la lectura del acta.';
        }

        if ($errores !== []) {
            throw ValidationException::withMessages(['datos' => $errores]);
        }

        $resolucion = trim((string) $datos['numero_resolucion']);
        $lectura = trim((string) $datos['fecha_lectura_acta']);

        return [
            'estado' => 'resolucion_hcc_registrada',
            'observacion' => "Resolución N.º {$resolucion} del HCC registrada. "
                ."Lectura del acta ante los tres tribunales programada para el {$lectura}.",
        ];
    }

    /**
     * Módulo 4 — reporte final: el acta de Excelencia Académica titula al
     * estudiante.
     *
     * Exige que la designación del tribunal, el informe de evaluación y la
     * lectura del acta hayan sido registrados: sin esos datos no hay
     * constancia que titular.
     *
     * @return array{estado:string, observacion:string}
     *
     * @throws ValidationException
     */
    private function destinoReporte(Tramite $tramite): array
    {
        $modulos = $tramite->hitos['modulos'] ?? [];
        $designacion = $modulos[self::MODULO_DESIGNACION] ?? [];
        $informe = $modulos[self::MODULO_INFORME] ?? [];
        $resolucion = $modulos[self::MODULO_RESOLUCION] ?? [];

        if (! $this->informado($designacion['numero_resolucion'] ?? null)) {
            throw ValidationException::withMessages([
                'datos' => ['Registre primero la designación del Tribunal de Evaluación.'],
            ]);
        }

        if (empty($informe['verificado'])) {
            throw ValidationException::withMessages([
                'datos' => ['Registre el Informe de Evaluación del Tribunal antes de emitir el título.'],
            ]);
        }

        if (! $this->informado($resolucion['fecha_lectura_acta'] ?? null)) {
            throw ValidationException::withMessages([
                'datos' => ['Registre la fecha de lectura del acta antes de emitir el título.'],
            ]);
        }

        $lectura = Carbon::parse($resolucion['fecha_lectura_acta'])->format('d/m/Y');

        return [
            'estado' => 'titulado',
            'observacion' => "Acta de Excelencia Académica generada. Lectura del acta del {$lectura} verificada. Estudiante titulado.",
        ];
    }

    /**
     * Ejecuta la transición de estado traduciendo el error de la máquina de
     * estados a un mensaje de validación que la tarjeta puede mostrar.
     *
     * @throws ValidationException
     */
    private function transicionar(Tramite $tramite, string $estado, string $observacion, User $actor): void
    {
        try {
            $this->stateService->transicionar($tramite, $estado, $observacion, $actor->id_usuario);
        } catch (TransicionNoPermitidaException $e) {
            throw ValidationException::withMessages([
                'datos' => [$e->getMessage()],
            ]);
        }
    }

    /**
     * Guarda los datos del formulario en `hitos.modulos`, igual que el resto de
     * flujos, para que el seguimiento del estudiante y el acta de titulación
     * muestren lo que registró KARDEX.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<string, mixed>  $destino
     * @param  array<string, UploadedFile>  $adjuntos
     */
    private function persistirModulo(
        Tramite $tramite,
        string $modulo,
        array $datos,
        array $destino,
        User $actor,
        array $adjuntos,
    ): void {
        $datos = $this->guardarInformesDelModulo($tramite, $actor, $modulo, $datos, $adjuntos);

        $hitos = $tramite->fresh()?->hitos ?? [];

        $modulos = $hitos['modulos'] ?? [];
        $guardado = array_replace($modulos[$modulo] ?? [], $datos);

        if (in_array($modulo, [
            self::MODULO_DESIGNACION,
            self::MODULO_INFORME,
            self::MODULO_RESOLUCION,
        ], true)) {
            $guardado['verificado'] = true;
            $guardado['fecha_verificacion'] = Carbon::today()->toDateString();
        }

        if ($modulo === self::MODULO_INFORME) {
            if (isset($destino['tribunal'])) {
                $guardado['tribunal'] = $destino['tribunal'];
            }
            if (isset($destino['resultado_evaluacion'])) {
                $guardado['resultado_evaluacion'] = $destino['resultado_evaluacion'];
            }
        }

        $modulos[$modulo] = $guardado;
        $hitos['modulos'] = $modulos;
        $hitos[$destino['estado']] = Carbon::today()->toDateString();

        $tramite->update(['hitos' => $hitos]);
    }

    /**
     * Guarda los informes PDF opcionales del módulo y agrega sus metadatos a
     * los datos que se conservan en `hitos.modulos`.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<string, UploadedFile>  $adjuntos
     * @return array<string, mixed>
     */
    private function guardarInformesDelModulo(
        Tramite $tramite,
        User $actor,
        string $modulo,
        array $datos,
        array $adjuntos,
    ): array {
        $documentos = match ($modulo) {
            self::MODULO_DESIGNACION => ['resolucion_tribunal' => self::DOC_RESOLUCION_DESIGNACION],
            self::MODULO_INFORME => ['informe_evaluacion_tribunal' => self::DOC_INFORME_EVALUACION],
            self::MODULO_RESOLUCION => ['informe_lectura_acta' => self::DOC_INFORME_LECTURA_ACTA],
            self::MODULO_VERIFICACION => ['informe_verificacion' => self::DOC_INFORME_VERIFICACION],
            default => [],
        };

        foreach ($documentos as $campo => $tipoDocumento) {
            if (! isset($adjuntos[$campo])) {
                continue;
            }

            $documento = $this->documentos->reemplazar(
                $tramite->id_tramite,
                $actor->id_usuario,
                $adjuntos[$campo],
                $tipoDocumento,
            );

            $datos[$campo] = [
                'nombre' => $documento->nombre_archivo,
                'tamanio' => $documento->tamanio_kb,
                'ruta' => $documento->ruta_archivo,
            ];
        }

        return $datos;
    }

    /**
     * Registra la solicitud del estudiante: la fecha de presentación y la
     * observación opcional. KARDEX continúa con la designación del tribunal.
     */
    private function registrarSolicitud(Tramite $tramite, ?string $observaciones = null): void
    {
        $hitos = $tramite->hitos ?? [];

        $hitos['solicitud_presentada'] = Carbon::today()->toDateString();

        if ($observaciones !== null && trim($observaciones) !== '') {
            $hitos['observaciones_solicitud'] = $observaciones;
        }

        $tramite->update(['hitos' => $hitos]);
    }

    /**
     * Trámite formateado con el estudiante y la modalidad cargados, listo para
     * la respuesta de la API.
     */
    private function formatear(Tramite $tramite): array
    {
        $tramite->refresh()->load(['modalidad', 'estudiante']);

        return $this->tramites->formatear($tramite);
    }

    /**
     * Promedio mínimo para optar por la modalidad (`config/excelencia.php`).
     */
    public static function notaMinima(): int
    {
        return (int) config('excelencia.nota_minima', 80);
    }

    /** Indica si un valor de formulario trae contenido. */
    private function informado(mixed $valor): bool
    {
        return is_string($valor) ? trim($valor) !== '' : $valor !== null;
    }

    /** Normaliza un campo opcional: cadena recortada o null. */
    private function opcional(mixed $valor): ?string
    {
        $texto = is_string($valor) ? trim($valor) : '';

        return $texto === '' ? null : $texto;
    }

    /** Interpreta el checkbox de confirmación enviado por el frontend. */
    private function confirmado(mixed $valor): bool
    {
        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }
}
