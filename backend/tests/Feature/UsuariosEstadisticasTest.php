<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Estadísticas del panel de administración: las tarjetas deben reflejar todos
 * los usuarios activos (no solo la página visible de la matriz).
 */
class UsuariosEstadisticasTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_tarjetas_cuentan_todos_los_usuarios_activos_por_rol(): void
    {
        User::factory()->count(3)->role(Roles::ESTUDIANTE)->create();
        User::factory()->count(2)->role(Roles::DOCENTE)->create();
        User::factory()->role(Roles::KARDEX)->create();
        User::factory()->role(Roles::ADMIN)->create();
        User::factory()->role(Roles::ESTUDIANTE)->create(['activo' => false]);

        $admin = User::factory()->role(Roles::ADMIN)->create();

        $estadisticas = $this->actingAs($admin)
            ->getJson('/api/usuarios/estadisticas')
            ->assertOk()
            ->json();

        $this->assertSame(8, $estadisticas['total']);
        $this->assertSame(3, $estadisticas['estudiantes']);
        $this->assertSame(2, $estadisticas['docentes']);
        $this->assertSame(3, $estadisticas['personal_academico']);
        $this->assertSame(3, $estadisticas['por_rol'][Roles::ESTUDIANTE]);
    }

    public function test_solo_el_admin_puede_consultar_las_estadisticas(): void
    {
        $docente = User::factory()->role(Roles::DOCENTE)->create();

        $this->actingAs($docente)
            ->getJson('/api/usuarios/estadisticas')
            ->assertForbidden();
    }
}
