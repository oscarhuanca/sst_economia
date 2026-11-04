<?php

namespace App\Modules\Tesis\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la solicitud de fecha de defensa de la tesis.
 *
 * El estudiante indica una fecha sugerida (opcional) para su defensa; la fecha
 * no puede ser anterior a hoy, de modo que la programación la defina Kardex.
 */
class SolicitarFechaDefensaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Roles::ESTUDIANTE;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'fecha_sugerida' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }
}