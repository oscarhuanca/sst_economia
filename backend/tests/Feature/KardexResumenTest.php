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
 * Panel general del KARDEX: el resumen debe pintar en verde (`aprobados`) los
 * trámites cuyo flujo ya culminó de forma aprobatoria —incluido `titulado`— y
 * dejar en curso los que aún esperan pasos (como `aprobado` de Examen de Grado,
 * que todavía debe emitir el reporte).
 */
class KardexResumenTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_tramites_titulados_cuentan_como_aprobados(): void
    {
        $this->crearTramite(Modalidad::factory()->tesis()->create(), 'titulado');
        $this->crearTramite(Modalidad::factory()->examen()->create(), 'titulado');

        $resumen = $this->consultarResumen();

        $this->assertSame(2, $resumen['totales']['aprobados']);
        $this->assertSame(0, $resumen['totales']['en_curso']);
        $this->assertSame(1, $this->modalidadDe($resumen, 'Tesis de Grado')['aprobados']);
        $this->assertSame(1, $this->modalidadDe($resumen, 'Examen de Grado')['aprobados']);
    }

    public function test_el_aprobado_de_examen_de_grado_sigue_en_curso(): void
    {
        $this->crearTramite(Modalidad::factory()->examen()->create(), 'aprobado');

        $modalidad = $this->modalidadDe($this->consultarResumen(), 'Examen de Grado');

        $this->assertSame(0, $modalidad['aprobados']);
        $this->assertSame(1, $modalidad['en_curso']);
    }

    public function test_el_aprobado_de_tesis_cuenta_como_aprobado(): void
    {
        $this->crearTramite(Modalidad::factory()->tesis()->create(), 'aprobado');

        $modalidad = $this->modalidadDe($this->consultarResumen(), 'Tesis de Grado');

        $this->assertSame(1, $modalidad['aprobados']);
        $this->assertSame(0, $modalidad['en_curso']);
    }

    public function test_los_fallos_no_cuentan_como_en_curso_ni_aprobados(): void
    {
        $this->crearTramite(Modalidad::factory()->tesis()->create(), 'reprobado');

        $modalidad = $this->modalidadDe($this->consultarResumen(), 'Tesis de Grado');

        $this->assertSame(1, $modalidad['total']);
        $this->assertSame(0, $modalidad['aprobados']);
        $this->assertSame(0, $modalidad['en_curso']);
    }

    /**
     * Consulta el resumen autenticado como usuario de gestión.
     *
     * @return array<string, mixed>
     */
    private function consultarResumen(): array
    {
        $gestion = User::factory()->role(Roles::KARDEX)->create();

        return $this->actingAs($gestion)
            ->getJson('/api/kardex/resumen')
            ->assertOk()
            ->json('resumen');
    }

    /**
     * Recupera la fila de una modalidad dentro del resumen.
     *
     * @param  array<string, mixed>  $resumen
     * @return array<string, mixed>
     */
    private function modalidadDe(array $resumen, string $nombre): array
    {
        foreach ($resumen['modalidades'] as $modalidad) {
            if ($modalidad['nombre'] === $nombre) {
                return $modalidad;
            }
        }

        $this->fail("No se encontró la modalidad {$nombre} en el resumen.");
    }

    /**
     * Trámite de una modalidad en el estado indicado.
     */
    private function crearTramite(Modalidad $modalidad, string $estado): Tramite
    {
        return Tramite::factory()->create([
            'id_estudiante' => Estudiante::factory()->create()->id_estudiante,
            'id_modalidad' => $modalidad->id_modalidad,
            'estado_actual' => $estado,
        ]);
    }
}
