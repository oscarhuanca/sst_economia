<?php

namespace App\Modules\Docentes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación del alta de un docente en el registro académico.
 *
 * Los únicos campos obligatorios son `ci` (único), `nombre` y `apellidos`;
 * teléfono, email y materia son opcionales.
 */
class StoreDocenteRequest extends FormRequest
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
        return [
            'ci' => ['required', 'string', 'max:20',
                Rule::unique('docentes', 'ci'),
            ],
            'nombre' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'materia' => ['nullable', 'string', 'max:120'],
        ];
    }
}