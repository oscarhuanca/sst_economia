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
 * Solicitud de Examen de Grado presentada desde el Portal del Estudiante.
 */
class ExamenSolicitudTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_estudiante_presenta_la_solicitud_de_examen_de_grado(): void
    {
        $modalidad = Modalidad::factory()->examen()->create();
        $estudiante = Estudiante::factory()->conCuenta()->create();

        $respuesta = $this->actingAs($this->usuarioDe($estudiante))
            ->postJson('/api/examen/solicitud')
            ->assertCreated();

        $this->assertSame($modalidad->id_modalidad, $respuesta->json('id_modalidad'));
        $this->assertSame('Examen de Grado', $respuesta->json('modalidad.nombre'));
        $this->assertSame('solicitud_presentada', $respuesta->json('estado_actual'));
        $this->assertSame(1, Tramite::where('id_estudiante', $estudiante->id_estudiante)->count());
        $this->assertSame(1, $estudiante->refresh()->numero_intentos);
    }

    public function test_no_se_presenta_una_segunda_solicitud_mientras_hay_una_en_curso(): void
    {
        Modalidad::factory()->examen()->create();
        $estudiante = Estudiante::factory()->conCuenta()->create();
        $usuario = $this->usuarioDe($estudiante);

        $this->actingAs($usuario)->postJson('/api/examen/solicitud')->assertCreated();
        $this->actingAs($usuario)->postJson('/api/examen/solicitud')->assertStatus(400);

        $this->assertSame(1, Tramite::where('id_estudiante', $estudiante->id_estudiante)->count());
    }

    public function test_no_se_puede_solicitar_examen_con_los_intentos_agotados(): void
    {
        Modalidad::factory()->examen()->create();
        $estudiante = Estudiante::factory()->conCuenta()->create(['numero_intentos' => 3]);

        $this->actingAs($this->usuarioDe($estudiante))
            ->postJson('/api/examen/solicitud')
            ->assertStatus(400);

        $this->assertSame(0, Tramite::where('id_estudiante', $estudiante->id_estudiante)->count());
    }

    public function test_solo_el_rol_estudiante_puede_presentar_la_solicitud(): void
    {
        Modalidad::factory()->examen()->create();
        $gestion = User::factory()->role(Roles::GESTION[0])->create();

        $this->actingAs($gestion)
            ->postJson('/api/examen/solicitud')
            ->assertForbidden();
    }

    private function usuarioDe(Estudiante $estudiante): User
    {
        return User::where('estudiante_id', $estudiante->id_estudiante)->firstOrFail();
    }
}
