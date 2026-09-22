<?php

namespace App\Modules\Tramites\Services;

use App\Models\DocumentoAdjunto;
use App\Models\EstadoTramite;
use App\Models\Tramite;
use App\Models\User;
use App\Modules\Tramites\Events\EstadoTramiteCambiado;
use App\Modules\Tramites\Events\TramiteCreado;
use App\Modules\Tramites\Events\TramiteRevisado;
use App\Modules\Tramites\Events\TutorAsignado;
use App\Support\Roles;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Casos de uso de trámites de titulación.
 *
 * Orquesta el ciclo de vida completo de un trámite: creación por el estudiante,
 * revisión de la documentación inicial, transiciones de estado, asignación de
 * tutor, paneles de consulta y estadísticas. Las notificaciones NO se envían
 * aquí directamente: se disparan eventos de dominio que el módulo de
 * Notificaciones escucha (desacoplamiento por eventos).
 */
class TramiteService
{
    /** Relaciones comunes que se cargan en cada trámite. */
    private const RELACIONES = ['modalidad', 'estudiante.user', 'documentos', 'estados.responsable', 'tutor'];

    public function __construct(private readonly TramiteStateService $stateService)
    {
    }

    /**
     * Trámite activo (más reciente) del estudiante autenticado.
     */
    public function activoDe(User $user): ?array
    {
        $estudiante = $user->estudiante;

        if (! $estudiante) {
            return null;
        }

        $tramite = Tramite::with(self::RELACIONES)
            ->where('id_estudiante', $estudiante->id_estudiante)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $tramite) {
            return null;
        }

        return $this->formatear($tramite);
    }

    /**
     * Crea un trámite: registra el estado inicial, sube los documentos y
     * notifica a los roles de gestión vía evento de dominio.
     */
    public function crear(array $validated, User $user): Tramite
    {
        $estudiante = $user->estudiante;

        if (! $estudiante) {
            throw new \DomainException('Debe completar su perfil de estudiante primero');
        }

        // Un estudiante que ya aprobó una modalidad no puede iniciar más trámites.
        $aprobada = Tramite::where('id_estudiante', $estudiante->id_estudiante)
            ->where('estado_actual', 'aprobado')
            ->exists();

        if ($aprobada) {
            throw new \DomainException('Ya aprobó su modalidad de titulación. No puede iniciar más solicitudes de trámite.');
        }

        $tramite = DB::transaction(function () use ($estudiante, $user, $validated) {
            $tramite = Tramite::create([
                'id_estudiante' => $estudiante->id_estudiante,
                'id_modalidad' => $validated['id_modalidad'],
                'estado_actual' => 'solicitud_presentada',
            ]);

            EstadoTramite::create([
                'id_tramite' => $tramite->id_tramite,
                'nombre_estado' => 'solicitud_presentada',
                'descripcion' => 'Solicitud presentada por el estudiante',
                'id_usuario_responsable' => $user->id_usuario,
                'observaciones' => 'Documentación inicial enviada.',
            ]);

            foreach ($validated['documentos'] as $documento) {
                $ruta = $documento['archivo']->store('documentos_tramites', 'public');

                DocumentoAdjunto::create([
                    'id_tramite' => $tramite->id_tramite,
                    'id_usuario_subio' => $user->id_usuario,
                    'tipo_documento' => $documento['tipo'],
                    'nombre_archivo' => $documento['archivo']->getClientOriginalName(),
                    'ruta_archivo' => $ruta,
                    'tamanio_kb' => round($documento['archivo']->getSize() / 1024, 2),
                ]);
            }

            return $tramite->load('modalidad', 'documentos', 'estados');
        });

        event(new TramiteCreado($tramite, $user));

        return $tramite;
    }

    /**
     * Solicitudes en proceso (estados no terminales) para los roles de gestión.
     */
    public function pendientes(): Collection
    {
        return Tramite::with(['estudiante.user', 'modalidad', 'documentos', 'tutor'])
            ->whereNotIn('estado_actual', ['aprobado', 'reprobado', 'rechazado', 'reprobado_ausencia'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Trámites que ya finalizaron su flujo (concluidos: aprobado, reprobado,
     * rechazado o reprobado por ausencia), ordenados por su cierre.
     */
    public function concluidos(): Collection
    {
        return Tramite::with(['estudiante.user', 'modalidad', 'documentos', 'tutor'])
            ->whereIn('estado_actual', ['aprobado', 'reprobado', 'rechazado', 'reprobado_ausencia'])
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Resumen estadístico de aprobados/reprobados por modalidad.
     */
    public function estadisticas(): array
    {
        $aprobados = ['aprobado'];
        $reprobados = ['rechazado', 'reprobado', 'reprobado_ausencia'];

        $porModalidad = Tramite::with('modalidad')
            ->get()
            ->groupBy('id_modalidad')
            ->map(function ($tramites, $idModalidad) use ($aprobados, $reprobados) {
                $primero = $tramites->first();

                return [
                    'id_modalidad' => $idModalidad,
                    'nombre' => $primero->modalidad->nombre ?? 'Sin modalidad',
                    'aprobados' => $tramites->whereIn('estado_actual', $aprobados)->count(),
                    'reprobados' => $tramites->whereIn('estado_actual', $reprobados)->count(),
                    'total' => $tramites->count(),
                ];
            })
            ->sortBy('nombre')
            ->values();

        return [
            'totales' => [
                'aprobados' => $porModalidad->sum('aprobados'),
                'reprobados' => $porModalidad->sum('reprobados'),
                'total' => $porModalidad->sum('total'),
            ],
            'porModalidad' => $porModalidad,
        ];
    }

    /**
     * Detalle completo de un trámite. Un estudiante solo puede ver los suyos.
     */
    public function mostrar(int $id, User $actor): array
    {
        $tramite = Tramite::with(self::RELACIONES)->findOrFail($id);

        if ($actor->rol === Roles::ESTUDIANTE) {
            $esSuyo = $actor->estudiante && $tramite->id_estudiante === $actor->estudiante->id_estudiante;

            if (! $esSuyo) {
                throw new AuthorizationException('No autorizado');
            }
        }

        return $this->formatear($tramite);
    }

    /**
     * Revisa la documentación inicial (`aprobar` o `rechazar`) y notifica el
     * resultado al estudiante vía evento de dominio.
     */
    public function revisar(int $id, string $accion, ?string $observaciones, User $actor): array
    {
        if ($accion === 'aprobar') {
            $siguientes = $this->stateService->getSiguientesEstados(
                Tramite::with('modalidad')->findOrFail($id)
            );

            $nuevoEstado = $siguientes[0] ?? null;

            if (! $nuevoEstado) {
                throw new \RuntimeException('No se pudo determinar el siguiente estado');
            }
        } else {
            $nuevoEstado = 'rechazado';
        }

        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if ($tramite->estado_actual !== 'solicitud_presentada') {
            throw new \RuntimeException('Este trámite ya fue revisado o avanza por otra vía');
        }

        $this->stateService->transicionar(
            $tramite,
            $nuevoEstado,
            $observaciones ?? ($accion === 'aprobar'
                ? 'Documentación inicial aprobada.'
                : 'Solicitud rechazada.'),
            $actor->id_usuario
        );

        $tramite->load(self::RELACIONES);

        event(new TramiteRevisado($tramite, $accion, $observaciones));

        return $this->formatear($tramite);
    }

    /**
     * Avanza un trámite a un nuevo estado del flujo de su modalidad y notifica
     * al estudiante y al tutor (si existe) vía evento de dominio.
     */
    public function transicionar(int $id, string $nuevoEstado, ?string $observaciones, User $actor): array
    {
        $tramite = Tramite::with('modalidad')->findOrFail($id);

        $this->stateService->transicionar(
            $tramite,
            $nuevoEstado,
            (string) $observaciones,
            $actor->id_usuario
        );

        $tramite->refresh()->load(self::RELACIONES);

        event(new EstadoTramiteCambiado($tramite, $nuevoEstado, $observaciones));

        return $this->formatear($tramite);
    }

    /**
     * Asigna (o reasigna) el docente tutor de un trámite y notifica al tutor
     * vía evento de dominio.
     */
    public function asignarTutor(int $id, int $idTutor): array
    {
        $tutor = User::findOrFail($idTutor);

        if ($tutor->rol !== Roles::DOCENTE) {
            throw ValidationException::withMessages([
                'id_tutor' => 'El tutor debe ser un usuario con rol docente',
            ]);
        }

        $tramite = Tramite::with('modalidad')->findOrFail($id);
        $tramite->update(['id_tutor' => $tutor->id_usuario]);

        $tramite->load(self::RELACIONES);

        event(new TutorAsignado($tramite, $tutor));

        return $this->formatear($tramite);
    }

    /**
     * Trámites donde el usuario autenticado figura como tutor (panel docente).
     */
    public function tutoriasDe(User $user): array
    {
        $tramites = Tramite::with(self::RELACIONES)
            ->where('id_tutor', $user->id_usuario)
            ->orderBy('created_at', 'desc')
            ->get();

        return $tramites->map(fn (Tramite $t) => $this->formatear($t))->all();
    }

    /**
     * Formatea un trámite enriqueciéndolo con los siguientes estados permitidos
     * y la secuencia completa de la modalidad (para la línea de tiempo).
     */
    public function formatear(Tramite $tramite): array
    {
        $datos = $tramite->toArray();
        $datos['siguientes_estados'] = $this->stateService->getSiguientesEstados($tramite);
        $datos['secuencia'] = $this->stateService->getSecuenciaEstados($tramite->modalidad->nombre);

        return $datos;
    }
}