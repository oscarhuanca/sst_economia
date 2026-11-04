<?php

namespace App\Modules\Docentes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de la actualización de un docente en el registro académico.
 *
 * Los campos son los mismos que en el alta; `ci` debe seguir siendo único
 * ignorando el propio registro que se está editando.
 */
class UpdateDocenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $docenteId = $this->route('id');

        return [
            'ci' => ['required', 'string', 'max:20',
                Rule::unique('docentes', 'ci')->ignore($docenteId, 'id_docente'),
            ],
            'nombre' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'materia' => ['nullable', 'string', 'max:120'],
        ];
    }
}