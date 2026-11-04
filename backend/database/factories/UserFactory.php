<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombres = fake()->firstName();
        $apellidos = fake()->lastName();

        return [
            'ci' => fake()->unique()->numerify('########'),
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'email' => fake()->unique()->safeEmail(),
            'telefono' => fake()->phoneNumber(),
            'username' => strtolower($nombres).'_'.fake()->unique()->numerify('########'),
            'rol' => 'estudiante',
            'password' => static::$password ??= Hash::make('password'),
            'activo' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Assign a role.
     */
    public function role(string $rol): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => $rol,
        ]);
    }
}
