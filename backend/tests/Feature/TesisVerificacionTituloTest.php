<?php

namespace Tests\Feature;

use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Modalidad;
use App\Models\Notificacion;
use App\Models\Tramite;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Verificación de títulos en el MÓDULO 1 del flujo de titulación (Perfil de
 * Tesis): el estudiante presenta su título, KARDEX lo aprueba o lo rechaza, y
 * tras el rechazo el estudiante debe presentar un título diferente.
 */
class TesisVerificacionTituloTest extends TestCase
{
    use RefreshDatabase;

    private const TITULO = 'Sistemas de riego eficientes para cultivos de altura en el altiplano';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_el_estudiante_registra_el_titulo_con_su_solicitud(): void
    {
        $estudiante = $this->crearEstudianteConCuenta();
        $this->crearModalidadTesis();

        $response = $this->actingAs($estudiante->user)
            ->postJson('/api/tesis/solicitud', $this->datosSolicitud(self::TITULO));

        $response->assertCreated();

        $tramite = Tramite::where('id_estudiante', $estudiante->id_estudiante)->firstOrFail();

        $this->assertSame(
            self::TITULO,
            $tramite->hitos['modulos']['perfil_tesis']['titulo'],
        );
    }

    public function test_no_se_acepta_un_titulo_ya_registrado_por_otro_postulante(): void
    {
        $this->crearModalidadTesis();
        $otro = $this->crearTramiteConTitulo(self::TITULO);
        $estudiante = $this->crearEstudianteConCuenta();

        $response = $this->actingAs($estudiante->user)
            ->postJson('/api/tesis/solicitud', $this->datosSolicitud(self::TITULO));

        $response->assertStatus(400);
        $this->assertStringContainsString('mismo título', $response->json('message'));
        $this->assertDatabaseCount('tramites', 1);
        $this->assertNotNull($otro);
    }

    public function test_kardex_rechaza_el_perfil_y_el_estudiante_reenvia_otro_titulo(): void
    {
        $this->crearModalidadTesis();
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $estudiante);
        $kardex = $this->crearUsuarioGestion();

        // Rechazar el perfil exige la observación del motivo.
        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/perfil_tesis", [
                'datos' => ['accion' => 'rechazar'],
            ])
            ->assertStatus(422);

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/perfil_tesis", [
                'datos' => [
                    'accion' => 'rechazar',
                    'observaciones' => 'El título repite un trabajo ya registrado. Presente otro.',
                ],
            ])
            ->assertCreated();

        $tramite->refresh();
        $this->assertSame('perfil_rechazado', $tramite->estado_actual);
        $this->assertSame(
            self::TITULO,
            $tramite->hitos['modulos']['perfil_tesis']['titulo'],
            'Al rechazar se conserva el título presentado por el estudiante.',
        );
        $this->assertStringContainsString(
            'El título repite un trabajo ya registrado',
            (string) $tramite->estados()->latest('id_estado')->first()->observaciones,
        );

        // El mismo título no sirve para corregir: debe ser otro.
        $this->actingAs($estudiante->user)
            ->postJson("/api/tesis/{$tramite->id_tramite}/reenviar", ['titulo' => self::TITULO])
            ->assertStatus(422)
            ->assertJsonValidationErrors('titulo');

        $nuevoTitulo = 'Modelo predictivo de demanda eléctrica en campuses universitarios';

        $this->actingAs($estudiante->user)
            ->postJson("/api/tesis/{$tramite->id_tramite}/reenviar", [
                'titulo' => $nuevoTitulo,
                'perfil' => UploadedFile::fake()->create('perfil.pdf', 120, 'application/pdf'),
                'observaciones' => 'Perfil corregido.',
            ])
            ->assertOk();

        $tramite->refresh();
        $this->assertSame('pendiente_concejo_universitario', $tramite->estado_actual);
        $this->assertSame($nuevoTitulo, $tramite->hitos['modulos']['perfil_tesis']['titulo']);
    }

    public function test_el_estudiante_recibe_la_observacion_del_rechazo(): void
    {
        $this->crearModalidadTesis();
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $estudiante);

        $this->actingAs($this->crearUsuarioGestion())
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/perfil_tesis", [
                'datos' => [
                    'accion' => 'rechazar',
                    'observaciones' => 'El título repite un trabajo ya registrado.',
                ],
            ])
            ->assertCreated();

        $notificacion = Notificacion::where('id_usuario', $estudiante->user->id_usuario)->latest('id_notificacion')->first();

        $this->assertNotNull($notificacion, 'El estudiante debe ser notificado del rechazo.');
        $this->assertStringContainsString('Perfil de tesis rechazado', (string) $notificacion->titulo);
        $this->assertStringContainsString('El título repite un trabajo ya registrado.', (string) $notificacion->mensaje);
    }

    public function test_kardex_no_puede_aprobar_un_perfil_con_titulo_duplicado(): void
    {
        $this->crearModalidadTesis();
        $this->crearTramiteConTitulo(self::TITULO);
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $this->crearEstudianteConCuenta());
        $kardex = $this->crearUsuarioGestion();

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/perfil_tesis", [
                'datos' => $this->datosApruebaPerfil(self::TITULO),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('pendiente_concejo_universitario', $tramite->refresh()->estado_actual);
    }

    public function test_kardex_aproba_el_perfil_y_el_titulo_no_se_compara_consigo_mismo(): void
    {
        $this->crearModalidadTesis();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $this->crearEstudianteConCuenta());
        $kardex = $this->crearUsuarioGestion();

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/perfil_tesis", [
                'datos' => $this->datosApruebaPerfil(self::TITULO),
            ])
            ->assertCreated();

        $tramite->refresh();
        $this->assertSame('perfil_aprobado', $tramite->estado_actual);
        $this->assertSame(self::TITULO, $tramite->hitos['modulos']['perfil_tesis']['titulo']);
    }

    public function test_la_aprobacion_del_tema_guarda_opcionalmente_la_resolucion_de_designacion_del_tutor(): void
    {
        $this->crearModalidadTesis();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $this->crearEstudianteConCuenta());
        $tramite->update(['estado_actual' => 'perfil_aprobado']);

        $docente = Docente::create([
            'ci' => '1234567',
            'nombre' => 'Julia',
            'apellidos' => 'Paredes Villca',
        ]);

        $this->actingAs($this->crearUsuarioGestion())
            ->post("/api/tramites/{$tramite->id_tramite}/flujo/aprobacion_tema", [
                'datos' => [
                    'numero_resolucion' => 'HCC-0102/2026',
                    'fecha_resolucion' => now()->toDateString(),
                    'tema_investigacion' => 'Sistemas de riego eficientes en el altiplano',
                    'tutor_id' => $docente->id_docente,
                ],
                'resolucion_tutor' => UploadedFile::fake()->create('resolucion_tutor.pdf', 100, 'application/pdf'),
            ])
            ->assertCreated();

        $tramite->refresh();
        $resolucion = $tramite->hitos['modulos']['aprobacion_tema']['resolucion_tutor'];

        $this->assertSame('tema_aprobado', $tramite->estado_actual);
        $this->assertSame($docente->id_docente, $tramite->id_tutor);
        $this->assertSame('resolucion_tutor.pdf', $resolucion['nombre']);
        $this->assertTrue(Storage::disk('public')->exists($resolucion['ruta']));
    }

    public function test_el_tribunal_revisor_exige_confirmar_las_notas_de_suficiencia_y_guarda_sus_pdf_opcionales(): void
    {
        $this->crearModalidadTesis();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $this->crearEstudianteConCuenta());
        $tramite->update(['estado_actual' => 'tema_aprobado']);

        $tribunal = [
            ['rol' => 'presidente', 'nombre' => 'Ana Rojas'],
            ['rol' => 'vocal', 'nombre' => 'Luis Mamani'],
            ['rol' => 'secretario', 'nombre' => 'Carla Quispe'],
        ];

        $datos = [
            'numero_resolucion' => 'HCC-0201/2026',
            'fecha_resolucion' => now()->toDateString(),
            'tribunal' => $tribunal,
        ];

        $this->actingAs($this->crearUsuarioGestion())
            ->post("/api/tramites/{$tramite->id_tramite}/flujo/tribunal_revisor", ['datos' => $datos])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos')
            ->assertJsonFragment(['Confirme que se cuenta con la Resolución de designación del tribunal revisor.']);

        $this->assertSame('tema_aprobado', $tramite->refresh()->estado_actual);

        $datos['nota_suficiencia_tutor_entregada'] = true;
        $datos['resolucion_tribunal_entregada'] = true;

        $this->actingAs($this->crearUsuarioGestion())
            ->post("/api/tramites/{$tramite->id_tramite}/flujo/tribunal_revisor", ['datos' => $datos])
            ->assertStatus(422)
            ->assertJsonFragment(['Confirme que el Vocal entregó su Nota de Suficiencia.']);

        $this->assertSame('tema_aprobado', $tramite->refresh()->estado_actual);

        $datos['tribunal'] = array_map(
            fn (array $miembro) => $miembro + ['nota_suficiencia_entregada' => true],
            $tribunal,
        );

        $this->actingAs($this->crearUsuarioGestion())
            ->post("/api/tramites/{$tramite->id_tramite}/flujo/tribunal_revisor", [
                'datos' => $datos,
                'resolucion_tribunal' => UploadedFile::fake()->create('resolucion_tribunal.pdf', 100, 'application/pdf'),
                'nota_suficiencia_tutor' => UploadedFile::fake()->create('nota_tutor.pdf', 100, 'application/pdf'),
                'nota_suficiencia_presidente' => UploadedFile::fake()->create('nota_presidente.pdf', 100, 'application/pdf'),
                'nota_suficiencia_vocal' => UploadedFile::fake()->create('nota_vocal.pdf', 100, 'application/pdf'),
                'nota_suficiencia_secretario' => UploadedFile::fake()->create('nota_secretario.pdf', 100, 'application/pdf'),
            ])
            ->assertCreated();

        $tramite->refresh();
        $modulo = $tramite->hitos['modulos']['tribunal_revisor'];

        $this->assertSame('tribunal_asignado', $tramite->estado_actual);
        $this->assertTrue((bool) $modulo['resolucion_tribunal_entregada']);
        $this->assertSame('resolucion_tribunal.pdf', $modulo['resolucion_tribunal']['nombre']);
        $this->assertTrue(Storage::disk('public')->exists($modulo['resolucion_tribunal']['ruta']));
        $this->assertTrue((bool) $modulo['nota_suficiencia_tutor_entregada']);
        $this->assertSame('nota_tutor.pdf', $modulo['nota_suficiencia_tutor']['nombre']);
        $this->assertTrue(Storage::disk('public')->exists($modulo['nota_suficiencia_tutor']['ruta']));

        $archivos = [
            'presidente' => 'nota_presidente.pdf',
            'vocal' => 'nota_vocal.pdf',
            'secretario' => 'nota_secretario.pdf',
        ];

        foreach ($archivos as $rol => $nombre) {
            $miembro = collect($modulo['tribunal'])->firstWhere('rol', $rol);

            $this->assertNotNull($miembro);
            $this->assertTrue((bool) $miembro['nota_suficiencia_entregada']);
            $this->assertSame($nombre, $miembro['nota_suficiencia']['nombre']);
            $this->assertTrue(Storage::disk('public')->exists($miembro['nota_suficiencia']['ruta']));
        }
    }

    public function test_la_solicitud_acepta_el_titulo_sin_documentos_pdf(): void
    {
        $estudiante = $this->crearEstudianteConCuenta();
        $this->crearModalidadTesis();

        $response = $this->actingAs($estudiante->user)
            ->postJson('/api/tesis/solicitud', ['titulo' => self::TITULO]);

        $response->assertCreated();

        $tramite = Tramite::where('id_estudiante', $estudiante->id_estudiante)->firstOrFail();
        $this->assertSame(self::TITULO, $tramite->hitos['modulos']['perfil_tesis']['titulo']);
        $this->assertCount(0, $tramite->documentos);
    }

    public function test_kardex_aproba_el_perfil_sin_pdf_adjunto(): void
    {
        $this->crearModalidadTesis();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $this->crearEstudianteConCuenta());
        $kardex = $this->crearUsuarioGestion();
        $datos = $this->datosApruebaPerfil(self::TITULO);

        // La verificación es obligatoria, pero el PDF adjunto es opcional.
        foreach ($datos['documentos'] as $tipo => $_) {
            $datos['documentos'][$tipo]['archivo'] = null;
        }

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/perfil_tesis", [
                'datos' => $datos,
            ])
            ->assertCreated();

        $this->assertSame('perfil_aprobado', $tramite->refresh()->estado_actual);
    }

    public function test_la_verificacion_de_kardex_omite_el_tramite_consultado(): void
    {
        $this->crearModalidadTesis();
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $estudiante);
        $kardex = $this->crearUsuarioGestion();

        $this->actingAs($kardex)
            ->getJson('/api/kardex/titulos-tesis?'.http_build_query([
                'titulo' => self::TITULO,
                'excluir_tramite' => $tramite->id_tramite,
            ]))
            ->assertOk()
            ->assertJsonPath('verificacion.unico', true);
    }

    public function test_el_estudiante_guarda_el_perfil_con_titulo_de_espacios_internos(): void
    {
        $this->crearModalidadTesis();
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = $this->crearTramiteEditable(self::TITULO, $estudiante);

        $nuevoTitulo = 'Modelo  predictivo   de demanda   electrica en campus';

        // Se envía por POST (no PUT): PHP no parsea multipart en PUT, así que el
        // título llegaría vacío y sería rechazado por error.
        $this->actingAs($estudiante->user)
            ->post("/api/tesis/{$tramite->id_tramite}/perfil", ['titulo' => $nuevoTitulo])
            ->assertOk();

        $this->assertSame(
            $nuevoTitulo,
            $tramite->refresh()->hitos['modulos']['perfil_tesis']['titulo'],
            'El título con espacios internos se conserva tal cual.',
        );
    }

    public function test_el_estudiante_guarda_el_perfil_recortando_espacios_extremos(): void
    {
        $this->crearModalidadTesis();
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = $this->crearTramiteEditable(self::TITULO, $estudiante);

        $this->actingAs($estudiante->user)
            ->post("/api/tesis/{$tramite->id_tramite}/perfil", [
                'titulo' => '   Modelo predictivo de demanda electrica   ',
            ])
            ->assertOk();

        $this->assertSame(
            'Modelo predictivo de demanda electrica',
            $tramite->refresh()->hitos['modulos']['perfil_tesis']['titulo'],
            'Los espacios extremos se recortan antes de guardar.',
        );
    }

    public function test_no_se_acepta_un_titulo_de_solo_espacios_en_el_perfil(): void
    {
        $this->crearModalidadTesis();
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = $this->crearTramiteEditable(self::TITULO, $estudiante);

        $this->actingAs($estudiante->user)
            ->postJson("/api/tesis/{$tramite->id_tramite}/perfil", ['titulo' => '               '])
            ->assertJsonValidationErrors('titulo');

        $this->assertSame(
            self::TITULO,
            $tramite->refresh()->hitos['modulos']['perfil_tesis']['titulo'],
            'El título previo no se pierde cuando el nuevo es solo espacios.',
        );
    }

    /**
     * Datos de la fase de solicitud: título y los 3 documentos en PDF
     * (opcionales, aquí se adjuntan los tres).
     *
     * @return array<string, mixed>
     */
    private function datosSolicitud(string $titulo): array
    {
        $documentos = [];

        foreach (array_keys(config('tesis.tipos_documento')) as $tipo) {
            $documentos[] = [
                'archivo' => UploadedFile::fake()->create("{$tipo}.pdf", 100, 'application/pdf'),
                'tipo' => $tipo,
            ];
        }

        return ['titulo' => $titulo, 'documentos' => $documentos];
    }

    /**
     * Datos con los que KARDEX aprueba el perfil (título y documentos verificados).
     *
     * @return array<string, mixed>
     */
    private function datosApruebaPerfil(string $titulo): array
    {
        $documentos = [];

        foreach (array_keys(config('tesis.tipos_documento')) as $tipo) {
            $documentos[$tipo] = [
                'marcado' => true,
                'archivo' => ['nombre' => "{$tipo}.pdf", 'tamanio' => 100],
            ];
        }

        return [
            'accion' => 'aprobar',
            'titulo' => $titulo,
            'documentos' => $documentos,
            'observaciones' => 'Documentación verificada.',
        ];
    }

    private function crearModalidadTesis(): Modalidad
    {
        return Modalidad::factory()->tesis()->create();
    }

    private function crearEstudianteConCuenta(): Estudiante
    {
        return Estudiante::factory()->conCuenta()->create();
    }

    private function crearUsuarioGestion(): User
    {
        return User::factory()->role(Roles::GESTION[0])->create();
    }

    /**
     * Trámite de tesis con el título ya registrado en su módulo de perfil.
     */
    private function crearTramiteConTitulo(string $titulo, ?Estudiante $estudiante = null): Tramite
    {
        $estudiante ??= $this->crearEstudianteConCuenta();

        return Tramite::factory()->create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_modalidad' => Modalidad::where('nombre', 'Tesis de Grado')->value('id_modalidad'),
            'estado_actual' => 'pendiente_concejo_universitario',
            'hitos' => [
                'solicitud_presentada' => now()->toDateString(),
                'modulos' => [
                    'perfil_tesis' => [
                        'titulo' => $titulo,
                        'titulo_actualizado' => now()->toDateString(),
                    ],
                ],
            ],
        ]);
    }

    /**
     * Trámite de tesis editable por el estudiante (`solicitud_presentada`), que
     * es el estado en el que puede corregir su título vía PUT /tesis/{id}/perfil.
     */
    private function crearTramiteEditable(string $titulo, ?Estudiante $estudiante = null): Tramite
    {
        $tramite = $this->crearTramiteConTitulo($titulo, $estudiante);
        $tramite->update(['estado_actual' => 'solicitud_presentada']);

        return $tramite;
    }
}
