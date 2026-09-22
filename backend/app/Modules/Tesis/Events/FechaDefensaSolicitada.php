<?php

namespace App\Modules\Tesis\Events;

use App\Models\Tramite;
use App\Models\User;

/**
 * Evento de dominio: un estudiante solicitó una fecha para su defensa de tesis.
 *
 * Lo escucha el módulo de Notificaciones para avisar a los roles de gestión
 * (Kardex, Secretaría, Dirección, Admin) para que programen la defensa.
 */
class FechaDefensaSolicitada
{
    public function __construct(
        public readonly Tramite $tramite,
        public readonly User $estudiante,
        public readonly ?string $fechaSugerida,
    ) {
    }
}