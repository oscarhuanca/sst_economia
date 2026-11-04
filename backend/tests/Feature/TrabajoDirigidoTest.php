<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Modalidad;
use App\Models\Tramite;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Flujo del estudiante en el MÓDULO DE TRABAJO DIRIGIDO.
 *
 * El Trabajo Dirigido comparte con la Tesis de Grado el flujo de titulación por
 * módulos: la diferencia está en el primer módulo (Perfil de Proyecto en lugar
 * de Perfil de Tesis) y en las etiquetas. Estas pruebas verifican que el
 * estudiante presenta su trabajo dirigido, que KARDEX aprueba o rechaza el
 * perfil de proyecto, que tras el rechazo debe presentar un título diferente y
 * que puede solicitar la fecha de su defensa.
 */
class TrabajoDirigidoTest extends TestCase
{
    use RefreshDatabase;

    private const TITULO = 'Impacto del reciclaje de residuos organicos en suelos andinos';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_el_estudiante_registra_el_titulo_del_trabajo_dirigido_con_su_solicitud(): void
    {
        $estudiante = $this->crearEstudianteConCuenta();
        $this->crearModalidadTrabajoDirigido();

        $response = $this->actingAs($estudiante->user)
            ->postJson('/api/trabajo-dirigido/solicitud', ['titulo' => self::TITULO]);

        $response->assertCreated();

        $tramite = Tramite::where('id_estudiante', $estudiante->id_estudiante)->firstOrFail();

        $this->assertSame(
            self::TITULO,
            $tramite->hitos['modulos']['perfil_proyecto']['titulo'],
        );
        $this->assertSame('Trabajo Dirigido', $tramite->modalidad->nombre);
    }

    public function test_la_solicitud_del_trabajo_dirigido_admite_titulo_sin_documentos_pdf(): void
    {
        $estudiante = $this->crearEstudianteConCuenta();
        $this->crearModalidadTrabajoDirigido();

        $this->actingAs($estudiante->user)
            ->postJson('/api/trabajo-dirigido/solicitud', ['titulo' => self::TITULO])
            ->assertCreated();

        $tramite = Tramite::where('id_estudiante', $estudiante->id_estudiante)->firstOrFail();

        $this->assertCount(0, $tramite->documentos);
    }

    public function test_la_solicitud_del_trabajo_dirigido_acepta_los_documentos_pdf_opcionales(): void
    {
        $estudiante = $this->crearEstudianteConCuenta();
        $this->crearModalidadTrabajoDirigido();

        $documentos = [];

        foreach (array_keys(config('trabajodirigido.tipos_documento')) as $tipo) {
            $documentos[] = [
                'archivo' => UploadedFile::fake()->create("{$tipo}.pdf", 100, 'application/pdf'),
                'tipo' => $tipo,
            ];
        }

        $this->actingAs($estudiante->user)
            ->postJson('/api/trabajo-dirigido/solicitud', [
                'titulo' => self::TITULO,
                'documentos' => $documentos,
            ])
            ->assertCreated();

        $tramite = Tramite::where('id_estudiante', $estudiante->id_estudiante)->firstOrFail();

        $this->assertCount(3, $tramite->documentos);
    }

    public function test_no_se_acepta_un_titulo_ya_registrado_por_otro_postulante(): void
    {
        $this->crearModalidadTrabajoDirigido();
        $otro = $this->crearTramiteConTitulo(self::TITULO);
        $estudiante = $this->crearEstudianteConCuenta();

        $response = $this->actingAs($estudiante->user)
            ->postJson('/api/trabajo-dirigido/solicitud', ['titulo' => self::TITULO]);

        $response->assertStatus(400);
        $response->assertJsonPath('message', fn (string $mensaje) => str_contains($mensaje, 'mismo título'));

        $this->assertSame(1, Tramite::count());
        $this->assertSame($otro->id_tramite, Tramite::first()->id_tramite);
    }

    public function test_kardex_aproba_el_perfil_de_proyecto(): void
    {
        $this->crearModalidadTrabajoDirigido();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $this->crearEstudianteConCuenta());
        $kardex = $this->crearUsuarioGestion();

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/perfil_proyecto", [
                'datos' => $this->datosApruebaPerfil(self::TITULO),
            ])
            ->assertCreated();

        $tramite->refresh();

        $this->assertSame('perfil_aprobado', $tramite->estado_actual);
        $this->assertSame(self::TITULO, $tramite->hitos['modulos']['perfil_proyecto']['titulo']);
    }

    public function test_kardex_rechaza_el_perfil_de_proyecto_y_el_estudiante_debe_reevaluar(): void
    {
        $this->crearModalidadTrabajoDirigido();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $this->crearEstudianteConCuenta());
        $kardex = $this->crearUsuarioGestion();

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/perfil_proyecto", [
                'datos' => [
                    'accion' => 'rechazar',
                    'observaciones' => 'El alcance del trabajo no corresponde a un trabajo dirigido.',
                ],
            ])
            ->assertCreated();

        $tramite->refresh();

        $this->assertSame('perfil_rechazado', $tramite->estado_actual);
        $this->assertSame(
            'El alcance del trabajo no corresponde a un trabajo dirigido.',
            $tramite->estados()->latest('id_estado')->first()->observaciones,
        );
    }

    public function test_kardex_debe_observar_el_rechazo_del_perfil_de_proyecto(): void
    {
        $this->crearModalidadTrabajoDirigido();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $this->crearEstudianteConCuenta());
        $kardex = $this->crearUsuarioGestion();

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/perfil_proyecto", [
                'datos' => [
                    'accion' => 'rechazar',
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('pendiente_concejo_universitario', $tramite->refresh()->estado_actual);
    }

    public function test_el_estudiante_reenvia_el_perfil_rechazado_con_un_titulo_diferente(): void
    {
        $this->crearModalidadTrabajoDirigido();
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $estudiante);
        $tramite->update(['estado_actual' => 'perfil_rechazado']);

        $nuevoTitulo = 'Recuperacion de suelos degradados mediante compostaje decentralized';

        $this->actingAs($estudiante->user)
            ->postJson("/api/trabajo-dirigido/{$tramite->id_tramite}/reenviar", [
                'titulo' => $nuevoTitulo,
            ])
            ->assertOk();

        $tramite->refresh();

        $this->assertSame('pendiente_concejo_universitario', $tramite->estado_actual);
        $this->assertSame($nuevoTitulo, $tramite->hitos['modulos']['perfil_proyecto']['titulo']);
    }

    public function test_el_estudiante_no_puede_reenviar_el_mismo_titulo_que_kardex_rechazo(): void
    {
        $this->crearModalidadTrabajoDirigido();
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $estudiante);
        $tramite->update(['estado_actual' => 'perfil_rechazado']);

        $this->actingAs($estudiante->user)
            ->postJson("/api/trabajo-dirigido/{$tramite->id_tramite}/reenviar", [
                'titulo' => self::TITULO,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('titulo');

        $this->assertSame('perfil_rechazado', $tramite->refresh()->estado_actual);
    }

    public function test_el_estudiante_corrige_el_titulo_del_perfil_mientras_la_solicitud_esta_en_verificacion(): void
    {
        $this->crearModalidadTrabajoDirigido();
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $estudiante);
        $tramite->update(['estado_actual' => 'solicitud_presentada']);

        $nuevoTitulo = 'Composicion del suelo y productividad de papa en la meseta de Huancavelica';

        $this->actingAs($estudiante->user)
            ->postJson("/api/trabajo-dirigido/{$tramite->id_tramite}/perfil", [
                'titulo' => $nuevoTitulo,
            ])
            ->assertOk();

        $this->assertSame($nuevoTitulo, $tramite->refresh()->hitos['modulos']['perfil_proyecto']['titulo']);
    }

    public function test_el_estudiante_solicita_la_fecha_de_defensa_del_trabajo_dirigido(): void
    {
        $this->crearModalidadTrabajoDirigido();
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $estudiante);
        $tramite->update(['estado_actual' => 'suficiente']);

        $this->actingAs($estudiante->user)
            ->postJson("/api/trabajo-dirigido/{$tramite->id_tramite}/solicitar-fecha-defensa")
            ->assertOk();

        $tramite->refresh();

        $this->assertSame('solicitud_fecha_defensa', $tramite->estado_actual);
        $this->assertArrayHasKey('fecha_defensa_solicitada', $tramite->hitos);
    }

    public function test_el_estudiante_no_puede_solicitar_fecha_de_defensa_sin_el_veredicto_suficiente(): void
    {
        $this->crearModalidadTrabajoDirigido();
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = $this->crearTramiteConTitulo(self::TITULO, $estudiante);
        $tramite->update(['estado_actual' => 'perfil_aprobado']);

        $this->actingAs($estudiante->user)
            ->postJson("/api/trabajo-dirigido/{$tramite->id_tramite}/solicitar-fecha-defensa")
            ->assertStatus(422);

        $this->assertSame('perfil_aprobado', $tramite->refresh()->estado_actual);
    }

    public function test_la_configuracion_del_modulo_expone_plazos_y_documentos(): void
    {
        $estudiante = $this->crearEstudianteConCuenta();

        $this->actingAs($estudiante->user)
            ->getJson('/api/trabajo-dirigido/config')
            ->assertOk()
            ->assertJsonPath('dias_correccion', 90)
            ->assertJsonPath('dias_remodalidad', 365)
            ->assertJsonPath('tipos_documento.perfil_proyecto', 'Perfil de Proyecto')
            ->assertJsonPath('estado_reenvio', 'pendiente_concejo_universitario');
    }

    public function test_un_tramite_de_tesis_no_puede_gestionarse_como_trabajo_dirigido(): void
    {
        Modalidad::factory()->tesis()->create();
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = Tramite::factory()->create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_modalidad' => Modalidad::where('nombre', 'Tesis de Grado')->value('id_modalidad'),
            'estado_actual' => 'suficiente',
        ]);

        $this->actingAs($estudiante->user)
            ->postJson("/api/trabajo-dirigido/{$tramite->id_tramite}/solicitar-fecha-defensa")
            ->assertStatus(422);
    }

    /**
     * Datos con los que KARDEX aprueba el perfil de proyecto (título y
     * documentos verificados).
     *
     * @return array<string, mixed>
     */
    private function datosApruebaPerfil(string $titulo): array
    {
        $documentos = [];

        foreach (array_keys(config('trabajodirigido.tipos_documento')) as $tipo) {
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

    private function crearModalidadTrabajoDirigido(): Modalidad
    {
        return Modalidad::factory()->trabajoDirigido()->create();
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
     * Trámite de trabajo dirigido con el título ya registrado en su módulo de
     * perfil.
     */
    private function crearTramiteConTitulo(string $titulo, ?Estudiante $estudiante = null): Tramite
    {
        $estudiante ??= $this->crearEstudianteConCuenta();

        return Tramite::factory()->create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_modalidad' => Modalidad::where('nombre', 'Trabajo Dirigido')->value('id_modalidad'),
            'estado_actual' => 'pendiente_concejo_universitario',
            'hitos' => [
                'solicitud_presentada' => now()->toDateString(),
                'modulos' => [
                    'perfil_proyecto' => [
                        'titulo' => $titulo,
                        'titulo_actualizado' => now()->toDateString(),
                    ],
                ],
            ],
        ]);
    }
}
