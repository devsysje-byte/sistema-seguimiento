<?php

namespace App\Modules\Tramites\Services;

use App\Models\EstadoTramite;
use App\Models\Tramite;
use App\Modules\Tramites\Exceptions\TransicionNoPermitidaException;
use Illuminate\Support\Facades\DB;

/**
 * Máquina de estados de los trámites de titulación.
 *
 * Centraliza el mapa de transiciones permitidas por modalidad, la validación y
 * ejecución de cambios de estado (con registro en el historial) y los métodos
 * de consulta de estados siguientes y de la secuencia para la línea de tiempo.
 * No depende de controladores ni de otros módulos.
 */
class TramiteStateService
{
    /**
     * Mapa de transiciones permitidas por modalidad de titulación.
     *
     * Cada modalidad define su flujo como `estado_actual => [estados siguientes]`.
     * El estado `rechazado` solo se alcanza por flujos de evaluación (revisión
     * inicial o rechazo definitivo del perfil), no como avance normal.
     *
     * Tesis de Grado sigue el flujo oficial del estudiante: solicitud con 3
     * archivos, evaluación del Consejo Universitario, reenvío del perfil
     * rechazado, tutor asignado, investigación con plazo de presentación
     * (3-12 meses), comisión revisora (suficiente/insuficiente), solicitud de
     * fecha de defensa, defensa y 90 días de corrección si no la aprueba.
     *
     * @var array<string, array<string, array<int, string>>>
     */
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
            // 1. Fase de solicitud.
            'solicitud_presentada' => ['pendiente_concejo_universitario', 'rechazado'],
            // 2. Evaluación del perfil por el Consejo Universitario.
            'pendiente_concejo_universitario' => ['perfil_aprobado', 'perfil_rechazado'],
            'perfil_rechazado' => ['pendiente_concejo_universitario', 'rechazado'],
            // 3. Tutor asignado e investigación en desarrollo (plazo configurable).
            'perfil_aprobado' => ['tutor_asignado'],
            'tutor_asignado' => ['investigacion_en_desarrollo'],
            'investigacion_en_desarrollo' => ['documento_final_presentado'],
            // 4. Comisión revisora del documento final.
            'documento_final_presentado' => ['comision_revisora'],
            'comision_revisora' => ['suficiente', 'insuficiente'],
            'insuficiente' => ['comision_revisora'],
            // 5. Fase final / defensa.
            'suficiente' => ['solicitud_fecha_defensa'],
            'solicitud_fecha_defensa' => ['defensa_programada'],
            'defensa_programada' => ['defensa_en_curso'],
            'defensa_en_curso' => ['aprobado', 'correcciones_90_dias'],
            // Tras reprobar la defensa, el estudiante tiene 90 días para corregir
            // y volver a solicitar una fecha; si no, la gestión lo pasa a `reprobado`.
            'correcciones_90_dias' => ['solicitud_fecha_defensa', 'reprobado'],
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

    /**
     * Estados terminales: desde ellos no se puede avanzar a ningún otro estado.
     *
     * @var array<int, string>
     */
    private const TERMINALES = ['aprobado', 'reprobado', 'rechazado', 'reprobado_ausencia'];

    /**
     * Estados terminales compartidos del sistema.
     *
     * Expuestos públicos para que otros servicios (listados, estadísticas)
     * filtren sin duplicar la lista de estados.
     *
     * @return array<int, string>
     */
    public static function terminales(): array
    {
        return self::TERMINALES;
    }

    /**
     * Valida y ejecuta una transición de estado sobre un trámite.
     *
     * Dentro de una transacción de BD registra el nuevo estado en el historial
     * y actualiza el `estado_actual` del trámite.
     *
     * @return \App\Models\EstadoTramite Registro de historial creado.
     *
     * @throws \App\Modules\Tramites\Exceptions\TransicionNoPermitidaException
     */
    public function transicionar(Tramite $tramite, string $nuevoEstado, string $observaciones, int $usuarioId): EstadoTramite
    {
        $modalidadNombre = $tramite->modalidad->nombre;
        $estadoActual = $tramite->estado_actual;

        if (! isset(self::TRANSITIONS[$modalidadNombre][$estadoActual])
            || ! in_array($nuevoEstado, self::TRANSITIONS[$modalidadNombre][$estadoActual], true)) {
            throw new TransicionNoPermitidaException(
                "Transición no permitida de '{$estadoActual}' a '{$nuevoEstado}' para {$modalidadNombre}."
            );
        }

        return DB::transaction(function () use ($tramite, $nuevoEstado, $observaciones, $usuarioId) {
            $historial = EstadoTramite::create([
                'id_tramite' => $tramite->id_tramite,
                'nombre_estado' => $nuevoEstado,
                'descripcion' => $tramite->modalidad->nombre . ' - Cambio de estado',
                'id_usuario_responsable' => $usuarioId,
                'observaciones' => $observaciones,
            ]);

            $tramite->update(['estado_actual' => $nuevoEstado]);

            return $historial;
        });
    }

    /**
     * Siguientes estados permitidos desde el estado actual de un trámite.
     *
     * Excluye `rechazado`, reservado al flujo de revisión inicial.
     *
     * @return array<int, string>
     */
    public function getSiguientesEstados(Tramite $tramite): array
    {
        $siguientes = self::TRANSITIONS[$tramite->modalidad->nombre][$tramite->estado_actual] ?? [];

        return array_values(array_filter($siguientes, fn ($s) => $s !== 'rechazado'));
    }

    /**
     * Secuencia ordenada de todos los estados de una modalidad para la línea
     * de tiempo del frontend.
     *
     * @return array<int, string>
     */
    public function getSecuenciaEstados(string $modalidadNombre): array
    {
        $mapa = self::TRANSITIONS[$modalidadNombre] ?? [];
        if (empty($mapa)) {
            return [];
        }

        $secuencia = [];
        $cola = ['solicitud_presentada'];
        $terminales = [];

        while (! empty($cola)) {
            $estado = array_shift($cola);
            if (in_array($estado, $secuencia, true)) {
                continue;
            }
            $secuencia[] = $estado;

            foreach ($mapa[$estado] ?? [] as $siguiente) {
                if (in_array($siguiente, self::TERMINALES, true)) {
                    if (! in_array($siguiente, $terminales, true)) {
                        $terminales[] = $siguiente;
                    }
                } else {
                    $cola[] = $siguiente;
                }
            }
        }

        // Estados definidos en el mapa pero nunca visitados (ramas muertas).
        foreach (array_keys($mapa) as $estado) {
            if (! in_array($estado, $secuencia, true)) {
                $secuencia[] = $estado;
            }
        }

        // Los estados terminales van al final.
        return array_merge(array_diff($secuencia, self::TERMINALES), $terminales);
    }
}