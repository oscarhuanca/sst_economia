<?php

namespace App\Modules\Tramites\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de la solicitud de Examen de Grado del estudiante.
 *
 * La solicitud adjunta dos PDF: la solicitud de examen y el certificado de
 * conclusión de estudios. KARDEX verifica ambos documentos antes de revisar
 * los intentos y habilitar al estudiante.
 */
class CrearSolicitudExamenRequest extends FormRequest
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
        $tipos = array_keys(config('examen.tipos_documento', []));

        return [
            'documentos' => ['required', 'array', 'size:'.count($tipos)],
            'documentos.*.archivo' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'documentos.*.tipo' => ['required', Rule::in($tipos), 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'documentos.required' => 'Adjunte la solicitud y el certificado de conclusión de estudios en PDF.',
            'documentos.size' => 'Adjunte exactamente los dos documentos requeridos en PDF.',
            'documentos.*.archivo.required' => 'Seleccione el archivo PDF de cada documento.',
            'documentos.*.archivo.mimes' => 'Cada documento debe ser un archivo PDF.',
            'documentos.*.archivo.max' => 'Cada documento no debe superar los 10 MB.',
            'documentos.*.tipo.in' => 'El tipo de documento no es válido.',
            'documentos.*.tipo.distinct' => 'No repita el tipo de documento.',
        ];
    }
}
