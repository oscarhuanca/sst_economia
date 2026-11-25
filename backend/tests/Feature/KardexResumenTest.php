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

    public function test_el_resumen_cuenta_y_lista_los_postulantes_sin_credenciales(): void
    {
        $sinCuenta = Estudiante::factory()->create([
            'nombres' => 'Pendiente',
            'apellidos' => 'Sin Cuenta',
        ]);
        Estudiante::factory()->conCuenta()->create([
            'nombres' => 'Verificado',
            'apellidos' => 'Con Cuenta',
        ]);

        $estudiantes = $this->consultarResumen()['estudiantes'];

        $this->assertSame(1, $estudiantes['pendientes_credenciales']);
        $this->assertCount(1, $estudiantes['pendientes']);
        $this->assertSame($sinCuenta->ci, $estudiantes['pendientes'][0]['ci']);
        $this->assertSame('Pendiente Sin Cuenta', $estudiantes['pendientes'][0]['nombres'].' '.$estudiantes['pendientes'][0]['apellidos']);
    }

    public function test_el_resumen_cuenta_y_lista_solo_las_solicitudes_nuevas_de_titulacion(): void
    {
        $tesis = Modalidad::factory()->tesis()->create();
        $examen = Modalidad::factory()->examen()->create();

        $estudianteTesis = Estudiante::factory()->create([
            'nombres' => 'Modalidad',
            'apellidos' => 'Tesis Nueva',
        ]);
        $tramiteTesis = Tramite::factory()->create([
            'id_estudiante' => $estudianteTesis->id_estudiante,
            'id_modalidad' => $tesis->id_modalidad,
            'estado_actual' => 'solicitud_presentada',
        ]);

        $this->crearTramite($tesis, 'perfil_aprobado');
        $this->crearTramite($examen, 'aprobado');
        $this->crearTramite($tesis, 'titulado');

        $estudiantes = $this->consultarResumen()['estudiantes'];

        $this->assertSame(1, $estudiantes['con_modalidad']);
        $this->assertCount(1, $estudiantes['titulaciones']);
        $this->assertSame(
            [$tramiteTesis->id_tramite],
            collect($estudiantes['titulaciones'])->pluck('id_tramite')->all()
        );

        $listado = $estudiantes['titulaciones'][0];
        $this->assertSame($estudianteTesis->ci, $listado['ci']);
        $this->assertSame('Modalidad Tesis Nueva', $listado['nombres'].' '.$listado['apellidos']);
        $this->assertSame('Tesis de Grado', $listado['modalidad']);
        $this->assertSame('solicitud_presentada', $listado['estado_actual']);
    }

    public function test_el_resumen_incluye_la_solicitud_nueva_de_excelencia_academica(): void
    {
        $excelencia = Modalidad::factory()->excelencia()->create();
        $estudiante = Estudiante::factory()->create([
            'nombres' => 'Solicitud',
            'apellidos' => 'Excelencia Nueva',
        ]);
        $tramite = Tramite::factory()->create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_modalidad' => $excelencia->id_modalidad,
            'estado_actual' => 'solicitud_presentada',
        ]);

        $estudiantes = $this->consultarResumen()['estudiantes'];

        $this->assertSame(1, $estudiantes['con_modalidad']);
        $this->assertCount(1, $estudiantes['titulaciones']);

        $listado = $estudiantes['titulaciones'][0];
        $this->assertSame($tramite->id_tramite, $listado['id_tramite']);
        $this->assertSame($estudiante->ci, $listado['ci']);
        $this->assertSame('Solicitud Excelencia Nueva', $listado['nombres'].' '.$listado['apellidos']);
        $this->assertSame('Excelencia Académica', $listado['modalidad']);
        $this->assertSame('solicitud_presentada', $listado['estado_actual']);
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
