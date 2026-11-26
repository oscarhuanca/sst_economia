<?php

namespace App\Modules\Kardex\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

class PublicarDocumentoFinalTesisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->rol, [Roles::KARDEX], true);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'documento_publicacion' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'documento_publicacion.required' => 'Debe adjuntar el documento final de la tesis en PDF.',
            'documento_publicacion.mimes' => 'El documento final debe ser un archivo PDF.',
            'documento_publicacion.max' => 'El documento final no debe superar los 10 MB.',
        ];
    }
}
