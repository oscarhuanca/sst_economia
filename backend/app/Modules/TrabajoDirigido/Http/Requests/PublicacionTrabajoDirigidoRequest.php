<?php

namespace App\Modules\TrabajoDirigido\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la subida del trabajo dirigido (publicación) por parte del
 * estudiante, una vez aprobada la defensa.
 */
class PublicacionTrabajoDirigidoRequest extends FormRequest
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
            'documento_publicacion' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'documento_publicacion.required' => 'Debe adjuntar su Trabajo Dirigido en PDF.',
            'documento_publicacion.mimes' => 'El archivo debe ser un PDF (.pdf).',
            'documento_publicacion.max' => 'El archivo PDF no debe superar los 5 MB.',
        ];
    }
}
