<?php

namespace Database\Factories;

use App\Models\Estudiante;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Estudiante>
 */
class EstudianteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ci' => fake()->unique()->numerify('########'),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'fecha_nacimiento' => fake()->dateTimeBetween('-30 years', '-20 years'),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => fake()->numerify('########'),
            'registro_universitario' => 'RU-'.fake()->unique()->numerify('####'),
            'promedio_global' => fake()->randomFloat(2, 50, 100),
            // El Examen de Grado limita los intentos; los de otras modalidades
            // nunca leen este campo.
            'numero_intentos' => 0,
        ];
    }

    /** Estudiante con su cuenta de acceso (rol `estudiante`) vinculada. */
    public function conCuenta(): static
    {
        return $this->afterCreating(function (Estudiante $estudiante) {
            User::factory()->create([
                'email' => $estudiante->email,
                'nombres' => $estudiante->nombres,
                'apellidos' => $estudiante->apellidos,
                'rol' => Roles::ESTUDIANTE,
                'estudiante_id' => $estudiante->id_estudiante,
            ]);
        });
    }
}
