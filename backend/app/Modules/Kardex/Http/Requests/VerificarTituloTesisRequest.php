<?php

namespace App\Modules\Kardex\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la verificación de unicidad del título de una tesis.
 *
 * KARDEX comprueba, antes de aprobar el perfil de un postulante, que el título
 * no repita ni se parezca a otro trabajo ya registrado. `excluir_tramite` permite
 * re-verificar el título de un trámite sin que el propio registro aparezca
 * como coincidencia, y `excluir_estudiante` omite los trámites anteriores del
 * propio postulante.
 */
class VerificarTituloTesisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->rol, Roles::GESTION, true);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'min:10', 'max:255'],
            'excluir_tramite' => ['nullable', 'integer', 'min:1'],
            'excluir_estudiante' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'titulo.required' => 'Ingrese el título de la tesis de grado.',
            'titulo.min' => 'El título debe tener al menos 10 caracteres.',
            'titulo.max' => 'El título no puede superar los 255 caracteres.',
            'excluir_tramite.integer' => 'El trámite a excluir no es válido.',
            'excluir_estudiante.integer' => 'El estudiante a excluir no es válido.',
        ];
    }
}
