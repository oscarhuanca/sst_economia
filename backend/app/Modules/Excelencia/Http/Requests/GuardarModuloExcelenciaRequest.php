<?php

namespace App\Modules\Excelencia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del guardado de un módulo del flujo de Excelencia Académica.
 *
 * Igual que en el flujo de Examen de Grado, este request es un sobre fino:
 * valida la forma del cuerpo y deja las reglas de cada módulo (promedio
 * válido, resolución obligatoria con su fecha, fecha de lectura del acta y
 * confirmación) al \App\Modules\Excelencia\Services\ExcelenciaService, que
 * además devuelve el mensaje en el idioma de la tarjeta que lo disparó.
 */
class GuardarModuloExcelenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `datos` puede viajar vacío (`{}`): el módulo de reporte no pide ningún
     * campo. Cada módulo declara sus propios requisitos en `ExcelenciaService`.
     * Los informes PDF son opcionales y viajan como archivos del request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'datos' => ['present', 'array'],
            'informe_verificacion' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'informe_lectura_acta' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'datos.present' => 'Debe enviar los datos del módulo de Excelencia Académica.',
            'datos.array' => 'Los datos del módulo deben ser un objeto.',
            'informe_verificacion.file' => 'El informe de verificación debe ser un archivo PDF.',
            'informe_verificacion.mimes' => 'El informe de verificación debe ser un PDF.',
            'informe_verificacion.max' => 'El informe de verificación no puede superar los 10 MB.',
            'informe_lectura_acta.file' => 'El informe de lectura del acta debe ser un archivo PDF.',
            'informe_lectura_acta.mimes' => 'El informe de lectura del acta debe ser un PDF.',
            'informe_lectura_acta.max' => 'El informe de lectura del acta no puede superar los 10 MB.',
        ];
    }
}
