<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Modalidad extends Model
{
    protected $table = 'modalidades';
    protected $primaryKey = 'id_modalidad';
    protected $fillable = ['nombre', 'descripcion', 'duracion_maxima_meses', 'requisitos_minimos', 'activo'];

    public function tramites() { return $this->hasMany(Tramite::class, 'id_modalidad', 'id_modalidad'); }
}
