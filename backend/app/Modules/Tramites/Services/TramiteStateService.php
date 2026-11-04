<?php

namespace App\Modules\Tramites\Services;

use App\Models\EstadoTramite;
use App\Models\Tramite;
use App\Modules\Tramites\Exceptions\TransicionNoPermitidaException;
use Illuminate\Support\Facades\DB;

/**
 * Máquina de estados de los trámites de titulación.
 *
 * Centraliza el mapa de transiciones permitidas por modalidad, la validación y
 * ejecución de cambios de estado (con registro en el historial) y los métodos
 * de consulta de estados siguientes y de la secuencia para la línea de tiempo.
 * No depende de controladores ni de otros módulos.
 */
class TramiteStateService
{
    /**
     * Nombre de la modalidad de titulación de Examen de Grado.
     *
     * Es la clave con la que se registra su flujo en `TRANSITIONS`, y la usan los
     * servicios que necesitan distinguirla: el contador de intentos de
     * `ExamenService` y el reconocimiento de títulos ya obtenidos en
     * `TramiteService::crear()`.
     */
    public const MODALIDAD_EXAMEN = 'Examen de Grado';

    /**
     * Flujo de estados de las modalidades que avanzan por MÓDULOS del flujo de
     * titulación (Tesis de Grado y Trabajo Dirigido).
     *
     * Ambas modalidades comparten la misma secuencia: solicitud con 3 archivos,
     * evaluación del Consejo Universitario, reenvío del perfil rechazado, tutor
     * asignado, investigación con plazo de presentación (3-12 meses), comisión
     * revisora (suficiente/insuficiente), solicitud de fecha de defensa,
     * defensa y 90 días de corrección si no la aprueba.
     *
     * @var array<string, array<int, string>>
     */
    private const TRANSICIONES_TITULACION = [
        // 1. Fase de solicitud. El puente a `perfil_aprobado` permite que
        //    Kardex registre el módulo de perfil directamente desde la
        //    solicitud, sin pasar por el estado intermedio del Consejo.
        'solicitud_presentada' => ['pendiente_concejo_universitario', 'perfil_aprobado', 'perfil_rechazado', 'rechazado'],
        // 2. Evaluación del perfil por el Consejo Universitario.
        'pendiente_concejo_universitario' => ['perfil_aprobado', 'perfil_rechazado'],
        'perfil_rechazado' => ['pendiente_concejo_universitario', 'perfil_aprobado', 'tutor_asignado', 'rechazado'],
        // 3. Módulos del flujo de titulación gestionados por Kardex. Cada
        //    módulo se guarda desde su formulario y avanza al estado objetivo.
        'perfil_aprobado' => ['tema_aprobado', 'tutor_asignado'],
        // Puente del flujo anterior: tras asignar tutor se puede continuar
        // por el flujo heredado (investigación) o registrar el tema (nuevo).
        'tutor_asignado' => ['tema_aprobado', 'investigacion_en_desarrollo'],
        'tema_aprobado' => ['tribunal_asignado'],
        'tribunal_asignado' => ['defensa_aprobada', 'solicitud_fecha_defensa', 'reprobado'],
        'defensa_aprobada' => ['publicado'],
        'publicado' => ['titulado'],
        'reporte_generado' => ['titulado'],
        // 4. Flujo heredado: investigación, comisión revisora y defensa.
        'investigacion_en_desarrollo' => ['documento_final_presentado', 'tribunal_asignado'],
        'documento_final_presentado' => ['comision_revisora', 'tribunal_asignado'],
        'comision_revisora' => ['suficiente', 'insuficiente', 'tribunal_asignado'],
        'insuficiente' => ['comision_revisora'],
        // Puentes hacia el estado objetivo del módulo de defensa. Registrar
        // la nota final de la defensa puede cerrar el trámite como
        // `defensa_aprobada` (nota mínima alcanzada) o `reprobado` (nota por
        // debajo del mínimo): en ese caso el flujo termina y el estudiante
        // debe volver a solicitar la modalidad de graduación.
        'suficiente' => ['solicitud_fecha_defensa', 'defensa_aprobada', 'reprobado'],
        'solicitud_fecha_defensa' => ['defensa_programada', 'defensa_aprobada', 'reprobado'],
        'defensa_programada' => ['defensa_en_curso', 'defensa_aprobada', 'reprobado'],
        'defensa_en_curso' => ['aprobado', 'correcciones_90_dias', 'defensa_aprobada', 'reprobado'],
        // Tras reprobar la defensa, el estudiante tiene 90 días para corregir
        // y volver a solicitar una fecha; si no, la gestión lo pasa a `reprobado`.
        'correcciones_90_dias' => ['solicitud_fecha_defensa', 'reprobado'],
    ];

    /**
     * Mapa de transiciones permitidas por modalidad de titulación.
     *
     * Cada modalidad define su flujo como `estado_actual => [estados siguientes]`.
     * El estado `rechazado` solo se alcanza por flujos de evaluación (revisión
     * inicial o rechazo definitivo del perfil), no como avance normal.
     *
     * Examen de Grado avanza por módulos gestionados por KARDEX: verificación
     * de intentos (rechazo por `numero_intentos >= 3`), habilitación con
     * Resolución del HCC, sorteo de bolo del examen teórico, Resolución de
     * aprobación de su resultado, sorteo del examen de especialidad (asistencia
     * con tema y fecha en cada uno), asignación de notas de LOS DOS exámenes y
     * reporte final. Reprobar o ausentarse NO cierra el flujo: el estudiante
     * puede volver a solicitar el examen y el trámite se reabre desde
     * `habilitado_examen`.
     *
     * Excelencia Académica también avanza por módulos gestionados por KARDEX:
     * verificación del promedio y Resolución de admisión (rechazo si el
     * resultado es RECHAZADO o el promedio queda por debajo del mínimo
     * reglamentario), registro de la Resolución del HCC con la lectura del acta
     * y reporte final, que titula al estudiante.
     *
     * @var array<string, array<string, array<int, string>>>
     */
    private const TRANSITIONS = [
        'Examen de Grado' => [
            // 1. Verificación de intentos. Se rechaza cuando el estudiante ya
            //    agotó los intentos permitidos (`numero_intentos >= 3`).
            'solicitud_presentada' => ['intentos_verificados', 'rechazado'],
            // 2. Habilitación del estudiante con la Resolución del HCC. Tras
            //    reabrir con AMBOS exámenes reprobados, la reapertura devuelve el
            //    trámite aquí y se registra una NUEVA resolución; cuando solo un
            //    examen quedó reprobado, la resolución en curso se conserva y la
            //    reapertura retoma desde el sorteo del examen reprobado.
            'intentos_verificados' => ['habilitado_examen', 'rechazado'],
            // 3. Sorteo de bolo del examen teórico. Registra la asistencia con el
            //    tema sorteado y la fecha; no asistir cierra el sorteo sin nota
            //    (resultado `no_asistio`) pero sigue en `examen_teorico_sorteado`.
            //    La reprobación recién se decide en la asignación de notas.
            'habilitado_examen' => ['examen_teorico_sorteado'],
            'examen_teorico_sorteado' => ['examen_teorico_aprobado'],
            // 4. Resolución del HCC que aprueba el resultado del examen teórico.
            //    Es el requisito para sortear la especialidad. En la re-solicitud
            //    de un teórico reprobado (con la especialidad ya aprobada) la
            //    Resolución de asignación de notas se habilita desde aquí, sin
            //    volver a sortear la especialidad.
            'examen_teorico_aprobado' => ['examen_especialidad_sorteado', 'notas_habilitadas', 'examen_teorico_reprobado'],
            // 5a. El teórico reprobado (con la especialidad ya aprobada) reabre
            //     desde `habilitado_examen` para sortear ÚNICAMENTE el teórico;
            //     la resolución del HCC en curso se conserva.
            'examen_teorico_reprobado' => ['habilitado_examen'],
            // 5b. Sorteo de bolo del examen de especialidad (misma dinámica): se
            //     alcanza solo cuando el resultado del teórico quedó aprobado.
            'examen_especialidad_sorteado' => ['notas_habilitadas'],
            // 6. Resolución de asignación de notas: habilita el cierre de los DOS
            //    exámenes. Es obligatoria antes de registrar las notas.
            'notas_habilitadas' => ['aprobado', 'examen_especialidad_reprobado', 'examen_teorico_reprobado', 'examen_ambos_reprobados'],
            // 7. Asignación de notas: cierra los DOS exámenes juntos. Con las dos
            //    notas en el mínimo queda `aprobado` (espera el reporte). Si un
            //    examen quedó por debajo del mínimo, el trámite cae en el estado
            //    reabrible que señala qué examen faltó aprobar: `examen_teorico_reprobado`
            //    (solo teórico, la especialidad se conserva), `examen_especialidad_reprobado`
            //    (solo especialidad, el teórico se conserva) o `examen_ambos_reprobados`.
            'examen_especialidad_reprobado' => ['examen_teorico_aprobado'],
            // Reapertura: reprobar UN SOLO examen permite re-solicitar conservando
            // la nota del examen aprobado (resolución en curso). Reprobar AMBOS
            // exige una NUEVA resolución: la reapertura regresa a la verificación
            // de intentos y KARDEX registra un nuevo N.º de Resolución del HCC.
            // El contador de intentos sube con cada reapertura.
            'examen_ambos_reprobados' => ['intentos_verificados'],
            // 8. Reporte en PDF y titulación. El reporte ES el último paso: al
            //    generarlo el estudiante queda titulado, de modo que no queda
            //    un estado intermedio en el que el examen esté aprobado sin
            //    concluir. `reporte_examen_generado` se conserva por si un
            //    migrate anterior dejó trámites en ese estado.
            'aprobado' => ['titulado', 'reporte_examen_generado'],
            'reporte_examen_generado' => ['titulado'],
        ],
        'Tesis de Grado' => self::TRANSICIONES_TITULACION,
        'Trabajo Dirigido' => self::TRANSICIONES_TITULACION,
        'Excelencia Académica' => [
            // 1. Verificación del promedio y Resolución de admisión. Con el
            //    resultado ACEPTADO y el promedio en o por encima del mínimo el
            //    trámite habilita al estudiante; con RECHAZADO, o por debajo del
            //    mínimo, la solicitud queda rechazada y el flujo termina (el
            //    estudiante no opta a la modalidad).
            'solicitud_presentada' => ['promedio_verificado', 'rechazado'],
            // 2. Resolución del HCC y lectura del acta ante los tres tribunales.
            'promedio_verificado' => ['resolucion_hcc_registrada'],
            // 3. Reporte final: el acta de Excelencia Académica titula al
            //    estudiante.
            'resolucion_hcc_registrada' => ['titulado'],
        ],
    ];

    /**
     * Estados terminales: desde ellos no se puede avanzar a ningún otro estado.
     *
     * @var array<int, string>
     */
    private const TERMINALES = ['aprobado', 'reprobado', 'rechazado', 'titulado'];

    /**
     * Estados de fallo: cierran el flujo sin titulación (rechazo o reprobación).
     *
     * @var array<int, string>
     */
    private const FALLOS = ['reprobado', 'rechazado'];

    /**
     * Estados de Examen de Grado que NO cierran el tr??mite.
     *
     * `aprobado` es terminal para el resto de las modalidades, pero en Examen
     * no lo es: `aprobado` espera el reporte que titula al estudiante. Los
     * listados de "pendientes" y "concluidos" los necesitan expl??citos, porque
     * de lo contrario un tr??mite que KARDEX todav??a tiene que resolver
     * desaparecer??a de la cola de trabajo.
     *
     * @var array<int, string>
     */
    private const EXAMEN_NO_TERMINALES = [
        'aprobado',
    ];

    /**
     * Estados de Examen de Grado que siguen abiertos.
     *
     * @return array<int, string>
     */
    public static function noTerminalesDeExamen(): array
    {
        return self::EXAMEN_NO_TERMINALES;
    }

    /**
     * Estados terminales POR MODALIDAD, para las modalidades cuyo flujo no cierra
     * en el mismo punto que el resto.
     *
     * En Examen de Grado la aprobación no es terminal: el trámite continúa con
     * el módulo de reporte y recién en `titulado` concluye. Además, una
     * reprobación o una ausencia NO cierran el flujo, porque el estudiante puede
     * volver a solicitar el examen (ver la sección de reapertura en `TRANSITIONS`).
     *
     * @var array<string, array<int, string>>
     */
    private const TERMINALES_POR_MODALIDAD = [
        'Examen de Grado' => ['rechazado', 'titulado'],
    ];

    /**
     * Estados terminales de una modalidad concreta.
     *
     * @param  string  $modalidadNombre  Nombre de la modalidad de titulación.
     * @return array<int, string>
     */
    public static function terminalesDe(string $modalidadNombre): array
    {
        return self::TERMINALES_POR_MODALIDAD[$modalidadNombre] ?? self::TERMINALES;
    }

    /**
     * Estados terminales de ÉXITO (aprobación o titulación) de una modalidad.
     *
     * Son los estados terminales que no son un rechazo ni una reprobación, y
     * los usa el panel general del KARDEX para pintar en verde los trámites ya
     * culminados. En Examen de Grado el único logro terminal es `titulado` (en
     * `aprobado` todavía falta emitir el reporte); en el resto de las
     * modalidades son `aprobado` y `titulado`.
     *
     * @param  string  $modalidadNombre  Nombre de la modalidad de titulación.
     * @return array<int, string>
     */
    public static function terminalesExitososDe(string $modalidadNombre): array
    {
        return array_values(array_diff(
            self::terminalesDe($modalidadNombre),
            self::FALLOS,
        ));
    }

    /**
     * Estados que cierran el flujo sin titulación (rechazo o reprobación).
     *
     * @return array<int, string>
     */
    public static function fallos(): array
    {
        return self::FALLOS;
    }

    /**
     * Estados terminales compartidos del sistema.
     *
     * Expuestos públicos para que otros servicios (listados, estadísticas)
     * filtren sin duplicar la lista de estados.
     *
     * @return array<int, string>
     */
    public static function terminales(): array
    {
        return self::TERMINALES;
    }

    /**
     * Indica si un trámite ya cerró su flujo según la modalidad a la que
     * pertenece.
     *
     * Es la comprobación que usan los servicios antes de registrar un módulo:
     * un `aprobado` de Examen de Grado todavía admite el módulo de reporte,
     * mientras que en las demás modalidades sí es el fin del trámite.
     */
    public function esTerminal(Tramite $tramite): bool
    {
        return in_array(
            $tramite->estado_actual,
            self::terminalesDe($tramite->modalidad->nombre),
            true
        );
    }

    /**
     * Valida y ejecuta una transición de estado sobre un trámite.
     *
     * Dentro de una transacción de BD registra el nuevo estado en el historial
     * y actualiza el `estado_actual` del trámite.
     *
     * @return EstadoTramite Registro de historial creado.
     *
     * @throws TransicionNoPermitidaException
     */
    public function transicionar(Tramite $tramite, string $nuevoEstado, string $observaciones, int $usuarioId): EstadoTramite
    {
        $modalidadNombre = $tramite->modalidad->nombre;
        $estadoActual = $tramite->estado_actual;

        if (! isset(self::TRANSITIONS[$modalidadNombre][$estadoActual])
            || ! in_array($nuevoEstado, self::TRANSITIONS[$modalidadNombre][$estadoActual], true)) {
            throw new TransicionNoPermitidaException(
                "Transición no permitida de '{$estadoActual}' a '{$nuevoEstado}' para {$modalidadNombre}."
            );
        }

        return DB::transaction(function () use ($tramite, $nuevoEstado, $observaciones, $usuarioId) {
            $historial = EstadoTramite::create([
                'id_tramite' => $tramite->id_tramite,
                'nombre_estado' => $nuevoEstado,
                'descripcion' => $tramite->modalidad->nombre.' - Cambio de estado',
                'id_usuario_responsable' => $usuarioId,
                'observaciones' => $observaciones,
            ]);

            $tramite->update(['estado_actual' => $nuevoEstado]);

            return $historial;
        });
    }

    /**
     * Siguientes estados permitidos desde el estado actual de un trámite.
     *
     * Excluye `rechazado`, reservado al flujo de revisión inicial.
     *
     * @return array<int, string>
     */
    public function getSiguientesEstados(Tramite $tramite): array
    {
        $siguientes = self::TRANSITIONS[$tramite->modalidad->nombre][$tramite->estado_actual] ?? [];

        return array_values(array_filter($siguientes, fn ($s) => $s !== 'rechazado'));
    }

    /**
     * Secuencia ordenada de todos los estados de una modalidad para la línea
     * de tiempo del frontend.
     *
     * @return array<int, string>
     */
    public function getSecuenciaEstados(string $modalidadNombre): array
    {
        $mapa = self::TRANSITIONS[$modalidadNombre] ?? [];
        if (empty($mapa)) {
            return [];
        }

        $secuencia = [];
        $cola = ['solicitud_presentada'];
        $terminales = [];
        $terminalesModalidad = self::terminalesDe($modalidadNombre);

        while (! empty($cola)) {
            $estado = array_shift($cola);
            if (in_array($estado, $secuencia, true)) {
                continue;
            }
            $secuencia[] = $estado;

            foreach ($mapa[$estado] ?? [] as $siguiente) {
                if (in_array($siguiente, $terminalesModalidad, true)) {
                    if (! in_array($siguiente, $terminales, true)) {
                        $terminales[] = $siguiente;
                    }
                } else {
                    $cola[] = $siguiente;
                }
            }
        }

        // Estados definidos en el mapa pero nunca visitados (ramas muertas).
        foreach (array_keys($mapa) as $estado) {
            if (! in_array($estado, $secuencia, true)) {
                $secuencia[] = $estado;
            }
        }

        // Los estados terminales van al final.
        return array_merge(array_diff($secuencia, $terminalesModalidad), $terminales);
    }
}
