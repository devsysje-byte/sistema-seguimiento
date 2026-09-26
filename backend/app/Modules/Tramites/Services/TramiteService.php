<?php

namespace App\Modules\Tramites\Services;

use App\Models\EstadoTramite;
use App\Models\Modalidad;
use App\Models\Tramite;
use App\Models\User;
use App\Modules\Tramites\Events\EstadoTramiteCambiado;
use App\Modules\Tramites\Events\TramiteCreado;
use App\Modules\Tramites\Events\TramiteRevisado;
use App\Modules\Tramites\Events\TutorAsignado;
use App\Modules\Tramites\Exceptions\TransicionNoPermitidaException;
use App\Support\BasePaginadoService;
use App\Support\DocumentoAdjuntoService;
use App\Support\Roles;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
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
class TramiteService extends BasePaginadoService
{
    /** Columnas estrictas del DETALLE de un trámite (prohibido `select *`). */
    private const DETALLE_COLUMNAS = [
        'id_tramite', 'id_estudiante', 'id_modalidad', 'id_tutor',
        'estado_actual', 'observaciones', 'hitos', 'created_at', 'updated_at',
    ];

    /** Columnas estrictas de los LISTADOS (solo lo que pinta el tablero). */
    private const LISTA_COLUMNAS = [
        'id_tramite', 'id_estudiante', 'id_modalidad', 'id_tutor',
        'estado_actual', 'hitos', 'created_at', 'updated_at',
    ];

    /** Eager-loads del detalle con proyección de columnas (ni contraseñas ni sobrantes). */
    private const DETALLE_CARGAS = [
        'modalidad:id_modalidad,nombre,descripcion,requisitos_minimos',
        'estudiante:id_estudiante,ci,nombres,apellidos,registro_universitario,email,telefono,promedio_global',
        'estudiante.user:id_usuario,nombres,apellidos,email,rol',
        'documentos:id_documento,id_tramite,tipo_documento,nombre_archivo,ruta_archivo,tamanio_kb,created_at',
        'estados:id_estado,id_tramite,nombre_estado,descripcion,observaciones,id_usuario_responsable,created_at',
        'estados.responsable:id_usuario,nombres,apellidos,rol',
        'tutor:id_usuario,nombres,apellidos,email,telefono,rol',
    ];

    /** Eager-loads mínimos de los listados (tablero de revisión). */
    private const LISTA_CARGAS = [
        'modalidad:id_modalidad,nombre',
        'estudiante:id_estudiante,ci,nombres,apellidos,registro_universitario,promedio_global',
        'estudiante.user:id_usuario,nombres,apellidos',
        'tutor:id_usuario,nombres,apellidos',
        'documentos:id_documento,id_tramite,tipo_documento,ruta_archivo',
    ];

    /**
     * Módulos del flujo de titulación de la Tesis de Grado y el estado objetivo
     * al que avanza cada uno (espejo del frontend).
     *
     * @var array<string, string>
     */
    private const MODULOS_FLUJO = [
        'perfil_tesis' => 'perfil_aprobado',
        'aprobacion_tema' => 'tema_aprobado',
        'tribunal_revisor' => 'tribunal_asignado',
        'defensa' => 'defensa_aprobada',
        'reporte' => 'reporte_generado',
        'publicacion' => 'titulado',
    ];

    /**
     * Porcentaje de avance por estado (incluye estados heredados) para saber si
     * un módulo avanza el flujo o ya fue superado. Espejo del frontend.
     *
     * @var array<string, int>
     */
    private const PCT_POR_ESTADO = [
        'solicitud_presentada' => 0,
        'pendiente_concejo_universitario' => 5,
        'perfil_rechazado' => 5,
        'perfil_aprobado' => 20,
        'tutor_asignado' => 30,
        'tema_aprobado' => 40,
        'investigacion_en_desarrollo' => 45,
        'documento_final_presentado' => 48,
        'comision_revisora' => 50,
        'insuficiente' => 50,
        'correcciones_90_dias' => 50,
        'suficiente' => 52,
        'solicitud_fecha_defensa' => 55,
        'defensa_programada' => 57,
        'defensa_en_curso' => 62,
        'tribunal_asignado' => 60,
        'defensa_aprobada' => 75,
        'reporte_generado' => 90,
        'aprobado' => 100,
        'titulado' => 100,
        'reprobado' => 75,
        'reprobado_ausencia' => 75,
        'rechazado' => 20,
    ];

    public function __construct(
        private readonly TramiteStateService $stateService,
        private readonly DocumentoAdjuntoService $documentos,
    ) {}

    /**
     * Trámite activo (más reciente) del estudiante autenticado.
     */
    public function activoDe(User $user): ?array
    {
        $estudiante = $user->estudiante;

        if (! $estudiante) {
            return null;
        }

        $tramite = Tramite::query()
            ->select(self::DETALLE_COLUMNAS)
            ->with(self::DETALLE_CARGAS)
            ->where('id_estudiante', $estudiante->id_estudiante)
            ->orderByDesc('created_at')
            ->limit(1)
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
                $this->documentos->guardar(
                    $tramite->id_tramite,
                    $user->id_usuario,
                    $documento['archivo'],
                    $documento['tipo'],
                );
            }

            return $tramite->load('modalidad', 'documentos', 'estados');
        });

        event(new TramiteCreado($tramite, $user));

        return $tramite;
    }

    /**
     * Solicitudes en proceso (estados no terminales) para los roles de gestión.
     *
     * Paginado por defecto y con proyección estricta de columnas; soporta
     * búsqueda (q) sobre estudiante, código, modalidad y estado.
     *
     * @return array{data: array, meta: array<string, int|bool>}
     */
    public function pendientes(?int $perPage = 20, ?string $q = null): array
    {
        return $this->paginar(
            $this->listadoBase($q)
                ->whereNotIn('estado_actual', TramiteStateService::terminales())
                ->orderByDesc('created_at'),
            $this->porPagina($perPage)
        );
    }

    /**
     * Trámites que ya finalizaron su flujo (concluidos), paginado por defecto.
     *
     * @return array{data: array, meta: array<string, int|bool>}
     */
    public function concluidos(?int $perPage = 20, ?string $q = null): array
    {
        return $this->paginar(
            $this->listadoBase($q)
                ->whereIn('estado_actual', TramiteStateService::terminales())
                ->orderByDesc('updated_at'),
            $this->porPagina($perPage)
        );
    }

    /**
     * Consulta base de listados con columnas estrictas, eager-loads mínimos y
     * búsqueda opcional delegada a subconsultas que usan los índices de FKs.
     */
    private function listadoBase(?string $q): Builder
    {
        $query = Tramite::query()
            ->select(self::LISTA_COLUMNAS)
            ->with(self::LISTA_CARGAS);

        if ($q !== null && trim($q) !== '') {
            $like = '%'.mb_strtolower(trim($q)).'%';

            $query->where(function (Builder $sub) use ($like) {
                $sub->orWhereRaw('LOWER(tramites.estado_actual) LIKE ?', [$like])
                    ->orWhereHas('modalidad', fn (Builder $m) => $m->whereRaw('LOWER(modalidades.nombre) LIKE ?', [$like]))
                    ->orWhereHas('estudiante', fn (Builder $e) => $e
                        ->whereRaw('LOWER(estudiantes.nombres) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(estudiantes.apellidos) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(estudiantes.registro_universitario) LIKE ?', [$like]));
            });
        }

        return $query;
    }

    /**
     * Resumen estadístico de aprobados/reprobados por modalidad.
     *
     * Agrega en SQL (GROUP BY sobre índices) en vez de cargar todas las filas
     * a PHP y agruparlas en memoria.
     */
    public function estadisticas(): array
    {
        $porModalidad = Modalidad::query()
            ->leftJoin('tramites', 'tramites.id_modalidad', '=', 'modalidades.id_modalidad')
            ->select(['modalidades.id_modalidad', 'modalidades.nombre'])
            ->selectRaw("SUM(CASE WHEN tramites.estado_actual = 'aprobado' THEN 1 ELSE 0 END) AS aprobados")
            ->selectRaw("SUM(CASE WHEN tramites.estado_actual IN ('rechazado', 'reprobado', 'reprobado_ausencia') THEN 1 ELSE 0 END) AS reprobados")
            ->selectRaw('COUNT(tramites.id_tramite) AS total')
            ->groupBy(['modalidades.id_modalidad', 'modalidades.nombre'])
            ->orderBy('modalidades.nombre')
            ->get()
            ->map(fn (Modalidad $m) => [
                'id_modalidad' => $m->id_modalidad,
                'nombre' => $m->nombre,
                'aprobados' => (int) $m->aprobados,
                'reprobados' => (int) $m->reprobados,
                'total' => (int) $m->total,
            ])
            ->all();

        return [
            'totales' => [
                'aprobados' => array_sum(array_column($porModalidad, 'aprobados')),
                'reprobados' => array_sum(array_column($porModalidad, 'reprobados')),
                'total' => array_sum(array_column($porModalidad, 'total')),
            ],
            'porModalidad' => $porModalidad,
        ];
    }

    /**
     * Detalle completo de un trámite. Un estudiante solo puede ver los suyos.
     */
    public function mostrar(int $id, User $actor): array
    {
        $tramite = Tramite::query()
            ->select(self::DETALLE_COLUMNAS)
            ->with(self::DETALLE_CARGAS)
            ->findOrFail($id);

        if ($actor->rol === Roles::ESTUDIANTE) {
            if (! $tramite->perteneceA($actor)) {
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

        $tramite->load(self::DETALLE_CARGAS);

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

        // La investigación en desarrollo solo comienza con tutor asignado:
        // Kardex/Secretaría debe asignarlo en el paso "Tutor Asignado" antes de avanzar.
        if ($nuevoEstado === 'investigacion_en_desarrollo' && $tramite->id_tutor === null) {
            throw ValidationException::withMessages([
                'id_tutor' => 'Debe asignar un tutor antes de pasar a Investigación en Desarrollo.',
            ]);
        }

        $this->stateService->transicionar(
            $tramite,
            $nuevoEstado,
            (string) $observaciones,
            $actor->id_usuario
        );

        $tramite->refresh()->load(self::DETALLE_CARGAS);

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

        $tramite->load(self::DETALLE_CARGAS);

        event(new TutorAsignado($tramite, $tutor));

        return $this->formatear($tramite);
    }

    /**
     * Guarda un módulo del flujo de titulación de la Tesis de Grado.
     *
     * Valida los datos del formulario del módulo, los registra en
     * `hitos.modulos`, asigna el tutor cuando corresponde (módulo de tema) y
     * avanza el trámite al estado objetivo del módulo a través de la máquina de
     * estados (incluye puentes con el flujo heredado).
     *
     * @return array Trámite formateado (con siguientes_estados y secuencia).
     *
     * @throws ValidationException
     */
    public function guardarModuloFlujo(int $id, string $modulo, array $datos, User $actor): array
    {
        if (! array_key_exists($modulo, self::MODULOS_FLUJO)) {
            throw ValidationException::withMessages([
                'datos' => ['El módulo del flujo no es reconocido.'],
            ]);
        }

        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if ($tramite->modalidad->nombre !== 'Tesis de Grado') {
            throw ValidationException::withMessages([
                'modulo' => ['El flujo de módulos solo aplica a la modalidad Tesis de Grado.'],
            ]);
        }

        if (in_array($tramite->estado_actual, TramiteStateService::terminales(), true)) {
            throw ValidationException::withMessages([
                'datos' => ['El trámite finalizó; no es posible registrar más módulos.'],
            ]);
        }

        // La defensa se registra en DOS pasos: el paso 1 guarda la Resolución de
        // Aprobación Final y la fecha de la defensa; el paso 2 cierra el módulo
        // con la nota final. Sin `paso` se interpreta como guardado completo.
        $paso = (int) ($datos['paso'] ?? 2);

        $this->validarDatosModulo($modulo, $datos, $paso);

        $objetivo = self::MODULOS_FLUJO[$modulo];
        $pctActual = self::PCT_POR_ESTADO[$tramite->estado_actual] ?? 0;
        $pctObjetivo = self::PCT_POR_ESTADO[$objetivo] ?? 0;

        // El paso 1 de la defensa solo registra los datos: el trámite avanza al
        // estado objetivo cuando la nota final se guarda (paso 2).
        $avanza = ! ($modulo === 'defensa' && $paso === 1);
        $transiciono = false;

        // El módulo ya fue superado (pct objetivo ≤ actual) no reclama transición;
        // solo se actualizan los datos guardados del módulo.
        if ($avanza && $pctObjetivo > $pctActual) {
            try {
                $this->stateService->transicionar(
                    $tramite,
                    $objetivo,
                    "Módulo '{$modulo}' del flujo de titulación completado.",
                    $actor->id_usuario
                );
                $transiciono = true;
            } catch (TransicionNoPermitidaException $e) {
                throw ValidationException::withMessages([
                    'datos' => ['No es posible registrar este módulo en el estado actual del trámite.'],
                ]);
            }
        }

        // El control de paso es un mecanismo de guardado, no un dato del módulo.
        unset($datos['paso']);

        $hitos = $tramite->hitos ?? [];
        $hitos['modulos'][$modulo] = $datos;
        $soloHitos = ['hitos' => $hitos];
        if ($avanza) {
            $soloHitos['estado_actual'] = $objetivo;
        }

        // El módulo de tema asigna el tutor designado en el formulario.
        if ($modulo === 'aprobacion_tema' && ! empty($datos['tutor_id'])) {
            $tutor = User::find($datos['tutor_id']);
            if ($tutor === null || $tutor->rol !== Roles::DOCENTE) {
                throw ValidationException::withMessages([
                    'datos' => ['El tutor seleccionado no es un docente válido.'],
                ]);
            }
            $soloHitos['id_tutor'] = $tutor->id_usuario;
        }

        $tramite->update($soloHitos);
        $tramite->refresh()->load(self::DETALLE_CARGAS);

        // Solo se notifica un cambio de estado si realmente hubo transición;
        // el paso 1 de la defensa guarda datos sin avanzar el trámite.
        if ($transiciono) {
            event(new EstadoTramiteCambiado($tramite, $objetivo, null));
        }

        return $this->formatear($tramite);
    }

    /**
     * Reglas de validación de cada módulo del flujo (espejo del frontend).
     *
     * @param  string  $modulo  Identificador del módulo.
     * @param  array  $datos  Datos enviados desde el formulario del módulo.
     * @param  int  $paso  Paso del módulo de defensa (1 = resolución + fecha, 2 = nota).
     *
     * @throws ValidationException
     */
    private function validarDatosModulo(string $modulo, array $datos, int $paso = 2): void
    {
        $val = static fn ($v) => is_string($v) && trim($v) !== '';
        $errores = [];

        switch ($modulo) {
            case 'perfil_tesis':
                foreach (['carta_solicitud', 'certificado_conclusion', 'perfil_tesis'] as $tipo) {
                    $item = $datos['documentos'][$tipo] ?? null;
                    if (empty($item['marcado'])) {
                        $errores[] = "Marque la verificación del documento: {$tipo}.";
                    } elseif (! $val($item['archivo']['nombre'] ?? null)) {
                        $errores[] = "Adjunte el PDF del documento: {$tipo}.";
                    }
                }
                break;

            case 'aprobacion_tema':
                if (! $val($datos['numero_resolucion'] ?? null)) {
                    $errores[] = 'Ingrese el número de Resolución del HCC.';
                }
                if (! $val($datos['fecha_resolucion'] ?? null)) {
                    $errores[] = 'Seleccione la fecha de la Resolución.';
                }
                if (! $val($datos['tema_investigacion'] ?? null) || mb_strlen(trim((string) $datos['tema_investigacion'])) < 5) {
                    $errores[] = 'Ingrese el tema de investigación (mínimo 5 caracteres).';
                }
                if (empty($datos['tutor_id'])) {
                    $errores[] = 'Seleccione el tutor asignado.';
                }
                break;

            case 'tribunal_revisor':
                if (! $val($datos['numero_resolucion'] ?? null)) {
                    $errores[] = 'Ingrese el número de Resolución del HCC.';
                }
                if (! $val($datos['fecha_resolucion'] ?? null)) {
                    $errores[] = 'Seleccione la fecha de la Resolución.';
                }
                foreach (['presidente', 'vocal', 'secretario'] as $rol) {
                    $miembro = collect($datos['tribunal'] ?? [])->first(fn ($t) => ($t['rol'] ?? null) === $rol);
                    if (! $val($miembro['nombre'] ?? null)) {
                        $errores[] = 'Complete el nombre del '.ucfirst($rol).'.';
                    }
                }
                break;

            case 'defensa':
                if (! $val($datos['numero_resolucion'] ?? null)) {
                    $errores[] = 'Ingrese el número de Resolución de Aprobación Final.';
                }
                if (! $val($datos['fecha_defensa'] ?? null)) {
                    $errores[] = 'Seleccione la fecha de la defensa.';
                }
                if ($paso !== 1) {
                    $nota = $datos['nota_final'] ?? null;
                    if ($nota === null || $nota === '') {
                        $errores[] = 'Ingrese la nota final de la defensa.';
                    } elseif (! is_numeric($nota) || (float) $nota < 0 || (float) $nota > 100) {
                        $errores[] = 'La nota debe estar entre 0 y 100.';
                    }
                }
                break;

            case 'reporte':
                break;

            case 'publicacion':
                if (! $val($datos['archivo']['nombre'] ?? null) && ! $val($datos['enlace_publico'] ?? null)) {
                    $errores[] = 'Adjunte el documento final en PDF o ingrese el enlace público.';
                }
                break;

            default:
                $errores[] = 'Módulo no reconocido.';
        }

        if ($errores !== []) {
            throw ValidationException::withMessages(['datos' => $errores]);
        }
    }

    /**
     * Trámites donde el usuario autenticado figura como tutor (panel docente),
     * paginado y con proyección estricta.
     *
     * @return array{data: array, meta: array<string, int|bool>}
     */
    public function tutoriasDe(User $user, ?int $perPage = 20): array
    {
        return $this->paginar(
            Tramite::query()
                ->select(self::DETALLE_COLUMNAS)
                ->with(self::DETALLE_CARGAS)
                ->where('id_tutor', $user->id_usuario)
                ->orderByDesc('created_at'),
            $this->porPagina($perPage),
            fn (Tramite $t) => $this->formatear($t)
        );
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
