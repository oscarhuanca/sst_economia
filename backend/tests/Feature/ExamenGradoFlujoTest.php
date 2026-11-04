<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\HistorialEstadoExamen;
use App\Models\Modalidad;
use App\Models\Tramite;
use App\Models\User;
use App\Modules\Tramites\Services\TramiteService;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Flujo de Examen de Grado gestionado por KARDEX.
 *
 * Cubre las reglas que no se deducen del resto del sistema: el límite de
 * intentos, los sorteos que registran asistencia con tema y fecha, la
 * asignación de notas de ambos exámenes en un módulo aparte y posterior a los
 * sorteos, la reprobación automática por no asistir y la reapertura del trámite
 * cuando quedó algún examen sin aprobar. Reprobar UN SOLO examen conserva la
 * nota del aprobado y la Resolución del HCC en curso; reprobar AMBOS exige una
 * nueva Resolución y rendir de nuevo los dos exámenes.
 */
class ExamenGradoFlujoTest extends TestCase
{
    use RefreshDatabase;

    public function test_kardex_verifica_los_intentos_y_habilita_al_estudiante(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->modulo($kardex, $tramite, 'verificacion_intentos', ['accion' => 'aprobar'])
            ->assertCreated();

        $this->assertSame('intentos_verificados', $tramite->refresh()->estado_actual);

        $habilitacion = $this->modulo($kardex, $tramite, 'habilitacion_examen', [
            'numero_resolucion' => 'HCC-042',
            'fecha_resolucion' => '2026-09-01',
        ])->assertCreated();

        $tramite->refresh();

        $this->assertSame('habilitado_examen', $tramite->estado_actual);
        $this->assertSame('HCC-042', $tramite->hitos['modulos']['habilitacion_examen']['numero_resolucion']);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'habilitacion_examen',
            'numero_resolucion' => 'HCC-042',
            'fecha_resolucion' => '2026-09-01',
        ]);

        // La fecha de la resolución viaja en la respuesta para la tarjeta y el
        // reporte, y no se confunde con la fecha en que se rinde el examen.
        $fila = collect($habilitacion->json('historial_examen'))
            ->firstWhere('etapa', 'habilitacion_examen');

        $this->assertSame('2026-09-01', substr((string) $fila['fecha_resolucion'], 0, 10));
        $this->assertNull($fila['fecha_examen']);
    }

    public function test_la_habilitacion_exige_el_numero_de_resolucion(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->modulo($kardex, $tramite, 'verificacion_intentos', ['accion' => 'aprobar'])
            ->assertCreated();

        $this->modulo($kardex, $tramite, 'habilitacion_examen', ['fecha_resolucion' => '2026-09-01'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('intentos_verificados', $tramite->refresh()->estado_actual);
    }

    public function test_se_rechaza_la_solicitud_cuando_el_estudiante_agoto_los_intentos(): void
    {
        $estudiante = $this->crearEstudiante(numero_intentos: 3);
        $tramite = $this->crearTramiteExamen($estudiante);
        $kardex = $this->crearUsuarioGestion();

        // Con los intentos agotados no se puede seguir el flujo...
        $this->modulo($kardex, $tramite, 'verificacion_intentos', ['accion' => 'aprobar'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('numero_intentos');

        $this->assertSame('solicitud_presentada', $tramite->refresh()->estado_actual);

        // ...y el rechazo exige el motivo.
        $this->modulo($kardex, $tramite, 'verificacion_intentos', ['accion' => 'rechazar'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('observaciones');

        $this->modulo($kardex, $tramite, 'verificacion_intentos', [
            'accion' => 'rechazar',
            'observaciones' => 'Agotó los tres intentos permitidos para el examen de grado.',
        ])->assertCreated();

        $this->assertSame('rechazado', $tramite->refresh()->estado_actual);
    }

    public function test_no_se_rechaza_por_intentos_si_el_estudiante_todavia_tiene(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->modulo($kardex, $tramite, 'verificacion_intentos', [
            'accion' => 'rechazar',
            'observaciones' => 'Motivo cualquiera.',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('numero_intentos');

        $this->assertSame('solicitud_presentada', $tramite->refresh()->estado_actual);
    }

    public function test_el_sorteo_teorico_registra_asistencia_tema_y_fecha(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => true,
            'tema' => 'Normativa de gestión administrativa del Estado',
            'fecha_examen' => '2026-10-15',
        ])->assertCreated();

        $this->assertSame('examen_teorico_sorteado', $tramite->refresh()->estado_actual);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'sorteo_teorico',
            'resultado' => 'asistio',
            'tema' => 'Normativa de gestión administrativa del Estado',
            'nota' => null,
        ]);
    }

    public function test_la_resolucion_del_resultado_teorico_exige_numero_y_fecha(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => true,
            'tema' => 'Normativa de gestión administrativa del Estado',
            'fecha_examen' => '2026-10-15',
        ])->assertCreated();

        $this->modulo($kardex, $tramite, 'resolucion_resultado_teorico', [
            'fecha_resolucion' => '2026-10-16',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('examen_teorico_sorteado', $tramite->refresh()->estado_actual);

        $this->modulo($kardex, $tramite, 'resolucion_resultado_teorico', [
            'numero_resolucion' => 'HCC-045',
            'fecha_resolucion' => '2026-10-16',
        ])->assertCreated();

        $tramite->refresh();

        $this->assertSame('examen_teorico_aprobado', $tramite->estado_actual);
        $this->assertSame('HCC-045', $tramite->hitos['modulos']['resolucion_resultado_teorico']['numero_resolucion']);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'resolucion_resultado_teorico',
            'resultado' => 'aprobado',
            'numero_resolucion' => 'HCC-045',
            'fecha_resolucion' => '2026-10-16',
        ]);
    }

    public function test_la_resolucion_de_asignacion_de_notas_exige_numero_y_fecha(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => true,
            'tema' => 'Tema teórico',
            'fecha_examen' => '2026-10-15',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'resolucion_resultado_teorico', [
            'numero_resolucion' => 'HCC-045',
            'fecha_resolucion' => '2026-10-16',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'sorteo_especialidad', [
            'asistio' => true,
            'tema' => 'Tema de especialidad',
            'fecha_examen' => '2026-10-20',
        ])->assertCreated();

        $this->modulo($kardex, $tramite, 'resolucion_asignacion_notas', [
            'fecha_resolucion' => '2026-10-21',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('examen_especialidad_sorteado', $tramite->refresh()->estado_actual);

        $this->modulo($kardex, $tramite, 'resolucion_asignacion_notas', [
            'numero_resolucion' => 'HCC-046',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->modulo($kardex, $tramite, 'resolucion_asignacion_notas', [
            'numero_resolucion' => 'HCC-046',
            'fecha_resolucion' => '2026-10-21',
        ])->assertCreated();

        $tramite->refresh();

        $this->assertSame('notas_habilitadas', $tramite->estado_actual);
        $this->assertSame('HCC-046', $tramite->hitos['modulos']['resolucion_asignacion_notas']['numero_resolucion']);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'resolucion_asignacion_notas',
            'resultado' => 'resolucion_registrada',
            'numero_resolucion' => 'HCC-046',
            'fecha_resolucion' => '2026-10-21',
        ]);
    }

    public function test_la_asignacion_de_notas_exige_la_resolucion_de_asignacion(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => true,
            'tema' => 'Tema teórico',
            'fecha_examen' => '2026-10-15',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'resolucion_resultado_teorico', [
            'numero_resolucion' => 'HCC-047',
            'fecha_resolucion' => '2026-10-16',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'sorteo_especialidad', [
            'asistio' => true,
            'tema' => 'Tema de especialidad',
            'fecha_examen' => '2026-10-20',
        ])->assertCreated();

        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 74,
            'nota_especialidad' => 82,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('examen_especialidad_sorteado', $tramite->refresh()->estado_actual);
    }

    public function test_la_nota_del_teorico_se_asigna_despues_de_los_dos_sorteos(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        $this->sorteosAsistidos($tramite, $kardex);

        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 74,
            'nota_especialidad' => 82,
        ])->assertCreated();

        $tramite->refresh();

        $this->assertSame('aprobado', $tramite->estado_actual);
        $this->assertSame(74, $tramite->hitos['modulos']['asignacion_notas']['nota_teorico']);
        $this->assertSame(82, $tramite->hitos['modulos']['asignacion_notas']['nota_especialidad']);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'asignacion_notas',
            'resultado' => 'aprobado',
            'nota' => 74,
        ]);
    }

    public function test_una_nota_inferior_al_minimo_reprueba_al_cerrar_las_notas(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);
        $this->sorteosAsistidos($tramite, $kardex);

        // El teórico con 50 deja el intento reprobado, aunque la especialidad
        // esté aprobada: titularse exige los dos exámenes con 51 o más.
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 50,
            'nota_especialidad' => 90,
        ])->assertCreated();

        $this->assertSame('examen_teorico_reprobado', $tramite->refresh()->estado_actual);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'asignacion_notas',
            'resultado' => 'reprobado',
        ]);
    }

    public function test_una_nota_fuera_del_rango_se_rechaza(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);
        $this->sorteosAsistidos($tramite, $kardex);

        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 80,
            'nota_especialidad' => 120,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('notas_habilitadas', $tramite->refresh()->estado_actual);
    }

    public function test_no_asistir_al_sorteo_teorico_no_bloquea_y_reprueba_al_asignar_notas(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => false,
            'fecha_examen' => '2026-10-15',
        ])->assertCreated();

        // La ausencia NO reproba todavía: el sorteo queda registrado y el
        // estudiante puede seguir al sorteo del examen de especialidad.
        $this->assertSame('examen_teorico_sorteado', $tramite->refresh()->estado_actual);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'sorteo_teorico',
            'resultado' => 'no_asistio',
            'nota' => null,
        ]);

        // El sorteo de especialidad exige la Resolución de aprobación del
        // resultado teórico, aunque el estudiante no haya asistido al sorteo.
        $this->modulo($kardex, $tramite, 'sorteo_especialidad', [
            'asistio' => true,
            'tema' => 'Tema de especialidad',
            'fecha_examen' => '2026-10-20',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->modulo($kardex, $tramite, 'resolucion_resultado_teorico', [
            'numero_resolucion' => 'HCC-050',
            'fecha_resolucion' => '2026-10-16',
        ])->assertCreated();

        $this->assertSame('examen_teorico_aprobado', $tramite->refresh()->estado_actual);

        $this->modulo($kardex, $tramite, 'sorteo_especialidad', [
            'asistio' => true,
            'tema' => 'Tema de especialidad',
            'fecha_examen' => '2026-10-20',
        ])->assertCreated();

        $this->assertSame('examen_especialidad_sorteado', $tramite->refresh()->estado_actual);

        $this->resolucionAsignacionNotas($kardex, $tramite, 'HCC-050-A');

        // Sin nota de teórico (no asistió), la asignación de notas deja el
        // trámite reabrible por el examen teórico.
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => '',
            'nota_especialidad' => 90,
        ])->assertCreated();

        $this->assertSame('examen_teorico_reprobado', $tramite->refresh()->estado_actual);
    }

    public function test_no_asistir_al_sorteo_de_especialidad_reprueba_el_examen_de_grado(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => true,
            'tema' => 'Tema',
            'fecha_examen' => '2026-10-15',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'resolucion_resultado_teorico', [
            'numero_resolucion' => 'HCC-051',
            'fecha_resolucion' => '2026-10-16',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'sorteo_especialidad', [
            'asistio' => false,
            'fecha_examen' => '2026-10-20',
        ])->assertCreated();

        $this->assertSame('examen_especialidad_sorteado', $tramite->refresh()->estado_actual);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'sorteo_especialidad',
            'resultado' => 'no_asistio',
            'nota' => null,
        ]);

        $this->resolucionAsignacionNotas($kardex, $tramite, 'HCC-051-A');

        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 80,
            'nota_especialidad' => '',
        ])->assertCreated();

        $this->assertSame('examen_especialidad_reprobado', $tramite->refresh()->estado_actual);
    }

    public function test_la_resolucion_de_asignacion_de_notas_exige_los_dos_sorteos_previos(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        // Solo está registrado el sorteo teórico: falta el de especialidad.
        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => true,
            'tema' => 'Tema',
            'fecha_examen' => '2026-10-15',
        ])->assertCreated();

        $this->modulo($kardex, $tramite, 'resolucion_asignacion_notas', [
            'numero_resolucion' => 'HCC-060',
            'fecha_resolucion' => '2026-10-21',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('examen_teorico_sorteado', $tramite->refresh()->estado_actual);
    }

    public function test_la_asignacion_de_notas_exige_la_nota_de_un_examen_asistido(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);
        $this->sorteosAsistidos($tramite, $kardex);

        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 74,
            'nota_especialidad' => '',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('notas_habilitadas', $tramite->refresh()->estado_actual);
    }

    public function test_reprobar_el_teorico_sin_nota_no_impide_rendir_el_de_especialidad(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => true,
            'tema' => 'Tema',
            'fecha_examen' => '2026-10-15',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'resolucion_resultado_teorico', [
            'numero_resolucion' => 'HCC-052',
            'fecha_resolucion' => '2026-10-16',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'sorteo_especialidad', [
            'asistio' => true,
            'tema' => 'Tema de especialidad',
            'fecha_examen' => '2026-10-20',
        ])->assertCreated();
        $this->resolucionAsignacionNotas($kardex, $tramite, 'HCC-052-A');
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 50,
            'nota_especialidad' => 90,
        ])->assertCreated();

        $this->assertSame('examen_teorico_reprobado', $tramite->refresh()->estado_actual);

        // Aprobar la especialidad no habilita el reporte: el teórico sigue
        // reprobado, así que el trámite queda reabrible para re-solicitar el
        // examen de grado.
        $this->modulo($kardex, $tramite, 'reporte')
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');
    }

    public function test_aprobar_la_especialidad_con_el_teorico_reprobado_no_titula(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);
        $this->sorteosAsistidos($tramite, $kardex);
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 40,
            'nota_especialidad' => 95,
        ])->assertCreated();

        $this->assertSame('examen_teorico_reprobado', $tramite->refresh()->estado_actual);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'asignacion_notas',
            'resultado' => 'reprobado',
            'nota' => 40,
        ]);

        $this->modulo($kardex, $tramite, 'reporte')
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('examen_teorico_reprobado', $tramite->refresh()->estado_actual);
    }

    public function test_un_examen_aprobado_se_conserva_y_la_reapertura_retoma_solo_lo_reprobado(): void
    {
        $estudiante = $this->crearEstudiante(numero_intentos: 1);
        $tramite = $this->crearTramiteExamen($estudiante);
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        // Primer intento: aprueba el teórico (80) y reproba la especialidad (30).
        $this->sorteosAsistidos($tramite, $kardex);
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 80,
            'nota_especialidad' => 30,
        ])->assertCreated();

        $tramite->refresh();

        $this->assertSame('examen_especialidad_reprobado', $tramite->estado_actual);
        $this->assertSame(80, $tramite->hitos['examen_aprobado']['examen_teorico']['nota']);

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/examen/reabrir")
            ->assertOk();

        // La reapertura solo vuelve al punto en que se sortea la especialidad:
        // el teórico aprobado se conserva (con su nota) y la resolución en curso
        // sigue valiendo, así que no se pide una nueva habilitación.
        $tramite->refresh();

        $this->assertSame('examen_teorico_aprobado', $tramite->estado_actual);
        $this->assertArrayNotHasKey('sorteo_especialidad', $tramite->hitos['modulos']);
        $this->assertArrayNotHasKey('resolucion_asignacion_notas', $tramite->hitos['modulos']);
        $this->assertArrayNotHasKey('asignacion_notas', $tramite->hitos['modulos']);
        $this->assertArrayHasKey('sorteo_teorico', $tramite->hitos['modulos']);
        $this->assertArrayHasKey('resolucion_resultado_teorico', $tramite->hitos['modulos']);
        $this->assertArrayHasKey('habilitacion_examen', $tramite->hitos['modulos']);
        $this->assertSame(80, $tramite->hitos['examen_aprobado']['examen_teorico']['nota']);

        // Sin volver a sortear el teórico (se conserva), se sortea la
        // especialidad, se registra su Resolución de asignación de notas y se
        // asigna la nota: el aprobado conservado ya no exige un nuevo sorteo ni
        // reasignación.
        $this->modulo($kardex, $tramite, 'sorteo_especialidad', [
            'asistio' => true,
            'tema' => 'Tema de especialidad',
            'fecha_examen' => '2026-11-10',
        ])->assertCreated();

        $this->assertSame('examen_especialidad_sorteado', $tramite->refresh()->estado_actual);

        $this->resolucionAsignacionNotas($kardex, $tramite, 'HCC-070');

        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => '',
            'nota_especialidad' => 70,
        ])->assertCreated();

        $tramite->refresh();

        $this->assertSame('aprobado', $tramite->estado_actual);
        $this->assertSame(80, $tramite->hitos['examen_aprobado']['examen_teorico']['nota']);
        $this->assertSame(70, $tramite->hitos['examen_aprobado']['examen_especialidad']['nota']);

        $this->modulo($kardex, $tramite, 'reporte', [])->assertCreated();

        $this->assertSame('titulado', $tramite->refresh()->estado_actual);
    }

    public function test_reprobar_ambos_examenes_vuelve_a_intentos_y_exige_una_nueva_resolucion(): void
    {
        $estudiante = $this->crearEstudiante(numero_intentos: 1);
        $tramite = $this->crearTramiteExamen($estudiante);
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);
        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => false,
            'fecha_examen' => '2026-10-15',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'resolucion_resultado_teorico', [
            'numero_resolucion' => 'HCC-053',
            'fecha_resolucion' => '2026-10-16',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'sorteo_especialidad', [
            'asistio' => false,
            'fecha_examen' => '2026-10-20',
        ])->assertCreated();
        $this->resolucionAsignacionNotas($kardex, $tramite, 'HCC-053-A');
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => '',
            'nota_especialidad' => '',
        ])->assertCreated();

        $tramite->refresh();

        // Sin ninguna aprobación, el estado reabrible es `examen_ambos_reprobados`.
        $this->assertSame('examen_ambos_reprobados', $tramite->estado_actual);
        $this->assertArrayNotHasKey('examen_aprobado', $tramite->hitos);

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/examen/reabrir", [
                'observaciones' => 'El estudiante pidió una nueva fecha para el sorteo.',
            ])
            ->assertOk();

        // Con ambos reprobados la reapertura vuelve a la verificación de
        // intentos: la resolución anterior deja de contar y hay que habilitar
        // al estudiante de nuevo con una NUEVA Resolución del HCC.
        $tramite->refresh();

        $this->assertSame('intentos_verificados', $tramite->estado_actual);
        $this->assertSame(2, $estudiante->refresh()->numero_intentos);

        // Se reinician los dos sorteos, las dos Resoluciones del intento, las
        // notas y la habilitación vigente.
        $this->assertArrayNotHasKey('sorteo_teorico', $tramite->hitos['modulos']);
        $this->assertArrayNotHasKey('resolucion_resultado_teorico', $tramite->hitos['modulos']);
        $this->assertArrayNotHasKey('sorteo_especialidad', $tramite->hitos['modulos']);
        $this->assertArrayNotHasKey('resolucion_asignacion_notas', $tramite->hitos['modulos']);
        $this->assertArrayNotHasKey('asignacion_notas', $tramite->hitos['modulos']);
        $this->assertArrayNotHasKey('habilitacion_examen', $tramite->hitos['modulos']);
        $this->assertArrayNotHasKey('examen_aprobado', $tramite->hitos);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'reapertura',
            'resultado' => 're_solicitud',
            'numero_intentos' => 2,
        ]);

        // Registrada la nueva resolución, el estudiante vuelve a quedar
        // habilitado para rendir los dos exámenes.
        $this->modulo($kardex, $tramite, 'habilitacion_examen', [
            'numero_resolucion' => 'HCC-099',
            'fecha_resolucion' => '2026-11-02',
        ])->assertCreated();

        $tramite->refresh();

        $this->assertSame('habilitado_examen', $tramite->estado_actual);
        $this->assertSame('HCC-099', $tramite->hitos['modulos']['habilitacion_examen']['numero_resolucion']);
    }

    public function test_una_reapertura_parcial_conserva_la_resolucion_y_no_pide_nueva_habilitacion(): void
    {
        $estudiante = $this->crearEstudiante(numero_intentos: 1);
        $tramite = $this->crearTramiteExamen($estudiante);
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);
        $this->sorteosAsistidos($tramite, $kardex);

        // Primer intento: el teórico reprobado (45) y la especialidad aprobada (90).
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 45,
            'nota_especialidad' => 90,
        ])->assertCreated();

        $this->assertSame('examen_teorico_reprobado', $tramite->refresh()->estado_actual);

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/examen/reabrir")
            ->assertOk();

        // Reprobar UN SOLO examen no devuelve a intentos: la resolución del HCC
        // y la nota de especialidad se conservan y solo se repite el sorteo del
        // teórico.
        $tramite->refresh();

        $this->assertSame('habilitado_examen', $tramite->estado_actual);
        $this->assertArrayHasKey('habilitacion_examen', $tramite->hitos['modulos']);
        $this->assertArrayHasKey('sorteo_especialidad', $tramite->hitos['modulos']);
        $this->assertArrayNotHasKey('sorteo_teorico', $tramite->hitos['modulos']);
        $this->assertArrayNotHasKey('resolucion_resultado_teorico', $tramite->hitos['modulos']);
        $this->assertArrayNotHasKey('resolucion_asignacion_notas', $tramite->hitos['modulos']);
        $this->assertArrayNotHasKey('asignacion_notas', $tramite->hitos['modulos']);
        $this->assertSame(90, $tramite->hitos['examen_aprobado']['examen_especialidad']['nota']);

        // Se sortea solo el teórico y, con la especialidad conservada, se registra
        // la Resolución de asignación de notas y se llega a `aprobado` sin
        // sortear la especialidad de nuevo.
        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => true,
            'tema' => 'Tema teórico',
            'fecha_examen' => '2026-11-05',
        ])->assertCreated();

        $this->assertSame('examen_teorico_sorteado', $tramite->refresh()->estado_actual);

        $this->modulo($kardex, $tramite, 'resolucion_resultado_teorico', [
            'numero_resolucion' => 'HCC-054',
            'fecha_resolucion' => '2026-11-06',
        ])->assertCreated();

        $this->assertSame('examen_teorico_aprobado', $tramite->refresh()->estado_actual);

        $this->resolucionAsignacionNotas($kardex, $tramite, 'HCC-054-A');

        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 60,
            'nota_especialidad' => '',
        ])->assertCreated();

        $tramite->refresh();

        $this->assertSame('aprobado', $tramite->estado_actual);
        $this->assertSame(60, $tramite->hitos['examen_aprobado']['examen_teorico']['nota']);
        $this->assertSame(90, $tramite->hitos['examen_aprobado']['examen_especialidad']['nota']);
    }

    public function test_una_resolucion_de_un_intento_anterior_no_habilita_la_especialidad_tras_varias_reaperturas(): void
    {
        $estudiante = $this->crearEstudiante(numero_intentos: 0);
        $tramite = $this->crearTramiteExamen($estudiante);
        $kardex = $this->crearUsuarioGestion();

        // Primer intento: ambos exámenes reprobados y reapertura completa.
        $this->habilitar($kardex, $tramite);
        $this->sorteosAsistidos($tramite, $kardex);
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 40,
            'nota_especialidad' => 40,
        ])->assertCreated();

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/examen/reabrir")
            ->assertOk();

        // Segundo intento: vuelve a reprobar ambos y se reabre otra vez.
        $this->modulo($kardex, $tramite, 'habilitacion_examen', [
            'numero_resolucion' => 'HCC-004',
            'fecha_resolucion' => '2026-11-01',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => true,
            'tema' => 'Tema teórico del segundo intento',
            'fecha_examen' => '2026-11-10',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'resolucion_resultado_teorico', [
            'numero_resolucion' => 'HCC-003',
            'fecha_resolucion' => '2026-11-11',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'sorteo_especialidad', [
            'asistio' => true,
            'tema' => 'Tema de especialidad del segundo intento',
            'fecha_examen' => '2026-11-15',
        ])->assertCreated();
        $this->resolucionAsignacionNotas($kardex, $tramite, 'HCC-003-A');
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 30,
            'nota_especialidad' => 30,
        ])->assertCreated();

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/examen/reabrir")
            ->assertOk();

        // Tercer intento: la Resolución del segundo intento NO cuenta para
        // habilitar la especialidad; se exige una nueva en el intento actual.
        $this->modulo($kardex, $tramite, 'habilitacion_examen', [
            'numero_resolucion' => 'HCC-005',
            'fecha_resolucion' => '2026-11-20',
        ])->assertCreated();
        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => true,
            'tema' => 'Tema teórico del tercer intento',
            'fecha_examen' => '2026-12-01',
        ])->assertCreated();

        $this->modulo($kardex, $tramite, 'sorteo_especialidad', [
            'asistio' => true,
            'tema' => 'Tema de especialidad del tercer intento',
            'fecha_examen' => '2026-12-05',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');

        $this->assertSame('examen_teorico_sorteado', $tramite->refresh()->estado_actual);

        // Con la Resolución del intento actual sí se habilita el sorteo.
        $this->modulo($kardex, $tramite, 'resolucion_resultado_teorico', [
            'numero_resolucion' => 'HCC-006',
            'fecha_resolucion' => '2026-12-02',
        ])->assertCreated();

        $this->modulo($kardex, $tramite, 'sorteo_especialidad', [
            'asistio' => true,
            'tema' => 'Tema de especialidad del tercer intento',
            'fecha_examen' => '2026-12-05',
        ])->assertCreated();

        $this->assertSame('examen_especialidad_sorteado', $tramite->refresh()->estado_actual);
    }

    public function test_no_se_reabre_un_tramite_que_no_reprobo(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/examen/reabrir")
            ->assertStatus(422)
            ->assertJsonValidationErrors('estado_actual');

        $this->assertSame('solicitud_presentada', $tramite->refresh()->estado_actual);
    }

    public function test_no_se_reabre_si_el_estudiante_ya_agoto_los_intentos(): void
    {
        $estudiante = $this->crearEstudiante(numero_intentos: 3);
        $tramite = $this->crearTramiteExamen($estudiante);
        $kardex = $this->crearUsuarioGestion();

        $tramite->update(['estado_actual' => 'examen_teorico_reprobado']);

        $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/examen/reabrir")
            ->assertStatus(422)
            ->assertJsonValidationErrors('numero_intentos');

        $this->assertSame(3, $estudiante->refresh()->numero_intentos);
    }

    public function test_el_flujo_completo_llega_a_titulado_y_cierra_el_tramite(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);

        $this->sorteosAsistidos($tramite, $kardex);
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 80,
            'nota_especialidad' => 88,
        ])->assertCreated();

        $this->assertSame('aprobado', $tramite->refresh()->estado_actual);
        $this->assertSame(Carbon::today()->toDateString(), $tramite->hitos['aprobado'] ?? null);

        $this->modulo($kardex, $tramite, 'reporte', [])->assertCreated();

        // El reporte es el último paso: genera el PDF y titula al estudiante.
        $tramite->refresh();

        $this->assertSame('titulado', $tramite->estado_actual);
        // El hito de título es la fecha en que KARDEX cerró el trámite.
        $this->assertSame(Carbon::today()->toDateString(), $tramite->hitos['titulado'] ?? null);

        $this->assertDatabaseHas('historial_estados_examen', [
            'id_tramite' => $tramite->id_tramite,
            'etapa' => 'reporte',
            'nombre_estado' => 'titulado',
            'resultado' => 'titulado',
        ]);

        // Cerrado el flujo, ningún módulo admite cambios.
        $this->modulo($kardex, $tramite, 'reporte', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');
    }

    public function test_el_flujo_de_examen_no_se_registra_en_otras_modalidades(): void
    {
        $estudiante = $this->crearEstudianteConCuenta();
        $tramite = Tramite::factory()->create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_modalidad' => Modalidad::factory()->tesis()->create()->id_modalidad,
            'estado_actual' => 'perfil_aprobado',
        ]);

        $this->modulo($this->crearUsuarioGestion(), $tramite, 'verificacion_intentos', ['accion' => 'aprobar'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('modulo');

        $this->assertSame('perfil_aprobado', $tramite->refresh()->estado_actual);
    }

    public function test_el_detalle_de_kardex_expone_el_historial_completo_del_examen(): void
    {
        // La tarjeta de habilitación y el acta del reporte necesitan la fecha de
        // la resolución: si el eager load la omite, ambos salen en blanco aunque
        // esté guardada en la base de datos.
        $tramite = $this->crearTramiteExamen();

        $this->habilitar($this->crearUsuarioGestion(), $tramite);

        $historial = $this->actingAs($this->crearUsuarioGestion())
            ->getJson("/api/kardex/postulantes/consultar?identificador={$tramite->estudiante->ci}")
            ->assertOk()
            ->json('tramite.historial_examen');

        $this->assertNotEmpty($historial);

        $habilitacion = collect($historial)->firstWhere('etapa', 'habilitacion_examen');

        $this->assertSame('HCC-001', $habilitacion['numero_resolucion']);
        $this->assertSame('2026-09-01', substr((string) $habilitacion['fecha_resolucion'], 0, 10));
    }

    public function test_un_modulo_desconocido_se_rechaza(): void
    {
        $tramite = $this->crearTramiteExamen();

        $this->modulo($this->crearUsuarioGestion(), $tramite, 'notas_finales', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('datos');
    }

    public function test_un_examen_aprobado_impide_iniciar_otra_postulacion(): void
    {
        // Aunque `aprobado` en Examen deja el trámite abierto a la espera del
        // reporte, el estudiante ya obtuvo su título: no puede volver a postular.
        $servicio = app(TramiteService::class);
        $estudiante = $this->crearEstudianteConCuenta();
        $usuario = User::where('estudiante_id', $estudiante->id_estudiante)->firstOrFail();
        $modalidad = Modalidad::factory()->examen()->create()->id_modalidad;
        $nuevaSolicitud = ['id_modalidad' => $modalidad, 'documentos' => []];

        $aprobado = $this->crearTramiteExamen($estudiante);
        $aprobado->update(['estado_actual' => 'aprobado']);

        $this->expectException(\DomainException::class);

        try {
            $servicio->crear($nuevaSolicitud, $usuario);
        } finally {
            // Reprobar, en cambio, sí permite presentar una nueva solicitud: la
            // anterior ya no lo bloquea.
            $aprobado->update(['estado_actual' => 'examen_teorico_reprobado']);

            $this->assertSame(
                $modalidad,
                $servicio->crear($nuevaSolicitud, $usuario)->id_modalidad,
            );
        }
    }

    public function test_un_examen_aprobado_pendiente_de_reporte_no_cierra_el_tramite(): void
    {
        // `aprobado` es terminal en el resto de modalidades, pero en Examen de
        // Grado solo significa que los dos exámenes se rindieron: falta el reporte
        // que titula al estudiante, así que el trámite sigue en la cola de trabajo.
        $kardex = $this->crearUsuarioGestion();

        $reprobado = $this->crearTramiteExamen();
        $reprobado->update(['estado_actual' => 'examen_especialidad_reprobado']);

        $aprobado = $this->crearTramiteExamen();
        $aprobado->update(['estado_actual' => 'aprobado']);

        $ids = collect($this->actingAs($kardex)
            ->getJson('/api/tramites/pendientes')
            ->assertOk()
            ->json('data'))
            ->pluck('id_tramite');

        $this->assertTrue($ids->contains($reprobado->id_tramite), 'El reprobado debe seguir pendiente de reapertura.');
        $this->assertTrue($ids->contains($aprobado->id_tramite), 'El aprobado debe seguir pendiente del reporte.');

        $concluidos = collect($this->actingAs($kardex)
            ->getJson('/api/tramites/concluidos')
            ->assertOk()
            ->json('data'))
            ->pluck('id_tramite');

        $this->assertFalse($concluidos->contains($reprobado->id_tramite));
        $this->assertFalse($concluidos->contains($aprobado->id_tramite));
    }

    public function test_el_historial_registra_cada_paso_del_flujo(): void
    {
        $tramite = $this->crearTramiteExamen();
        $kardex = $this->crearUsuarioGestion();

        $this->habilitar($kardex, $tramite);
        $this->sorteosAsistidos($tramite, $kardex);
        $this->modulo($kardex, $tramite, 'asignacion_notas', [
            'nota_teorico' => 70,
            'nota_especialidad' => 75,
        ])->assertCreated();

        $this->assertSame(
            [
                'verificacion_intentos',
                'habilitacion_examen',
                'sorteo_teorico',
                'resolucion_resultado_teorico',
                'sorteo_especialidad',
                'resolucion_asignacion_notas',
                'asignacion_notas',
            ],
            HistorialEstadoExamen::where('id_tramite', $tramite->id_tramite)
                ->orderBy('id_historial_examen')
                ->pluck('etapa')
                ->all(),
        );
    }

    // ------------------------------------------------------------------
    // Utilidades
    // ------------------------------------------------------------------

    /**
     * Ejecuta un módulo del flujo de Examen de Grado como KARDEX.
     *
     * @param  array<string, mixed>  $datos  El módulo de reporte no pide campos,
     *                                       así que puede omitirse.
     */
    private function modulo(User $kardex, Tramite $tramite, string $modulo, array $datos = [])
    {
        return $this->actingAs($kardex)
            ->postJson("/api/tramites/{$tramite->id_tramite}/examen/{$modulo}", ['datos' => $datos]);
    }

    /** Deja el trámite habilitado para rendir el examen. */
    private function habilitar(User $kardex, Tramite $tramite): void
    {
        $this->modulo($kardex, $tramite, 'verificacion_intentos', ['accion' => 'aprobar'])
            ->assertCreated();

        $this->modulo($kardex, $tramite, 'habilitacion_examen', [
            'numero_resolucion' => 'HCC-001',
            'fecha_resolucion' => '2026-09-01',
        ])->assertCreated();
    }

    /** Registra los dos sorteos con asistencia y las dos resoluciones del HCC. */
    private function sorteosAsistidos(Tramite $tramite, User $kardex): void
    {
        $this->modulo($kardex, $tramite, 'sorteo_teorico', [
            'asistio' => true,
            'tema' => 'Tema teórico',
            'fecha_examen' => '2026-10-15',
        ])->assertCreated();

        $this->modulo($kardex, $tramite, 'resolucion_resultado_teorico', [
            'numero_resolucion' => 'HCC-002',
            'fecha_resolucion' => '2026-10-16',
        ])->assertCreated();

        $this->modulo($kardex, $tramite, 'sorteo_especialidad', [
            'asistio' => true,
            'tema' => 'Tema de especialidad',
            'fecha_examen' => '2026-10-20',
        ])->assertCreated();

        $this->resolucionAsignacionNotas($kardex, $tramite);
    }

    /** Registra la Resolución de asignación de notas. */
    private function resolucionAsignacionNotas(User $kardex, Tramite $tramite, string $numero = 'HCC-003'): void
    {
        $this->modulo($kardex, $tramite, 'resolucion_asignacion_notas', [
            'numero_resolucion' => $numero,
            'fecha_resolucion' => '2026-10-21',
        ])->assertCreated();
    }

    private function crearTramiteExamen(?Estudiante $estudiante = null): Tramite
    {
        $estudiante ??= $this->crearEstudianteConCuenta();

        return Tramite::factory()->create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_modalidad' => Modalidad::factory()->examen()->create()->id_modalidad,
            'estado_actual' => 'solicitud_presentada',
        ]);
    }

    private function crearEstudiante(?int $numero_intentos = null): Estudiante
    {
        return Estudiante::factory()->create(
            $numero_intentos === null ? [] : ['numero_intentos' => $numero_intentos],
        );
    }

    private function crearEstudianteConCuenta(): Estudiante
    {
        return Estudiante::factory()->conCuenta()->create();
    }

    private function crearUsuarioGestion(): User
    {
        return User::factory()->role(Roles::GESTION[0])->create();
    }
}
