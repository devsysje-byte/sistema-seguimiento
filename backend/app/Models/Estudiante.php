<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de la tabla `estudiantes`.
 *
 * Representa el perfil académico de un estudiante (código universitario, plan de
 * estudios, promedio global, etc.). Pertenece a un usuario y puede tener varios
 * trámites de titulación.
 */
class Estudiante extends Model
{
    protected $primaryKey = 'id_estudiante';
    protected $fillable = ['id_usuario', 'codigo_universitario', 'plan_estudios', 'fecha_conclusion_plan', 'promedio_global', 'estado'];

    /**
     * Relación inversa uno a uno con el usuario propietario del perfil.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Usuario `\App\Models\User`
     *         dueño de este perfil de estudiante.
     */
    public function user() { return $this->belongsTo(User::class, 'id_usuario', 'id_usuario'); }

    /**
     * Relación uno a muchos con los trámites del estudiante.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany Trámites `\App\Models\Tramite`
     *         presentados por este estudiante.
     */
    public function tramites() { return $this->hasMany(Tramite::class, 'id_estudiante', 'id_estudiante'); }
}
