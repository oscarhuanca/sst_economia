<?php

namespace App\Modules\Tesis\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la actualización del perfil de tesis del ESTUDIANTE.
 *
 * Mientras la solicitud sigue en verificación (`solicitud_presentada`) el
 * estudiante puede corregir el título de su perfil y (opcionalmente) subir los
 * 3 documentos PDF de la fase de solicitud. El título es obligatorio y debe
 * cumplir la extensión mínima exigida por el backend; los documentos, si se
 * envían, deben ser PDF válidos con un tipo válido y sin repetirse.
 */
class ActualizarPerfilTesisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Roles::ESTUDIANTE;
    }

    /**
     * Recorta los espacios extremos del título antes de validarlo para que
     * `required`/`min:10` se evalúen sobre el texto real y no sobre el valor
     * trimeado por el middleware global (ver SolicitudTesisRequest).
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
        $tipos = array_keys(config('tesis.tipos_documento', []));

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
            'titulo.required' => 'Ingrese el título de su tesis de grado.',
            'titulo.string' => 'El título de su tesis de grado debe ser texto.',
            'titulo.min' => 'El título de su tesis debe tener al menos 10 caracteres.',
            'titulo.max' => 'El título de su tesis no debe superar los 255 caracteres.',
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
