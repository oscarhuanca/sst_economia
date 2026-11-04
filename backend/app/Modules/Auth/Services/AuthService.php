<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

/**
 * Casos de uso de autenticación.
 *
 * Contiene la lógica de ingreso (login), cierre de sesión (logout) y consulta
 * del usuario autenticado. Los controladores del módulo delegan aquí toda la
 * lógica, quedando como una capa delgada de HTTP/JSON.
 */
class AuthService
{
    /**
     * Autentica al usuario con username y contraseña.
     *
     * Lanza AuthenticationException (401) si las credenciales son incorrectas
     * y AuthorizationException (403) si la cuenta está deshabilitada. El perfil
     * aislado del estudiante (si el usuario es estudiante) se carga en la
     * respuesta para que el cliente lo use directamente.
     *
     * @return array{access_token: string, token_type: string, user: User}
     */
    public function login(string $username, string $password): array
    {
        $user = User::where('username', $username)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new AuthenticationException('Credenciales incorrectas');
        }

        if (! $user->activo) {
            throw new AuthorizationException('Usuario deshabilitado');
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->loadMissing('estudiante'),
        ];
    }

    /**
     * Invalida el token actual del usuario autenticado.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
