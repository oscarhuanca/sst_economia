<?php

namespace App\Modules\TrabajoDirigido\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la actualización del perfil de proyecto del ESTUDIANTE.
 *
 * Mientras la solicitud sigue en verificación (`solicitud_presentada`) el
 * estudiante puede corregir el título de su trabajo dirigido y (opcionalmente)
 * subir los 3 documentos PDF de la fase de solicitud.
 */
class ActualizarPerfilTrabajoDirigidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Roles::ESTUDIANTE;
    }

    /**
     * Recorta los espacios extremos del título antes de validarlo para que
     * `required`/`min:10` se evalúen sobre el texto real.
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
        $tipos = array_keys(config('trabajodirigido.tipos_documento', []));

        return [
            'titulo' => ['required', 'string', 'min:10', 'max:255'],
            'documentos' => ['nullable', 'array'],
            'documentos.*.archivo' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'documentos.*.tipo' => ['required', 'string', 'in:'.implode(',', $tipos)],
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

    /**
     * Si se adjuntan documentos, verifica que los tipos sean válidos y no
     * estén repetidos.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $documentos = collect($this->input('documentos', []));

            $duplicados = $documentos->pluck('tipo')->filter()->duplicates()->values();

            if ($duplicados->isNotEmpty()) {
                $validator->errors()->add(
                    'documentos',
                    'Documento duplicado: '.implode(', ', $duplicados).'.'
                );
            }
        });
    }
}
