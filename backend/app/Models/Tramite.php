<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Tramite extends Model
{
    protected $primaryKey = 'id_tramite';
    protected $table = 'tramites';
    protected $fillable = ['id_estudiante', 'id_modalidad', 'id_tutor', 'estado_actual', 'observaciones'];

    public function estudiante() { return $this->belongsTo(Estudiante::class, 'id_estudiante', 'id_estudiante'); }
    public function modalidad() { return $this->belongsTo(Modalidad::class, 'id_modalidad', 'id_modalidad'); }
    public function tutor() { return $this->belongsTo(User::class, 'id_tutor', 'id_usuario'); }
    public function documentos() { return $this->hasMany(DocumentoAdjunto::class, 'id_tramite', 'id_tramite'); }
    public function estados() { return $this->hasMany(EstadoTramite::class, 'id_tramite', 'id_tramite'); }
}
