<?php

namespace Tests\Feature;

use App\Models\Docente;
use App\Models\EstadoTramite;
use App\Models\Estudiante;
use App\Models\Modalidad;
use App\Models\Tramite;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Retroceso de Tesis de Grado: Kardex puede devolver un trámite a cualquier
 * estado anterior del flujo para corregir los módulos ya registrados.
 */
class TesisRetrocesoKardexTest extends TestCase
{
    use RefreshDatabase;

    public function test_kardex_puede_devolver_la_tesis_a_un_estado_anterior(): void
    {
        $docente = Docente::create([
            'ci' => fake()->unique()->numerify('#######'),
            'nombre' => 'Ana',
            'apellidos' => 'Perez',
            'email' => 'ana@example.test',
        ]);
        $tramite = $this->crearTramiteTesis('titulado', [
            'perfil_aprobado' => '2026-01-10',
            'tema_aprobado' => '2026-02-10',
            'tribunal_asignado' => '2026-03-10',
            'defensa_aprobada' => '2026-04-10',
            'publicado' => '2026-05-10',
            'titulado' => '2026-06-10',
            'limite_presentacion' => '2026-04-10',
            'fecha_defensa' => '2026-04-01',
            'modulos' => [
                'publicacion' => ['numero_resolucion' => 'HCC-001/2026'],
                'reporte' => ['generado' => '2026-06-10'],
            ],
        ], $docente->id_docente);

        $respuesta = $this->retroceder($tramite, 'perfil_aprobado', 'Corregir el perfil de la tesis.');

        $respuesta->assertOk();
        $tramite->refresh();

        $this->assertSame('perfil_aprobado', $tramite->estado_actual);
        $this->assertNull($tramite->id_tutor);
        $this->assertArrayNotHasKey('tema_aprobado', $tramite->hitos);
        $this->assertArrayNotHasKey('defensa_aprobada', $tramite->hitos);
        $this->assertArrayNotHasKey('publicado', $tramite->hitos);
        $this->assertArrayNotHasKey('titulado', $tramite->hitos);
        $this->assertArrayNotHasKey('fecha_defensa', $tramite->hitos);
        $this->assertSame('2026-01-10', $tramite->hitos['perfil_aprobado']);
        $this->assertSame(
            'HCC-001/2026',
            $tramite->hitos['modulos']['publicacion']['numero_resolucion'],
        );
        $this->assertDatabaseHas('estados_tramite', [
            'id_tramite' => $tramite->id_tramite,
            'nombre_estado' => 'perfil_aprobado',
            'observaciones' => 'Corregir el perfil de la tesis.',
        ]);
        $respuesta->assertJsonFragment(['nombre_estado' => 'perfil_aprobado']);
    }

    public function test_despues_de_retroceder_se_puede_volver_a_registrar_un_modulo(): void
    {
        $tramite = $this->crearTramiteTesis('titulado', [
            'publicado' => '2026-05-10',
            'titulado' => '2026-06-10',
        ]);

        $this->retroceder($tramite, 'publicado', null)->assertOk();

        $this->actingAs(User::factory()->role(Roles::KARDEX)->create())
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/reporte", [
                'datos' => ['generado' => '2026-06-20'],
            ])
            ->assertCreated();

        $tramite->refresh();

        $this->assertSame('titulado', $tramite->estado_actual);
        $this->assertSame('2026-06-20', $tramite->hitos['modulos']['reporte']['generado']);
    }

    public function test_kardex_puede_modificar_un_modulo_registrado_sin_cambiar_el_estado(): void
    {
        $docente = Docente::create([
            'ci' => fake()->unique()->numerify('#######'),
            'nombre' => 'Luis',
            'apellidos' => 'Gomez',
            'email' => 'luis@example.test',
        ]);
        $tramite = $this->crearTramiteTesis('tema_aprobado', [
            'perfil_aprobado' => '2026-01-10',
            'tema_aprobado' => '2026-02-10',
            'modulos' => [
                'aprobacion_tema' => [
                    'numero_resolucion' => 'HCC-0102/2026',
                    'fecha_resolucion' => '2026-02-05',
                    'tema_investigacion' => 'Tema original de la tesis',
                    'tutor_id' => $docente->id_docente,
                ],
            ],
        ]);
        $historialAntes = EstadoTramite::where('id_tramite', $tramite->id_tramite)->count();

        $respuesta = $this->actingAs(User::factory()->role(Roles::KARDEX)->create())
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/aprobacion_tema", [
                'datos' => [
                    'numero_resolucion' => 'HCC-9999/2026',
                    'fecha_resolucion' => '2026-02-05',
                    'tema_investigacion' => 'Tema corregido de la tesis',
                    'tutor_id' => $docente->id_docente,
                ],
            ]);

        $respuesta->assertCreated();
        $tramite->refresh();

        $this->assertSame('tema_aprobado', $tramite->estado_actual);
        $this->assertSame('HCC-9999/2026', $tramite->hitos['modulos']['aprobacion_tema']['numero_resolucion']);
        $this->assertSame('Tema corregido de la tesis', $tramite->hitos['modulos']['aprobacion_tema']['tema_investigacion']);
        $this->assertSame(
            $historialAntes,
            EstadoTramite::where('id_tramite', $tramite->id_tramite)->count(),
        );
    }

    public function test_kardex_puede_guardar_datos_de_un_modulo_sin_avanzar_el_flujo(): void
    {
        $docente = Docente::create([
            'ci' => fake()->unique()->numerify('#######'),
            'nombre' => 'Marta',
            'apellidos' => 'Lopez',
            'email' => 'marta@example.test',
        ]);
        $tramite = $this->crearTramiteTesis('perfil_aprobado', [
            'perfil_aprobado' => '2026-01-10',
        ]);
        $historialAntes = EstadoTramite::where('id_tramite', $tramite->id_tramite)->count();

        $this->actingAs(User::factory()->role(Roles::KARDEX)->create())
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/aprobacion_tema", [
                'solo_datos' => true,
                'datos' => [
                    'numero_resolucion' => 'HCC-7777/2026',
                    'fecha_resolucion' => '2026-02-05',
                    'tema_investigacion' => 'Tema precargado por Kardex',
                    'tutor_id' => $docente->id_docente,
                ],
            ])
            ->assertCreated();

        $tramite->refresh();

        $this->assertSame('perfil_aprobado', $tramite->estado_actual);
        $this->assertSame('HCC-7777/2026', $tramite->hitos['modulos']['aprobacion_tema']['numero_resolucion']);
        $this->assertSame($docente->id_docente, $tramite->id_tutor);
        $this->assertSame(
            $historialAntes,
            EstadoTramite::where('id_tramite', $tramite->id_tramite)->count(),
        );
    }

    public function test_no_se_puede_retroceder_a_un_estado_posterior(): void
    {
        $tramite = $this->crearTramiteTesis('perfil_aprobado');

        $this->retroceder($tramite, 'titulado', null)
            ->assertStatus(422)
            ->assertJsonValidationErrors('nuevo_estado');

        $this->assertSame('perfil_aprobado', $tramite->refresh()->estado_actual);
    }

    public function test_el_retroceso_solo_aplica_a_tesis_de_grado(): void
    {
        $modalidad = Modalidad::factory()->trabajoDirigido()->create();
        $tramite = Tramite::factory()->create([
            'id_estudiante' => Estudiante::factory()->conCuenta()->create()->id_estudiante,
            'id_modalidad' => $modalidad->id_modalidad,
            'estado_actual' => 'publicado',
        ]);

        $this->retroceder($tramite, 'perfil_aprobado', null)
            ->assertStatus(422)
            ->assertJsonValidationErrors('nuevo_estado');
    }

    public function test_secretaria_no_puede_retroceder_una_tesis(): void
    {
        $tramite = $this->crearTramiteTesis('publicado');

        $this->actingAs(User::factory()->role(Roles::SECRETARIA)->create())
            ->postJson("/api/tramites/{$tramite->id_tramite}/retroceder", [
                'nuevo_estado' => 'perfil_aprobado',
            ])
            ->assertStatus(403);

        $this->assertSame('publicado', $tramite->refresh()->estado_actual);
    }

    /** Trámite de Tesis de Grado en el estado indicado. */
    private function crearTramiteTesis(string $estado, array $hitos = [], ?int $idTutor = null): Tramite
    {
        return Tramite::factory()->create([
            'id_estudiante' => Estudiante::factory()->conCuenta()->create()->id_estudiante,
            'id_modalidad' => Modalidad::factory()->tesis()->create()->id_modalidad,
            'estado_actual' => $estado,
            'hitos' => $hitos,
            'id_tutor' => $idTutor,
        ]);
    }

    /** Solicitud de retroceso autenticada como Kardex. */
    private function retroceder(Tramite $tramite, string $nuevoEstado, ?string $observaciones)
    {
        return $this->actingAs(User::factory()->role(Roles::KARDEX)->create())
            ->postJson("/api/tramites/{$tramite->id_tramite}/retroceder", [
                'nuevo_estado' => $nuevoEstado,
                'observaciones' => $observaciones,
            ]);
    }
}
