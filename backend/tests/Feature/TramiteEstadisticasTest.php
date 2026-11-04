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
 * Estadísticas de graduación del panel de administración: `titulado` debe
 * contar como aprobado y el `aprobado` de Examen de Grado aún no cierra el
 * flujo (debe emitir el reporte final).
 */
class TramiteEstadisticasTest extends TestCase
{
    use RefreshDatabase;

    public function test_titulado_cuenta_como_aprobado(): void
    {
        $tesis = Modalidad::factory()->tesis()->create();
        $this->crearTramite($tesis, 'titulado');

        $modalidad = $this->modalidadDe('Tesis de Grado');

        $this->assertSame(1, $modalidad['aprobados']);
        $this->assertSame(0, $modalidad['reprobados']);
        $this->assertSame(1, $modalidad['total']);
    }

    public function test_el_aprobado_de_examen_de_grado_no_cuenta_como_aprobado(): void
    {
        $examen = Modalidad::factory()->examen()->create();
        $this->crearTramite($examen, 'aprobado');

        $modalidad = $this->modalidadDe('Examen de Grado');

        $this->assertSame(0, $modalidad['aprobados']);
        $this->assertSame(1, $modalidad['total']);
    }

    public function test_el_aprobado_de_tesis_cuenta_como_aprobado(): void
    {
        $tesis = Modalidad::factory()->tesis()->create();
        $this->crearTramite($tesis, 'aprobado');

        $this->assertSame(1, $this->modalidadDe('Tesis de Grado')['aprobados']);
    }

    public function test_los_fallos_se_cuentan_como_reprobados(): void
    {
        $tesis = Modalidad::factory()->tesis()->create();
        $this->crearTramite($tesis, 'reprobado');
        $this->crearTramite($tesis, 'rechazado');

        $modalidad = $this->modalidadDe('Tesis de Grado');

        $this->assertSame(2, $modalidad['reprobados']);
        $this->assertSame(0, $modalidad['aprobados']);
    }

    /**
     * @return array<string, mixed>
     */
    private function modalidadDe(string $nombre): array
    {
        $gestion = User::factory()->role(Roles::ADMIN)->create();

        $estadisticas = $this->actingAs($gestion)
            ->getJson('/api/tramites/estadisticas')
            ->assertOk()
            ->json();

        foreach ($estadisticas['porModalidad'] as $modalidad) {
            if ($modalidad['nombre'] === $nombre) {
                return $modalidad;
            }
        }

        $this->fail("No se encontró la modalidad {$nombre} en las estadísticas.");
    }

    private function crearTramite(Modalidad $modalidad, string $estado): Tramite
    {
        return Tramite::factory()->create([
            'id_estudiante' => Estudiante::factory()->create()->id_estudiante,
            'id_modalidad' => $modalidad->id_modalidad,
            'estado_actual' => $estado,
        ]);
    }
}
