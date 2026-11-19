<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Tramite;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Primera etapa del registro desde KARDEX: la generación de credenciales crea
 * la cuenta de acceso una única vez y NO inicia ningún trámite.
 */
class KardexGenerarCredencialesTest extends TestCase
{
    use RefreshDatabase;

    public function test_generar_credenciales_crea_la_cuenta_aunque_el_email_ya_este_en_uso(): void
    {
        $otroEstudiante = Estudiante::factory()->create();
        $cuentaExistente = User::factory()->create([
            'email' => 'compartido@example.com',
            'rol' => Roles::ESTUDIANTE,
            'estudiante_id' => $otroEstudiante->id_estudiante,
        ]);
        $estudiante = Estudiante::factory()->create(['email' => $cuentaExistente->email]);
        $kardex = User::factory()->role(Roles::KARDEX)->create();

        $this->actingAs($kardex)
            ->postJson('/api/kardex/postulantes/generar-credenciales', [
                'identificador' => $estudiante->ci,
            ])
            ->assertCreated()
            ->assertJsonPath('credenciales.generadas', true)
            ->assertJsonPath('credenciales.password', $estudiante->fecha_nacimiento->format('d-m-Y'))
            ->assertJsonPath('postulante.tiene_cuenta', true);

        $this->assertDatabaseHas('users', [
            'estudiante_id' => $estudiante->id_estudiante,
            'email' => null,
            'rol' => Roles::ESTUDIANTE,
        ]);
        $this->assertDatabaseMissing('tramites', ['id_estudiante' => $estudiante->id_estudiante]);
    }

    public function test_generar_credenciales_no_vuelve_a_entregar_la_contrasena(): void
    {
        $estudiante = Estudiante::factory()->create();
        $kardex = User::factory()->role(Roles::KARDEX)->create();

        $primera = $this->actingAs($kardex)
            ->postJson('/api/kardex/postulantes/generar-credenciales', [
                'identificador' => $estudiante->registro_universitario,
            ])
            ->assertCreated();

        $segunda = $this->actingAs($kardex)
            ->postJson('/api/kardex/postulantes/generar-credenciales', [
                'identificador' => $estudiante->ci,
            ])
            ->assertCreated()
            ->assertJsonPath('credenciales.generadas', false)
            ->assertJsonPath('credenciales.password', null);

        $this->assertSame(
            $primera->json('credenciales.username'),
            $segunda->json('credenciales.username'),
        );
        $this->assertSame(1, User::query()->where('estudiante_id', $estudiante->id_estudiante)->count());
        $this->assertSame(0, Tramite::query()->where('id_estudiante', $estudiante->id_estudiante)->count());
    }

    public function test_ci_duplicado_devuelve_un_422_con_mensaje_en_lugar_de_un_500(): void
    {
        $estudiante = Estudiante::factory()->create();
        $kardex = User::factory()->role(Roles::KARDEX)->create();

        User::factory()->create([
            'ci' => $estudiante->ci,
            'rol' => Roles::DOCENTE,
            'estudiante_id' => null,
        ]);

        $this->actingAs($kardex)
            ->postJson('/api/kardex/postulantes/generar-credenciales', [
                'identificador' => $estudiante->ci,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('identificador');
    }

    public function test_generar_credenciales_requiere_un_rol_de_gestion(): void
    {
        $estudiante = Estudiante::factory()->create();
        $cuenta = User::factory()->create(['estudiante_id' => $estudiante->id_estudiante]);

        $this->actingAs($cuenta)
            ->postJson('/api/kardex/postulantes/generar-credenciales', [
                'identificador' => $estudiante->ci,
            ])
            ->assertForbidden();
    }
}
