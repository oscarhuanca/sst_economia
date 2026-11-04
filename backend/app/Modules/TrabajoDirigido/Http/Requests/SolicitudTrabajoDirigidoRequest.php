<?php

namespace App\Modules\TrabajoDirigido\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la fase de solicitud del Módulo de Trabajo Dirigido.
 *
 * Exige que el estudiante proponga el título de su trabajo dirigido (es el que
 * KARDEX verificará antes de aprobar el perfil de proyecto). Los 3 documentos
 * oficiales (Nota de Solicitud, Certificado de Notas y Perfil de Proyecto) son
 * OPCIONALES: si se adjuntan, cada archivo debe ser un PDF válido.
 */
class SolicitudTrabajoDirigidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Roles::ESTUDIANTE;
    }

    /**
     * Recorta los espacios extremos del título antes de validarlo.
     *
     * Sin esto, Laravel trimea el campo con su middleware global y un título de
     * solo espacios llega como `null`, produciendo el engaño "The titulo field
     * is required." aunque el estudiante sí haya escrito algo.
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
     * Mensajes de validación del título en español.
     *
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
