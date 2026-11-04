<?php

namespace App\Modules\Tramites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la revisión inicial de la documentación de un trámite.
 */
class RevisarTramiteRequest extends FormRequest
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
            'accion' => ['required', 'in:aprobar,rechazar'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}