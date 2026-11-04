<?php

namespace App\Modules\Tramites\Services;

use App\Models\Modalidad;
use App\Models\Tramite;
use App\Models\User;
use App\Modules\Kardex\Services\VerificacionTituloService;
use App\Modules\Tramites\Events\EstadoTramiteCambiado;
use App\Modules\Tramites\Events\FechaDefensaSolicitada;
use App\Support\DocumentoAdjuntoService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Casos de uso del lado del ESTUDIANTE en las modalidades que comparten el
 * FLUJO DE TITULACIÓN por módulos (Tesis de Grado y Trabajo Dirigido).
 *
 * Encapsula el flujo oficial del estudiante: presentación de la solicitud con
 * el título del trabajo (los documentos en PDF son opcionales), corrección del
 * perfil mientras se verifica, reenvío del perfil tras su rechazo, reenvío del
 * documento final insuficiente, subida del trabajo de investigación y solicitud
 * de fecha de defensa. Reutiliza el motor genérico de trámites
 * (TramiteService/TramiteStateService) sin modificar su contrato.
 *
 * Las diferencias entre modalidades (identificador del módulo de perfil,
 * espacio de configuración y etiquetas de la interfaz) se resuelven con los
 * métodos overridibles, cuyos valores por defecto salen de
 * {@see FlujoTitulacionModalidad}.
 */
abstract class FlujoTitulacionEstudianteService
{
    /** Documento final presentado a la Comisión Revisora (y sus correcciones). */
    public const DOC_FINAL = 'documento_final';

    /** Trabajo de investigación (publicación) subido por el estudiante. */
    public const DOC_PUBLICACION = 'trabajo_publicacion';

    /** Módulo del flujo donde se registra el trabajo de investigación. */
    protected const MODULO_PUBLICACION = 'publicacion';

    /**
     * Estado desde el que el estudiante puede subir su trabajo de investigación:
     * con la defensa aprobada, KARDEX valida la publicación para cerrar el flujo.
     */
    protected const ESTADO_PUBLICACION = 'defensa_aprobada';

    /** Estados desde los que el estudiante puede solicitar (o re-solicitar) su fecha de defensa. */
    protected const ESTADOS_SOLICITUD_DEFENSA = ['suficiente', 'correcciones_90_dias', 'solicitud_fecha_defensa'];

    /** Estado en el que el estudiante aún puede corregir su perfil (título y PDFs). */
    protected const ESTADO_EDICION_PERFIL = 'solicitud_presentada';

    /** Estados terminales que permiten iniciar una nueva solicitud. */
    protected const TERMINALES = ['aprobado', 'reprobado', 'rechazado'];

    public function __construct(
        protected readonly TramiteService $tramiteService,
        protected readonly TramiteStateService $stateService,
        protected readonly DocumentoAdjuntoService $documentos,
        protected readonly VerificacionTituloService $verificacionTitulo,
    ) {}

    /**
     * Nombre de la modalidad que atiende este servicio.
     */
    abstract protected function modalidad(): string;

    /**
     * Estado al que vuelve un perfil corregido y reenviado.
     */
    public function estadoReenvio(): string
    {
        return 'pendiente_concejo_universitario';
    }

    /**
     * Estado al que vuelve un documento final corregido tras calificación
     * insuficiente de la Comisión Revisora.
     */
    public function estadoReenvioDocumento(): string
    {
        return 'comision_revisora';
    }

    /**
     * Identificador del módulo del perfil en el flujo de titulación.
     */
    protected function moduloPerfil(): string
    {
        return FlujoTitulacionModalidad::perfilDe($this->modalidad());
    }

    /**
     * Tipo documental del perfil del estudiante.
     */
    protected function documentoPerfil(): string
    {
        return $this->moduloPerfil();
    }

    /**
     * Espacio de configuración del módulo de la modalidad (`tesis` /
     * `trabajodirigido`).
     */
    protected function claveConfig(): string
    {
        return FlujoTitulacionModalidad::configDe($this->modalidad());
    }

    /**
     * Cómo se nombra el trabajo de la modalidad en los textos del estudiante.
     */
    protected function etiquetaModalidad(): string
    {
        return FlujoTitulacionModalidad::etiquetaDe($this->modalidad());
    }

    /**
     * Artículo que acompaña a la etiqueta del trabajo ("una" / "un").
     */
    protected function articuloModalidad(): string
    {
        return FlujoTitulacionModalidad::articuloDe($this->modalidad());
    }

    /**
     * Cómo se nombra el TÍTULO del trabajo en los textos del estudiante.
     */
    protected function etiquetaTitulo(): string
    {
        return FlujoTitulacionModalidad::etiquetaTituloDe($this->modalidad());
    }

    /**
     * Cómo se nombra el primer módulo del flujo, en minúscula.
     */
    protected function etiquetaPerfil(): string
    {
        return FlujoTitulacionModalidad::etiquetaPerfilDe($this->modalidad());
    }

    /**
     * El estudiante solicita una fecha para su defensa.
     *
     * Puede invocarse cuando la Comisión Revisora emitió el veredicto suficiente
     * (`suficiente`), cuando el estudiante debe re-solicitar tras no aprobar la
     * defensa (`correcciones_90_dias`) o cuando ya está esperando la programación
     * de Kardex (`solicitud_fecha_defensa`). Desde `suficiente` y
     * `correcciones_90_dias` la solicitud avanza formalmente al estado
     * `solicitud_fecha_defensa`, registra el hito y notifica a los roles de
     * gestión vía evento de dominio para que programen la defensa.
     *
     * @throws AuthorizationException Si no es su trámite.
     * @throws ValidationException Si el trámite no pertenece a la modalidad o no
     *                             está en un estado habilitado.
     */
    public function solicitarFechaDefensa(int $id, User $user, ?string $fechaSugerida): array
    {
        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if (! $tramite->perteneceA($user)) {
            throw new AuthorizationException('No autorizado');
        }

        $this->verificarModalidad($tramite);

        if (! in_array($tramite->estado_actual, self::ESTADOS_SOLICITUD_DEFENSA, true)) {
            throw ValidationException::withMessages([
                'estado' => 'Solo puede solicitar la fecha de defensa cuando la Comisión Revisora calificó su '
                    .$this->etiquetaTitulo().' como suficiente o cuando debe volver a solicitarla tras una corrección.',
            ]);
        }

        // Una solicitud ya registrada en espera de programación evita re-notificar.
        $yaSolicitada = $tramite->estado_actual === 'solicitud_fecha_defensa'
            && ! empty($tramite->hitos['fecha_defensa_solicitada']);

        // Desde el veredicto o las correcciones se avanza a la espera de programación.
        if ($tramite->estado_actual !== 'solicitud_fecha_defensa') {
            $this->stateService->transicionar(
                $tramite,
                'solicitud_fecha_defensa',
                'El estudiante solicitó una fecha para su defensa.',
                $user->id_usuario
            );
        }

        $tramite->refresh();

        $hoy = Carbon::now()->toDateString();
        $hitos = $tramite->hitos ?? [];
        $hitos['fecha_defensa_solicitada'] = $hoy;

        if ($fechaSugerida) {
            $hitos['fecha_defensa_sugerida'] = $fechaSugerida;
        }

        $tramite->update(['hitos' => $hitos]);

        $tramite->refresh()->load('modalidad', 'estudiante.user', 'documentos', 'estados.responsable', 'tutor');

        if (! $yaSolicitada) {
            event(new FechaDefensaSolicitada($tramite, $user, $fechaSugerida));
        }

        return $this->tramiteService->formatear($tramite);
    }

    /**
     * Crea la solicitud de la modalidad con el título propuesto y, de ser el
     * caso, los documentos en PDF (opcionales).
     *
     * El título se registra en el módulo de perfil del flujo de titulación
     * (`hitos.modulos.<perfil>.titulo`) para que KARDEX pueda verificar su
     * unicidad antes de aprobar el perfil. Si ya existe un trabajo de otro
     * postulante con el mismo título, la solicitud no se crea: el estudiante
     * debe proponer otro.
     *
     * @throws \DomainException Si la modalidad no está configurada, el
     *                          estudiante ya tiene un trabajo en curso, el
     *                          título está repetido o no cumple los plazos de
     *                          re-opción.
     */
    public function crearSolicitud(array $validated, User $user): Tramite
    {
        $modalidad = Modalidad::where('nombre', $this->modalidad())->where('activo', true)->first();

        if (! $modalidad) {
            throw new \DomainException('La modalidad '.$this->modalidad().' no está configurada en el sistema.');
        }

        $estudiante = $user->estudiante;

        if (! $estudiante) {
            throw new \DomainException('Debe completar su perfil de estudiante primero.');
        }

        $titulo = trim((string) ($validated['titulo'] ?? ''));

        $ultimo = Tramite::where('id_estudiante', $estudiante->id_estudiante)
            ->where('id_modalidad', $modalidad->id_modalidad)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($ultimo) {
            // Un estudiante ya aprobado no puede iniciar más solicitudes de trámite.
            if ($ultimo->estado_actual === 'aprobado') {
                throw new \DomainException('Ya aprobó su '.$this->etiquetaModalidad()
                    .'. No puede iniciar más solicitudes de trámite.');
            }

            // Tras reprobar, debe cumplir los plazos reglamentarios (90 días de
            // corrección o el periodo máximo de 365 días) para optar nuevamente.
            if (in_array($ultimo->estado_actual, ['reprobado'], true)
                && ! $this->puedeReoptar($ultimo)) {
                $proxima = $this->proximaReoptar($ultimo);

                throw new \DomainException(
                    'Debe esperar a que se cumplan los plazos reglamentarios para volver a presentar su solicitud'
                    .($proxima ? " (habilitado a partir del {$proxima})." : '.')
                );
            }
        }

        $activa = Tramite::where('id_estudiante', $estudiante->id_estudiante)
            ->where('id_modalidad', $modalidad->id_modalidad)
            ->whereNotIn('estado_actual', self::TERMINALES)
            ->exists();

        if ($activa) {
            throw new \DomainException('Ya tiene '.$this->articuloModalidad().' '.$this->etiquetaModalidad()
                .' en curso. Avanza desde el seguimiento de su trámite.');
        }

        // Los trámites anteriores del propio estudiante no cuentan como duplicado:
        // solo se compara contra lo registrado por otros postulantes.
        $duplicado = $this->verificacionTitulo->duplicado($titulo, null, (int) $estudiante->id_estudiante);

        if ($duplicado !== null) {
            throw new \DomainException($this->mensajeTituloRepetido($duplicado));
        }

        $tramite = $this->tramiteService->crear(
            [
                'id_modalidad' => (int) $modalidad->id_modalidad,
                'documentos' => collect($validated['documentos'] ?? [])->values()->all(),
            ],
            $user
        );

        $this->registrarTituloPerfil($tramite, $titulo);

        return $tramite;
    }

    /**
     * Registra el título propuesto por el estudiante en el módulo de perfil.
     *
     * Se conserva en `hitos.modulos.<perfil>.titulo` aunque KARDEX todavía
     * no haya aprobado el perfil: es el título que la verificación de unicidad
     * compara y el que el estudiante debe corregir si su perfil es rechazado.
     */
    protected function registrarTituloPerfil(Tramite $tramite, string $titulo): void
    {
        $hitos = $tramite->hitos ?? [];
        $modulos = $hitos['modulos'] ?? [];
        $perfil = $modulos[$this->moduloPerfil()] ?? [];

        $perfil['titulo'] = $titulo;
        $perfil['titulo_actualizado'] = Carbon::now()->toDateString();

        $modulos[$this->moduloPerfil()] = $perfil;
        $hitos['modulos'] = $modulos;

        $tramite->update(['hitos' => $hitos]);
    }

    /**
     * Título del trabajo registrado en el módulo de perfil del trámite (o null
     * si el trámite aún no lo tiene).
     */
    protected function tituloDePerfil(Tramite $tramite): ?string
    {
        $titulo = $tramite->hitos['modulos'][$this->moduloPerfil()]['titulo'] ?? null;

        return is_string($titulo) && trim($titulo) !== '' ? trim($titulo) : null;
    }

    /**
     * Actualiza el título del perfil y (opcionalmente) los documentos PDF de la
     * fase de solicitud mientras la solicitud sigue en verificación.
     *
     * El estudiante puede corregir su título antes de que KARDEX/Consejo lo
     * revisen: si cambia, se re-verifica la unicidad contra otros trabajos de
     * grado (el propio trámite queda excluido) y se registra en el módulo de
     * perfil. Los PDFs enviados reemplazan a los previos del mismo tipo; los
     * documentos siguen siendo opcionales.
     *
     * @param  array<string, mixed>  $validated  `titulo` y `documentos[...]` opcionales.
     * @return array Trámite formateado para el seguimiento del estudiante.
     *
     * @throws AuthorizationException Si no es su trámite.
     * @throws ValidationException Si el trámite no pertenece a la modalidad, la
     *                             solicitud ya avanzó de `solicitud_presentada`
     *                             o el título está repetido.
     */
    public function actualizarPerfil(array $validated, int $id, User $user): array
    {
        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if (! $tramite->perteneceA($user)) {
            throw new AuthorizationException('No autorizado');
        }

        $this->verificarModalidad($tramite);

        if ($tramite->estado_actual !== self::ESTADO_EDICION_PERFIL) {
            throw ValidationException::withMessages([
                'estado' => 'Solo puede modificar el título y los documentos de su perfil mientras la solicitud está en verificación.',
            ]);
        }

        $titulo = trim((string) ($validated['titulo'] ?? ''));
        $tituloActual = $this->tituloDePerfil($tramite);

        // Solo se re-verifica cuando el estudiante cambia el título: el vigente
        // ya superó la comprobación de unicidad al crearse o al reenviarse.
        if ($tituloActual === null || ! $this->verificacionTitulo->esIgual($titulo, $tituloActual)) {
            $duplicado = $this->verificacionTitulo->duplicado($titulo, (int) $tramite->id_tramite);

            if ($duplicado !== null) {
                throw ValidationException::withMessages([
                    'titulo' => $this->mensajeTituloRepetido($duplicado),
                ]);
            }
        }

        $documentos = collect($validated['documentos'] ?? []);

        DB::transaction(function () use ($tramite, $user, $titulo, $documentos) {
            foreach ($documentos as $documento) {
                $this->documentos->reemplazar(
                    $tramite->id_tramite,
                    $user->id_usuario,
                    $documento['archivo'],
                    $documento['tipo'],
                );
            }

            $this->registrarTituloPerfil($tramite, $titulo);
        });

        $tramite->refresh()->load('modalidad', 'estudiante.user', 'documentos', 'estados.responsable', 'tutor');

        return $this->tramiteService->formatear($tramite);
    }

    /**
     * Mensaje que ve el estudiante cuando su título ya está registrado.
     *
     * @param  array<string, mixed>  $duplicado  Coincidencia exacta devuelta por la verificación.
     */
    protected function mensajeTituloRepetido(array $duplicado): string
    {
        $autor = trim(($duplicado['estudiante']['nombres'] ?? '').' '.($duplicado['estudiante']['apellidos'] ?? ''));

        return 'Ya existe un trabajo de grado registrado con el mismo título de '.$this->etiquetaTitulo().'.'
            .($autor !== '' ? " Corresponde al trabajo de {$autor}." : '')
            .' Presente su perfil con un título diferente.';
    }

    /**
     * Reenvía el perfil corregido después de su rechazo.
     *
     * El estudiante debe presentar un título (nuevo frente al que fue
     * rechazado): es el que KARDEX verificará antes de volver a aprobar el
     * perfil. Se registra el nuevo documento (si se subió), se guarda el título
     * en el módulo de perfil, la solicitud vuelve a evaluación de KARDEX y se
     * dispara el evento de dominio para notificar.
     *
     * @param  string  $titulo  Título propuesto en la corrección.
     * @return array Trámite formateado para el seguimiento del estudiante.
     *
     * @throws AuthorizationException Si no es su trámite.
     * @throws ValidationException Si el trámite no pertenece a la modalidad, no
     *                             está rechazado o el título está repetido.
     */
    public function reenviar(int $id, User $user, ?UploadedFile $perfil, ?string $titulo, ?string $observaciones): array
    {
        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if (! $tramite->perteneceA($user)) {
            throw new AuthorizationException('No autorizado');
        }

        $this->verificarModalidad($tramite);

        if ($tramite->estado_actual !== 'perfil_rechazado') {
            throw ValidationException::withMessages([
                'estado' => 'Solo puede reenviar el perfil cuando KARDEX lo rechazó.',
            ]);
        }

        $titulo = trim((string) $titulo);

        // El título corregido debe ser distinto del que KARDEX rechazó: reenviar
        // el mismo título no corrige nada y dejaría el perfil en el mismo punto.
        $tituloRechazado = $this->tituloDePerfil($tramite);

        if ($tituloRechazado !== null && $this->verificacionTitulo->esIgual($titulo, $tituloRechazado)) {
            throw ValidationException::withMessages([
                'titulo' => 'El título es el mismo que KARDEX rechazó. Presente su perfil con un título de '
                    .$this->etiquetaTitulo().' diferente.',
            ]);
        }

        // El título corregido no puede repetir el de otro trabajo de grado: el
        // propio trámite queda excluido para no compararse consigo mismo.
        $duplicado = $this->verificacionTitulo->duplicado($titulo, (int) $tramite->id_tramite);

        if ($duplicado !== null) {
            throw ValidationException::withMessages([
                'titulo' => $this->mensajeTituloRepetido($duplicado),
            ]);
        }

        $nuevoEstado = $this->estadoReenvio();

        DB::transaction(function () use ($tramite, $user, $perfil, $titulo, $observaciones, $nuevoEstado) {
            if ($perfil) {
                $this->documentos->guardar(
                    $tramite->id_tramite,
                    $user->id_usuario,
                    $perfil,
                    $this->documentoPerfil(),
                );
            }

            $this->registrarTituloPerfil($tramite, $titulo);

            $this->stateService->transicionar(
                $tramite,
                $nuevoEstado,
                $observaciones ?? 'Perfil corregido y reenviado por el estudiante.',
                $user->id_usuario
            );
        });

        $tramite->refresh()->load('modalidad', 'estudiante.user', 'documentos', 'estados.responsable', 'tutor');

        event(new EstadoTramiteCambiado($tramite, $nuevoEstado, $observaciones));

        return $this->tramiteService->formatear($tramite);
    }

    /**
     * Reenvía el documento final corregido después de una calificación
     * insuficiente de la Comisión Revisora.
     *
     * Registra el nuevo documento (si se subió) y devuelve el trámite a la
     * Comisión Revisora para una nueva evaluación.
     *
     * @return array Trámite formateado para el seguimiento del estudiante.
     *
     * @throws AuthorizationException Si no es su trámite.
     * @throws ValidationException Si el trámite no pertenece a la modalidad o no
     *                             está calificado como insuficiente.
     */
    public function reenviarDocumentoFinal(int $id, User $user, ?UploadedFile $documento, ?string $observaciones): array
    {
        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if (! $tramite->perteneceA($user)) {
            throw new AuthorizationException('No autorizado');
        }

        $this->verificarModalidad($tramite);

        if ($tramite->estado_actual !== 'insuficiente') {
            throw ValidationException::withMessages([
                'estado' => 'Solo puede reenviar su documento cuando la Comisión Revisora lo calificó como insuficiente.',
            ]);
        }

        $nuevoEstado = $this->estadoReenvioDocumento();

        DB::transaction(function () use ($tramite, $user, $documento, $observaciones, $nuevoEstado) {
            if ($documento) {
                $this->documentos->guardar(
                    $tramite->id_tramite,
                    $user->id_usuario,
                    $documento,
                    self::DOC_FINAL,
                );
            }

            $this->stateService->transicionar(
                $tramite,
                $nuevoEstado,
                $observaciones ?? 'Documento final corregido y reenviado por el estudiante.',
                $user->id_usuario
            );
        });

        $tramite->refresh()->load('modalidad', 'estudiante.user', 'documentos', 'estados.responsable', 'tutor');

        event(new EstadoTramiteCambiado($tramite, $nuevoEstado, $observaciones));

        return $this->tramiteService->formatear($tramite);
    }

    /**
     * Sube el trabajo de investigación (publicación) tras aprobar la defensa.
     *
     * El documento queda registrado en el módulo de publicación, pero el trámite
     * NO avanza: KARDEX valida la publicación desde su flujo para cerrarlo
     * (`publicado`).
     *
     * @return array Trámite formateado para el seguimiento del estudiante.
     *
     * @throws AuthorizationException Si no es su trámite.
     * @throws ValidationException Si el trámite no pertenece a la modalidad o no
     *                             está en `defensa_aprobada`.
     */
    public function subirPublicacion(int $id, User $user, UploadedFile $documento, ?string $observaciones): array
    {
        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if (! $tramite->perteneceA($user)) {
            throw new AuthorizationException('No autorizado');
        }

        $this->verificarModalidad($tramite);

        if ($tramite->estado_actual !== self::ESTADO_PUBLICACION) {
            throw ValidationException::withMessages([
                'estado' => 'Solo puede subir su trabajo de investigación cuando la defensa fue aprobada.',
            ]);
        }

        DB::transaction(function () use ($tramite, $user, $documento, $observaciones) {
            $this->documentos->guardar(
                $tramite->id_tramite,
                $user->id_usuario,
                $documento,
                self::DOC_PUBLICACION,
            );

            $hitos = $tramite->hitos ?? [];
            $modulos = $hitos['modulos'] ?? [];
            $modulos[self::MODULO_PUBLICACION] = array_replace(
                $modulos[self::MODULO_PUBLICACION] ?? [],
                [
                    'archivo' => [
                        'nombre' => $documento->getClientOriginalName(),
                        'tamanio' => round($documento->getSize() / 1024, 2),
                    ],
                    'fecha_publicacion' => Carbon::now()->toDateString(),
                    'observaciones' => $observaciones,
                ],
            );
            $hitos['modulos'] = $modulos;
            $tramite->update(['hitos' => $hitos]);
        });

        $tramite->refresh()->load('modalidad', 'estudiante.user', 'documentos', 'estados.responsable', 'tutor');

        return $this->tramiteService->formatear($tramite);
    }

    /**
     * Asegura que el trámite pertenezca a la modalidad que atiende el servicio.
     *
     * @throws ValidationException
     */
    protected function verificarModalidad(Tramite $tramite): void
    {
        if (($tramite->modalidad->nombre ?? null) !== $this->modalidad()) {
            throw ValidationException::withMessages([
                'modalidad' => 'Este trámite no corresponde a la modalidad de '.$this->modalidad().'.',
            ]);
        }
    }

    /**
     * Indica si el estudiante ya puede volver a presentar una solicitud tras
     * ser reprobado: cuando venció el plazo de correcciones (90 días) o el
     * plazo máximo de presentación (365 días / 3-12 meses).
     */
    protected function puedeReoptar(Tramite $tramite): bool
    {
        $hoy = Carbon::now()->startOfDay();

        foreach ($this->fechasReoptar($tramite) as $fecha) {
            if ($hoy->gte(Carbon::parse($fecha))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fecha (YYYY-MM-DD) desde la cual el estudiante quedó habilitado a volver
     * a presentar su solicitud, o null si ya está habilitado.
     */
    protected function proximaReoptar(Tramite $tramite): ?string
    {
        $hoy = Carbon::now()->startOfDay();
        $pendientes = array_filter(
            $this->fechasReoptar($tramite),
            fn (string $fecha) => $hoy->lt(Carbon::parse($fecha))
        );

        if (empty($pendientes)) {
            return null;
        }

        return min($pendientes);
    }

    /**
     * Fechas límite que habilitan la re-opción de modalidad tras un reprobado:
     * el cierre del plazo de correcciones (90 días) y el cierre del plazo máximo
     * de presentación (365 días). Se leen de los hitos con fallback a partir de
     * la fecha de reprobación.
     *
     * @return array<int, string>
     */
    protected function fechasReoptar(Tramite $tramite): array
    {
        $hitos = $tramite->hitos ?? [];
        $reprobado = isset($hitos['reprobado'])
            ? Carbon::parse($hitos['reprobado'])->startOfDay()
            : null;

        $limiteCorreccion = $hitos['limite_correccion'] ?? ($reprobado
            ? $reprobado->copy()->addDays((int) config($this->claveConfig().'.dias_correccion'))->toDateString()
            : null);

        $limitePresentacion = $hitos['limite_presentacion'] ?? ($reprobado
            ? $reprobado->copy()->addDays((int) config($this->claveConfig().'.dias_remodalidad'))->toDateString()
            : null);

        return array_values(array_filter([$limiteCorreccion, $limitePresentacion]));
    }
}
