<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Modalidad;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Segunda etapa del registro desde KARDEX: solo se asigna la modalidad y se
 * inicia el trámite. El servicio no genera credenciales: exige que la etapa 1
 * ya haya creado y vinculado la cuenta del postulante.
 */
class KardexAsignarModalidadTest extends TestCase
{
    use RefreshDatabase;

    public function test_asignar_modalidad_sin_credenciales_devuelve_un_422_y_no_crea_tramite(): void
    {
        $modalidad = Modalidad::factory()->tesis()->create();
        $estudiante = Estudiante::factory()->create();
        $kardex = User::factory()->role(Roles::KARDEX)->create();

        $this->actingAs($kardex)
            ->postJson('/api/kardex/postulantes/asignar-modalidad', [
                'identificador' => $estudiante->ci,
                'id_modalidad' => $modalidad->id_modalidad,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('identificador');

        $this->assertDatabaseMissing('users', ['estudiante_id' => $estudiante->id_estudiante]);
        $this->assertDatabaseMissing('tramites', ['id_estudiante' => $estudiante->id_estudiante]);
    }

    public function test_asignar_modalidad_crea_el_tramite_para_un_postulante_verificado(): void
    {
        $modalidad = Modalidad::factory()->tesis()->create();
        $estudiante = Estudiante::factory()->conCuenta()->create();
        $kardex = User::factory()->role(Roles::KARDEX)->create();

        $respuesta = $this->actingAs($kardex)
            ->postJson('/api/kardex/postulantes/asignar-modalidad', [
                'identificador' => $estudiante->ci,
                'id_modalidad' => $modalidad->id_modalidad,
            ])
            ->assertCreated()
            ->assertJsonPath('credenciales.generadas', false)
            ->assertJsonPath('tramite.estado_actual', 'solicitud_presentada');

        $this->assertNull($respuesta->json('credenciales.password'));
        $this->assertDatabaseHas('tramites', [
            'id_estudiante' => $estudiante->id_estudiante,
            'id_modalidad' => $modalidad->id_modalidad,
        ]);
    }
}
