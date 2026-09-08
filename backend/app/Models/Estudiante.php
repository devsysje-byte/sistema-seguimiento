<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    protected $primaryKey = 'id_estudiante';
    protected $fillable = ['id_usuario', 'codigo_universitario', 'plan_estudios', 'fecha_conclusion_plan', 'promedio_global', 'estado'];

    public function user() { return $this->belongsTo(User::class, 'id_usuario', 'id_usuario'); }
    public function tramites() { return $this->hasMany(Tramite::class, 'id_estudiante', 'id_estudiante'); }
}
