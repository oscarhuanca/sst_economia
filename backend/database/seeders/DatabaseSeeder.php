<?php

namespace Database\Seeders;

use App\Models\Estudiante;
use App\Models\User;
use App\Support\CredencialesEstudiante;
use App\Support\Roles;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usuarios de gestión (sin perfil aislado en `estudiantes`).
        // El username se asigna manualmente siguiendo la regla del sistema.
        $gestion = [
            ['ci' => '1234567', 'nombres' => 'Administrador', 'apellidos' => 'Sistema', 'email' => 'admin@example.com', 'telefono' => '59170000001', 'rol' => Roles::ADMIN],
            ['ci' => '2345678', 'nombres' => 'Kardex', 'apellidos' => 'Encargado', 'email' => 'kardex@example.com', 'telefono' => '59170000002', 'rol' => Roles::KARDEX],
            ['ci' => '3456789', 'nombres' => 'Secretaria', 'apellidos' => 'Académica', 'email' => 'secretaria@example.com', 'telefono' => '59170000003', 'rol' => Roles::SECRETARIA],
            ['ci' => '4567890', 'nombres' => 'Dirección', 'apellidos' => 'Carrera', 'email' => 'direccion@example.com', 'telefono' => '59170000004', 'rol' => Roles::DIRECCION],
            ['ci' => '6789012', 'nombres' => 'Docente', 'apellidos' => 'Tutor', 'email' => 'docente@example.com', 'telefono' => '59170000006', 'rol' => Roles::DOCENTE],
        ];

        $passwordGestion = Hash::make('password');

        foreach ($gestion as $datos) {
            $username = strtolower(explode(' ', $datos['nombres'])[0]).'_'.$datos['ci'];

            User::updateOrCreate(
                ['ci' => $datos['ci']],
                array_merge($datos, ['username' => $username, 'password' => $passwordGestion])
            );
        }

        // Perfiles AISLADOS de estudiantes primero; luego sus cuentas de acceso
        // con username y contraseña autogenerados (dd-mm-aa de la fecha de nacimiento).
        // El email y teléfono viven en el perfil del estudiante.
        $estudiantes = [
            [
                'ci' => '7890123', 'nombres' => 'Estudiante', 'apellidos' => 'Prueba',
                'registro_universitario' => '2020-0001', 'fecha_nacimiento' => '2001-04-10',
                'promedio_global' => 85.5, 'email' => 'estudiante@example.com', 'telefono' => '59170000010',
            ],
            [
                'ci' => '8901234', 'nombres' => 'Estudiante', 'apellidos' => 'Dos',
                'registro_universitario' => '2020-0002', 'fecha_nacimiento' => '2002-08-22',
                'promedio_global' => 78.0, 'email' => 'estudiante2@example.com', 'telefono' => '59170000011',
            ],
        ];

        foreach ($estudiantes as $datos) {
            $perfil = Estudiante::updateOrCreate(
                ['ci' => $datos['ci']],
                $datos
            );

            $password = CredencialesEstudiante::passwordDe($perfil);

            User::updateOrCreate(
                ['estudiante_id' => $perfil->id_estudiante],
                [
                    'ci' => $perfil->ci,
                    'nombres' => $perfil->nombres,
                    'apellidos' => $perfil->apellidos,
                    'email' => $perfil->email,
                    'telefono' => $perfil->telefono,
                    'username' => CredencialesEstudiante::usernameUnico($perfil),
                    'password' => Hash::make($password),
                    'rol' => Roles::ESTUDIANTE,
                    'activo' => true,
                ]
            );
        }

        $this->call(ModalidadSeeder::class);
    }
}
