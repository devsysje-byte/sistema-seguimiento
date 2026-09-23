<?php

namespace App\Modules\Tesis\Services;

use App\Models\Modalidad;
use App\Models\Tramite;
use App\Models\User;
use App\Modules\Tesis\Events\FechaDefensaSolicitada;
use App\Modules\Tramites\Events\EstadoTramiteCambiado;
use App\Modules\Tramites\Services\TramiteService;
use App\Modules\Tramites\Services\TramiteStateService;
use App\Support\DocumentoAdjuntoService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Casos de uso del lado del ESTUDIANTE en el Módulo de Tesis de Grado.
 *
 * Encapsula el flujo oficial del estudiante independiente del resto de
 * modalidades: presentación de la solicitud con los 3 documentos obligatorios
 * y reenvío del perfil tras su rechazo. Reutiliza el motor genérico de trámites
 * (TramiteService/TramiteStateService) sin modificar su contrato.
 */
class TesisService
{
    private const MODALIDAD = 'Tesis de Grado';

    /** Documentos oficiales de la fase de solicitud. */
    public const DOC_PERFIL = 'perfil_tesis';

    /** Documento final presentado a la Comisión Revisora (y sus correcciones). */
    public const DOC_FINAL = 'documento_final';

    /** Estados desde los que el estudiante puede solicitar (o re-solicitar) su fecha de defensa. */
    private const ESTADOS_SOLICITUD_DEFENSA = ['suficiente', 'correcciones_90_dias', 'solicitud_fecha_defensa'];

    /** Estados terminales que permiten iniciar una nueva solicitud. */
    private const TERMINALES = ['aprobado', 'reprobado', 'rechazado', 'reprobado_ausencia'];

    public function __construct(
        private readonly TramiteService $tramiteService,
        private readonly TramiteStateService $stateService,
        private readonly DocumentoAdjuntoService $documentos,
    ) {
    }

    /**
     * Estado al que vuelve un perfil corregido y reenviado.
     */
    public function estadoReenvio(): string
    {
        return 'pendiente_concejo_universitario';
    }

    /**
     * El estudiante solicita una fecha para su defensa de tesis.
     *
     * Puede invocarse cuando la Comisión Revisora emitió el veredicto suficiente
     * (`suficiente`), cuando el estudiante debe re-solicitar tras no aprobar la
     * defensa (`correcciones_90_dias`) o cuando ya está esperando la programación
     * de Kardex (`solicitud_fecha_defensa`). Desde `suficiente` y
     * `correcciones_90_dias` la solicitud avanza formalmente al estado
     * `solicitud_fecha_defensa`, registra el hito y notifica a los roles de
     * gestión vía evento de dominio para que programen la defensa.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException Si no es su trámite.
     * @throws \Illuminate\Validation\ValidationException Si el trámite no es de
     *                                                    tesis o no está en un estado habilitado.
     */
    public function solicitarFechaDefensa(int $id, User $user, ?string $fechaSugerida): array
    {
        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if (! $tramite->perteneceA($user)) {
            throw new AuthorizationException('No autorizado');
        }

        $this->verificarEsTesis($tramite);

        if (! in_array($tramite->estado_actual, self::ESTADOS_SOLICITUD_DEFENSA, true)) {
            throw ValidationException::withMessages([
                'estado' => 'Solo puede solicitar la fecha de defensa cuando la Comisión Revisora calificó su tesis como suficiente o cuando debe volver a solicitarla tras una corrección.',
            ]);
        }

        // Una solicitud ya registrada en espera de programación evita re-notificar.
        $yaSolicitada = $tramite->estado_actual === 'solicitud_fecha_defensa'
            && ! empty($tramite->hitos['fecha_defensa_solicitada']);

        // Desde el veredicto o las correcciones se avanza a la espera de programación.
        if ($tramite->estado_actual !== 'solicitud_fecha_defensa') {
            $this->stateService->transicionar(
                $tramite,
                'solicitud_fecha_defensa',
                'El estudiante solicitó una fecha para su defensa.',
                $user->id_usuario
            );
        }

        $tramite->refresh();

        $hoy = Carbon::now()->toDateString();
        $hitos = $tramite->hitos ?? [];
        $hitos['fecha_defensa_solicitada'] = $hoy;

        if ($fechaSugerida) {
            $hitos['fecha_defensa_sugerida'] = $fechaSugerida;
        }

        $tramite->update(['hitos' => $hitos]);

        $tramite->refresh()->load('modalidad', 'estudiante.user', 'documentos', 'estados.responsable', 'tutor');

        if (! $yaSolicitada) {
            event(new FechaDefensaSolicitada($tramite, $user, $fechaSugerida));
        }

        return $this->tramiteService->formatear($tramite);
    }

    /**
     * Asegura que el trámite pertenezca a la modalidad Tesis de Grado.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function verificarEsTesis(Tramite $tramite): void
    {
        if (($tramite->modalidad->nombre ?? null) !== self::MODALIDAD) {
            throw ValidationException::withMessages([
                'modalidad' => 'Este trámite no corresponde a la modalidad de Tesis de Grado.',
            ]);
        }
    }

    /**
     * Crea la solicitud de Tesis de Grado con los 3 documentos obligatorios.
     *
     * @throws \DomainException Si la modalidad Tesis no está configurada o el
     *                          estudiante ya tiene una tesis en curso.
     */
    public function crearSolicitud(array $validated, User $user): Tramite
    {
        $modalidad = Modalidad::where('nombre', self::MODALIDAD)->where('activo', true)->first();

        if (! $modalidad) {
            throw new \DomainException('La modalidad ' . self::MODALIDAD . ' no está configurada en el sistema.');
        }

        $estudiante = $user->estudiante;

        if (! $estudiante) {
            throw new \DomainException('Debe completar su perfil de estudiante primero.');
        }

        $ultimo = Tramite::where('id_estudiante', $estudiante->id_estudiante)
            ->where('id_modalidad', $modalidad->id_modalidad)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($ultimo) {
            // Un estudiante ya aprobado no puede iniciar más solicitudes de trámite.
            if ($ultimo->estado_actual === 'aprobado') {
                throw new \DomainException('Su tesis de grado ya fue aprobada. No puede iniciar más solicitudes de trámite.');
            }

            // Tras reprobar, debe cumplir los plazos reglamentarios (90 días de
            // corrección o el periodo máximo de 365 días) para optar nuevamente.
            if (in_array($ultimo->estado_actual, ['reprobado', 'reprobado_ausencia'], true)
                && ! $this->puedeReoptar($ultimo)) {
                $proxima = $this->proximaReoptar($ultimo);

                throw new \DomainException(
                    'Debe esperar a que se cumplan los plazos reglamentarios para volver a presentar su solicitud'
                    . ($proxima ? " (habilitado a partir del {$proxima})." : '.')
                );
            }
        }

        $activa = Tramite::where('id_estudiante', $estudiante->id_estudiante)
            ->where('id_modalidad', $modalidad->id_modalidad)
            ->whereNotIn('estado_actual', self::TERMINALES)
            ->exists();

        if ($activa) {
            throw new \DomainException('Ya tiene una tesis de grado en curso. Avanza desde el seguimiento de su trámite.');
        }

        return $this->tramiteService->crear(
            [
                'id_modalidad' => (int) $modalidad->id_modalidad,
                'documentos' => collect($validated['documentos'] ?? [])->values()->all(),
            ],
            $user
        );
    }

    /**
     * Reenvía el perfil corregido después de un rechazo del Consejo.
     *
     * Registra el nuevo documento (si se subió), vuelve la solicitud a
     * evaluación del Consejo y dispara el evento de dominio para notificar.
     *
     * @return array Trámite formateado para el seguimiento del estudiante.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException Si no es su trámite.
     * @throws \Illuminate\Validation\ValidationException Si el trámite no es de
     *                                                    tesis o no está rechazado.
     */
    public function reenviar(int $id, User $user, ?UploadedFile $perfil, ?string $observaciones): array
    {
        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if (! $tramite->perteneceA($user)) {
            throw new AuthorizationException('No autorizado');
        }

        $this->verificarEsTesis($tramite);

        if ($tramite->estado_actual !== 'perfil_rechazado') {
            throw ValidationException::withMessages([
                'estado' => 'Solo puede reenviar el perfil cuando el Consejo Universitario lo rechazó.',
            ]);
        }

        $nuevoEstado = $this->estadoReenvio();

        DB::transaction(function () use ($tramite, $user, $perfil, $observaciones, $nuevoEstado) {
            if ($perfil) {
                $this->documentos->guardar(
                    $tramite->id_tramite,
                    $user->id_usuario,
                    $perfil,
                    self::DOC_PERFIL,
                );
            }

            $this->stateService->transicionar(
                $tramite,
                $nuevoEstado,
                $observaciones ?? 'Perfil corregido y reenviado por el estudiante.',
                $user->id_usuario
            );
        });

        $tramite->refresh()->load('modalidad', 'estudiante.user', 'documentos', 'estados.responsable', 'tutor');

        event(new EstadoTramiteCambiado($tramite, $nuevoEstado, $observaciones));

        return $this->tramiteService->formatear($tramite);
    }

    /**
     * Estado al que vuelve un documento final corregido tras calificación
     * insuficiente de la Comisión Revisora.
     */
    public function estadoReenvioDocumento(): string
    {
        return 'comision_revisora';
    }

    /**
     * Reenvía el documento final corregido después de una calificación
     * insuficiente de la Comisión Revisora.
     *
     * Registra el nuevo documento (si se subió) y devuelve el trámite a la
     * Comisión Revisora para una nueva evaluación.
     *
     * @return array Trámite formateado para el seguimiento del estudiante.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException Si no es su trámite.
     * @throws \Illuminate\Validation\ValidationException Si el trámite no es de
     *                                                    tesis o no está calificado
     *                                                    como insuficiente.
     */
    public function reenviarDocumentoFinal(int $id, User $user, ?UploadedFile $documento, ?string $observaciones): array
    {
        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if (! $tramite->perteneceA($user)) {
            throw new AuthorizationException('No autorizado');
        }

        $this->verificarEsTesis($tramite);

        if ($tramite->estado_actual !== 'insuficiente') {
            throw ValidationException::withMessages([
                'estado' => 'Solo puede reenviar su documento cuando la Comisión Revisora lo calificó como insuficiente.',
            ]);
        }

        $nuevoEstado = $this->estadoReenvioDocumento();

        DB::transaction(function () use ($tramite, $user, $documento, $observaciones, $nuevoEstado) {
            if ($documento) {
                $this->documentos->guardar(
                    $tramite->id_tramite,
                    $user->id_usuario,
                    $documento,
                    self::DOC_FINAL,
                );
            }

            $this->stateService->transicionar(
                $tramite,
                $nuevoEstado,
                $observaciones ?? 'Documento final corregido y reenviado por el estudiante.',
                $user->id_usuario
            );
        });

        $tramite->refresh()->load('modalidad', 'estudiante.user', 'documentos', 'estados.responsable', 'tutor');

        event(new EstadoTramiteCambiado($tramite, $nuevoEstado, $observaciones));

        return $this->tramiteService->formatear($tramite);
    }

    /**
     * Indica si el estudiante ya puede volver a presentar una solicitud de tesis
     * tras ser reprobado: cuando venció el plazo de correcciones (90 días) o el
     * plazo máximo de presentación (365 días / 3-12 meses).
     */
    private function puedeReoptar(Tramite $tramite): bool
    {
        $hoy = Carbon::now()->startOfDay();

        foreach ($this->fechasReoptar($tramite) as $fecha) {
            if ($hoy->gte(Carbon::parse($fecha))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fecha (YYYY-MM-DD) desde la cual el estudiante quedará habilitado a volver
     * a presentar su solicitud de tesis, o null si ya está habilitado.
     */
    private function proximaReoptar(Tramite $tramite): ?string
    {
        $hoy = Carbon::now()->startOfDay();
        $pendientes = array_filter(
            $this->fechasReoptar($tramite),
            fn (string $fecha) => $hoy->lt(Carbon::parse($fecha))
        );

        if (empty($pendientes)) {
            return null;
        }

        return min($pendientes);
    }

    /**
     * Fechas límite que habilitan la re-opción de modalidad tras un reprobado:
     * el cierre del plazo de correcciones (90 días) y el cierre del plazo máximo
     * de presentación (365 días). Se leen de los hitos con fallback a partir de
     * la fecha de reprobación.
     *
     * @return array<int, string>
     */
    private function fechasReoptar(Tramite $tramite): array
    {
        $hitos = $tramite->hitos ?? [];
        $reprobado = isset($hitos['reprobado'])
            ? Carbon::parse($hitos['reprobado'])->startOfDay()
            : null;

        $limiteCorreccion = $hitos['limite_correccion'] ?? ($reprobado
            ? $reprobado->copy()->addDays((int) config('tesis.dias_correccion'))->toDateString()
            : null);

        $limitePresentacion = $hitos['limite_presentacion'] ?? ($reprobado
            ? $reprobado->copy()->addDays((int) config('tesis.dias_remodalidad'))->toDateString()
            : null);

        return array_values(array_filter([$limiteCorreccion, $limitePresentacion]));
    }
}