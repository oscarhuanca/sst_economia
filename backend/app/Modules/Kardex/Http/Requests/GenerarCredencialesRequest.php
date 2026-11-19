<?php

namespace App\Modules\Kardex\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la generación de credenciales de acceso de un postulante.
 *
 * Primera etapa del registro: la instancia académica aporta únicamente el
 * identificador (CI o registro universitario). El servicio crea el usuario con
 * usuario temporal y contraseña temporal, sin iniciar ningún trámite.
 */
class GenerarCredencialesRequest extends FormRequest
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
