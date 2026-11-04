<?php

namespace App\Modules\Usuarios\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del alta automática de la cuenta de acceso de un estudiante
 * (CREAR USUARIO). La instancia académica (admin o kardex) solo aporta el
 * identificador (CI o registro universitario) del estudiante ya registrado;
 * el sistema genera las credenciales.
 */
class AltaEstudianteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->rol, [Roles::ADMIN, Roles::KARDEX], true);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'identificador' => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'identificador.required' => 'Indique el CI o registro universitario del estudiante.',
        ];
    }
}
