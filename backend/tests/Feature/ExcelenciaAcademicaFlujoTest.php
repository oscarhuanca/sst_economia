<?php

namespace Tests\Feature;

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

/**
 * Flujo de Excelencia Académica gestionado por KARDEX.
 *
 * Cubre lo que no se deduce del resto del sistema: la solicitud del estudiante
 * con su promedio DECLARADO, la verificación de KARDEX (que puede corregir la
 * nota y la consolida en el perfil), el resultado ACEPTADO/RECHAZADO de la
 * admisión, el rechazo que deja el trámite en estado terminal, el registro de la
 * Resolución del HCC con la lectura del acta y el acta final que titula al
 * estudiante. También comprueba los informes PDF opcionales de cada paso.
 */
class ExcelenciaAcademicaFlujoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_estudiante_presenta_la_solicitud_con_su_promedio_declarado(): void
    {
        $this->crearModalidad();
        $estudiante = $this->crearEstudianteConCuenta();

        $respuesta = $this->actingAs($this->usuarioDe($estudiante))
            ->postJson('/api/excelencia/solicitud', ['promedio' => 87.5])
            ->assertCreated();

        $this->assertSame('Excelencia Académica', $respuesta->json('modalidad.nombre'));
        $this->assertSame('solicitud_presentada', $respuesta->json('estado_actual'));

        $tramite = Tramite::where('id_estudiante', $estudiante->id_estudiante)->firstOrFail();

        // El promedio declarado queda registrado para que KARDEX lo verifique
        // (y lo corrija) después.
        $this->assertSame(87.5, (float) $tramite->hitos['modulos']['verificacion_promedio']['promedio_declarado']);
        $this->assertSame(Carbon::today()->toDateString(), $tramite->hitos['solicitud_presentada'] ?? null);
    }

    public function test_el_estudiante_declara_su_promedio_desde_el_seguimiento(): void
    {
        // KARDEX puede asignar la modalidad sin que el estudiante haya declarado
        // su promedio: el seguimiento le permite ingresarlo después.
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = $this->crearTramiteExcelencia($estudiante);

        $this->actingAs($this->usuarioDe($estudiante))
            ->putJson("/api/excelencia/solicitud/{$tramite->id_tramite}/promedio", ['promedio' => 91.4])
            ->assertOk()
            ->assertJsonPath('estado_actual', 'solicitud_presentada');

        $tramite->refresh();

        $this->assertSame(91.4, (float) $tramite->hitos['modulos']['verificacion_promedio']['promedio_declarado']);
        $this->assertSame(80, $tramite->hitos['modulos']['verificacion_promedio']['nota_minima']);
    }

    public function test_el_estudiante_corrige_su_promedio_declarado_antes_de_la_verificacion(): void
    {
        $this->crearModalidad();
        $estudiante = $this->crearEstudianteConCuenta();
        $usuario = $this->usuarioDe($estudiante);

        $creado = $this->actingAs($usuario)
            ->postJson('/api/excelencia/solicitud', ['promedio' => 82])
            ->assertCreated();

        $id = $creado->json('id_tramite');

        $this->actingAs($usuario)
            ->putJson("/api/excelencia/solicitud/{$id}/promedio", ['promedio' => 89.75])
            ->assertOk();

        $tramite = Tramite::findOrFail($id);

        $this->assertSame(89.75, (float) $tramite->hitos['modulos']['verificacion_promedio']['promedio_declarado']);
        $this->assertSame('solicitud_presentada', $tramite->estado_actual);
    }

    public function test_no_se_actualiza_el_promedio_despues_de_la_verificacion(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $this->verificarPromedio($this->crearUsuarioGestion(), $tramite);

        $this->actingAs($this->usuarioDe($tramite->estudiante))
            ->putJson("/api/excelencia/solicitud/{$tramite->id_tramite}/promedio", ['promedio' => 95])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('promedio_verificado', $tramite->refresh()->estado_actual);
    }

    public function test_otro_estudiante_no_puede_declarar_el_promedio(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $otro = $this->crearEstudianteConCuenta();

        $this->actingAs($this->usuarioDe($otro))
            ->putJson("/api/excelencia/solicitud/{$tramite->id_tramite}/promedio", ['promedio' => 95])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertNull($tramite->refresh()->hitos['modulos']['verificacion_promedio']['promedio_declarado'] ?? null);
    }

    public function test_la_solicitud_exige_un_promedio_valido(): void
    {
        $estudiante = $this->crearEstudianteConCuenta();
        $usuario = $this->usuarioDe($estudiante);

        $this->actingAs($usuario)
            ->postJson('/api/excelencia/solicitud', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('promedio');

        $this->actingAs($usuario)
            ->postJson('/api/excelencia/solicitud', ['promedio' => 120])
            ->assertStatus(422)
            ->assertJsonValidationErrors('promedio');
    }

    public function test_no_se_presenta_una_segunda_solicitud_mientras_hay_una_en_curso(): void
    {
        $this->crearModalidad();
        $estudiante = $this->crearEstudianteConCuenta();
        $usuario = $this->usuarioDe($estudiante);

        $this->actingAs($usuario)
            ->postJson('/api/excelencia/solicitud', ['promedio' => 88])
            ->assertCreated();

        $this->actingAs($usuario)
            ->postJson('/api/excelencia/solicitud', ['promedio' => 90])
            ->assertStatus(400);
    }

    public function test_kardex_verifica_el_promedio_y_consolida_la_nota_en_el_perfil(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        // KARDEX puede CORREGIR el promedio declarado: el que se registra es el
        // verificado, que además queda en el perfil del estudiante. Para admitir
        // la solicitud registra la Resolución del HCC y confirma la revisión.
        $this->modulo($kardex, $tramite, 'verificacion_promedio', $this->datosVerificacion(84.5))
            ->assertCreated();

        $tramite->refresh();

        $this->assertSame('promedio_verificado', $tramite->estado_actual);
        $this->assertSame(84.5, (float) $tramite->hitos['modulos']['verificacion_promedio']['promedio']);
        $this->assertSame('aceptado', $tramite->hitos['modulos']['verificacion_promedio']['resultado']);
        $this->assertSame('HCC-ADM-001/2026', $tramite->hitos['modulos']['verificacion_promedio']['numero_resolucion']);
        $this->assertSame(84.5, (float) $tramite->estudiante->refresh()->promedio_global);
    }

    public function test_un_resultado_rechazado_cierra_el_flujo_aunque_el_promedio_alcance(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        $this->modulo($kardex, $tramite, 'verificacion_promedio', $this->datosVerificacion(90, 'rechazado'))
            ->assertCreated();

        $tramite->refresh();

        // El rechazo es terminal: el estudiante no habilita la modalidad.
        $this->assertSame('rechazado', $tramite->estado_actual);
        $this->assertSame('rechazado', $tramite->hitos['modulos']['verificacion_promedio']['resultado']);
        $this->assertSame(90.0, (float) $tramite->estudiante->refresh()->promedio_global);

        // Con el trámite cerrado no se admiten más módulos.
        $this->modulo($kardex, $tramite, 'resolucion_hcc', $this->datosResolucion())
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');
    }

    public function test_no_se_puede_aceptar_con_un_promedio_inferior_al_minimo(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        $this->modulo($kardex, $tramite, 'verificacion_promedio', $this->datosVerificacion(65))
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('solicitud_presentada', $tramite->refresh()->estado_actual);
        $this->assertNull($tramite->hitos['modulos']['verificacion_promedio']['resultado'] ?? null);
    }

    public function test_los_informes_pdf_opcionales_se_adjuntan_a_su_modulo(): void
    {
        Storage::fake('public');

        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        $this->actingAs($kardex)
            ->post("/api/tramites/{$tramite->id_tramite}/excelencia/verificacion_promedio", [
                'datos' => $this->datosVerificacion(86),
                'informe_verificacion' => UploadedFile::fake()->create('verificacion.pdf', 120, 'application/pdf'),
            ])
            ->assertCreated();

        $this->assertDatabaseHas('documentos_adjuntos', [
            'id_tramite' => $tramite->id_tramite,
            'tipo_documento' => 'informe_verificacion_excelencia',
            'nombre_archivo' => 'verificacion.pdf',
        ]);

        $tramite->refresh();
        $this->assertSame(
            'verificacion.pdf',
            $tramite->hitos['modulos']['verificacion_promedio']['informe_verificacion']['nombre']
        );

        $this->actingAs($kardex)
            ->post("/api/tramites/{$tramite->id_tramite}/excelencia/resolucion_hcc", [
                'datos' => $this->datosResolucion(),
                'informe_lectura_acta' => UploadedFile::fake()->create('lectura-acta.pdf', 80, 'application/pdf'),
            ])
            ->assertCreated();

        $this->assertDatabaseHas('documentos_adjuntos', [
            'id_tramite' => $tramite->id_tramite,
            'tipo_documento' => 'informe_lectura_acta_excelencia',
            'nombre_archivo' => 'lectura-acta.pdf',
        ]);

        $tramite->refresh();
        $this->assertSame(
            'lectura-acta.pdf',
            $tramite->hitos['modulos']['resolucion_hcc']['informe_lectura_acta']['nombre']
        );
    }

    public function test_la_verificacion_exige_resultado_resolucion_y_confirmacion(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        $this->modulo($kardex, $tramite, 'verificacion_promedio', ['promedio' => 88])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('solicitud_presentada', $tramite->refresh()->estado_actual);
    }

    public function test_la_resolucion_exige_fecha_de_lectura_y_confirmacion(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        $this->verificarPromedio($kardex, $tramite);

        $this->modulo($kardex, $tramite, 'resolucion_hcc', [
            'numero_resolucion' => 'HCC-0412/2026',
            'fecha_resolucion' => '2026-09-01',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('promedio_verificado', $tramite->refresh()->estado_actual);
    }

    public function test_la_verificacion_exige_un_promedio_valido(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        $this->modulo($kardex, $tramite, 'verificacion_promedio', $this->datosVerificacion(140))
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('solicitud_presentada', $tramite->refresh()->estado_actual);
    }

    public function test_la_resolucion_del_hcc_exige_numero_y_fecha(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        $this->verificarPromedio($kardex, $tramite);

        $this->modulo($kardex, $tramite, 'resolucion_hcc', ['fecha_resolucion' => '2026-09-01'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('promedio_verificado', $tramite->refresh()->estado_actual);
    }

    public function test_el_flujo_completo_llega_a_titulado_y_cierra_el_tramite(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        $this->verificarPromedio($kardex, $tramite);

        $this->modulo($kardex, $tramite, 'resolucion_hcc', $this->datosResolucion())->assertCreated();

        $tramite->refresh();

        $this->assertSame('resolucion_hcc_registrada', $tramite->estado_actual);
        $this->assertSame('HCC-0412/2026', $tramite->hitos['modulos']['resolucion_hcc']['numero_resolucion']);
        $this->assertSame('2026-09-15', $tramite->hitos['modulos']['resolucion_hcc']['fecha_lectura_acta']);
        $this->assertTrue($tramite->hitos['modulos']['resolucion_hcc']['confirmado']);

        $this->modulo($kardex, $tramite, 'reporte', [])->assertCreated();

        $tramite->refresh();

        $this->assertSame('titulado', $tramite->estado_actual);
        $this->assertSame(Carbon::today()->toDateString(), $tramite->hitos['titulado'] ?? null);

        // Cerrado el flujo, ningún módulo admite cambios.
        $this->modulo($kardex, $tramite, 'reporte', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');
    }

    public function test_el_acta_exige_la_verificacion_previa_del_promedio(): void
    {
        $tramite = $this->crearTramiteExcelencia();
        $kardex = $this->crearUsuarioGestion();

        // Sin la verificación no hay nada que titular.
        $this->modulo($kardex, $tramite, 'reporte', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('solicitud_presentada', $tramite->refresh()->estado_actual);
    }

    public function test_el_flujo_de_excelencia_no_se_registra_en_otras_modalidades(): void
    {
        $estudiante = $this->crearEstudianteConCuenta();

        $tramite = Tramite::factory()->create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_modalidad' => Modalidad::factory()->tesis()->create()->id_modalidad,
            'estado_actual' => 'perfil_aprobado',
        ]);

        $this->modulo($this->crearUsuarioGestion(), $tramite, 'verificacion_promedio', ['promedio' => 90])
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
            ->postJson("/api/tramites/{$tramite->id_tramite}/excelencia/verificacion_promedio", [
                'datos' => ['promedio' => 90],
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

    /** Verifica el promedio con una nota habilitante. */
    private function verificarPromedio(User $kardex, Tramite $tramite): void
    {
        $this->modulo($kardex, $tramite, 'verificacion_promedio', $this->datosVerificacion(88))
            ->assertCreated();
    }

    /**
     * Datos mínimos del paso 1: promedio verificado, Resolución de admisión,
     * decisión y confirmación de KARDEX.
     *
     * @return array<string, mixed>
     */
    private function datosVerificacion(float $promedio, string $resultado = 'aceptado'): array
    {
        return [
            'promedio' => $promedio,
            'resultado' => $resultado,
            'numero_resolucion' => 'HCC-ADM-001/2026',
            'fecha_resolucion' => '2026-09-01',
            'confirmado' => true,
        ];
    }

    /**
     * Datos mínimos del paso 2: Resolución del HCC, fecha de lectura y
     * confirmación de KARDEX.
     *
     * @return array<string, mixed>
     */
    private function datosResolucion(): array
    {
        return [
            'numero_resolucion' => 'HCC-0412/2026',
            'fecha_resolucion' => '2026-09-01',
            'fecha_lectura_acta' => '2026-09-15',
            'confirmado' => true,
        ];
    }

    private function crearTramiteExcelencia(?Estudiante $estudiante = null): Tramite
    {
        $estudiante ??= $this->crearEstudianteConCuenta();

        return Tramite::factory()->create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_modalidad' => $this->crearModalidad()->id_modalidad,
            'estado_actual' => 'solicitud_presentada',
        ]);
    }

    /** Modalidad de Excelencia Académica (única en la base de las pruebas). */
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
