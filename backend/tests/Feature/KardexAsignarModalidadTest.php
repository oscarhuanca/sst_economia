<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Modalidad;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alta automática de credenciales al asignar una modalidad desde KARDEX.
 *
 * Los índices únicos de `users` (email y ci) no deben convertir una asignación
 * válida en un error 500 cuando el perfil del estudiante reutiliza datos de
 * contacto ya registrados.
 */
class KardexAsignarModalidadTest extends TestCase
{
    use RefreshDatabase;

    public function test_asignar_modalidad_crea_la_cuenta_aunque_el_email_ya_este_en_uso(): void
    {
        $modalidad = Modalidad::factory()->tesis()->create();
        $otroEstudiante = Estudiante::factory()->create();
        $cuentaExistente = User::factory()->create([
            'email' => 'compartido@example.com',
            'rol' => Roles::ESTUDIANTE,
            'estudiante_id' => $otroEstudiante->id_estudiante,
        ]);

        $estudiante = Estudiante::factory()->create([
            'email' => $cuentaExistente->email,
        ]);
        $kardex = User::factory()->role(Roles::KARDEX)->create();

        $this->actingAs($kardex)
            ->postJson('/api/kardex/postulantes/asignar-modalidad', [
                'identificador' => $estudiante->ci,
                'id_modalidad' => $modalidad->id_modalidad,
            ])
            ->assertCreated()
            ->assertJsonPath('credenciales.generadas', true);

        $this->assertDatabaseHas('users', [
            'estudiante_id' => $estudiante->id_estudiante,
            'email' => null,
        ]);
        $this->assertDatabaseHas('tramites', [
            'id_estudiante' => $estudiante->id_estudiante,
            'id_modalidad' => $modalidad->id_modalidad,
        ]);
    }

    public function test_ci_duplicado_devuelve_un_422_con_mensaje_en_lugar_de_un_500(): void
    {
        $modalidad = Modalidad::factory()->tesis()->create();
        $estudiante = Estudiante::factory()->create();
        $kardex = User::factory()->role(Roles::KARDEX)->create();

        User::factory()->create([
            'ci' => $estudiante->ci,
            'estudiante_id' => null,
        ]);

        $this->actingAs($kardex)
            ->postJson('/api/kardex/postulantes/asignar-modalidad', [
                'identificador' => $estudiante->ci,
                'id_modalidad' => $modalidad->id_modalidad,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('identificador');
    }
}
