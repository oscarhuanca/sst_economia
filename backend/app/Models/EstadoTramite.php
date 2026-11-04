<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de la tabla `estados_tramite`.
 *
 * Representa una entrada del historial (línea de tiempo) de un trámite: cada
 * cambio de estado registra quién lo efectuó y con qué observaciones.
 */
class EstadoTramite extends Model
{
    protected $table = 'estados_tramite';
    protected $primaryKey = 'id_estado';
    protected $fillable = ['id_tramite', 'nombre_estado', 'descripcion', 'id_usuario_responsable', 'observaciones'];

    /**
     * Relación inversa con el trámite al que pertenece este registro del historial.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Trámite `\App\Models\Tramite`.
     */
    public function tramite() { return $this->belongsTo(Tramite::class, 'id_tramite', 'id_tramite'); }

    /**
     * Relación inversa con el usuario responsable del cambio de estado.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Usuario `\App\Models\User`
     *         que ejecutó la transición.
     */
    public function responsable() { return $this->belongsTo(User::class, 'id_usuario_responsable', 'id_usuario'); }
}
