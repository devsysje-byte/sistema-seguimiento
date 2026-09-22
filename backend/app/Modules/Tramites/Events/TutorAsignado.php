<?php

namespace App\Modules\Tramites\Events;

use App\Models\Tramite;
use App\Models\User;

/**
 * Evento de dominio: se asignó (o reasignó) un docente tutor a un trámite.
 */
class TutorAsignado
{
    public function __construct(
        public readonly Tramite $tramite,
        public readonly User $tutor,
    ) {
    }
}