<?php

namespace App\Modules\Usuarios\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de actualización de usuarios.
 *
 * Ya no se sincroniza el perfil de estudiante desde el panel de usuarios: el
 * perfil aislado se gestiona administrativamente en el módulo de estudiantes y
 * los campos académicos los mantiene el propio estudiante.
 */
class UpdateUserRequest extends FormRequest
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
        $usuarioId = $this->route('id');
        $roles = implode(',', Roles::TODOS);

        return [
            'ci' => ['required', 'string', "unique:users,ci,{$usuarioId},id_usuario"],
            'nombres' => ['required', 'string'],
            'apellidos' => ['required', 'string'],
            'email' => ['nullable', 'email', "unique:users,email,{$usuarioId},id_usuario"],
            'telefono' => ['nullable', 'string'],
            'username' => ['required', 'string', "unique:users,username,{$usuarioId},id_usuario"],
            'rol' => ['required', "in:{$roles}"],
            'password' => ['nullable', 'string', 'min:6'],
        ];
    }
}
