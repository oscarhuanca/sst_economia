<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Eloquent de la tabla `historial_estados_examen`.
 *
 * Historial especializado de la modalidad Examen de Grado: cada fila es una
 * transición de estado guardada por KARDEX con el detalle propio del examen
 * (etapa del flujo, asistencia al sorteo, tema sorteado, fecha, nota y número
 * de resolución del HCC). Es la fuente de verdad del reporte en PDF.
 */
class HistorialEstadoExamen extends Model
{
    protected $table = 'historial_estados_examen';

    protected $primaryKey = 'id_historial_examen';

    protected $fillable = [
        'id_tramite',
        'nombre_estado',
        'etapa',
        'resultado',
        'numero_resolucion',
        'fecha_resolucion',
        'tema',
        'fecha_examen',
        'nota',
        'numero_intentos',
        'observaciones',
        'id_usuario_responsable',
    ];

    /**
     * Atributos con conversión de tipos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_examen' => 'date',
        'fecha_resolucion' => 'date',
        'nota' => 'decimal:2',
        'numero_intentos' => 'integer',
    ];

    /**
     * Trámite de Examen de Grado al que pertenece este registro del historial.
     *
     * @return BelongsTo<Tramite>
     */
    public function tramite()
    {
        return $this->belongsTo(Tramite::class, 'id_tramite', 'id_tramite');
    }

    /**
     * Usuario de KARDEX que efectuó la transición.
     *
     * @return BelongsTo<User>
     */
    public function responsable()
    {
        return $this->belongsTo(User::class, 'id_usuario_responsable', 'id_usuario');
    }
}
