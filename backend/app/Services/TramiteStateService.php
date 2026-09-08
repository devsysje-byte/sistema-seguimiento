<?php
namespace App\Services;

use App\Models\Tramite;
use App\Models\EstadoTramite;
use Illuminate\Support\Facades\DB;
use Exception;

class TramiteStateService
{
    // Mapa de transiciones permitidas por Modalidad (Basado en tu documento)
    private const TRANSITIONS = [
        'Examen de Grado' => [
            'solicitud_presentada' => ['certificacion_acreditacion'],
            'certificacion_acreditacion' => ['inscripcion_pagada'],
            'inscripcion_pagada' => ['espera_de_sorteo'],
            'espera_de_sorteo' => ['tema_sorteado', 'reprobado_ausencia'],
            'tema_sorteado' => ['examen_en_curso'],
            'examen_en_curso' => ['deliberacion_tribunal'],
            'deliberacion_tribunal' => ['acta_registrada'],
            'acta_registrada' => ['aprobado', 'reprobado'],
        ],
        'Tesis de Grado' => [
            'solicitud_presentada' => ['tema_aprobado'],
            'tema_aprobado' => ['perfil_presentado'],
            'perfil_presentado' => ['perfil_en_evaluacion'],
            'perfil_en_evaluacion' => ['perfil_aprobado', 'perfil_rechazado'],
            'perfil_aprobado' => ['investigacion_en_desarrollo'],
            'investigacion_en_desarrollo' => ['documento_final_presentado'],
            'documento_final_presentado' => ['conformidad_tutor'],
            'conformidad_tutor' => ['tribunal_designado'],
            'tribunal_designado' => ['documento_en_evaluacion'],
            'documento_en_evaluacion' => ['complementaciones_en_curso', 'aprobado_resolucion'],
            'complementaciones_en_curso' => ['documento_en_evaluacion'],
            'aprobado_resolucion' => ['fecha_defensa_programada'],
            'fecha_defensa_programada' => ['defensa_en_curso'],
            'defensa_en_curso' => ['deliberacion_defensa'],
            'deliberacion_defensa' => ['aprobado', 'ampliacion_defensa', 'reprobado'],
        ],
        // 'Trabajo Dirigido' y 'Excelencia Académica' seguirían la misma estructura...
    ];

    public function transicionar(Tramite $tramite, string $nuevoEstado, string $observaciones, int $usuarioId): EstadoTramite
    {
        $modalidadNombre = $tramite->modalidad->nombre;
        $estadoActual = $tramite->estado_actual;

        // 1. Validar si la transición está permitida
        if (!isset(self::TRANSITIONS[$modalidadNombre][$estadoActual]) ||
            !in_array($nuevoEstado, self::TRANSITIONS[$modalidadNombre][$estadoActual])) {
            throw new Exception("Transición no permitida de '{$estadoActual}' a '{$nuevoEstado}' para {$modalidadNombre}.");
        }

        return DB::transaction(function () use ($tramite, $nuevoEstado, $observaciones, $usuarioId) {
            // 2. Registrar en el historial
            $historial = EstadoTramite::create([
                'id_tramite' => $tramite->id_tramite,
                'nombre_estado' => $nuevoEstado,
                'descripcion' => $tramite->modalidad->nombre . " - Cambio de estado",
                'id_usuario_responsable' => $usuarioId,
                'observaciones' => $observaciones,
            ]);

            // 3. Actualizar el estado actual del trámite
            $tramite->update(['estado_actual' => $nuevoEstado]);

            // 4. Aquí dispararíamos la Notificación de WhatsApp (Sprint 4)
            // event(new TramiteActualizado($tramite, $historial));

            return $historial;
        });
    }

    // Obtener los siguientes estados permitidos para un trámite
    public function getSiguientesEstados(Tramite $tramite): array
    {
        $modalidadNombre = $tramite->modalidad->nombre;
        $estadoActual = $tramite->estado_actual;
        return self::TRANSITIONS[$modalidadNombre][$estadoActual] ?? [];
    }
}
