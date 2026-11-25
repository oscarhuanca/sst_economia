<?php

namespace App\Modules\Excelencia\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de la solicitud de Excelencia Académica del estudiante.
 *
 * El estudiante adjunta tres PDF (nota de solicitud, certificado de
 * calificaciones y récord académico) y puede añadir una observación. KARDEX
 * recepciona los documentos y verifica después el promedio del estudiante.
 */
class CrearSolicitudExcelenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Roles::ESTUDIANTE;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $tipos = array_keys(config('excelencia.tipos_documento', []));

        return [
            'documentos' => ['required', 'array', 'size:'.count($tipos)],
            'documentos.*.archivo' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'documentos.*.tipo' => ['required', Rule::in($tipos), 'distinct'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'documentos.required' => 'Adjunte la nota de solicitud, el certificado de calificaciones y el récord académico en PDF.',
            'documentos.size' => 'Adjunte exactamente los tres documentos requeridos en PDF.',
            'documentos.*.archivo.required' => 'Seleccione el archivo PDF de cada documento.',
            'documentos.*.archivo.mimes' => 'Cada documento debe ser un archivo PDF.',
            'documentos.*.archivo.max' => 'Cada documento no debe superar los 10 MB.',
            'documentos.*.tipo.in' => 'El tipo de documento no es válido.',
            'documentos.*.tipo.distinct' => 'No repita el tipo de documento.',
            'observaciones.max' => 'La observación no debe superar los 500 caracteres.',
        ];
    }
}
