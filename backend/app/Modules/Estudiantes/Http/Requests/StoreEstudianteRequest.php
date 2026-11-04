<?php

namespace App\Modules\Estudiantes\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación del alta administrativa del perfil de un estudiante.
 *
 * El administrador registra los datos personales, institucionales y de
 * contacto del estudiante (CI, nombres, apellidos, registro universitario,
 * fecha de nacimiento, email y teléfono); el alta de credenciales de acceso es
 * un paso posterior e independiente (CREAR USUARIO). Los datos académicos ya no
 * se piden en el alta: el promedio global es opcional. Autorización resuelta
 * aquí: solo admin.
 */
class StoreEstudianteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Roles::ADMIN;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'ci' => ['required', 'string', 'max:20',
                Rule::unique('estudiantes', 'ci'),
            ],
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'registro_universitario' => ['required', 'string', 'max:50',
                Rule::unique('estudiantes', 'registro_universitario'),
            ],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
            'email' => ['nullable', 'email', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'authorize' => 'Solo el administrador puede crear estudiantes',
        ];
    }
}