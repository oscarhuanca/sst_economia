<?php

namespace App\Modules\Tramites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la re-solicitud del Examen de Grado.
 *
 * La reabertura del trámite solo aplica a estudiantes que reprobaron o no
 * asistieron al sorteo, y consume un intento: el contador y la elegibilidad se
 * comprueban en \App\Modules\Tramites\Services\ExamenService, que es quien
 * conoce la máquina de estados.
 */
class ReabrirExamenRequest extends FormRequest
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
        return [
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'observaciones.max' => 'La observación no puede superar los 1000 caracteres.',
        ];
    }
}
