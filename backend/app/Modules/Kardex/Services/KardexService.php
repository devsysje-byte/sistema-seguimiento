<?php

namespace App\Modules\Kardex\Services;

use App\Models\Estudiante;
use App\Models\Modalidad;
use App\Models\Tramite;
use App\Models\User;
use App\Modules\Tramites\Services\TramiteService;
use App\Support\CredencialesEstudiante;
use App\Support\Roles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Casos de uso del Dashboard de KARDEX.
 *
 * Orquesta la búsqueda de postulantes por CI o registro universitario, la
 * asignación de la modalidad de titulación con generación automática de
 * credenciales (usuario + contraseña temporal, entregadas una única vez) y la
 * consulta del flujo actual del postulante. La autorización por rol se resuelve
 * en las rutas vía el middleware `role`; aquí solo se asume permiso verificado.
 */
class KardexService
{
    /** Eager-loads del detalle de trámite para la consulta del postulante. */
    private const DETALLE_CARGAS = [
        'modalidad:id_modalidad,nombre,descripcion,requisitos_minimos',
        'estudiante:id_estudiante,ci,nombres,apellidos,registro_universitario,email,telefono,promedio_global',
        'estudiante.user:id_usuario,nombres,apellidos,email,rol',
        'documentos:id_documento,id_tramite,tipo_documento,nombre_archivo,ruta_archivo,created_at',
        'estados:id_estado,id_tramite,nombre_estado,descripcion,observaciones,id_usuario_responsable,created_at',
        'estados.responsable:id_usuario,nombres,apellidos,rol',
        'tutor:id_usuario,nombres,apellidos,email,rol',
    ];

    public function __construct(
        private readonly TramiteService $tramites,
    ) {}

    /**
     * Busca un postulante por CI o registro universitario.
     *
     * @return array{postulante: array<string, mixed>}
     *
     * @throws ValidationException Si no existe un perfil de estudiante con el
     *                             identificador indicado.
     */
    public function buscarPostulante(string $identificador): array
    {
        return [
            'postulante' => $this->postulante($identificador, true),
        ];
    }

    /**
     * Asigna la modalidad de titulación al postulante.
     *
     * Crea la cuenta de acceso automáticamente si el postulante aún no tiene
     * una (usuario + contraseña temporal) y registra el trámite en su estado
     * inicial. Los pasos se ejecutan en una sola transacción: si falla la
     * asignación no queda una cuenta huérfana.
     *
     * @param  array<string, mixed>  $validated
     * @return array{postulante: array<string, mixed>, credenciales: array<string, mixed>, tramite: array<string, mixed>}
     *
     * @throws ValidationException
     * @throws \DomainException
     */
    public function asignarModalidad(array $validated, User $actor): array
    {
        $identificador = (string) $validated['identificador'];
        $idModalidad = (int) $validated['id_modalidad'];

        $estudiante = Estudiante::query()
            ->where('ci', $identificador)
            ->orWhere('registro_universitario', $identificador)
            ->first();

        if ($estudiante === null) {
            throw ValidationException::withMessages([
                'identificador' => 'No se encontró un postulante con ese CI o registro universitario.',
            ]);
        }

        $modalidad = Modalidad::query()
            ->whereKey($idModalidad)
            ->where('activo', true)
            ->first();

        if ($modalidad === null) {
            throw ValidationException::withMessages([
                'id_modalidad' => 'La modalidad seleccionada no está disponible.',
            ]);
        }

        $this->asegurarModalidadDisponible($estudiante, $idModalidad);

        [$credenciales, $usuario] = DB::transaction(function () use ($estudiante) {
            if ($estudiante->user !== null) {
                return [
                    [
                        'generadas' => false,
                        'ya_existia' => true,
                        'username' => $estudiante->user->username,
                        'password' => null,
                    ],
                    $estudiante->user,
                ];
            }

            $username = CredencialesEstudiante::usernameUnico($estudiante);
            $password = CredencialesEstudiante::passwordDe($estudiante);

            $usuario = User::create([
                'ci' => $estudiante->ci,
                'nombres' => $estudiante->nombres,
                'apellidos' => $estudiante->apellidos,
                'email' => $estudiante->email,
                'telefono' => $estudiante->telefono,
                'username' => $username,
                'password' => Hash::make($password),
                'rol' => Roles::ESTUDIANTE,
                'activo' => true,
                'estudiante_id' => $estudiante->id_estudiante,
            ]);

            return [
                [
                    'generadas' => true,
                    'ya_existia' => false,
                    'username' => $username,
                    'password' => $password,
                ],
                $usuario,
            ];
        });

        $tramite = $this->tramites->crear([
            'id_modalidad' => $idModalidad,
            'documentos' => [],
        ], $usuario);

        return [
            'postulante' => $this->postulante($estudiante->ci, false),
            'credenciales' => $credenciales,
            'tramite' => $this->tramites->formatear($tramite),
        ];
    }

    /**
     * Consulta el flujo actual del postulante: datos del perfil y su trámite
     * más reciente (o null si aún no tiene uno asignado).
     *
     * @return array{postulante: array<string, mixed>, tramite: array<string, mixed>|null}
     *
     * @throws ValidationException
     */
    public function consultar(string $identificador): array
    {
        $estudiante = Estudiante::query()
            ->where('ci', $identificador)
            ->orWhere('registro_universitario', $identificador)
            ->first();

        if ($estudiante === null) {
            throw ValidationException::withMessages([
                'identificador' => 'No se encontró un postulante con ese CI o registro universitario.',
            ]);
        }

        $tramite = Tramite::query()
            ->select([
                'id_tramite', 'id_estudiante', 'id_modalidad', 'id_tutor',
                'estado_actual', 'observaciones', 'hitos', 'created_at', 'updated_at',
            ])
            ->with(self::DETALLE_CARGAS)
            ->where('id_estudiante', $estudiante->id_estudiante)
            ->orderByDesc('created_at')
            ->limit(1)
            ->first();

        return [
            'postulante' => $this->postulante($estudiante->ci, false),
            'tramite' => $tramite !== null ? $this->tramites->formatear($tramite) : null,
        ];
    }

    /**
     * Resumen estadístico del Dashboard de KARDEX.
     *
     * Agrega en SQL los totales por modalidad, estudiantes y tutores para la
     * vista principal del dashboard (panel general).
     *
     * @return array{modalidades: array<int, array<string, mixed>>, estudiantes: array<string, int>, tutores: array<string, int>, totales: array<string, int>}
     */
    public function resumen(): array
    {
        $porModalidad = Modalidad::query()
            ->withCount('tramites')
            ->withCount(['tramites as aprobados' => fn ($q) => $q->where('estado_actual', 'aprobado')])
            ->withCount([
                'tramites as en_curso' => fn ($q) => $q->whereNotIn(
                    'estado_actual',
                    ['aprobado', 'reprobado', 'reprobado_ausencia', 'rechazado'],
                ),
            ])
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (Modalidad $modalidad) => [
                'id_modalidad' => $modalidad->id_modalidad,
                'nombre' => $modalidad->nombre,
                'total' => (int) $modalidad->tramites_count,
                'aprobados' => (int) $modalidad->aprobados,
                'en_curso' => (int) $modalidad->en_curso,
            ])
            ->all();

        $enCursoEstados = ['aprobado', 'reprobado', 'reprobado_ausencia', 'rechazado'];

        return [
            'modalidades' => $porModalidad,
            'estudiantes' => [
                'total' => (int) Estudiante::query()->count(),
                'con_cuenta' => (int) User::query()->where('rol', Roles::ESTUDIANTE)->count(),
                'con_tramite' => (int) Tramite::query()->distinct()->count('id_estudiante'),
            ],
            'tutores' => [
                'total' => (int) User::query()->where('rol', Roles::DOCENTE)->count(),
                'activos' => (int) Tramite::query()->whereNotNull('id_tutor')->distinct()->count('id_tutor'),
            ],
            'totales' => [
                'tramites' => (int) Tramite::query()->count(),
                'aprobados' => (int) Tramite::query()->where('estado_actual', 'aprobado')->count(),
                'en_curso' => (int) Tramite::query()->whereNotIn('estado_actual', $enCursoEstados)->count(),
            ],
        ];
    }

    /**
     * Perfil del postulante listo para la vista:
     * incluye si ya posee cuenta de acceso y su username (si existe).
     *
     * @return array<string, mixed>
     */
    private function postulante(string $identificador, bool $conUsuario): array
    {
        $estudiante = Estudiante::query()
            ->when($conUsuario, fn ($q) => $q->with('user:id_usuario,username,nombres,apellidos,rol'))
            ->where('ci', $identificador)
            ->orWhere('registro_universitario', $identificador)
            ->firstOrFail();

        return [
            'id_estudiante' => $estudiante->id_estudiante,
            'ci' => $estudiante->ci,
            'nombres' => $estudiante->nombres,
            'apellidos' => $estudiante->apellidos,
            'registro_universitario' => $estudiante->registro_universitario,
            'fecha_nacimiento' => $estudiante->fecha_nacimiento?->format('Y-m-d'),
            'email' => $estudiante->email,
            'telefono' => $estudiante->telefono,
            'promedio_global' => $estudiante->promedio_global !== null
                ? round((float) $estudiante->promedio_global, 2)
                : null,
            'tiene_cuenta' => $estudiante->user !== null,
            'username' => $estudiante->user?->username,
        ];
    }

    /**
     * Evita asignar una modalidad repetida: no se permite un trámite activo o
     * ya aprobado en la misma modalidad.
     */
    private function asegurarModalidadDisponible(Estudiante $estudiante, int $idModalidad): void
    {
        $existeActivo = Tramite::query()
            ->where('id_estudiante', $estudiante->id_estudiante)
            ->where('id_modalidad', $idModalidad)
            ->whereNotIn('estado_actual', ['aprobado'])
            ->exists();

        if ($existeActivo) {
            throw ValidationException::withMessages([
                'id_modalidad' => 'El postulante ya tiene un trámite en curso para esta modalidad.',
            ]);
        }

        $aprobada = Tramite::query()
            ->where('id_estudiante', $estudiante->id_estudiante)
            ->where('id_modalidad', $idModalidad)
            ->where('estado_actual', 'aprobado')
            ->exists();

        if ($aprobada) {
            throw ValidationException::withMessages([
                'id_modalidad' => 'El postulante ya aprobó su titulación en esta modalidad.',
            ]);
        }
    }
}