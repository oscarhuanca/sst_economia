<?php

namespace App\Modules\TrabajoDirigido\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del reenvío del perfil de proyecto rechazado por KARDEX.
 *
 * El estudiante debe volver a presentar un título de trabajo dirigido (el que
 * KARDEX volverá a verificar) y, opcionalmente, la versión corregida del
 * perfil en PDF: si conserva el documento anterior, basta con el título.
 */
class ReenviarPerfilTrabajoDirigidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Roles::ESTUDIANTE;
    }

    /**
     * Recorta los espacios extremos del título antes de validarlo.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('titulo') && is_string($this->input('titulo'))) {
            $this->merge(['titulo' => trim($this->input('titulo'))]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'min:10', 'max:255'],
            'perfil' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'titulo.required' => 'Ingrese el título de su trabajo dirigido.',
            'titulo.string' => 'El título de su trabajo dirigido debe ser texto.',
            'titulo.min' => 'El título de su trabajo dirigido debe tener al menos 10 caracteres.',
            'titulo.max' => 'El título de su trabajo dirigido no debe superar los 255 caracteres.',
        ];
    }
}
