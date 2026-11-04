<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Eloquent de la tabla `modalidades`.
 *
 * Representa una modalidad de titulación (Examen de Grado, Tesis de Grado,
 * Trabajo Dirigido, Excelencia Académica), con su descripción, duración máxima,
 * requisitos mínimos y si se encuentra activa.
 */
class Modalidad extends Model
{
    use HasFactory;

    protected $table = 'modalidades';

    protected $primaryKey = 'id_modalidad';

    protected $fillable = ['nombre', 'descripcion', 'duracion_maxima_meses', 'requisitos_minimos', 'activo'];

    /**
     * Relación uno a muchos con los trámites de esta modalidad.
     *
     * @return HasMany Trámites `\App\Models\Tramite`
     *                 creados bajo esta modalidad.
     */
    public function tramites()
    {
        return $this->hasMany(Tramite::class, 'id_modalidad', 'id_modalidad');
    }
}
