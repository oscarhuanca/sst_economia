<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Modalidad;
use App\Models\Tramite;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nota mínima de la defensa de tesis (módulo 4 del flujo de titulación):
 * con 51 o más el trámite pasa a `defensa_aprobada`; con menos queda
 * `reprobado` (estado terminal), el flujo termina y el estudiante deberá
 * volver a solicitar la modalidad de graduación.
 */
class TesisDefensaNotaMinimaTest extends TestCase
{
    use RefreshDatabase;

    public function test_una_nota_en_el_minimo_aprueba_la_defensa(): void
    {
        $tramite = $this->crearTramiteEnDefensa();

        $this->defensa($tramite, 51)->assertCreated();

        $this->assertSame('defensa_aprobada', $tramite->refresh()->estado_actual);
    }

    public function test_una_nota_inferior_al_minimo_deja_el_tramite_reprobado(): void
    {
        $tramite = $this->crearTramiteEnDefensa();

        $this->defensa($tramite, 50)->assertCreated();

        $tramite->refresh();

        $this->assertSame('reprobado', $tramite->estado_actual);
        // El acta documenta la acción en el historial del trámite.
        $this->assertDatabaseHas('estados_tramite', [
            'id_tramite' => $tramite->id_tramite,
            'nombre_estado' => 'reprobado',
        ]);
        $this->assertSame(
            'reprobado',
            $tramite->estados->last()->nombre_estado,
        );
        $this->assertStringContainsString('nota 50', $tramite->estados->last()->observaciones);
        $this->assertStringContainsString('mínimo de aprobación: 51', $tramite->estados->last()->observaciones);
        $this->assertStringContainsString('modalidad de graduación', $tramite->estados->last()->observaciones);
    }

    public function test_tras_reprobar_el_flujo_termina_y_no_admite_mas_modulos(): void
    {
        $tramite = $this->crearTramiteEnDefensa();

        $this->defensa($tramite, 30)->assertCreated();

        $this->assertSame('reprobado', $tramite->refresh()->estado_actual);

        // `reprobado` es terminal: el módulo de reporte ya no se registra.
        $this->guardarModulo($tramite, 'reporte', [])->assertStatus(422);

        $this->assertSame('reprobado', $tramite->refresh()->estado_actual);
    }

    public function test_una_nota_fuera_del_rango_se_rechaza(): void
    {
        $tramite = $this->crearTramiteEnDefensa();

        $this->defensa($tramite, 120)
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('tribunal_asignado', $tramite->refresh()->estado_actual);
    }

    public function test_la_defensa_exige_confirmar_la_entrega_de_empastados_y_cd(): void
    {
        $tramite = $this->crearTramiteEnDefensa();

        $this->guardarModulo($tramite, 'defensa', [
            'numero_resolucion' => 'HCC-0315/2026',
            'fecha_defensa' => '2026-10-20',
        ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'Confirme que el estudiante presentó los 8 empastados y el CD de su tesis de grado.',
            ]);

        $this->assertSame('tribunal_asignado', $tramite->refresh()->estado_actual);
    }

    public function test_el_paso_de_resolucion_y_fecha_no_cierra_la_defensa(): void
    {
        $tramite = $this->crearTramiteEnDefensa();

        $this->guardarModulo($tramite, 'defensa', [
            'empastados_cd_entregados' => true,
            'numero_resolucion' => 'HCC-0315/2026',
            'fecha_defensa' => '2026-10-20',
            'paso' => 2,
        ])->assertCreated();

        $tramite->refresh();
        $modulo = $tramite->hitos['modulos']['defensa'];

        $this->assertSame('tribunal_asignado', $tramite->estado_actual);
        $this->assertTrue((bool) $modulo['empastados_cd_entregados']);
        $this->assertSame('HCC-0315/2026', $modulo['numero_resolucion']);
        $this->assertSame('2026-10-20', $modulo['fecha_defensa']);
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    /** Trámite de Tesis de Grado listo para registrar la defensa. */
    private function crearTramiteEnDefensa(): Tramite
    {
        $modalidad = Modalidad::factory()->tesis()->create();

        return Tramite::factory()->create([
            'id_estudiante' => Estudiante::factory()->conCuenta()->create()->id_estudiante,
            'id_modalidad' => $modalidad->id_modalidad,
            'estado_actual' => 'tribunal_asignado',
        ]);
    }

    /** Registra la defensa con la nota final indicada (paso 3 completo). */
    private function defensa(Tramite $tramite, float $nota)
    {
        return $this->guardarModulo($tramite, 'defensa', [
            'empastados_cd_entregados' => true,
            'numero_resolucion' => 'HCC-0315/2026',
            'fecha_defensa' => '2026-10-20',
            'nota_final' => $nota,
        ]);
    }

    private function guardarModulo(Tramite $tramite, string $modulo, array $datos)
    {
        $kardex = User::factory()->role(Roles::GESTION[0])->create();

        return $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/flujo/{$modulo}", ['datos' => $datos]);
    }
}
