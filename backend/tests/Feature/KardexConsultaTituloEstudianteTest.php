<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Modalidad;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Flujo completo estudiante → KARDEX: el postulante presenta (o corrige) su
 * perfil con el título de tesis y los PDFs, y la consulta de KARDEX
 * (`GET /kardex/postulantes/consultar`) debe devolver el título registrado en
 * `hitos.modulos.perfil_tesis.titulo` y los documentos subidos, que son los
 * que la vista de KARDEX pinta para verificar el perfil.
 */
class KardexConsultaTituloEstudianteTest extends TestCase
{
    use RefreshDatabase;

    private const TITULO = 'Sistemas de riego eficientes para cultivos de altura en el altiplano';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_kardex_ve_el_titulo_y_documento_tras_asignar_modalidad_y_corregir_el_estudiante(): void
    {
        $modalidad = Modalidad::factory()->tesis()->create();
        $estudiante = Estudiante::factory()->create();
        $kardex = User::factory()->role(Roles::KARDEX)->create();

        // 1. KARDEX asigna la modalidad: crea el trámite (sin título ni PDFs).
        $this->actingAs($kardex)
            ->postJson('/api/kardex/postulantes/asignar-modalidad', [
                'identificador' => $estudiante->ci,
                'id_modalidad' => $modalidad->id_modalidad,
            ])
            ->assertCreated();

        $tramite = $estudiante->tramites()->firstOrFail();
        $this->assertSame('solicitud_presentada', $tramite->estado_actual);
        $this->assertNull($tramite->hitos['modulos']['perfil_tesis']['titulo'] ?? null);

        // 2. El estudiante usa su cuenta recién generada y guarda el perfil.
        $this->actingAs($estudiante->fresh()->user)
            ->post("/api/tesis/{$tramite->id_tramite}/perfil", [
                'titulo' => self::TITULO,
                'documentos' => [
                    [
                        'archivo' => UploadedFile::fake()->create('perfil.pdf', 120, 'application/pdf'),
                        'tipo' => 'perfil_tesis',
                    ],
                ],
            ])
            ->assertOk();

        // 3. La consulta de KARDEX debe devolver el título y el documento.
        $respuesta = $this->actingAs($kardex)
            ->getJson('/api/kardex/postulantes/consultar?'.http_build_query([
                'identificador' => $estudiante->ci,
            ]))
            ->assertOk()
            ->json('tramite');

        $this->assertSame(
            self::TITULO,
            $respuesta['hitos']['modulos']['perfil_tesis']['titulo'] ?? null,
            'La consulta de KARDEX debe incluir el título que el estudiante guardó en su perfil.',
        );

        $tipos = array_column($respuesta['documentos'] ?? [], 'tipo_documento');
        $this->assertContains(
            'perfil_tesis',
            $tipos,
            'La consulta de KARDEX debe incluir el documento que el estudiante subió.',
        );
    }

    public function test_kardex_ve_el_titulo_y_documentos_de_la_solicitud_creada_por_el_estudiante(): void
    {
        Modalidad::factory()->tesis()->create();
        $estudiante = Estudiante::factory()->conCuenta()->create();
        $kardex = User::factory()->role(Roles::KARDEX)->create();

        // 1. El estudiante presenta su solicitud con el título y los 3 PDFs.
        $documentos = [];
        foreach (array_keys(config('tesis.tipos_documento')) as $tipo) {
            $documentos[] = [
                'archivo' => UploadedFile::fake()->create("{$tipo}.pdf", 100, 'application/pdf'),
                'tipo' => $tipo,
            ];
        }

        $this->actingAs($estudiante->user)
            ->post('/api/tesis/solicitud', ['titulo' => self::TITULO, 'documentos' => $documentos])
            ->assertCreated();

        // 2. La consulta de KARDEX refleja el título y los 3 documentos.
        $respuesta = $this->actingAs($kardex)
            ->getJson('/api/kardex/postulantes/consultar?'.http_build_query([
                'identificador' => $estudiante->ci,
            ]))
            ->assertOk()
            ->json('tramite');

        $this->assertSame(
            self::TITULO,
            $respuesta['hitos']['modulos']['perfil_tesis']['titulo'] ?? null,
        );

        $tipos = array_column($respuesta['documentos'] ?? [], 'tipo_documento');
        foreach (array_keys(config('tesis.tipos_documento')) as $tipo) {
            $this->assertContains($tipo, $tipos);
        }
    }
}
