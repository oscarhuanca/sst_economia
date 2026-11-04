<?php

namespace App\Modules\Excelencia\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la solicitud de Excelencia Académica del estudiante.
 *
 * El estudiante declara su promedio general, que KARDEX verifica después (y
 * puede corregir). El backend vuelve a validar el rango del promedio porque
 * el mínimo reglamentario es una decisión de la administración.
 */
class CrearSolicitudExcelenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Roles::ESTUDIANTE;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        [$minimo, $maximo] = config('excelencia.rango_nota', [0, 100]);

        return [
            'promedio' => ['required', 'numeric', "min:{$minimo}", "max:{$maximo}"],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'promedio.required' => 'Ingrese su promedio general.',
            'promedio.numeric' => 'El promedio debe ser un número.',
            'promedio.min' => 'El promedio no puede ser menor a 0.',
            'promedio.max' => 'El promedio no puede ser mayor a 100.',
            'observaciones.max' => 'La observación no debe superar los 500 caracteres.',
        ];
    }
}
