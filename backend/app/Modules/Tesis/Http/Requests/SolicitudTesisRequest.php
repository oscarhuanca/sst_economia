<?php

namespace App\Modules\Tesis\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la fase de solicitud del Módulo de Tesis de Grado.
 *
 * Exige que el estudiante proponga el título de su tesis (es el que KARDEX
 * verificará antes de aprobar el perfil). Los 3 documentos oficiales (Nota de
 * Solicitud, Certificado de Notas y Perfil de Tesis de Grado) son OPCIONALES:
 * si se adjuntan, cada archivo debe ser un PDF válido.
 */
class SolicitudTesisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Roles::ESTUDIANTE;
    }

    /**
     * Recorta los espacios extremos del título antes de validarlo.
     *
     * Sin esto, Laravel trimea el campo con su middleware global y un título de
     * solo espacios llega como `null`, produciendo el engañoso "The titulo field
     * is required." aunque el estudiante sí haya escrito algo. Recortando aquí,
     * `required` y `min:10` se evalúan sobre el texto real y los mensajes son
     * correctos.
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
     * Mensajes de validación del título en español (evita el genérico en inglés
     * cuando el título queda vacío tras recortar espacios).
     *
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
     * estén repetidos (el PDF es opcional, pero lo que se envía debe ser
     * correcto).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $documentos = collect($this->input('documentos', []));

            $tiposSubidos = $documentos->pluck('tipo')->filter()->values();

            $duplicados = $tiposSubidos->duplicates()->values();

            if ($duplicados->isNotEmpty()) {
                $validator->errors()->add(
                    'documentos',
                    'Documento duplicado: '.implode(', ', $duplicados).'.'
                );
            }
        });
    }
}
