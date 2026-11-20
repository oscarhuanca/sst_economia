<?php

namespace App\Modules\Tramites\Http\Requests;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de la solicitud de Examen de Grado del estudiante.
 *
 * La solicitud no requiere datos adicionales: los requisitos del examen se
 * verifican después en KARDEX (intentos y Resolución del HCC).
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
        return [];
    }
}
