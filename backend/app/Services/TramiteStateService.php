<?php
namespace App\Services;

use App\Models\Tramite;
use App\Models\EstadoTramite;
use Illuminate\Support\Facades\DB;
use Exception;

class TramiteStateService
{
    // Mapa de transiciones permitidas por Modalidad
    private const TRANSITIONS = [
        'Examen de Grado' => [
            'solicitud_presentada' => ['certificacion_acreditacion', 'rechazado'],
            'certificacion_acreditacion' => ['inscripcion_pagada'],
            'inscripcion_pagada' => ['espera_de_sorteo'],
            'espera_de_sorteo' => ['tema_sorteado', 'reprobado_ausencia'],
            'tema_sorteado' => ['examen_en_curso'],
            'examen_en_curso' => ['deliberacion_tribunal'],
            'deliberacion_tribunal' => ['acta_registrada'],
            'acta_registrada' => ['aprobado', 'reprobado'],
        ],
        'Tesis de Grado' => [
            'solicitud_presentada' => ['tema_aprobado', 'rechazado'],
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
        'Trabajo Dirigido' => [
            'solicitud_presentada' => ['convenio_verificado', 'rechazado'],
            'convenio_verificado' => ['plan_trabajo_aprobado'],
            'plan_trabajo_aprobado' => ['ejecucion_en_curso'],
            'ejecucion_en_curso' => ['informe_final_presentado'],
            'informe_final_presentado' => ['informe_en_evaluacion'],
            'informe_en_evaluacion' => ['complementaciones_en_curso', 'defensa_programada'],
            'complementaciones_en_curso' => ['informe_en_evaluacion'],
            'defensa_programada' => ['defensa_en_curso'],
            'defensa_en_curso' => ['deliberacion_defensa'],
            'deliberacion_defensa' => ['aprobado', 'reprobado'],
        ],
        'Excelencia Académica' => [
            'solicitud_presentada' => ['promedio_verificado', 'rechazado'],
            'promedio_verificado' => ['monografia_presentada'],
            'monografia_presentada' => ['monografia_en_evaluacion'],
            'monografia_en_evaluacion' => ['monografia_aprobada', 'monografia_rechazada'],
            'monografia_aprobada' => ['revision_final'],
            'revision_final' => ['aprobado', 'reprobado'],
        ],
    ];

    // Estados terminales: no se puede avanzar desde ellos
    private const TERMINALES = ['aprobado', 'reprobado', 'rechazado', 'reprobado_ausencia'];

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
        $siguientes = self::TRANSITIONS[$modalidadNombre][$estadoActual] ?? [];
        // 'rechazado' solo se aplica por el flujo de revisión, no se ofrece como avance normal
        return array_values(array_filter($siguientes, fn ($s) => $s !== 'rechazado'));
    }

    // Secuencia ordenada de todos los estados de una modalidad (para la línea de tiempo)
    public function getSecuenciaEstados(string $modalidadNombre): array
    {
        $mapa = self::TRANSITIONS[$modalidadNombre] ?? [];
        if (empty($mapa)) {
            return [];
        }

        $secuencia = [];
        $cola = ['solicitud_presentada'];
        $terminales = [];

        while (!empty($cola)) {
            $estado = array_shift($cola);
            if (in_array($estado, $secuencia)) {
                continue;
            }
            $secuencia[] = $estado;

            foreach ($mapa[$estado] ?? [] as $siguiente) {
                if (in_array($siguiente, self::TERMINALES)) {
                    if (!in_array($siguiente, $terminales)) {
                        $terminales[] = $siguiente;
                    }
                } else {
                    $cola[] = $siguiente;
                }
            }
        }

        // Estados definidos en el mapa pero nunca visitados (ramas muertas)
        foreach (array_keys($mapa) as $estado) {
            if (!in_array($estado, $secuencia)) {
                $secuencia[] = $estado;
            }
        }

        // Los estados terminales (rechazo, aprobado, reprobado) van al final
        return array_merge(array_diff($secuencia, self::TERMINALES), $terminales);
    }
}