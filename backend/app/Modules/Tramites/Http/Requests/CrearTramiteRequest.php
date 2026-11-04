<?php

namespace App\Modules\Tramites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la creación de un trámite (modalidad + documentos adjuntos).
 */
class CrearTramiteRequest extends FormRequest
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
            'id_modalidad' => ['required', 'exists:modalidades,id_modalidad'],
            'documentos' => ['required', 'array'],
            'documentos.*.archivo' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'documentos.*.tipo' => ['required', 'string'],
        ];
    }
}