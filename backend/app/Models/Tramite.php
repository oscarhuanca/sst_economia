<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Eloquent de la tabla `tramites`.
 *
 * Representa un trámite de titulación presentado por un estudiante en una
 * modalidad concreta. Mantiene el estado actual (`estado_actual`), un historial
 * de estados (`estados`), los documentos adjuntos (`documentos`) y el docente
 * tutor asignado (`tutor`).
 */
class Tramite extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_tramite';

    protected $table = 'tramites';

    protected $fillable = ['id_estudiante', 'id_modalidad', 'id_tutor', 'estado_actual', 'observaciones', 'hitos'];

    /**
     * Atributos con conversión de tipos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hitos' => 'array',
    ];

    /**
     * Relación inversa con el estudiante solicitante del trámite.
     *
     * @return BelongsTo Estudiante `\App\Models\Estudiante`
     *                   propietario del trámite.
     */
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante', 'id_estudiante');
    }

    /**
     * Relación inversa con la modalidad de titulación del trámite.
     *
     * @return BelongsTo Modalidad `\App\Models\Modalidad`
     *                   a la que pertenece el trámite.
     */
    public function modalidad()
    {
        return $this->belongsTo(Modalidad::class, 'id_modalidad', 'id_modalidad');
    }

    /**
     * Relación inversa con el docente tutor asignado al trámite.
     *
     * @return BelongsTo Docente `\App\Models\Docente`
     *                   del registro académico, `null` si aún no se asignó.
     */
    public function tutor()
    {
        return $this->belongsTo(Docente::class, 'id_tutor', 'id_docente');
    }

    /**
     * Relación uno a muchos con los documentos adjuntos del trámite.
     *
     * @return HasMany Documentos `\App\Models\DocumentoAdjunto`
     *                 subidos para este trámite.
     */
    public function documentos()
    {
        return $this->hasMany(DocumentoAdjunto::class, 'id_tramite', 'id_tramite');
    }

    /**
     * Relación uno a muchos con el historial de estados del trámite (línea de tiempo).
     *
     * @return HasMany Registros `\App\Models\EstadoTramite`
     *                 que componen la línea de tiempo del trámite.
     */
    public function estados()
    {
        return $this->hasMany(EstadoTramite::class, 'id_tramite', 'id_tramite');
    }

    /**
     * Relación uno a muchos con el HISTORIAL ESPECIALIZADO del Examen de Grado.
     *
     * Solo se usa en trámites de la modalidad Examen de Grado: guarda el detalle
     * propio de cada transición (etapa, asistencia, tema, fecha, nota y número
     * de resolución). `estados` sigue siendo la línea de tiempo genérica.
     *
     * @return HasMany Historiales `\App\Models\HistorialEstadoExamen`
     *                 del flujo de examen de grado.
     */
    public function historialExamen()
    {
        return $this->hasMany(HistorialEstadoExamen::class, 'id_tramite', 'id_tramite');
    }

    /**
     * Indica si un usuario es el estudiante propietario de este trámite.
     *
     * Centraliza la comprobación de propiedad que los servicios de Tramites y
     * Tesis repiten al autorizar al estudiante.
     *
     * @return bool true si el usuario tiene un perfil de estudiante y coincide
     *              con el solicitante del trámite.
     */
    public function perteneceA(User $user): bool
    {
        return $user->estudiante && $this->id_estudiante === $user->estudiante->id_estudiante;
    }
}
