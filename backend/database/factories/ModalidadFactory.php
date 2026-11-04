<?php

namespace Database\Factories;

use App\Models\Modalidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Modalidad>
 */
class ModalidadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->randomElement([
                'Tesis de Grado',
                'Examen de Grado',
                'Trabajo Dirigido',
                'Excelencia Académica',
            ]),
            'descripcion' => fake()->sentence(),
            'duracion_maxima_meses' => 12,
            'requisitos_minimos' => fake()->sentence(),
            'activo' => true,
        ];
    }

    /** Modalidad de Tesis de Grado (la del flujo de titulación por módulos). */
    public function tesis(): static
    {
        return $this->state(fn (array $attributes) => ['nombre' => 'Tesis de Grado']);
    }

    /** Modalidad de Examen de Grado (flujo de sorteos, nota y reporte). */
    public function examen(): static
    {
        return $this->state(fn (array $attributes) => ['nombre' => 'Examen de Grado']);
    }

    /** Modalidad de Trabajo Dirigido (flujo de titulación por módulos). */
    public function trabajoDirigido(): static
    {
        return $this->state(fn (array $attributes) => ['nombre' => 'Trabajo Dirigido']);
    }

    /** Modalidad de Excelencia Académica (flujo por promedio, resolución y acta). */
    public function excelencia(): static
    {
        return $this->state(fn (array $attributes) => ['nombre' => 'Excelencia Académica']);
    }
}
