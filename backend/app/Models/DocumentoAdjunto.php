<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoAdjunto extends Model
{
    protected $table = 'documentos_adjuntos';
    protected $primaryKey = 'id_documento';
    protected $fillable = ['id_tramite', 'id_usuario_subio', 'tipo_documento', 'nombre_archivo', 'ruta_archivo', 'tamanio_kb'];

    public function tramite() { return $this->belongsTo(Tramite::class, 'id_tramite', 'id_tramite'); }
    public function usuario() { return $this->belongsTo(User::class, 'id_usuario_subio', 'id_usuario'); }
}
