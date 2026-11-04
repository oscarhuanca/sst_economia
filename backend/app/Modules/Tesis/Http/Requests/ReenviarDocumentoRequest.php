<?php

namespace App\Modules\Tesis\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del reenvío del documento final tras su calificación como
 * insuficiente por la Comisión Revisora.
 *
 * El estudiante puede reenviar la versión corregida del documento final (PDF
 * opcional: si deja el anterior, basta con un mensaje) y vuelve a quedar en
 * evaluación de la Comisión Revisora.
 */
class ReenviarDocumentoRequest extends FormRequest
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
            'documento_final' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }
}