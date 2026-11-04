<?php

namespace App\Modules\Estudiantes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación del auto-registro público del estudiante desde el login.
 *
 * El aspirante aporta sus datos personales, institucionales y de contacto
 * (CI, nombres, apellidos, registro universitario, fecha de nacimiento, email
 * y teléfono). Con estos datos se crea el perfil aislado; las credenciales de
 * acceso llegan después vía el flujo CREAR USUARIO de la instancia académica.
 * El endpoint es público (no requiere sesión).
 */
class RegistroEstudianteRequest extends FormRequest
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
}