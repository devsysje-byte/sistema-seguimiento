<?php

namespace App\Modules\Tramites\Services;

use App\Models\Estudiante;
use App\Models\HistorialEstadoExamen;
use App\Models\Tramite;
use App\Models\User;
use App\Modules\Tramites\Events\EstadoTramiteCambiado;
use App\Modules\Tramites\Exceptions\TransicionNoPermitidaException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Flujo de titulación de la modalidad Examen de Grado.
 *
 * A diferencia de la Tesis de Grado, el Examen de Grado no tiene tutor ni
 * tribunal: son SEIS módulos que KARDEX completa uno a uno desde el panel,
 * donde cada tarjeta permanece bloqueada hasta que la anterior queda cerrada.
 *
 *  1. `verificacion_intentos` — comprueba `numero_intentos` contra
 *     `examen.maximo_intentos`. Con `numero_intentos >= 3` la solicitud se
 *     rechaza; con menos, el estudiante sigue el flujo.
 *  2. `habilitacion_examen` — registra el N.º de Resolución del HCC con el que
 *     el estudiante queda habilitado para rendir el examen de grado.
 *  3. `sorteo_teorico` — asistencia con el tema sorteado y la fecha del examen.
 *     No asistir cierra el sorteo con `no_asistio`, pero no reproba todavía:
 *     el estudiante puede seguir al sorteo del examen de especialidad.
 *  4. `sorteo_especialidad` — misma dinámica para el examen de especialidad.
 *  5. `asignacion_notas` — registra las notas de LOS DOS exámenes y es quien
 *     decide el resultado: ambas con 51 o más dejan el trámite en `aprobado`;
 *     cualquier ausencia o nota inferior al mínimo deja el trámite en el estado
 *     reabrible que corresponde (`examen_teorico_reprobado` o
 *     `examen_especialidad_reprobado`).
 *  6. `reporte` — genera el reporte en PDF y cierra el trámite en `titulado`.
 *
 * Reprobar cualquiera de los dos exámenes NO cierra el flujo:
 * `reabrirSolicitud()` devuelve el trámite a `habilitado_examen` y consume un
 * intento, para que el estudiante pueda volver a rendir el examen de grado.
 * Titularse exige aprobar AMBOS exámenes con 51 puntos o más.
 *
 * Cada transición se escribe en dos lugares: la línea de tiempo genérica
 * (`estados_tramite`, que consume el seguimiento del estudiante) y el historial
 * especializado (`historial_estados_examen`, con el detalle de tema, fecha, nota
 * y resolución que usa el reporte en PDF).
 */
class ExamenService
{
    /**
     * Estado objetivo de cada módulo cuando se registra satisfactoriamente.
     *
     * `asignacion_notas` no tiene estado fijo: cierra el flujo en `aprobado`
     * cuando las dos notas llegan al mínimo, o en el estado reabrible que
     * corresponde (ver `destinoNotas()`).
     *
     * @var array<string, string>
     */
    private const MODULOS = [
        'verificacion_intentos' => 'intentos_verificados',
        'habilitacion_examen' => 'habilitado_examen',
        'sorteo_teorico' => 'examen_teorico_sorteado',
        'sorteo_especialidad' => 'examen_especialidad_sorteado',
        'asignacion_notas' => 'aprobado',
        'reporte' => 'titulado',
    ];

    /**
     * Estados reabribles a los que puede llegar `asignacion_notas`.
     *
     * Cada uno señala qué examen faltó aprobar: si el teórico no se aprobó
     * (ausencia o nota inferior al mínimo) manda `examen_teorico_reprobado`,
     * aunque la especialidad haya sido aprobada, porque titularse exige los dos.
     *
     * @var array<string, string>
     */
    private const ESTADO_REPROBADO = [
        'examen_teorico' => 'examen_teorico_reprobado',
        'examen_especialidad' => 'examen_especialidad_reprobado',
    ];

    /** Estado al que regresa el trámite cuando el estudiante re-solicita. */
    private const REAPERTURA = 'habilitado_examen';

    /**
     * Estados desde los que el estudiante puede volver a solicitar el examen.
     *
     * @var array<int, string>
     */
    private const REAPERIBLES = [
        'examen_teorico_reprobado',
        'examen_especialidad_reprobado',
    ];

    public function __construct(
        private readonly TramiteStateService $stateService,
        private readonly TramiteService $tramites,
    ) {}

    /**
     * Registra un módulo del flujo de Examen de Grado y avanza el estado del
     * trámite al que corresponde.
     *
     * @param  string  $modulo  Identificador del módulo (ver `self::MODULOS`).
     * @param  array<string, mixed>  $datos  Datos del formulario del módulo.
     * @return array Trámite formateado (con `historial_examen` y siguientes estados).
     *
     * @throws ValidationException
     */
    public function guardarModulo(int $id, string $modulo, array $datos, User $actor): array
    {
        return DB::transaction(fn () => $this->resolverModulo($id, $modulo, $datos, $actor));
    }

    /**
     * Guarda el módulo dentro de una transacción: la transición, el registro en
     * `hitos` y la inserción en el historial van juntos, para que un fallo a
     * mitad del guardado no deje el trámite en un estado sin su registro.
     *
     * @param  array<string, mixed>  $datos
     *
     * @throws ValidationException
     */
    private function resolverModulo(int $id, string $modulo, array $datos, User $actor): array
    {
        if (! array_key_exists($modulo, self::MODULOS)) {
            throw ValidationException::withMessages([
                'datos' => ['El módulo del flujo de examen no es reconocido.'],
            ]);
        }

        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if ($tramite->modalidad->nombre !== TramiteStateService::MODALIDAD_EXAMEN) {
            throw ValidationException::withMessages([
                'modulo' => ['El flujo de examen de grado solo aplica a la modalidad Examen de Grado.'],
            ]);
        }

        if ($this->stateService->esTerminal($tramite)) {
            throw ValidationException::withMessages([
                'datos' => ['El trámite finalizó; no es posible registrar más módulos.'],
            ]);
        }

        $estudiante = Estudiante::findOrFail($tramite->id_estudiante);

        $resolucion = $this->destino($modulo, $datos, $estudiante, $tramite);

        $this->transicionar($tramite, $resolucion['estado'], $resolucion['observacion'], $actor);

        // `paso` y `accion` son controles del formulario, no datos del módulo.
        unset($datos['paso'], $datos['accion']);

        $this->persistirModulo($tramite, $modulo, $datos);
        $this->persistirHito($tramite, $resolucion['estado']);
        $this->registrarHistorial($tramite, $modulo, $resolucion, $estudiante, $actor);

        event(new EstadoTramiteCambiado($tramite, $resolucion['estado'], $resolucion['observacion']));

        return $this->formatear($tramite);
    }

    /**
     * Reabre un trámite reprobado o con ausencia para que el estudiante pueda
     * volver a sortear el examen.
     *
     * Consume un intento: si el estudiante ya agotó los permitidos, no se puede
     * re-solicitar y KARDEX debe registrar una nueva postulación.
     */
    public function reabrirSolicitud(int $id, ?string $observaciones, User $actor): array
    {
        // La reapertura toca tres tablas (trámite, estudiante e historial). Sin
        // transacción, un fallo al escribir el historial dejaría el intento
        // consumido y el estado revertido al habilitado sin rastro de por qué, y
        // el panel de Examen —que se construye desde el historial— mostraría los
        // sorteos del intento anterior como si siguieran vigentes.
        return DB::transaction(fn () => $this->resolverReapertura($id, $observaciones, $actor));
    }

    /**
     * Cuerpo de la reapertura, ejecutado dentro de una transacción.
     *
     * @param  string|null  $observaciones  Motivo de la reapertura.
     * @return array{Trámite formateado con su historial de examen.}
     */
    private function resolverReapertura(int $id, ?string $observaciones, User $actor): array
    {
        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if ($tramite->modalidad->nombre !== TramiteStateService::MODALIDAD_EXAMEN) {
            throw ValidationException::withMessages([
                'tramite' => ['Solo los trámites de Examen de Grado pueden re-solicitar el examen.'],
            ]);
        }

        if (! in_array($tramite->estado_actual, self::REAPERIBLES, true)) {
            throw ValidationException::withMessages([
                'estado_actual' => ['El estudiante solo puede re-solicitar el examen si reprobó alguno de los dos exámenes.'],
            ]);
        }

        $estudiante = Estudiante::findOrFail($tramite->id_estudiante);

        if ($estudiante->intentosAgotados()) {
            throw ValidationException::withMessages([
                'numero_intentos' => [
                    'El estudiante agotó los intentos permitidos para el Examen de Grado. '
                    .'Registre una nueva postulación si corresponde.',
                ],
            ]);
        }

        $intentos = $estudiante->numero_intentos + 1;
        $observacion = trim((string) $observaciones) !== ''
            ? (string) $observaciones
            : 'El estudiante solicitó volver a rendir el examen de grado.';

        $this->transicionar($tramite, self::REAPERTURA, $observacion, $actor);

        $estudiante->update(['numero_intentos' => $intentos]);
        $this->persistirHito($tramite, self::REAPERTURA);
        $this->limpiarModulosDeIntento($tramite);
        $this->registrarHistorial(
            $tramite,
            'reapertura',
            [
                'estado' => self::REAPERTURA,
                'resultado' => 're_solicitud',
                'numero_resolucion' => null,
                'tema' => null,
                'fecha_examen' => null,
                'nota' => null,
                'observacion' => $observacion,
            ],
            $estudiante,
            $actor,
            intentos: $intentos
        );

        event(new EstadoTramiteCambiado($tramite, self::REAPERTURA, $observacion));

        return $this->formatear($tramite);
    }

    /**
     * Resuelve el estado objetivo de un módulo a partir de sus datos, validando
     * el formulario y componiendo el registro del historial.
     *
     * @param  array<string, mixed>  $datos  Datos del formulario.
     * @return array{estado:string, resultado:?string, numero_resolucion:?string,
     *               tema:?string, fecha_examen:?string, nota:?float, observacion:string}
     */
    private function destino(string $modulo, array $datos, Estudiante $estudiante, Tramite $tramite): array
    {
        return match ($modulo) {
            'verificacion_intentos' => $this->destinoIntentos($datos, $estudiante, $tramite),
            'habilitacion_examen' => $this->destinoHabilitacion($datos),
            'sorteo_teorico' => $this->destinoSorteo($datos, 'examen_teorico'),
            'sorteo_especialidad' => $this->destinoSorteo($datos, 'examen_especialidad'),
            'asignacion_notas' => $this->destinoNotas($datos, $tramite),
            'reporte' => $this->destinoReporte($tramite),
            default => throw ValidationException::withMessages([
                'datos' => ['El módulo del flujo de examen no es reconocido.'],
            ]),
        };
    }

    /**
     * Módulo 1 — verificación de intentos.
     *
     * Aprobar exige que al estudiante le queden intentos; rechazar es
     * exclusivamente por intento agotado, por lo que también se valida que los
     * haya agotado:rejectar por otro motivo debe hacerse desde la revisión de la
     * solicitud, no desde este módulo.
     *
     * @param  array<string, mixed>  $datos
     * @return array{estado:string, resultado:?string, numero_resolucion:?string,
     *               tema:?string, fecha_examen:?string, nota:?float, observacion:string}
     */
    private function destinoIntentos(array $datos, Estudiante $estudiante, Tramite $tramite): array
    {
        $maximo = (int) config('examen.maximo_intentos', 3);
        $intentos = (int) $estudiante->numero_intentos;
        $agotados = $intentos >= $maximo;

        if (($datos['accion'] ?? 'aprobar') === 'rechazar') {
            if (! $agotados) {
                throw ValidationException::withMessages([
                    'numero_intentos' => [
                        "El estudiante lleva {$intentos} de {$maximo} intentos permitidos. "
                        .'Solo se rechaza la solicitud cuando los intentos están agotados.',
                    ],
                ]);
            }

            $motivo = trim((string) ($datos['observaciones'] ?? ''));

            if ($motivo === '') {
                throw ValidationException::withMessages([
                    'observaciones' => ['Indique el motivo por el que se rechaza la solicitud.'],
                ]);
            }

            return [
                'estado' => 'rechazado',
                'resultado' => 'rechazado',
                'numero_resolucion' => null,
                'tema' => null,
                'fecha_examen' => null,
                'nota' => null,
                'observacion' => $motivo,
            ];
        }

        if ($agotados) {
            throw ValidationException::withMessages([
                'numero_intentos' => [
                    "El estudiante agotó los {$maximo} intentos permitidos para el Examen de Grado. "
                    .'Rechace la solicitud en lugar de habilitarla.',
                ],
            ]);
        }

        return [
            'estado' => 'intentos_verificados',
            'resultado' => 'verificado',
            'numero_resolucion' => null,
            'tema' => null,
            'fecha_examen' => null,
            'nota' => null,
            'observacion' => "Intento {$intentos} de {$maximo}. El estudiante habilita su solicitud de examen de grado.",
        ];
    }

    /**
     * Módulo 2 — habilitación del estudiante con la Resolución del HCC.
     *
     * La fecha de la resolución se guarda en su propia columna del historial
     * (`fecha_resolucion`) y no en `fecha_examen`, que es la fecha en que se
     * rindió cada examen.
     *
     * @param  array<string, mixed>  $datos
     * @return array{estado:string, resultado:?string, numero_resolucion:?string,
     *               tema:?string, fecha_examen:?string, nota:?float, observacion:string,
     *               fecha_resolucion:?string}
     */
    private function destinoHabilitacion(array $datos): array
    {
        $errores = [];

        if (! $this->informado($datos['numero_resolucion'] ?? null)) {
            $errores[] = 'Ingrese el número de Resolución del HCC.';
        }
        if (! $this->informado($datos['fecha_resolucion'] ?? null)) {
            $errores[] = 'Seleccione la fecha de la Resolución.';
        }

        if ($errores !== []) {
            throw ValidationException::withMessages(['datos' => $errores]);
        }

        $resolucion = trim((string) $datos['numero_resolucion']);
        $fechaResolucion = $this->opcional($datos['fecha_resolucion'] ?? null);

        return [
            'estado' => 'habilitado_examen',
            'resultado' => 'habilitado',
            'numero_resolucion' => $resolucion,
            'fecha_resolucion' => $fechaResolucion,
            'tema' => null,
            'fecha_examen' => null,
            'nota' => null,
            'observacion' => "Estudiante habilitado para el examen de grado. Resolución N.º {$resolucion} del HCC.",
        ];
    }

    /**
     * Módulos 3 y 4 — sorteo de bolo del examen teórico y de especialidad.
     *
     * Registra la asistencia con el tema sorteado y la fecha del examen. No
     * asistir cierra el sorteo con el resultado `no_asistio` y sin nota, pero
     * el estado del trámite sigue siendo `{prefijo}_sorteado`: la reprobación
     * recién se decide en el módulo 5 (`asignacion_notas`), de modo que un
     * examen no rendido no bloquea el sorteo del otro.
     *
     * @param  array<string, mixed>  $datos
     * @param  string  $prefijo  `examen_teorico` o `examen_especialidad`.
     * @return array{estado:string, resultado:?string, numero_resolucion:?string,
     *               tema:?string, fecha_examen:?string, nota:?float, observacion:string}
     */
    private function destinoSorteo(array $datos, string $prefijo): array
    {
        $etiqueta = $prefijo === 'examen_teorico' ? 'examen teórico' : 'examen de especialidad';
        $asistio = filter_var($datos['asistio'] ?? null, FILTER_VALIDATE_BOOLEAN);

        if (! $asistio) {
            return [
                'estado' => "{$prefijo}_sorteado",
                'resultado' => 'no_asistio',
                'numero_resolucion' => $this->opcional($datos['numero_resolucion'] ?? null),
                'tema' => null,
                'fecha_examen' => $this->opcional($datos['fecha_examen'] ?? null),
                'nota' => null,
                'observacion' => "El estudiante no asistió al sorteo de bolos del {$etiqueta}. "
                    .'La nota se registra en Asignación de Notas.',
            ];
        }

        $errores = [];

        if (! $this->informado($datos['tema'] ?? null)) {
            $errores[] = "Ingrese el tema sorteado para el {$etiqueta}.";
        }
        if (! $this->informado($datos['fecha_examen'] ?? null)) {
            $errores[] = "Seleccione la fecha del {$etiqueta}.";
        }

        if ($errores !== []) {
            throw ValidationException::withMessages(['datos' => $errores]);
        }

        return [
            'estado' => "{$prefijo}_sorteado",
            'resultado' => 'asistio',
            'numero_resolucion' => $this->opcional($datos['numero_resolucion'] ?? null),
            'tema' => trim((string) $datos['tema']),
            'fecha_examen' => $this->opcional($datos['fecha_examen']),
            'nota' => null,
            'observacion' => "Asistió al sorteo de bolos del {$etiqueta} y presentó el examen. "
                .'La nota se registra en Asignación de Notas.',
        ];
    }

    /**
     * Módulo 5 — asignación de notas de LOS DOS exámenes.
     *
     * Es el único módulo que decide el resultado del intento: exige que los dos
     * sorteos del intento en curso estén registrados y, por cada examen rendido,
     * una nota dentro del rango permitido. Un examen no rendido (ausencia en su
     * sorteo) cierra sin nota y cuenta como reprobado.
     *
     * El estado resultante es `aprobado` solo cuando las dos notas llegan al
     * mínimo; si no, deja el trámite en el estado reabrible del examen que faltó
     * aprobar (`examen_teorico_reprobado` manda sobre el de especialidad,
     * porque titularse exige los dos).
     *
     * @param  array<string, mixed>  $datos
     * @return array{estado:string, resultado:?string, numero_resolucion:?string,
     *               tema:?string, fecha_examen:?string, nota:?float, observacion:string}
     */
    private function destinoNotas(array $datos, Tramite $tramite): array
    {
        $intento = $this->historialDelIntento($tramite);
        $minimo = (int) config('examen.nota_minima', 51);
        [$rangoMin, $rangoMax] = config('examen.rango_nota', [0, 100]);

        $campos = [
            'examen_teorico' => ['sorteo_teorico', 'nota_teorico', 'examen teórico'],
            'examen_especialidad' => ['sorteo_especialidad', 'nota_especialidad', 'examen de especialidad'],
        ];

        $notas = [];
        $ausencias = [];

        foreach ($campos as $prefijo => [$etapa, $campo, $etiqueta]) {
            $sorteo = $intento->firstWhere('etapa', $etapa);

            if ($sorteo === null) {
                throw ValidationException::withMessages([
                    'datos' => ["Registre primero el sorteo del {$etiqueta} antes de asignar la nota."],
                ]);
            }

            if ($sorteo->resultado !== 'asistio') {
                $notas[$prefijo] = null;
                $ausencias[$prefijo] = true;

                continue;
            }

            $valor = $datos[$campo] ?? null;

            if ($valor === null || $valor === '' || ! is_numeric($valor)) {
                throw ValidationException::withMessages([
                    'datos' => ["Ingrese la nota del {$etiqueta}."],
                ]);
            }

            $nota = (float) $valor;

            if ($nota < $rangoMin || $nota > $rangoMax) {
                throw ValidationException::withMessages([
                    'datos' => ["La nota del {$etiqueta} debe estar entre {$rangoMin} y {$rangoMax}."],
                ]);
            }

            $notas[$prefijo] = $nota;
            $ausencias[$prefijo] = false;
        }

        $teoricoAprobado = $this->examenAprobado($notas['examen_teorico'], $ausencias['examen_teorico'], $minimo);
        $especialidadAprobado = $this->examenAprobado($notas['examen_especialidad'], $ausencias['examen_especialidad'], $minimo);

        if ($teoricoAprobado && $especialidadAprobado) {
            $estado = 'aprobado';
        } elseif (! $teoricoAprobado) {
            $estado = self::ESTADO_REPROBADO['examen_teorico'];
        } else {
            $estado = self::ESTADO_REPROBADO['examen_especialidad'];
        }

        return [
            'estado' => $estado,
            'resultado' => $estado === 'aprobado' ? 'aprobado' : 'reprobado',
            'numero_resolucion' => null,
            'tema' => null,
            'fecha_examen' => null,
            'nota' => $notas['examen_teorico'],
            'observacion' => $this->observacionAsignacionNotas($notas, $ausencias, $minimo, $estado),
        ];
    }

    /** Indica si un examen quedó aprobado: asistió y alcanzó el mínimo. */
    private function examenAprobado(?float $nota, bool $ausente, int $minimo): bool
    {
        return ! $ausente && $nota !== null && $nota >= $minimo;
    }

    /**
     * Registros del historial que pertenecen al intento en curso.
     *
     * Espejo de `registrosDelIntento()` del frontend: lo anterior a la última
     * reapertura corresponde al intento ya consumido y no cuenta.
     *
     * @return Collection<int, HistorialEstadoExamen>
     */
    private function historialDelIntento(Tramite $tramite): Collection
    {
        $filas = $tramite->historialExamen
            ->sortBy([['created_at', 'asc'], ['id_historial_examen', 'asc']])
            ->values();

        $corte = $filas->search(fn (HistorialEstadoExamen $f) => $f->etapa === 'reapertura');

        return $corte === false ? $filas : $filas->slice($corte + 1);
    }

    /**
     * Observación del historial para la asignación de notas.
     *
     * Resume las dos notas juntas: la fila del historial tiene una sola columna
     * de nota (se usa la del examen teórico) y el detalle completo de ambas
     * queda en `hitos.modulos.asignacion_notas`.
     */
    private function observacionAsignacionNotas(array $notas, array $ausencias, int $minimo, string $estado): string
    {
        $detalle = [];

        foreach (['examen_teorico' => 'examen teórico', 'examen_especialidad' => 'examen de especialidad'] as $prefijo => $etiqueta) {
            $detalle[] = $ausencias[$prefijo]
                ? "{$etiqueta}: no asistió (sin nota, REPROBADO)"
                : "{$etiqueta}: {$this->formatearNota((float) $notas[$prefijo])} puntos";
        }

        $resumen = 'Asignación de notas — '.implode(' · ', $detalle)." (mínimo {$minimo}).";

        if ($estado === 'aprobado') {
            return $resumen.' Ambos exámenes APROBADOS.';
        }

        return $resumen.' Al menos un examen NO fue aprobado: el trámite queda reabrible.';
    }

    /**
     * Módulo 6 — reporte en PDF; el trámite queda en `titulado`.
     *
     * Generar el reporte es el último paso del Examen de Grado: el estudiante
     * aprobó los dos exámenes, así que el cierre del flujo y la titulación
     * ocurren juntos y no queda una etapa intermedia en la que el trámite esté
     * aprobado sin concluir.
     *
     * Exige que la asignación de notas del intento en curso haya cerrado en
     * `aprobado`: con una nota reprobada o ausente no se emite el reporte.
     *
     * @return array{estado:string, resultado:?string, numero_resolucion:?string,
     *               tema:?string, fecha_examen:?string, nota:?float, observacion:string}
     *
     * @throws ValidationException
     */
    private function destinoReporte(Tramite $tramite): array
    {
        // El reporte titula al estudiante, así que se comprueba que las notas
        // del intento en curso quedaron aprobadas. La máquina de estados ya
        // impide llegar con el estado equivocado, pero se valida explícitamente
        // para no depender de ella: título es lo que no se puede deshacer.
        $aprobado = $this->historialDelIntento($tramite)->contains(
            fn (HistorialEstadoExamen $f) => $f->etapa === 'asignacion_notas' && $f->resultado === 'aprobado'
        );

        if (! $aprobado) {
            throw ValidationException::withMessages([
                'datos' => [
                    'Solo se emite el reporte cuando el estudiante aprobó el examen teórico '
                    .'y el examen de especialidad.',
                ],
            ]);
        }

        return [
            'estado' => 'titulado',
            'resultado' => 'titulado',
            'numero_resolucion' => null,
            'tema' => null,
            'fecha_examen' => null,
            'nota' => null,
            'observacion' => 'Reporte de Examen de Grado generado. Estudiante titulado.',
        ];
    }

    /**
     * Ejecuta la transición de estado traduciendo el error de la máquina de
     * estados a un mensaje de validación que la tarjeta puede mostrar.
     *
     * @throws ValidationException
     */
    private function transicionar(Tramite $tramite, string $estado, string $observacion, User $actor): void
    {
        try {
            $this->stateService->transicionar($tramite, $estado, $observacion, $actor->id_usuario);
        } catch (TransicionNoPermitidaException $e) {
            throw ValidationException::withMessages([
                'datos' => [$e->getMessage()],
            ]);
        }
    }

    /**
     * Guarda los datos del formulario en `hitos.modulos`, igual que el flujo de
     * Tesis, para que el seguimiento del estudiante muestre lo que registró
     * KARDEX sin conocer el detalle técnico de la tabla de historial.
     */
    private function persistirModulo(Tramite $tramite, string $modulo, array $datos): void
    {
        $hitos = $tramite->hitos ?? [];

        $hitos['modulos'][$modulo] = array_replace($hitos['modulos'][$modulo] ?? [], $datos);

        $tramite->update(['hitos' => $hitos]);
    }

    /** Registra la fecha en que el trámite alcanzó el estado, para la línea de tiempo. */
    private function persistirHito(Tramite $tramite, string $estado): void
    {
        $hitos = $tramite->fresh()?->hitos ?? [];

        if (in_array($estado, ['rechazado', 'titulado', 'aprobado'], true)) {
            $hitos[$estado] = Carbon::today()->toDateString();
        }

        $tramite->update(['hitos' => $hitos]);
    }

    /**
     * Deja en blanco los módulos que se repiten en cada intento tras una
     * reapertura.
     *
     * Los sorteos y la asignación de notas viven en `hitos.modulos` para el
     * seguimiento, pero pertenecen al intento que se acaba de consumir: si se
     * conservaran, la tarjeta del nuevo intento mostraría el tema, la fecha y
     * las notas del intento anterior como si fueran los vigentes.
     */
    private function limpiarModulosDeIntento(Tramite $tramite): void
    {
        $hitos = $tramite->hitos ?? [];

        foreach (['sorteo_teorico', 'sorteo_especialidad', 'asignacion_notas'] as $modulo) {
            unset($hitos['modulos'][$modulo]);
        }

        $tramite->update(['hitos' => $hitos]);
    }

    /**
     * Inserta la transición en `historial_estados_examen`, el registro
     * especializado con el detalle del examen que consume el reporte en PDF.
     *
     * @param  array{resultado:?string, numero_resolucion:?string, tema:?string,
     *               fecha_examen:?string, nota:?float, observacion:string,
     *               fecha_resolucion?:?string}  $resolucion
     */
    private function registrarHistorial(
        Tramite $tramite,
        string $etapa,
        array $resolucion,
        Estudiante $estudiante,
        User $actor,
        ?int $intentos = null,
    ): void {
        HistorialEstadoExamen::create([
            'id_tramite' => $tramite->id_tramite,
            'nombre_estado' => $resolucion['estado'],
            'etapa' => $etapa,
            'resultado' => $resolucion['resultado'],
            'numero_resolucion' => $resolucion['numero_resolucion'],
            'fecha_resolucion' => $resolucion['fecha_resolucion'] ?? null,
            'tema' => $resolucion['tema'],
            'fecha_examen' => $resolucion['fecha_examen'],
            'nota' => $resolucion['nota'],
            'numero_intentos' => $intentos ?? (int) $estudiante->numero_intentos,
            'observaciones' => $resolucion['observacion'],
            'id_usuario_responsable' => $actor->id_usuario,
        ]);
    }

    /**
     * Trámite formateado con el historial de examen cargado, listo para la
     * respuesta de la API.
     */
    private function formatear(Tramite $tramite): array
    {
        $tramite->refresh()->load('historialExamen');

        $respuesta = $this->tramites->formatear($tramite);
        $respuesta['historial_examen'] = $tramite->historialExamen
            ->sortBy('created_at')
            ->values()
            ->toArray();

        return $respuesta;
    }

    /** Indica si un valor de formulario trae contenido. */
    private function informado(mixed $valor): bool
    {
        return is_string($valor) ? trim($valor) !== '' : $valor !== null;
    }

    /** Normaliza un campo opcional: cadena recortada o null. */
    private function opcional(mixed $valor): ?string
    {
        $texto = is_string($valor) ? trim($valor) : '';

        return $texto === '' ? null : $texto;
    }

    /** Presenta la nota sin decimales innecesarios (74 en vez de 74.00). */
    private function formatearNota(float $nota): string
    {
        return rtrim(rtrim(number_format($nota, 2, '.', ''), '0'), '.');
    }
}
