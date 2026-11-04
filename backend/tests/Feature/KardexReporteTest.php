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
 * Reportes estadísticos del KARDEX: el módulo de Reporte debe poder filtrar por
 * modalidad y por mes/año, además de ofrecer el consolidado general.
 */
class KardexReporteTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_reporte_general_incluye_todos_los_tramites(): void
    {
        $tesis = Modalidad::factory()->tesis()->create();
        $examen = Modalidad::factory()->examen()->create();

        $this->crearTramite($tesis, 'titulado');
        $this->crearTramite($examen, 'aprobado');
        $this->crearTramite($tesis, 'reprobado');

        $reporte = $this->consultarReporte();

        $this->assertSame(3, $reporte['totales']['total']);
        $this->assertSame(1, $reporte['totales']['finalizados']);
        $this->assertSame(1, $reporte['totales']['fallos']);
        $this->assertSame(1, $reporte['totales']['en_proceso']);
        $this->assertCount(2, $reporte['por_modalidad']);
        $this->assertCount(3, $reporte['listado']);
    }

    public function test_el_reporte_se_filtra_por_modalidad(): void
    {
        $tesis = Modalidad::factory()->tesis()->create();
        $examen = Modalidad::factory()->examen()->create();

        $this->crearTramite($tesis, 'titulado');
        $this->crearTramite($examen, 'aprobado');

        $reporte = $this->consultarReporte(['id_modalidad' => $tesis->id_modalidad]);

        $this->assertSame(1, $reporte['totales']['total']);
        $this->assertSame('Tesis de Grado', $reporte['por_modalidad'][0]['nombre']);
    }

    public function test_el_reporte_se_filtra_por_anio_y_mes(): void
    {
        $tesis = Modalidad::factory()->tesis()->create();

        $this->crearTramite($tesis, 'titulado', '2025-03-15 10:00:00');
        $this->crearTramite($tesis, 'titulado', '2025-04-10 10:00:00');
        $this->crearTramite($tesis, 'titulado', '2024-03-10 10:00:00');

        $porAnio = $this->consultarReporte(['anio' => 2025]);
        $this->assertSame(2, $porAnio['totales']['total']);

        $porMes = $this->consultarReporte(['anio' => 2025, 'mes' => 3]);
        $this->assertSame(1, $porMes['totales']['total']);
        $this->assertSame('2025-03', $porMes['por_mes'][0]['clave']);
    }

    public function test_el_reporte_se_filtra_por_rango_de_fechas(): void
    {
        $tesis = Modalidad::factory()->tesis()->create();

        $this->crearTramite($tesis, 'titulado', '2025-01-10 10:00:00');
        $this->crearTramite($tesis, 'titulado', '2025-06-10 10:00:00');

        $reporte = $this->consultarReporte([
            'desde' => '2025-05-01',
            'hasta' => '2025-12-31',
        ]);

        $this->assertSame(1, $reporte['totales']['total']);
    }

    /**
     * Consulta el reporte autenticado como usuario de gestión.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    private function consultarReporte(array $filtros = []): array
    {
        $gestion = User::factory()->role(Roles::KARDEX)->create();

        return $this->actingAs($gestion)
            ->getJson('/api/kardex/reportes?'.http_build_query($filtros))
            ->assertOk()
            ->json('reporte');
    }

    /**
     * Trámite de una modalidad en el estado y fecha indicados.
     */
    private function crearTramite(Modalidad $modalidad, string $estado, ?string $fecha = null): Tramite
    {
        $tramite = Tramite::factory()->create([
            'id_estudiante' => Estudiante::factory()->create()->id_estudiante,
            'id_modalidad' => $modalidad->id_modalidad,
            'estado_actual' => $estado,
        ]);

        if ($fecha !== null) {
            $tramite->forceFill(['created_at' => $fecha])->saveQuietly();
        }

        return $tramite;
    }
}
