<?php

namespace App\Modules\Kardex\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de la asignación de la modalidad de titulación a un postulante.
 *
 * La instancia académica indica el identificador (CI o registro universitario)
 * del postulante y la modalidad que se le asigna. Las credenciales de acceso se
 * generan de forma automática en el servicio si el postulante aún no tiene
 * cuenta.
 */
class AsignarModalidadRequest extends FormRequest
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
            'identificador' => ['required', 'string', 'max:50'],
            'id_modalidad' => [
                'required',
                'integer',
                Rule::exists('modalidades', 'id_modalidad')->where('activo', true),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'identificador.required' => 'Indique el CI o registro universitario del postulante.',
            'id_modalidad.required' => 'Seleccione la modalidad de titulación a asignar.',
            'id_modalidad.exists' => 'La modalidad seleccionada no está disponible.',
        ];
    }
}