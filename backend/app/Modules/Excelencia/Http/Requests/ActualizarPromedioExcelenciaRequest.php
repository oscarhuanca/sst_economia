<?php

namespace App\Modules\Excelencia\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del promedio declarado por el estudiante en el seguimiento.
 *
 * El estudiante puede declarar (o corregir) su promedio mientras KARDEX aún no
 * lo haya verificado. El rango se valida aquí y el servicio comprueba que el
 * trámite esté en el estado inicial y pertenezca al estudiante autenticado.
 */
class ActualizarPromedioExcelenciaRequest extends FormRequest
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
        ];
    }
}
