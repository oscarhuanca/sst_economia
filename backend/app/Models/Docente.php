<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de la tabla `docentes`.
 *
 * Registro académico independiente de los docentes de la carrera: nombre,
 * apellidos, CI (único), teléfono, email y la materia que imparte (opcional).
 * No representa credenciales de acceso (esas viven en `users`).
 */
class Docente extends Model
{
    protected $primaryKey = 'id_docente';

    protected $fillable = [
        'ci', 'nombre', 'apellidos', 'telefono', 'email', 'materia',
    ];

    protected $appends = ['nombres'];

    protected function nombres(): Attribute
    {
        return Attribute::get(fn (): string => $this->nombre);
    }
}
