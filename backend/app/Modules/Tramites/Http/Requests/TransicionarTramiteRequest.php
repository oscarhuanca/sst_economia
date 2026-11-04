<?php

namespace App\Modules\Tramites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la transición de estado de un trámite.
 */
class TransicionarTramiteRequest extends FormRequest
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
            'nuevo_estado' => ['required', 'string'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}