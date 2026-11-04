<?php

namespace App\Modules\Tramites\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación del guardado de un módulo del flujo de Examen de Grado.
 *
 * Igual que en el flujo de Tesis, este request es un sobre fino: valida la
 * forma del cuerpo y deja las reglas de cada módulo (intentos disponibles,
 * resolución obligatoria, tema y fecha del sorteo, notas válidas, asistencia)
 * al \App\Modules\Tramites\Services\ExamenService, que además devuelve el
 * mensaje en el idioma de la tarjeta que lo disparó.
 *
 * Los sorteos registran la asistencia con el tema y la fecha; el módulo
 * `asignacion_notas` registra después las notas de ambos exámenes.
 */
class GuardarModuloExamenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `datos` puede viajar vacío (`{}`): el módulo de reporte no pide ningún
     * campo. Cada módulo declara sus propios requisitos en `ExamenService`.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'datos' => ['present', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'datos.present' => 'Debe enviar los datos del módulo del examen.',
            'datos.array' => 'Los datos del módulo deben ser un objeto.',
        ];
    }
}
