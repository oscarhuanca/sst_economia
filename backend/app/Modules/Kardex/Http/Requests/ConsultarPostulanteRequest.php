<?php

namespace App\Modules\Kardex\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la consulta del flujo de un postulante.
 *
 * Reutiliza el mismo identificador (CI o registro universitario) de la búsqueda
 * de postulantes; el servicio devuelve el trámite más reciente del postulante
 * con su secuencia y estados siguientes (línea de tiempo).
 */
class ConsultarPostulanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->rol, Roles::GESTION, true);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'identificador' => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'identificador.required' => 'Indique el CI o registro universitario del postulante.',
        ];
    }
}