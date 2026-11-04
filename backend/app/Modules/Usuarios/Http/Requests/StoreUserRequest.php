<?php

namespace App\Modules\Usuarios\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de creación de usuarios.
 *
 * El `username` es el identificador de ingreso al sistema; para estudiantes se
 * autogenera (ver CredencialesEstudiante), para el resto de los roles lo
 * asigna el administrador aquí.
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $roles = implode(',', Roles::TODOS);

        return [
            'ci' => ['required', 'string', 'unique:users,ci'],
            'nombres' => ['required', 'string'],
            'apellidos' => ['required', 'string'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'telefono' => ['nullable', 'string'],
            'username' => ['required', 'string', 'unique:users,username'],
            'rol' => ['required', "in:{$roles}"],
            'password' => ['required', 'string', 'min:6'],
        ];
    }
}
