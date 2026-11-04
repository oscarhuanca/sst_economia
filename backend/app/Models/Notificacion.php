<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de la tabla `notificaciones`.
 *
 * Representa un aviso interno destinado a un usuario del sistema, con un tipo,
 * título, mensaje, enlace opcional y el flag de lectura (`leida`).
 */
class Notificacion extends Model
{
    protected $primaryKey = 'id_notificacion';
    protected $table = 'notificaciones';
    protected $fillable = ['id_usuario', 'tipo', 'titulo', 'mensaje', 'enlace', 'leida'];

    /**
     * Definición de casts de los atributos del modelo.
     *
     * @return array<string, string> Mapa de atributo => tipo de cast:
     *         `leida` se castea a booleano.
     */
    protected function casts(): array
    {
        return [
            'leida' => 'boolean',
        ];
    }

    /**
     * Relación inversa con el usuario destinatario de la notificación.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Usuario `\App\Models\User`
     *         que recibe la notificación.
     */
    public function usuario() { return $this->belongsTo(User::class, 'id_usuario', 'id_usuario'); }
}