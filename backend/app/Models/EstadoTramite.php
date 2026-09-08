<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EstadoTramite extends Model
{
    protected $table = 'estados_tramite';
    protected $primaryKey = 'id_estado';
    protected $fillable = ['id_tramite', 'nombre_estado', 'descripcion', 'id_usuario_responsable', 'observaciones'];

    public function tramite() { return $this->belongsTo(Tramite::class, 'id_tramite', 'id_tramite'); }
    public function responsable() { return $this->belongsTo(User::class, 'id_usuario_responsable', 'id_usuario'); }
}
