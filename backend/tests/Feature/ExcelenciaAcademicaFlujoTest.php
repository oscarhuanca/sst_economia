<?php

namespace Tests\Feature;

use App\Models\Docente;
use App\Models\DocumentoAdjunto;
use App\Models\Estudiante;
use App\Models\Modalidad;
use App\Models\Tramite;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExcelenciaAcademicaFlujoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_el_estudiante_presenta_la_solicitud_con_los_tres_documentos(): void
    {
        $this->crearModalidad();
        $estudiante = $this->crearEstudianteConCuenta();

        $respuesta = $this->actingAs($this->usuarioDe($estudiante))
            ->post('/api/excelencia/solicitud', ['documentos' => $this->documentos()])
            ->assertCreated();

        $this->assertSame('Excelencia Académica', $respuesta->json('modalidad.nombre'));
        $this->assertSame('solicitud_presentada', $respuesta->json('estado_actual'));
        $this->assertCount(3, $respuesta->json('documentos'));

        $tramite = Tramite::where('id_estudiante', $estudiante->id_estudiante)->firstOrFail();

        $this->assertSame(
            ['certificado_calificaciones', 'nota_solicitud', 'record_academico'],
            $tramite->documentos->pluck('tipo_documento')->sort()->values()->all()
        );
        $this->assertSame(Carbon::today()->toDateString(), $tramite->hitos['solicitud_presentada'] ?? null);
    }

    public function test_kardex_verifica_los_pdf_y_pasa_al_modulo_de_excelencia(): void
    {
        $tramite = $this->crearTramiteExcelencia(conDocumentos: true);
        $kardex = $this->crearUsuarioGestion();
        $documentos = [];

        foreach (array_keys(config('excelencia.tipos_documento')) as $tipo) {
            $documentos[$tipo] = true;
        }

        $respuesta = $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/excelencia/recepcion-documentos", [
                'datos' => ['documentos' => $documentos, 'observaciones' => 'PDF cotejados con el físico.'],
            ])
            ->assertOk();

        $this->assertCount(3, $respuesta->json('documentos'));
        $this->assertSame('solicitud_presentada', $tramite->refresh()->estado_actual);
        $this->assertTrue($tramite->hitos['recepcion_documentos']['verificado']);
        $this->assertSame($kardex->id_usuario, $tramite->hitos['recepcion_documentos']['id_usuario_verifico']);
        $this->assertSame('PDF cotejados con el físico.', $tramite->hitos['recepcion_documentos']['observaciones']);

        foreach (array_keys(config('excelencia.tipos_documento')) as $tipo) {
            $this->assertTrue($tramite->hitos['recepcion_documentos']['documentos'][$tipo]['marcado']);
        }

        $this->designar($kardex, $tramite);
        $this->assertSame('tribunal_designado', $tramite->refresh()->estado_actual);
    }

    public function test_no_se_pueden_verificar_pdf_sin_todos_los_tipos_requeridos(): void
    {
        $tramite = $this->crearTramiteExcelencia(conDocumentos: true);
        $kardex = $this->crearUsuarioGestion();
        $tipos = array_keys(config('excelencia.tipos_documento'));

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/excelencia/recepcion-documentos", [
                'datos' => ['documentos' => [$tipos[0] => true]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('solicitud_presentada', $tramite->refresh()->estado_actual);
        $this->assertNull($tramite->hitos['recepcion_documentos'] ?? null);
    }

    public function test_la_solicitud_exige_los_tres_documentos_en_pdf(): void
    {
        $estudiante = $this->crearEstudianteConCuenta();
        $usuario = $this->usuarioDe($estudiante);

        $this->actingAs($usuario)
            ->postJson('/api/excelencia/solicitud', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('documentos');

        $documentos = $this->documentos();
        $documentos[0]['archivo'] = UploadedFile::fake()->create('nota_solicitud.txt', 100);

        $this->actingAs($usuario)
            ->post('/api/excelencia/solicitud', ['documentos' => $documentos])
            ->assertStatus(422)
            ->assertJsonValidationErrors('documentos.0.archivo');
    }

    public function test_no_se_presenta_una_segunda_solicitud_mientras_hay_una_en_curso(): void
    {
        $this->crearModalidad();
        $estudiante = $this->crearEstudianteConCuenta();
        $usuario = $this->usuarioDe($estudiante);

        $this->actingAs($usuario)
            ->post('/api/excelencia/solicitud', ['documentos' => $this->documentos()])
            ->assertCreated();

        $this->actingAs($usuario)
            ->post('/api/excelencia/solicitud', ['documentos' => $this->documentos()])
            ->assertStatus(400);
    }

    public function test_la_designacion_del_tribunal_registra_numero_fecha_y_avanza(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        $this->designar($kardex, $tramite)->assertCreated();

        $tramite->refresh();

        $this->assertSame('tribunal_designado', $tramite->estado_actual);
        $this->assertSame(
            'HCC-DIS-001/2026',
            $tramite->hitos['modulos']['designacion_tribunal']['numero_resolucion']
        );
        $this->assertSame(
            '2026-09-01',
            $tramite->hitos['modulos']['designacion_tribunal']['fecha_resolucion']
        );
        $this->assertTrue($tramite->hitos['modulos']['designacion_tribunal']['verificado']);
    }

    public function test_el_guardado_de_un_modulo_conserva_los_documentos_de_la_solicitud(): void
    {
        $tramite = $this->crearTramiteExcelencia(conDocumentos: true);
        $kardex = $this->crearUsuarioGestion();

        $respuesta = $this->designar($kardex, $tramite)->assertCreated();

        $this->assertCount(3, $respuesta->json('documentos'));
        $this->assertSame(
            ['certificado_calificaciones', 'nota_solicitud', 'record_academico'],
            collect($respuesta->json('documentos'))->pluck('tipo_documento')->sort()->values()->all()
        );
    }

    public function test_el_informe_registra_los_dos_tribunales_y_avanza(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();
        $docentes = $this->crearDocentes();

        $this->designar($kardex, $tramite)->assertCreated();
        $this->informar($kardex, $tramite, $docentes)->assertCreated();

        $tramite->refresh();
        $registro = $tramite->hitos['modulos']['informe_tribunal'];

        $this->assertSame('informe_tribunal_registrado', $tramite->estado_actual);
        $this->assertSame((int) $docentes[0]->id_docente, $registro['tribunal_1']['id_docente']);
        $this->assertSame('Ana Pérez', $registro['tribunal_1']['nombre']);
        $this->assertSame((int) $docentes[1]->id_docente, $registro['tribunal_2']['id_docente']);
        $this->assertSame('Luis Gómez', $registro['tribunal_2']['nombre']);
        $this->assertSame('correcto', $registro['resultado_evaluacion']);
        $this->assertTrue($registro['verificado']);
    }

    public function test_el_informe_rechaza_un_mismo_docente_para_ambos_tribunales(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();
        $docentes = $this->crearDocentes();

        $this->designar($kardex, $tramite)->assertCreated();

        $datos = $this->datosInforme($docentes);
        $datos['tribunal_2'] = $datos['tribunal_1'];

        $this->modulo($kardex, $tramite, 'informe_tribunal', $datos)
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('tribunal_designado', $tramite->refresh()->estado_actual);
    }

    public function test_el_informe_rechaza_un_docente_inexistente(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();
        $docentes = $this->crearDocentes();

        $this->designar($kardex, $tramite)->assertCreated();

        $datos = $this->datosInforme($docentes);
        $datos['tribunal_2']['id_docente'] = 999999;

        $this->modulo($kardex, $tramite, 'informe_tribunal', $datos)
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('tribunal_designado', $tramite->refresh()->estado_actual);
    }

    public function test_el_informe_rechaza_un_resultado_de_evaluacion_desconocido(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();
        $docentes = $this->crearDocentes();

        $this->designar($kardex, $tramite)->assertCreated();

        $datos = $this->datosInforme($docentes);
        $datos['resultado_evaluacion'] = 'desconocido';

        $this->modulo($kardex, $tramite, 'informe_tribunal', $datos)
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('tribunal_designado', $tramite->refresh()->estado_actual);
    }

    public function test_los_informes_pdf_opcionales_se_adjuntan_a_su_modulo(): void
    {
        Storage::fake('public');

        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();
        $docentes = $this->crearDocentes();

        $this->actingAs($kardex)
            ->post("/api/tramites/{$tramite->id_tramite}/excelencia/designacion_tribunal", [
                'datos' => $this->datosDesignacion(),
                'resolucion_tribunal' => UploadedFile::fake()->create('designacion.pdf', 120, 'application/pdf'),
            ])
            ->assertCreated();

        $this->assertDatabaseHas('documentos_adjuntos', [
            'id_tramite' => $tramite->id_tramite,
            'tipo_documento' => 'resolucion_designacion_tribunal_excelencia',
            'nombre_archivo' => 'designacion.pdf',
        ]);

        $this->actingAs($kardex)
            ->post("/api/tramites/{$tramite->id_tramite}/excelencia/informe_tribunal", [
                'datos' => $this->datosInforme($docentes),
                'informe_evaluacion_tribunal' => UploadedFile::fake()->create('evaluacion.pdf', 120, 'application/pdf'),
            ])
            ->assertCreated();

        $this->assertDatabaseHas('documentos_adjuntos', [
            'id_tramite' => $tramite->id_tramite,
            'tipo_documento' => 'informe_evaluacion_tribunal_excelencia',
            'nombre_archivo' => 'evaluacion.pdf',
        ]);

        $this->actingAs($kardex)
            ->post("/api/tramites/{$tramite->id_tramite}/excelencia/resolucion_hcc", [
                'datos' => $this->datosCalificacion(),
            ])
            ->assertCreated();

        $this->actingAs($kardex)
            ->post("/api/tramites/{$tramite->id_tramite}/excelencia/acta_lectura", [
                'datos' => $this->datosActa(),
                'informe_lectura_acta' => UploadedFile::fake()->create('lectura_acta.pdf', 120, 'application/pdf'),
            ])
            ->assertCreated();

        $tramite->refresh();

        $this->assertSame(
            'designacion.pdf',
            $tramite->hitos['modulos']['designacion_tribunal']['resolucion_tribunal']['nombre']
        );
        $this->assertSame(
            'evaluacion.pdf',
            $tramite->hitos['modulos']['informe_tribunal']['informe_evaluacion_tribunal']['nombre']
        );
        $this->assertSame(
            'lectura_acta.pdf',
            $tramite->hitos['modulos']['acta_lectura']['informe_lectura_acta']['nombre']
        );
    }

    public function test_la_designacion_exige_numero_fecha_y_confirmacion(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        $this->modulo($kardex, $tramite, 'designacion_tribunal', [
            'numero_resolucion' => 'HCC-DIS-001/2026',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('solicitud_presentada', $tramite->refresh()->estado_actual);
    }

    public function test_el_informe_exige_los_dos_tribunales_y_confirmacion(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        $this->designar($kardex, $tramite)->assertCreated();

        $this->modulo($kardex, $tramite, 'informe_tribunal', ['confirmado' => true])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('tribunal_designado', $tramite->refresh()->estado_actual);
    }

    public function test_la_calificacion_exige_las_tres_areas_y_confirmacion(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        $this->designar($kardex, $tramite)->assertCreated();
        $this->informar($kardex, $tramite)->assertCreated();

        $this->modulo($kardex, $tramite, 'resolucion_hcc', [
            'calificacion_cuantitativa' => 90,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('informe_tribunal_registrado', $tramite->refresh()->estado_actual);
    }

    public function test_la_calificacion_rechaza_notas_fuera_de_rango(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        $this->designar($kardex, $tramite)->assertCreated();
        $this->informar($kardex, $tramite)->assertCreated();

        $datos = $this->datosCalificacion();
        $datos['calificacion_cuantitativa'] = 150;

        $this->modulo($kardex, $tramite, 'resolucion_hcc', $datos)
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('informe_tribunal_registrado', $tramite->refresh()->estado_actual);
    }

    public function test_la_calificacion_final_se_calcula_con_los_pesos_de_cada_area(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        $this->designar($kardex, $tramite)->assertCreated();
        $this->informar($kardex, $tramite)->assertCreated();

        $respuesta = $this->modulo($kardex, $tramite, 'resolucion_hcc', [
            'calificacion_cuantitativa' => 85.5,
            'calificacion_teoria_economica' => 92,
            'calificacion_materias_complementarias' => 78,
            'calificacion_final' => 0,
            'confirmado' => true,
        ])->assertCreated();

        $this->assertSame(86.6, (float) $respuesta->json('hitos.modulos.resolucion_hcc.calificacion_final'));
        $this->assertSame(86.6, (float) $tramite->refresh()->hitos['modulos']['resolucion_hcc']['calificacion_final']);
    }

    public function test_el_flujo_completo_llega_a_titulado_y_cierra_el_tramite(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        $this->designar($kardex, $tramite)->assertCreated();
        $this->informar($kardex, $tramite)->assertCreated();
        $this->modulo($kardex, $tramite, 'resolucion_hcc', $this->datosCalificacion())->assertCreated();

        $tramite->refresh();

        $this->assertSame('resolucion_hcc_registrada', $tramite->estado_actual);
        $this->assertSame(90.0, (float) $tramite->hitos['modulos']['resolucion_hcc']['calificacion_cuantitativa']);
        $this->assertSame(85.0, (float) $tramite->hitos['modulos']['resolucion_hcc']['calificacion_teoria_economica']);
        $this->assertSame(80.0, (float) $tramite->hitos['modulos']['resolucion_hcc']['calificacion_materias_complementarias']);
        $this->assertSame(86.0, (float) $tramite->hitos['modulos']['resolucion_hcc']['calificacion_final']);
        $this->assertTrue($tramite->hitos['modulos']['resolucion_hcc']['confirmado']);

        $this->modulo($kardex, $tramite, 'reporte', [])->assertStatus(422);
        $this->assertSame('resolucion_hcc_registrada', $tramite->refresh()->estado_actual);

        $this->lectura($kardex, $tramite)->assertCreated();

        $tramite->refresh();

        $this->assertSame('acta_lectura_registrada', $tramite->estado_actual);
        $this->assertSame('2026-10-15', $tramite->hitos['modulos']['acta_lectura']['fecha_lectura']);
        $this->assertTrue($tramite->hitos['modulos']['acta_lectura']['verificado']);

        $this->modulo($kardex, $tramite, 'reporte', [])->assertCreated();

        $tramite->refresh();

        $this->assertSame('titulado', $tramite->estado_actual);
        $this->assertSame(Carbon::today()->toDateString(), $tramite->hitos['titulado'] ?? null);

        $this->modulo($kardex, $tramite, 'reporte', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');
    }

    public function test_el_acta_exige_calificacion_y_confirmacion(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        $this->modulo($kardex, $tramite, 'acta_lectura', $this->datosActa())
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('solicitud_presentada', $tramite->refresh()->estado_actual);

        $this->designar($kardex, $tramite)->assertCreated();
        $this->informar($kardex, $tramite)->assertCreated();

        $this->modulo($kardex, $tramite, 'acta_lectura', $this->datosActa())
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('informe_tribunal_registrado', $tramite->refresh()->estado_actual);
    }

    public function test_el_reporte_exige_la_lectura_del_acta(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        $this->designar($kardex, $tramite)->assertCreated();
        $this->informar($kardex, $tramite)->assertCreated();
        $this->modulo($kardex, $tramite, 'resolucion_hcc', $this->datosCalificacion())->assertCreated();

        $this->modulo($kardex, $tramite, 'reporte', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('resolucion_hcc_registrada', $tramite->refresh()->estado_actual);
    }

    public function test_la_lectura_del_acta_exige_fecha_y_confirmacion(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        $this->designar($kardex, $tramite)->assertCreated();
        $this->informar($kardex, $tramite)->assertCreated();
        $this->modulo($kardex, $tramite, 'resolucion_hcc', $this->datosCalificacion())->assertCreated();

        $this->modulo($kardex, $tramite, 'acta_lectura', ['fecha_lectura' => '2026-10-15'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('resolucion_hcc_registrada', $tramite->refresh()->estado_actual);
    }

    public function test_el_flujo_de_excelencia_no_se_registra_en_otras_modalidades(): void
    {
        $estudiante = $this->crearEstudianteConCuenta();

        $tramite = Tramite::factory()->create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_modalidad' => Modalidad::factory()->tesis()->create()->id_modalidad,
            'estado_actual' => 'perfil_aprobado',
        ]);

        $this->modulo($this->crearUsuarioGestion(), $tramite, 'designacion_tribunal', $this->datosDesignacion())
            ->assertStatus(422)
            ->assertJsonValidationErrors('modulo');

        $this->assertSame('perfil_aprobado', $tramite->refresh()->estado_actual);
    }

    public function test_un_modulo_desconocido_se_rechaza(): void
    {
        $tramite = $this->crearTramiteExcelencia();

        $this->modulo($this->crearUsuarioGestion(), $tramite, 'notas_finales', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');
    }

    public function test_el_estudiante_no_puede_registrar_modulos_de_kardex(): void
    {
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = $this->crearTramiteExcelencia($estudiante);

        $this->actingAs($this->usuarioDe($estudiante))
            ->postJson("/api/tramites/{$tramite->id_tramite}/excelencia/designacion_tribunal", [
                'datos' => $this->datosDesignacion(),
            ])
            ->assertStatus(403);
    }

    public function test_la_configuracion_publica_el_minimo_y_el_rango_de_nota(): void
    {
        $this->actingAs($this->crearUsuarioGestion())
            ->getJson('/api/excelencia/config')
            ->assertOk()
            ->assertJsonPath('modalidad', 'Excelencia Académica')
            ->assertJsonPath('nota_minima', 80)
            ->assertJsonPath('rango_nota', [0, 100]);
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    /**
     * Ejecuta un módulo del flujo de Excelencia Académica como KARDEX.
     *
     * @param  array<string, mixed>  $datos
     */
    private function modulo(User $kardex, Tramite $tramite, string $modulo, array $datos = [])
    {
        return $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/excelencia/{$modulo}", ['datos' => $datos]);
    }

    private function designar(User $kardex, Tramite $tramite)
    {
        return $this->modulo($kardex, $tramite, 'designacion_tribunal', $this->datosDesignacion());
    }

    private function informar(User $kardex, Tramite $tramite, ?array $docentes = null)
    {
        return $this->modulo($kardex, $tramite, 'informe_tribunal', $this->datosInforme($docentes));
    }

    private function lectura(User $kardex, Tramite $tramite)
    {
        return $this->modulo($kardex, $tramite, 'acta_lectura', $this->datosActa());
    }

    /** @return array<string, mixed> */
    private function datosDesignacion(): array
    {
        return [
            'numero_resolucion' => 'HCC-DIS-001/2026',
            'fecha_resolucion' => '2026-09-01',
            'confirmado' => true,
        ];
    }

    /** @return array<string, mixed> */
    private function datosInforme(?array $docentes = null): array
    {
        $docentes ??= $this->crearDocentes();

        return [
            'tribunal_1' => [
                'id_docente' => $docentes[0]->id_docente,
                'nombre' => trim("{$docentes[0]->nombre} {$docentes[0]->apellidos}"),
            ],
            'tribunal_2' => [
                'id_docente' => $docentes[1]->id_docente,
                'nombre' => trim("{$docentes[1]->nombre} {$docentes[1]->apellidos}"),
            ],
            'resultado_evaluacion' => 'correcto',
            'confirmado' => true,
        ];
    }

    /** @return array<string, mixed> */
    private function datosCalificacion(): array
    {
        return [
            'calificacion_cuantitativa' => 90,
            'calificacion_teoria_economica' => 85,
            'calificacion_materias_complementarias' => 80,
            'confirmado' => true,
        ];
    }

    /** @return array<string, mixed> */
    private function datosActa(): array
    {
        return [
            'fecha_lectura' => '2026-10-15',
            'confirmado' => true,
        ];
    }

    /** @return array<int, Docente> */
    private function crearDocentes(): array
    {
        return [
            Docente::create([
                'ci' => '1111111',
                'nombre' => 'Ana',
                'apellidos' => 'Pérez',
                'materia' => 'Economía',
            ]),
            Docente::create([
                'ci' => '2222222',
                'nombre' => 'Luis',
                'apellidos' => 'Gómez',
                'materia' => 'Contabilidad',
            ]),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function documentos(): array
    {
        $documentos = [];

        foreach (array_keys(config('excelencia.tipos_documento')) as $tipo) {
            $documentos[] = [
                'archivo' => UploadedFile::fake()->create("{$tipo}.pdf", 100, 'application/pdf'),
                'tipo' => $tipo,
            ];
        }

        return $documentos;
    }

    private function crearTramiteExcelencia(?Estudiante $estudiante = null, bool $conDocumentos = false): Tramite
    {
        $estudiante ??= $this->crearEstudianteConCuenta();

        $tramite = Tramite::factory()->create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_modalidad' => $this->crearModalidad()->id_modalidad,
            'estado_actual' => 'solicitud_presentada',
        ]);

        if ($conDocumentos) {
            $idUsuario = $estudiante->user?->id_usuario ?? User::factory()->create()->id_usuario;

            foreach (array_keys(config('excelencia.tipos_documento')) as $tipo) {
                DocumentoAdjunto::create([
                    'id_tramite' => $tramite->id_tramite,
                    'id_usuario_subio' => $idUsuario,
                    'tipo_documento' => $tipo,
                    'nombre_archivo' => "{$tipo}.pdf",
                    'ruta_archivo' => "documentos_tramites/{$tipo}.pdf",
                    'tamanio_kb' => 100,
                ]);
            }
        }

        return $tramite;
    }

    private function crearModalidad(): Modalidad
    {
        return Modalidad::factory()->excelencia()->create();
    }

    private function crearEstudianteConCuenta(): Estudiante
    {
        return Estudiante::factory()->conCuenta()->create();
    }

    private function usuarioDe(Estudiante $estudiante): User
    {
        return User::where('estudiante_id', $estudiante->id_estudiante)->firstOrFail();
    }

    private function crearUsuarioGestion(): User
    {
        return User::factory()->role(Roles::GESTION[0])->create();
    }
}
