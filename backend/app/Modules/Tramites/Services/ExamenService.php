<?php

namespace App\Modules\Tramites\Services;

use App\Models\Estudiante;
use App\Models\HistorialEstadoExamen;
use App\Models\Tramite;
use App\Models\User;
use App\Modules\Tramites\Events\EstadoTramiteCambiado;
use App\Modules\Tramites\Exceptions\TransicionNoPermitidaException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Flujo de titulación de la modalidad Examen de Grado.
 *
 * A diferencia de la Tesis de Grado, el Examen de Grado no tiene tutor ni
 * tribunal: son OCHO módulos que KARDEX completa uno a uno desde el panel,
 * donde cada tarjeta permanece bloqueada hasta que la anterior queda cerrada.
 *
 *  1. `verificacion_intentos` — comprueba `numero_intentos` contra
 *     `examen.maximo_intentos`. Con `numero_intentos >= 3` la solicitud se
 *     rechaza; con menos, el estudiante sigue el flujo.
 *  2. `habilitacion_examen` — registra el N.º de Resolución del HCC con el que
 *     el estudiante queda habilitado para rendir el examen de grado.
 *  3. `sorteo_teorico` — asistencia con el tema sorteado y la fecha del examen.
 *     No asistir cierra el sorteo con `no_asistio`, pero no reproba todavía.
 *  4. `resolucion_resultado_teorico` — registra la Resolución del HCC y su
 *     fecha con la que se aprueba el resultado del examen teórico. Es el
 *     requisito para habilitar el sorteo del examen de especialidad.
 *  5. `sorteo_especialidad` — misma dinámica para el examen de especialidad.
 *  6. `resolucion_asignacion_notas` — registra la Resolución del HCC y su fecha
 *     con la que se habilita la asignación de notas de ambos exámenes.
 *  7. `asignacion_notas` — registra las notas de LOS DOS exámenes y es quien
 *     decide el resultado: ambas con 51 o más dejan el trámite en `aprobado`;
 *     cualquier ausencia o nota inferior al mínimo deja el trámite en un estado
 *     reabrible: `examen_teorico_reprobado` (solo teórico reprobado, la nota
 *     de especialidad se conserva), `examen_especialidad_reprobado` (solo
 *     especialidad reprobada, la nota del teórico se conserva) o
 *     `examen_ambos_reprobados` (ninguno aprobó).
 *  8. `reporte` — genera el reporte en PDF y cierra el trámite en `titulado`.
 *
 * Reprobar cualquiera de los dos exámenes NO cierra el flujo: el estudiante
 * puede re-solicitar el examen y volver a rendir ÚNICAMENTE el examen que
 * reprobó, conservando la nota del examen aprobado y la Resolución del HCC en
 * curso. Reprobar AMBOS exige una NUEVA Resolución y rendir de nuevo los dos
 * exámenes. Cada reapertura consume un intento.
 * Titularse exige aprobar AMBOS exámenes con 51 puntos o más.
 *
 * Cada transición se escribe en dos lugares: la línea de tiempo genérica
 * (`estados_tramite`, que consume el seguimiento del estudiante) y el historial
 * especializado (`historial_estados_examen`, con el detalle de tema, fecha, nota
 * y resolución que usa el reporte en PDF).
 */
class ExamenService
{
    /**
     * Estado objetivo de cada módulo cuando se registra satisfactoriamente.
     *
     * `asignacion_notas` no tiene estado fijo: cierra el flujo en `aprobado`
     * cuando las dos notas llegan al mínimo, o en el estado reabrible que
     * corresponde (ver `destinoNotas()`).
     *
     * @var array<string, string>
     */
    private const MODULOS = [
        'verificacion_intentos' => 'intentos_verificados',
        'habilitacion_examen' => 'habilitado_examen',
        'sorteo_teorico' => 'examen_teorico_sorteado',
        'resolucion_resultado_teorico' => 'examen_teorico_aprobado',
        'sorteo_especialidad' => 'examen_especialidad_sorteado',
        'resolucion_asignacion_notas' => 'notas_habilitadas',
        'asignacion_notas' => 'aprobado',
        'reporte' => 'titulado',
    ];

    /**
     * Estado al que vuelve la reapertura según el examen que quedó reprobado.
     *
     * - Solo teórico: retoma desde `habilitado_examen` para sortear de nuevo
     *   únicamente el teórico; la Resolución del HCC en curso se conserva.
     * - Solo especialidad: retoma desde `examen_teorico_aprobado` (el teórico ya
     *   aprobado se conserva) para sortear solo la especialidad.
     * - Ambos: regresa a la verificación de intentos, porque la re-solicitud
     *   exige registrar una NUEVA Resolución del HCC antes de los sorteos.
     *
     * El `resultado` con que se registra la reapertura en el historial indica
     * el alcance: `re_solicitud` (completa, nueva resolución),
     * `re_solicitud_teorico` y `re_solicitud_especialidad` (solo un examen).
     *
     * @var array<string, array{estado:string, resultado:string}>
     */
    private const REAPERTURA_POR_ESTADO = [
        'examen_teorico_reprobado' => ['estado' => 'habilitado_examen', 'resultado' => 're_solicitud_teorico'],
        'examen_especialidad_reprobado' => ['estado' => 'examen_teorico_aprobado', 'resultado' => 're_solicitud_especialidad'],
        'examen_ambos_reprobados' => ['estado' => 'intentos_verificados', 'resultado' => 're_solicitud'],
    ];

    /**
     * Estados desde los que el estudiante puede volver a solicitar el examen.
     *
     * @var array<int, string>
     */
    private const REAPERIBLES = [
        'examen_teorico_reprobado',
        'examen_especialidad_reprobado',
        'examen_ambos_reprobados',
    ];

    /** Módulos del intento que se repiten tras una reapertura (sorteos, resoluciones y notas). */
    private const MODULOS_DE_INTENTO = [
        'sorteo_teorico',
        'resolucion_resultado_teorico',
        'sorteo_especialidad',
        'resolucion_asignacion_notas',
        'asignacion_notas',
    ];

    public function __construct(
        private readonly TramiteStateService $stateService,
        private readonly TramiteService $tramites,
    ) {}

    /**
     * Registra un módulo del flujo de Examen de Grado y avanza el estado del
     * trámite al que corresponde.
     *
     * @param  string  $modulo  Identificador del módulo (ver `self::MODULOS`).
     * @param  array<string, mixed>  $datos  Datos del formulario del módulo.
     * @return array Trámite formateado (con `historial_examen` y siguientes estados).
     *
     * @throws ValidationException
     */
    public function guardarModulo(int $id, string $modulo, array $datos, User $actor): array
    {
        return DB::transaction(fn () => $this->resolverModulo($id, $modulo, $datos, $actor));
    }

    /**
     * Guarda el módulo dentro de una transacción: la transición, el registro en
     * `hitos` y la inserción en el historial van juntos, para que un fallo a
     * mitad del guardado no deje el trámite en un estado sin su registro.
     *
     * @param  array<string, mixed>  $datos
     *
     * @throws ValidationException
     */
    private function resolverModulo(int $id, string $modulo, array $datos, User $actor): array
    {
        if (! array_key_exists($modulo, self::MODULOS)) {
            throw ValidationException::withMessages([
                'datos' => ['El módulo del flujo de examen no es reconocido.'],
            ]);
        }

        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if ($tramite->modalidad->nombre !== TramiteStateService::MODALIDAD_EXAMEN) {
            throw ValidationException::withMessages([
                'modulo' => ['El flujo de examen de grado solo aplica a la modalidad Examen de Grado.'],
            ]);
        }

        if ($this->stateService->esTerminal($tramite)) {
            throw ValidationException::withMessages([
                'datos' => ['El trámite finalizó; no es posible registrar más módulos.'],
            ]);
        }

        $estudiante = Estudiante::findOrFail($tramite->id_estudiante);

        $resolucion = $this->destino($modulo, $datos, $estudiante, $tramite);

        $this->transicionar($tramite, $resolucion['estado'], $resolucion['observacion'], $actor);

        // `paso` y `accion` son controles del formulario, no datos del módulo.
        unset($datos['paso'], $datos['accion']);

        // En la asignación de notas el backend es quien conoce la nota de cada
        // examen (la de un examen conservado viene del hito `examen_aprobado`).
        // Se integran a los datos para que `hitos.modulos.asignacion_notas`
        // queden completos y el seguimiento y el reporte muestren ambas notas.
        if ($modulo === 'asignacion_notas') {
            $datos['nota_teorico'] = $resolucion['notas']['examen_teorico'] ?? null;
            $datos['nota_especialidad'] = $resolucion['notas']['examen_especialidad'] ?? null;
        }

        $this->persistirModulo($tramite, $modulo, $datos);
        $this->persistirHito($tramite, $resolucion['estado']);
        $this->persistirExamenesAprobados($tramite, $resolucion);
        $this->registrarHistorial($tramite, $modulo, $resolucion, $estudiante, $actor);

        event(new EstadoTramiteCambiado($tramite, $resolucion['estado'], $resolucion['observacion']));

        return $this->formatear($tramite);
    }

    /**
     * Reabre un trámite reprobado o con ausencia para que el estudiante pueda
     * volver a rendir el examen.
     *
     * El alcance depende del estado al que quedó la asignación de notas:
     * reprobar un SOLO examen reabre únicamente ese examen (la nota del examen
     * aprobado y la Resolución del HCC en curso se conservan); reprobar AMBOS
     * reabre el intento completo y exige registrar una nueva Resolución.
     *
     * Consume un intento: si el estudiante ya agotó los permitidos, no se puede
     * re-solicitar y KARDEX debe registrar una nueva postulación.
     */
    public function reabrirSolicitud(int $id, ?string $observaciones, User $actor): array
    {
        // La reapertura toca tres tablas (trámite, estudiante e historial). Sin
        // transacción, un fallo al escribir el historial dejaría el intento
        // consumido y el estado revertido al habilitado sin rastro de por qué, y
        // el panel de Examen —que se construye desde el historial— mostraría los
        // sorteos del intento anterior como si siguieran vigentes.
        return DB::transaction(fn () => $this->resolverReapertura($id, $observaciones, $actor));
    }

    /**
     * Cuerpo de la reapertura, ejecutado dentro de una transacción.
     *
     * @param  string|null  $observaciones  Motivo de la reapertura.
     * @return array{Trámite formateado con su historial de examen.}
     */
    private function resolverReapertura(int $id, ?string $observaciones, User $actor): array
    {
        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if ($tramite->modalidad->nombre !== TramiteStateService::MODALIDAD_EXAMEN) {
            throw ValidationException::withMessages([
                'tramite' => ['Solo los trámites de Examen de Grado pueden re-solicitar el examen.'],
            ]);
        }

        if (! in_array($tramite->estado_actual, self::REAPERIBLES, true)) {
            throw ValidationException::withMessages([
                'estado_actual' => ['El estudiante solo puede re-solicitar el examen si reprobó alguno de los dos exámenes.'],
            ]);
        }

        $estudiante = Estudiante::findOrFail($tramite->id_estudiante);

        if ($estudiante->intentosAgotados()) {
            throw ValidationException::withMessages([
                'numero_intentos' => [
                    'El estudiante agotó los intentos permitidos para el Examen de Grado. '
                    .'Registre una nueva postulación si corresponde.',
                ],
            ]);
        }

        $config = self::REAPERTURA_POR_ESTADO[$tramite->estado_actual];
        $estadoReabierto = $tramite->estado_actual;
        $intentos = $estudiante->numero_intentos + 1;
        $observacion = trim((string) $observaciones) !== ''
            ? (string) $observaciones
            : $this->observacionReapertura($tramite->estado_actual);

        $this->transicionar($tramite, $config['estado'], $observacion, $actor);

        $estudiante->update(['numero_intentos' => $intentos]);
        $this->persistirHito($tramite, $config['estado']);
        $this->limpiarModulosParaReapertura($tramite, $estadoReabierto);
        $this->registrarHistorial(
            $tramite,
            'reapertura',
            [
                'estado' => $config['estado'],
                'resultado' => $config['resultado'],
                'numero_resolucion' => null,
                'tema' => null,
                'fecha_examen' => null,
                'nota' => null,
                'observacion' => $observacion,
            ],
            $estudiante,
            $actor,
            intentos: $intentos
        );

        $eventoEstado = $tramite->estado_actual;
        $tramite->refresh();

        event(new EstadoTramiteCambiado($tramite, $eventoEstado, $observacion));

        return $this->formatear($tramite);
    }

    /**
     * Resuelve el estado objetivo de un módulo a partir de sus datos, validando
     * el formulario y componiendo el registro del historial.
     *
     * @param  array<string, mixed>  $datos  Datos del formulario.
     * @return array{estado:string, resultado:?string, numero_resolucion:?string,
     *               tema:?string, fecha_examen:?string, nota:?float, observacion:string}
     */
    private function destino(string $modulo, array $datos, Estudiante $estudiante, Tramite $tramite): array
    {
        return match ($modulo) {
            'verificacion_intentos' => $this->destinoIntentos($datos, $estudiante, $tramite),
            'habilitacion_examen' => $this->destinoHabilitacion($datos),
            'sorteo_teorico' => $this->destinoSorteo($datos, 'examen_teorico', $tramite),
            'resolucion_resultado_teorico' => $this->destinoResolucionResultado($datos),
            'sorteo_especialidad' => $this->destinoSorteo($datos, 'examen_especialidad', $tramite),
            'resolucion_asignacion_notas' => $this->destinoResolucionAsignacionNotas($datos, $tramite),
            'asignacion_notas' => $this->destinoNotas($datos, $tramite),
            'reporte' => $this->destinoReporte($tramite),
            default => throw ValidationException::withMessages([
                'datos' => ['El módulo del flujo de examen no es reconocido.'],
            ]),
        };
    }

    /**
     * Módulo 1 — verificación de intentos.
     *
     * Aprobar exige que al estudiante le queden intentos; rechazar es
     * exclusivamente por intento agotado, por lo que también se valida que los
     * haya agotado:rejectar por otro motivo debe hacerse desde la revisión de la
     * solicitud, no desde este módulo.
     *
     * @param  array<string, mixed>  $datos
     * @return array{estado:string, resultado:?string, numero_resolucion:?string,
     *               tema:?string, fecha_examen:?string, nota:?float, observacion:string}
     */
    private function destinoIntentos(array $datos, Estudiante $estudiante, Tramite $tramite): array
    {
        $maximo = (int) config('examen.maximo_intentos', 3);
        $intentos = (int) $estudiante->numero_intentos;
        $agotados = $intentos >= $maximo;

        if (($datos['accion'] ?? 'aprobar') === 'rechazar') {
            if (! $agotados) {
                throw ValidationException::withMessages([
                    'numero_intentos' => [
                        "El estudiante lleva {$intentos} de {$maximo} intentos permitidos. "
                        .'Solo se rechaza la solicitud cuando los intentos están agotados.',
                    ],
                ]);
            }

            $motivo = trim((string) ($datos['observaciones'] ?? ''));

            if ($motivo === '') {
                throw ValidationException::withMessages([
                    'observaciones' => ['Indique el motivo por el que se rechaza la solicitud.'],
                ]);
            }

            return [
                'estado' => 'rechazado',
                'resultado' => 'rechazado',
                'numero_resolucion' => null,
                'tema' => null,
                'fecha_examen' => null,
                'nota' => null,
                'observacion' => $motivo,
            ];
        }

        if ($agotados) {
            throw ValidationException::withMessages([
                'numero_intentos' => [
                    "El estudiante agotó los {$maximo} intentos permitidos para el Examen de Grado. "
                    .'Rechace la solicitud en lugar de habilitarla.',
                ],
            ]);
        }

        return [
            'estado' => 'intentos_verificados',
            'resultado' => 'verificado',
            'numero_resolucion' => null,
            'tema' => null,
            'fecha_examen' => null,
            'nota' => null,
            'observacion' => "Intento {$intentos} de {$maximo}. El estudiante habilita su solicitud de examen de grado.",
        ];
    }

    /**
     * Módulo 2 — habilitación del estudiante con la Resolución del HCC.
     *
     * La fecha de la resolución se guarda en su propia columna del historial
     * (`fecha_resolucion`) y no en `fecha_examen`, que es la fecha en que se
     * rindió cada examen.
     *
     * @param  array<string, mixed>  $datos
     * @return array{estado:string, resultado:?string, numero_resolucion:?string,
     *               tema:?string, fecha_examen:?string, nota:?float, observacion:string,
     *               fecha_resolucion:?string}
     */
    private function destinoHabilitacion(array $datos): array
    {
        $errores = [];

        if (! $this->informado($datos['numero_resolucion'] ?? null)) {
            $errores[] = 'Ingrese el número de Resolución del HCC.';
        }
        if (! $this->informado($datos['fecha_resolucion'] ?? null)) {
            $errores[] = 'Seleccione la fecha de la Resolución.';
        }

        if ($errores !== []) {
            throw ValidationException::withMessages(['datos' => $errores]);
        }

        $resolucion = trim((string) $datos['numero_resolucion']);
        $fechaResolucion = $this->opcional($datos['fecha_resolucion'] ?? null);

        return [
            'estado' => 'habilitado_examen',
            'resultado' => 'habilitado',
            'numero_resolucion' => $resolucion,
            'fecha_resolucion' => $fechaResolucion,
            'tema' => null,
            'fecha_examen' => null,
            'nota' => null,
            'observacion' => "Estudiante habilitado para el examen de grado. Resolución N.º {$resolucion} del HCC.",
        ];
    }

    /**
     * Módulos 3 y 5 - sorteo de bolo del examen teórico y de especialidad.
     *
     * Registra la asistencia con el tema sorteado y la fecha del examen. No
     * asistir cierra el sorteo con el resultado `no_asistio` y sin nota, pero
     * el estado del trámite sigue siendo `{prefijo}_sorteado`: la reprobación
     * recién se decide en el módulo 7 (`asignacion_notas`), de modo que un
     * examen no rendido no bloquea el sorteo del otro.
     *
     * El sorteo de especialidad exige la Resolución de aprobación del resultado
     * del teórico (módulo 4) o que el teórico se conserve aprobado tras una
     * reapertura por especialidad.
     *
     * @param  array<string, mixed>  $datos
     * @param  string  $prefijo  `examen_teorico` o `examen_especialidad`.
     * @return array{estado:string, resultado:?string, numero_resolucion:?string,
     *               tema:?string, fecha_examen:?string, nota:?float, observacion:string}
     */
    private function destinoSorteo(array $datos, string $prefijo, Tramite $tramite): array
    {
        $etiqueta = $prefijo === 'examen_teorico' ? 'examen teórico' : 'examen de especialidad';

        // El sorteo de especialidad solo se habilita cuando el resultado del
        // teórico quedó aprobado por Resolución del HCC. En una reapertura por
        // especialidad esa aprobación se conserva en `hitos.examen_aprobado`.
        if ($prefijo === 'examen_especialidad' && ! $this->teoricoAprobado($tramite)) {
            throw ValidationException::withMessages([
                'datos' => [
                    'Registre la Resolución de aprobación del resultado del examen teórico '
                    .'antes de sortear el examen de especialidad.',
                ],
            ]);
        }

        $asistio = filter_var($datos['asistio'] ?? null, FILTER_VALIDATE_BOOLEAN);

        if (! $asistio) {
            return [
                'estado' => "{$prefijo}_sorteado",
                'resultado' => 'no_asistio',
                'numero_resolucion' => $this->opcional($datos['numero_resolucion'] ?? null),
                'tema' => null,
                'fecha_examen' => $this->opcional($datos['fecha_examen'] ?? null),
                'nota' => null,
                'observacion' => "El estudiante no asistió al sorteo de bolos del {$etiqueta}. "
                    .'La nota se registra en Asignación de Notas.',
            ];
        }

        $errores = [];

        if (! $this->informado($datos['tema'] ?? null)) {
            $errores[] = "Ingrese el tema sorteado para el {$etiqueta}.";
        }
        if (! $this->informado($datos['fecha_examen'] ?? null)) {
            $errores[] = "Seleccione la fecha del {$etiqueta}.";
        }

        if ($errores !== []) {
            throw ValidationException::withMessages(['datos' => $errores]);
        }

        return [
            'estado' => "{$prefijo}_sorteado",
            'resultado' => 'asistio',
            'numero_resolucion' => $this->opcional($datos['numero_resolucion'] ?? null),
            'tema' => trim((string) $datos['tema']),
            'fecha_examen' => $this->opcional($datos['fecha_examen']),
            'nota' => null,
            'observacion' => "Asistió al sorteo de bolos del {$etiqueta} y presentó el examen. "
                .'La nota se registra en Asignación de Notas.',
        ];
    }

    /**
     * Módulo 6 - Resolución de asignación de notas.
     *
     * Registra el N.º y la fecha de la Resolución del HCC con la que se habilita
     * la asignación de notas de ambos exámenes. Exige que cada examen tenga
     * origen en el intento en curso (su sorteo) o que esté conservado como
     * aprobado de la misma resolución.
     *
     * @param  array<string, mixed>  $datos
     * @return array{estado:string, resultado:?string, numero_resolucion:?string,
     *               tema:?string, fecha_examen:?string, nota:?float, observacion:string,
     *               fecha_resolucion:?string}
     */
    private function destinoResolucionAsignacionNotas(array $datos, Tramite $tramite): array
    {
        $errores = [];

        if (! $this->informado($datos['numero_resolucion'] ?? null)) {
            $errores[] = 'Ingrese el número de Resolución de asignación de notas.';
        }
        if (! $this->informado($datos['fecha_resolucion'] ?? null)) {
            $errores[] = 'Seleccione la fecha de la Resolución de asignación de notas.';
        }

        if ($errores !== []) {
            throw ValidationException::withMessages(['datos' => $errores]);
        }

        $this->exigirOrigenDeNotas($tramite);

        $resolucion = trim((string) $datos['numero_resolucion']);
        $fechaResolucion = $this->opcional($datos['fecha_resolucion'] ?? null);

        return [
            'estado' => 'notas_habilitadas',
            'resultado' => 'resolucion_registrada',
            'numero_resolucion' => $resolucion,
            'fecha_resolucion' => $fechaResolucion,
            'tema' => null,
            'fecha_examen' => null,
            'nota' => null,
            'observacion' => "Resolución de asignación de notas N.º {$resolucion} del HCC.",
        ];
    }

    /**
     * Comprueba que ambos exámenes tengan un origen válido para asignar notas:
     * su sorteo en el intento en curso o su aprobación conservada.
     *
     * @throws ValidationException
     */
    private function exigirOrigenDeNotas(Tramite $tramite): void
    {
        $intento = $this->historialDelIntento($tramite);
        $aprobados = $tramite->hitos['examen_aprobado'] ?? [];
        $faltantes = [];

        foreach ([
            'examen_teorico' => ['sorteo_teorico', 'examen teórico'],
            'examen_especialidad' => ['sorteo_especialidad', 'examen de especialidad'],
        ] as $prefijo => [$etapa, $etiqueta]) {
            if (isset($aprobados[$prefijo])) {
                continue;
            }

            if ($intento->firstWhere('etapa', $etapa) === null) {
                $faltantes[] = "Registre primero el sorteo del {$etiqueta} antes de la Resolución de asignación de notas.";
            }
        }

        if ($faltantes !== []) {
            throw ValidationException::withMessages(['datos' => $faltantes]);
        }
    }

    /**
     * Módulo 7 - asignación de notas de LOS DOS exámenes.
     *
     * Es el único módulo que decide el resultado del intento. Cada examen se
     * resuelve con DOS fuentes posibles:
     *
     *  - Conservado: el examen quedó APROBADO en un intento anterior de la misma
     *    Resolución del HCC (vive en `hitos.examen_aprobado`). Su nota se
     *    conserva tal cual: no se exige un nuevo sorteo ni se vuelve a asignar.
     *  - Del intento en curso: el examen se sortea y se rinde ahora. Exige que el
     *    sorteo del intento en curso esté registrado y una nota dentro del rango.
     *    Un examen no rendido (ausencia en su sorteo) cierra sin nota y cuenta
     *    como reprobado.
     *
     * El estado resultante es `aprobado` solo cuando las dos notas llegan al
     * mínimo; si no, deja el trámite en el estado reabrible del examen que faltó
     * aprobar: `examen_teorico_reprobado`, `examen_especialidad_reprobado` o
     * `examen_ambos_reprobados`.
     *
     * @param  array<string, mixed>  $datos
     * @return array{estado:string, resultado:?string, numero_resolucion:?string,
     *               tema:?string, fecha_examen:?string, nota:?float, observacion:string,
     *               notas:array<string, ?float>}
     */
    private function destinoNotas(array $datos, Tramite $tramite): array
    {
        $intento = $this->historialDelIntento($tramite);
        $minimo = (int) config('examen.nota_minima', 51);
        [$rangoMin, $rangoMax] = config('examen.rango_nota', [0, 100]);
        $aprobados = $tramite->hitos['examen_aprobado'] ?? [];

        if ($intento->firstWhere('etapa', 'resolucion_asignacion_notas') === null) {
            throw ValidationException::withMessages([
                'datos' => [
                    'Registre la Resolución de asignación de notas antes de asignar las notas.',
                ],
            ]);
        }

        $campos = [
            'examen_teorico' => ['sorteo_teorico', 'nota_teorico', 'examen teórico'],
            'examen_especialidad' => ['sorteo_especialidad', 'nota_especialidad', 'examen de especialidad'],
        ];

        $notas = [];
        $ausencias = [];
        $fuentes = [];

        foreach ($campos as $prefijo => [$etapa, $campo, $etiqueta]) {
            // El examen ya fue aprobado en un intento anterior de esta resolución:
            // se conserva su nota y no vuelve a sortearse.
            if (isset($aprobados[$prefijo])) {
                $notas[$prefijo] = (float) $aprobados[$prefijo]['nota'];
                $ausencias[$prefijo] = false;
                $fuentes[$prefijo] = 'conservado';

                continue;
            }

            $sorteo = $intento->firstWhere('etapa', $etapa);

            if ($sorteo === null) {
                throw ValidationException::withMessages([
                    'datos' => ["Registre primero el sorteo del {$etiqueta} antes de asignar la nota."],
                ]);
            }

            if ($sorteo->resultado !== 'asistio') {
                $notas[$prefijo] = null;
                $ausencias[$prefijo] = true;
                $fuentes[$prefijo] = 'no_asistio';

                continue;
            }

            $valor = $datos[$campo] ?? null;

            if ($valor === null || $valor === '' || ! is_numeric($valor)) {
                throw ValidationException::withMessages([
                    'datos' => ["Ingrese la nota del {$etiqueta}."],
                ]);
            }

            $nota = (float) $valor;

            if ($nota < $rangoMin || $nota > $rangoMax) {
                throw ValidationException::withMessages([
                    'datos' => ["La nota del {$etiqueta} debe estar entre {$rangoMin} y {$rangoMax}."],
                ]);
            }

            // Se conserva el valor original (int/float) tal como llegó del
            // formulario: volver a persistirlo mantiene el tipo sin redondeos.
            $notas[$prefijo] = $valor;
            $ausencias[$prefijo] = false;
            $fuentes[$prefijo] = 'asistio';
        }

        $teoricoAprobado = $this->examenAprobado($notas['examen_teorico'], $ausencias['examen_teorico'], $minimo);
        $especialidadAprobado = $this->examenAprobado($notas['examen_especialidad'], $ausencias['examen_especialidad'], $minimo);

        if ($teoricoAprobado && $especialidadAprobado) {
            $estado = 'aprobado';
        } elseif (! $teoricoAprobado && ! $especialidadAprobado) {
            $estado = 'examen_ambos_reprobados';
        } elseif (! $teoricoAprobado) {
            $estado = 'examen_teorico_reprobado';
        } else {
            $estado = 'examen_especialidad_reprobado';
        }

        return [
            'estado' => $estado,
            'resultado' => $estado === 'aprobado' ? 'aprobado' : 'reprobado',
            'numero_resolucion' => null,
            'tema' => null,
            'fecha_examen' => null,
            'nota' => $notas['examen_teorico'],
            'notas' => $notas,
            'examen_aprobado' => $this->examenesAprobados($intento, $aprobados, $notas, $teoricoAprobado, $especialidadAprobado),
            'observacion' => $this->observacionAsignacionNotas($notas, $fuentes, $minimo, $estado),
        ];
    }

    /**
     * Exámenes aprobados de la resolución en curso, para arrastrar su nota a los
     * intentos siguientes.
     *
     * Solo se registran los exámenes con nota igual o superior al mínimo; el
     * examen reprobado no figura y se vuelve a rendir en la re-solicitud. La nota
     * de un examen conservado y aprobado de nuevo se conserva.
     *
     * @return array<string, array{nota:float, tema:?string, fecha_examen:?string}>
     */
    private function examenesAprobados(
        Collection $intento,
        array $aprobados,
        array $notas,
        bool $teoricoAprobado,
        bool $especialidadAprobado
    ): array {
        $resultado = [];

        foreach ([
            'examen_teorico' => ['sorteo_teorico', $teoricoAprobado],
            'examen_especialidad' => ['sorteo_especialidad', $especialidadAprobado],
        ] as $prefijo => [$etapa, $aprobado]) {
            if (! $aprobado || $notas[$prefijo] === null) {
                continue;
            }

            if (isset($aprobados[$prefijo])) {
                // Conservado y sigue aprobado: se arrastra tal cual.
                $resultado[$prefijo] = $aprobados[$prefijo];

                continue;
            }

            $sorteo = $intento->firstWhere('etapa', $etapa);

            $resultado[$prefijo] = [
                'nota' => $notas[$prefijo],
                'tema' => $sorteo?->tema,
                'fecha_examen' => $sorteo?->fecha_examen,
            ];
        }

        return $resultado;
    }

    /** Indica si un examen quedó aprobado: asistió y alcanzó el mínimo. */
    private function examenAprobado(?float $nota, bool $ausente, int $minimo): bool
    {
        return ! $ausente && $nota !== null && $nota >= $minimo;
    }

    /**
     * Registros del historial que pertenecen al intento en curso.
     *
     * Espejo de `registrosDelIntento()` del frontend: lo anterior a la última
     * reapertura corresponde al intento ya consumido y no cuenta.
     *
     * @return Collection<int, HistorialEstadoExamen>
     */
    private function historialDelIntento(Tramite $tramite): Collection
    {
        $filas = $tramite->historialExamen
            ->sortBy([['created_at', 'asc'], ['id_historial_examen', 'asc']])
            ->values();

        $corte = null;

        foreach ($filas as $indice => $fila) {
            if ($fila->etapa === 'reapertura') {
                $corte = $indice;
            }
        }

        return $corte === null ? $filas : $filas->slice($corte + 1);
    }

    /**
     * Observación del historial para la asignación de notas.
     *
     * Resume las dos notas juntas: la fila del historial tiene una sola columna
     * de nota (se usa la del examen teórico) y el detalle completo de ambas
     * queda en `hitos.modulos.asignacion_notas`.
     */
    private function observacionAsignacionNotas(array $notas, array $fuentes, int $minimo, string $estado): string
    {
        $detalle = [];

        foreach ([
            'examen_teorico' => 'examen teórico',
            'examen_especialidad' => 'examen de especialidad',
        ] as $prefijo => $etiqueta) {
            $detalle[] = match ($fuentes[$prefijo]) {
                'no_asistio' => "{$etiqueta}: no asistió (sin nota, REPROBADO)",
                'conservado' => "{$etiqueta}: {$this->formatearNota((float) $notas[$prefijo])} puntos (conservado)",
                default => "{$etiqueta}: {$this->formatearNota((float) $notas[$prefijo])} puntos",
            };
        }

        $resumen = 'Asignación de notas — '.implode(' · ', $detalle)." (mínimo {$minimo}).";

        if ($estado === 'aprobado') {
            return $resumen.' Ambos exámenes APROBADOS.';
        }

        return $resumen.match ($estado) {
            'examen_teorico_reprobado' => ' El examen teórico no fue aprobado: se conserva la nota de especialidad y el estudiante re-sortea solo el teórico.',
            'examen_especialidad_reprobado' => ' El examen de especialidad no fue aprobado: se conserva la nota del teórico y el estudiante re-sortea solo la especialidad.',
            default => ' Ninguno de los dos exámenes fue aprobado: la re-solicitud exige una nueva Resolución del HCC.',
        };
    }

    /** Motivo por defecto de una reapertura, según el examen que quedó reprobado. */
    private function observacionReapertura(string $estado): string
    {
        return match ($estado) {
            'examen_teorico_reprobado' => 'El estudiante solicitó volver a rendir únicamente el examen teórico; la especialidad aprobada se conserva.',
            'examen_especialidad_reprobado' => 'El estudiante solicitó volver a rendir únicamente el examen de especialidad; el teórico aprobado se conserva.',
            default => 'El estudiante solicitó volver a rendir ambos exámenes con una nueva Resolución del HCC.',
        };
    }

    /**
     * Módulo 7 — reporte en PDF; el trámite queda en `titulado`.
     *
     * Generar el reporte es el último paso del Examen de Grado: el estudiante
     * aprobó los dos exámenes, así que el cierre del flujo y la titulación
     * ocurren juntos y no queda una etapa intermedia en la que el trámite esté
     * aprobado sin concluir.
     *
     * Exige que la asignación de notas del intento en curso haya cerrado en
     * `aprobado`: con una nota reprobada o ausente no se emite el reporte.
     *
     * @return array{estado:string, resultado:?string, numero_resolucion:?string,
     *               tema:?string, fecha_examen:?string, nota:?float, observacion:string}
     *
     * @throws ValidationException
     */
    private function destinoReporte(Tramite $tramite): array
    {
        // El reporte titula al estudiante, así que se comprueba que las notas
        // del intento en curso quedaron aprobadas. La máquina de estados ya
        // impide llegar con el estado equivocado, pero se valida explícitamente
        // para no depender de ella: título es lo que no se puede deshacer.
        $aprobado = $this->historialDelIntento($tramite)->contains(
            fn (HistorialEstadoExamen $f) => $f->etapa === 'asignacion_notas' && $f->resultado === 'aprobado'
        );

        if (! $aprobado) {
            throw ValidationException::withMessages([
                'datos' => [
                    'Solo se emite el reporte cuando el estudiante aprobó el examen teórico '
                    .'y el examen de especialidad.',
                ],
            ]);
        }

        return [
            'estado' => 'titulado',
            'resultado' => 'titulado',
            'numero_resolucion' => null,
            'tema' => null,
            'fecha_examen' => null,
            'nota' => null,
            'observacion' => 'Reporte de Examen de Grado generado. Estudiante titulado.',
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
     * Guarda los datos del formulario en `hitos.modulos`, igual que el flujo de
     * Tesis, para que el seguimiento del estudiante muestre lo que registró
     * KARDEX sin conocer el detalle técnico de la tabla de historial.
     */
    private function persistirModulo(Tramite $tramite, string $modulo, array $datos): void
    {
        $hitos = $tramite->hitos ?? [];

        $hitos['modulos'][$modulo] = array_replace($hitos['modulos'][$modulo] ?? [], $datos);

        $tramite->update(['hitos' => $hitos]);
    }

    /** Registra la fecha en que el trámite alcanzó el estado, para la línea de tiempo. */
    private function persistirHito(Tramite $tramite, string $estado): void
    {
        $hitos = $tramite->fresh()?->hitos ?? [];

        if (in_array($estado, ['rechazado', 'titulado', 'aprobado'], true)) {
            $hitos[$estado] = Carbon::today()->toDateString();
        }

        $tramite->update(['hitos' => $hitos]);
    }

    /**
     * Deja en blanco los módulos que se repiten tras una reapertura.
     *
     * Los sorteos, las Resoluciones del HCC del intento (resultado teórico y
     * asignación de notas) y la asignación de notas viven en `hitos.modulos`
     * para el seguimiento, pero pertenecen al intento que se acaba de consumir:
     * si se conservaran, la tarjeta del nuevo intento mostraría el tema, la
     * fecha, la resolución y las notas del intento anterior como si fueran los
     * vigentes.
     *
     * El alcance depende del examen que quedó reprobado:
     *
     *  - Solo teórico: se reinician el sorteo del teórico, su Resolución de
     *    aprobación, la Resolución de asignación de notas y las notas; el sorteo
     *    de especialidad y la Resolución de habilitación del HCC en curso se
     *    conservan.
     *  - Solo especialidad: se reinician el sorteo de especialidad, la Resolución
     *    de asignación de notas y las notas; el teórico aprobado, su Resolución y
     *    la habilitación se conservan.
     *  - Ambos: se reinician LOS DOS sorteos, las dos Resoluciones del intento,
     *    las notas y la habilitación, porque la re-solicitud exige registrar una
     *    NUEVA Resolución del HCC.
     */
    private function limpiarModulosParaReapertura(Tramite $tramite, string $estadoReabierto): void
    {
        $hitos = $tramite->hitos ?? [];
        $aReiniciar = self::MODULOS_DE_INTENTO;

        // Cuando se reprueban AMBOS hay que registrar una nueva resolución: la
        // habilitación anterior deja de contar para lo que viene.
        if ($estadoReabierto === 'examen_ambos_reprobados') {
            $aReiniciar[] = 'habilitacion_examen';
        } elseif ($estadoReabierto === 'examen_teorico_reprobado') {
            $aReiniciar = array_values(array_diff($aReiniciar, ['sorteo_especialidad']));
        } elseif ($estadoReabierto === 'examen_especialidad_reprobado') {
            $aReiniciar = array_values(array_diff(
                $aReiniciar,
                ['sorteo_teorico', 'resolucion_resultado_teorico']
            ));
        }

        foreach ($aReiniciar as $modulo) {
            unset($hitos['modulos'][$modulo]);
        }

        $tramite->update(['hitos' => $hitos]);
    }

    /**
     * Persiste los exámenes aprobados de la resolución en curso.
     *
     * `hitos.examen_aprobado` guarda, por cada examen aprobado de la resolución
     * vigente, su nota, tema y fecha, para que una re-solicitud parcial (solo el
     * examen reprobado) conserve la nota del examen aprobado sin volver a
     * sortearlo ni reasignarlo.
     */
    private function persistirExamenesAprobados(Tramite $tramite, array $resolucion): void
    {
        if (! array_key_exists('examen_aprobado', $resolucion) || $resolucion['examen_aprobado'] === []) {
            return;
        }

        $hitos = $tramite->fresh()?->hitos ?? [];
        $hitos['examen_aprobado'] = $resolucion['examen_aprobado'];

        $tramite->update(['hitos' => $hitos]);
    }

    /**
     * Inserta la transición en `historial_estados_examen`, el registro
     * especializado con el detalle del examen que consume el reporte en PDF.
     *
     * @param  array{resultado:?string, numero_resolucion:?string, tema:?string,
     *               fecha_examen:?string, nota:?float, observacion:string,
     *               fecha_resolucion?:?string}  $resolucion
     */
    private function registrarHistorial(
        Tramite $tramite,
        string $etapa,
        array $resolucion,
        Estudiante $estudiante,
        User $actor,
        ?int $intentos = null,
    ): void {
        HistorialEstadoExamen::create([
            'id_tramite' => $tramite->id_tramite,
            'nombre_estado' => $resolucion['estado'],
            'etapa' => $etapa,
            'resultado' => $resolucion['resultado'],
            'numero_resolucion' => $resolucion['numero_resolucion'],
            'fecha_resolucion' => $resolucion['fecha_resolucion'] ?? null,
            'tema' => $resolucion['tema'],
            'fecha_examen' => $resolucion['fecha_examen'],
            'nota' => $resolucion['nota'],
            'numero_intentos' => $intentos ?? (int) $estudiante->numero_intentos,
            'observaciones' => $resolucion['observacion'],
            'id_usuario_responsable' => $actor->id_usuario,
        ]);
    }

    /**
     * Trámite formateado con el historial de examen cargado, listo para la
     * respuesta de la API.
     */
    private function formatear(Tramite $tramite): array
    {
        $tramite->refresh()->load('historialExamen');

        $respuesta = $this->tramites->formatear($tramite);
        $respuesta['historial_examen'] = $tramite->historialExamen
            ->sortBy('created_at')
            ->values()
            ->toArray();

        return $respuesta;
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

    /** Presenta la nota sin decimales innecesarios (74 en vez de 74.00). */
    private function formatearNota(float $nota): string
    {
        return rtrim(rtrim(number_format($nota, 2, '.', ''), '0'), '.');
    }

    /**
     * Módulo 4 - Resolución del HCC que aprueba el resultado del examen teórico.
     *
     * La fecha se guarda en `fecha_resolucion` y no en `fecha_examen`, que es la
     * fecha en que se rindió el examen. Este registro es el requisito para
     * habilitar el sorteo del examen de especialidad.
     *
     * @param  array<string, mixed>  $datos
     * @return array{estado:string, resultado:?string, numero_resolucion:?string,
     *               tema:?string, fecha_examen:?string, nota:?float, observacion:string,
     *               fecha_resolucion:?string}
     */
    private function destinoResolucionResultado(array $datos): array
    {
        $errores = [];

        if (! $this->informado($datos['numero_resolucion'] ?? null)) {
            $errores[] = 'Ingrese el número de Resolución del HCC.';
        }
        if (! $this->informado($datos['fecha_resolucion'] ?? null)) {
            $errores[] = 'Seleccione la fecha de la Resolución.';
        }

        if ($errores !== []) {
            throw ValidationException::withMessages(['datos' => $errores]);
        }

        $resolucion = trim((string) $datos['numero_resolucion']);
        $fechaResolucion = $this->opcional($datos['fecha_resolucion'] ?? null);

        return [
            'estado' => 'examen_teorico_aprobado',
            'resultado' => 'aprobado',
            'numero_resolucion' => $resolucion,
            'fecha_resolucion' => $fechaResolucion,
            'tema' => null,
            'fecha_examen' => null,
            'nota' => null,
            'observacion' => "Resultado del examen teórico aprobado. Resolución N.º {$resolucion} del HCC.",
        ];
    }

    /**
     * Indica si el resultado del examen teórico está aprobado en la resolución
     * en curso.
     *
     * En una reapertura por especialidad el examen teórico se conserva en
     * `hitos.examen_aprobado`; en el flujo normal la aprobación queda en el
     * historial del intento actual.
     */
    private function teoricoAprobado(Tramite $tramite): bool
    {
        if (isset($tramite->hitos['examen_aprobado']['examen_teorico'])) {
            return true;
        }

        return $this->historialDelIntento($tramite)->contains(
            fn (HistorialEstadoExamen $f) => $f->etapa === 'resolucion_resultado_teorico'
                && $f->resultado === 'aprobado'
        );
    }
}
