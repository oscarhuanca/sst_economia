<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de la tabla `documentos_adjuntos`.
 *
 * Representa un archivo (PDF) subido como parte de un trámite de titulación,
 * con su ruta de almacenamiento, nombre original y tamaño en KB.
 */
class DocumentoAdjunto extends Model
{
    protected $table = 'documentos_adjuntos';
    protected $primaryKey = 'id_documento';
    protected $fillable = ['id_tramite', 'id_usuario_subio', 'tipo_documento', 'nombre_archivo', 'ruta_archivo', 'tamanio_kb'];

    /**
     * Relación inversa con el trámite al que pertenece el documento.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Trámite `\App\Models\Tramite`.
     */
    public function tramite() { return $this->belongsTo(Tramite::class, 'id_tramite', 'id_tramite'); }

    /**
     * Relación inversa con el usuario que subió el documento.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Usuario `\App\Models\User`
     *         que adjuntó el archivo.
     */
    public function usuario() { return $this->belongsTo(User::class, 'id_usuario_subio', 'id_usuario'); }
}
