<?php

namespace App\Modules\Kardex\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la búsqueda de un postulante por CI o registro universitario.
 *
 * La instancia académica (kardex, secretaría, dirección o admin) solo aporta el
 * identificador; el sistema resuelve si corresponde a la cédula de identidad o
 * al registro universitario del perfil de estudiante.
 */
class BuscarPostulanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->rol, Roles::GESTION, true);
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
            'identificador.required' => 'Indique el CI o registro universitario del postulante.',
        ];
    }
}