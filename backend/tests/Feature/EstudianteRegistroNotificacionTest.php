<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * El auto-registro público avisa a los roles de gestión cuántos postulantes
 * están esperando credenciales en el panel general del KARDEX.
 */
class EstudianteRegistroNotificacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_registrar_estudiante_avisa_a_gestion_y_cuenta_los_pendientes(): void
    {
        $kardex = User::factory()->role(Roles::KARDEX)->create();
        $docente = User::factory()->role(Roles::DOCENTE)->create();

        $this->registrar('7777001', 'RU-NOTIF-001')->assertCreated();
        $this->registrar('7777002', 'RU-NOTIF-002')->assertCreated();

        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => $kardex->id_usuario,
            'tipo' => 'nuevo_registro_estudiante',
            'titulo' => 'Nuevo postulante registrado',
            'mensaje' => 'Ana Prueba (CI 7777002) se registró. Hay 2 postulantes en espera de credenciales.',
            'enlace' => '/kardex',
            'leida' => false,
        ]);
        $this->assertDatabaseMissing('notificaciones', ['id_usuario' => $docente->id_usuario]);
    }

    private function registrar(string $ci, string $registroUniversitario): TestResponse
    {
        return $this->postJson('/api/estudiantes/registro', [
            'ci' => $ci,
            'nombres' => 'Ana',
            'apellidos' => 'Prueba',
            'registro_universitario' => $registroUniversitario,
            'fecha_nacimiento' => '2001-05-14',
        ]);
    }
}
