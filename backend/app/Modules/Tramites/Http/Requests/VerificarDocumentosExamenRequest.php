<?php

namespace App\Modules\Tramites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerificarDocumentosExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'datos' => ['present', 'array'],
            'datos.documentos' => ['required', 'array', 'min:1'],
            'datos.documentos.*' => ['required', 'boolean'],
            'datos.observaciones' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'datos.present' => 'Debe enviar los documentos verificados.',
            'datos.documentos.required' => 'Marque los documentos recepcionados.',
            'datos.documentos.*.required' => 'Indique si el documento fue verificado.',
            'datos.documentos.*.boolean' => 'La verificación de cada documento debe ser verdadera o falsa.',
        ];
    }
}
