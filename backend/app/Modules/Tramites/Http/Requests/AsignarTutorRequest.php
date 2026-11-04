<?php

namespace App\Modules\Tramites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la asignación de tutor docente a un trámite.
 */
class AsignarTutorRequest extends FormRequest
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
            'id_tutor' => ['required', 'exists:docentes,id_docente'],
        ];
    }
}
