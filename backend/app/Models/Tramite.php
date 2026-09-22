<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent de la tabla `tramites`.
 *
 * Representa un trámite de titulación presentado por un estudiante en una
 * modalidad concreta. Mantiene el estado actual (`estado_actual`), un historial
 * de estados (`estados`), los documentos adjuntos (`documentos`) y el docente
 * tutor asignado (`tutor`).
 */
class Tramite extends Model
{
    protected $primaryKey = 'id_tramite';
    protected $table = 'tramites';
    protected $fillable = ['id_estudiante', 'id_modalidad', 'id_tutor', 'estado_actual', 'observaciones', 'hitos'];

    /**
     * Atributos con conversión de tipos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hitos' => 'array',
    ];

    /**
     * Relación inversa con el estudiante solicitante del trámite.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Estudiante `\App\Models\Estudiante`
     *         propietario del trámite.
     */
    public function estudiante() { return $this->belongsTo(Estudiante::class, 'id_estudiante', 'id_estudiante'); }

    /**
     * Relación inversa con la modalidad de titulación del trámite.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Modalidad `\App\Models\Modalidad`
     *         a la que pertenece el trámite.
     */
    public function modalidad() { return $this->belongsTo(Modalidad::class, 'id_modalidad', 'id_modalidad'); }

    /**
     * Relación inversa con el docente tutor asignado al trámite.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Usuario tutor `\App\Models\User`
     *         (rol docente), `null` si aún no se asignó.
     */
    public function tutor() { return $this->belongsTo(User::class, 'id_tutor', 'id_usuario'); }

    /**
     * Relación uno a muchos con los documentos adjuntos del trámite.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany Documentos `\App\Models\DocumentoAdjunto`
     *         subidos para este trámite.
     */
    public function documentos() { return $this->hasMany(DocumentoAdjunto::class, 'id_tramite', 'id_tramite'); }

    /**
     * Relación uno a muchos con el historial de estados del trámite (línea de tiempo).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany Registros `\App\Models\EstadoTramite`
     *         que componen la línea de tiempo del trámite.
     */
    public function estados() { return $this->hasMany(EstadoTramite::class, 'id_tramite', 'id_tramite'); }
}
