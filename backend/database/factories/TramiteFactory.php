<?php

namespace Database\Factories;

use App\Models\Tramite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tramite>
 */
class TramiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'estado_actual' => 'solicitud_presentada',
            'observaciones' => null,
            'hitos' => [],
        ];
    }

    /** Trámite en la fase de revisión del perfil por parte de KARDEX. */
    public function enRevisionDePerfil(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_actual' => 'pendiente_concejo_universitario',
        ]);
    }

    /** Trámite con el perfil rechazado y esperando un perfil corregido. */
    public function perfilRechazado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado_actual' => 'perfil_rechazado',
        ]);
    }
}
