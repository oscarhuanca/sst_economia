<?php

namespace App\Modules\Tramites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del guardado de un módulo del flujo de titulación (Tesis de Grado).
 *
 * El identificador del módulo llega como parámetro de ruta (`{modulo}`); aquí
 * solo se valida el cuerpo con los datos del formulario del módulo.
 */
class GuardarModuloFlujoRequest extends FormRequest
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
            'datos' => ['required', 'array'],
            'resolucion_tutor' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'resolucion_tribunal' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'nota_suficiencia_tutor' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'nota_suficiencia_presidente' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'nota_suficiencia_vocal' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'nota_suficiencia_secretario' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'datos.required' => 'Debe enviar los datos del módulo del flujo.',
            'datos.array' => 'Los datos del módulo deben ser un objeto.',
            'resolucion_tutor.mimes' => 'La Resolución de designación como tutor debe ser un archivo PDF.',
            'resolucion_tutor.max' => 'La Resolución de designación como tutor no debe superar los 10 MB.',
            'resolucion_tribunal.mimes' => 'La Resolución de designación del tribunal revisor debe ser un archivo PDF.',
            'resolucion_tribunal.max' => 'La Resolución de designación del tribunal revisor no debe superar los 10 MB.',
            'nota_suficiencia_tutor.mimes' => 'La Nota de Suficiencia del tutor debe ser un archivo PDF.',
            'nota_suficiencia_tutor.max' => 'La Nota de Suficiencia del tutor no debe superar los 10 MB.',
            'nota_suficiencia_presidente.mimes' => 'La Nota de Suficiencia del presidente debe ser un archivo PDF.',
            'nota_suficiencia_presidente.max' => 'La Nota de Suficiencia del presidente no debe superar los 10 MB.',
            'nota_suficiencia_vocal.mimes' => 'La Nota de Suficiencia del vocal debe ser un archivo PDF.',
            'nota_suficiencia_vocal.max' => 'La Nota de Suficiencia del vocal no debe superar los 10 MB.',
            'nota_suficiencia_secretario.mimes' => 'La Nota de Suficiencia del secretario debe ser un archivo PDF.',
            'nota_suficiencia_secretario.max' => 'La Nota de Suficiencia del secretario no debe superar los 10 MB.',
        ];
    }
}
