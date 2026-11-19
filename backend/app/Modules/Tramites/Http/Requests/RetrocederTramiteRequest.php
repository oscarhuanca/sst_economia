<?php

namespace App\Modules\Tramites\Http\Requests;

use App\Modules\Tramites\Services\TramiteStateService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación del retroceso de una Tesis de Grado gestionado por Kardex.
 *
 * El destino se contrasta con los estados anteriores calculados por la máquina
 * de estados; la modalidad y la coherencia del trámite se validan en
 * \App\Modules\Tramites\Services\TramiteService.
 */
class RetrocederTramiteRequest extends FormRequest
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
            'nuevo_estado' => ['required', 'string', Rule::in(TramiteStateService::estadosFlujoDeTesis())],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nuevo_estado.required' => 'Debe seleccionar el estado al que desea volver.',
            'nuevo_estado.in' => 'El estado indicado no pertenece al flujo de Tesis de Grado.',
            'observaciones.max' => 'La observación no puede superar los 1000 caracteres.',
        ];
    }
}
