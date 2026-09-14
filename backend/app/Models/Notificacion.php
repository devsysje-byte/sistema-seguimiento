<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $primaryKey = 'id_notificacion';
    protected $table = 'notificaciones';
    protected $fillable = ['id_usuario', 'tipo', 'titulo', 'mensaje', 'enlace', 'leida'];

    protected function casts(): array
    {
        return [
            'leida' => 'boolean',
        ];
    }

    public function usuario() { return $this->belongsTo(User::class, 'id_usuario', 'id_usuario'); }
}