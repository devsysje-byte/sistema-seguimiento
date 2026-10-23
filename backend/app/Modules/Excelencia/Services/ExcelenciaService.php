<?php

namespace App\Modules\Excelencia\Services;

use App\Models\Estudiante;
use App\Models\Modalidad;
use App\Models\Tramite;
use App\Models\User;
use App\Modules\Tramites\Events\EstadoTramiteCambiado;
use App\Modules\Tramites\Exceptions\TransicionNoPermitidaException;
use App\Modules\Tramites\Services\TramiteService;
use App\Modules\Tramites\Services\TramiteStateService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Flujo de titulación de la modalidad Excelencia Académica.
 *
 * El estudiante opta a la modalidad por su promedio sobresaliente: presenta su
 * solicitud declarando el promedio general y KARDEX lo verifica (el valor
 * verificado puede corregirse), registra la Resolución del HCC que lo habilita
 * y, por último, emite el acta de titulación.
 *
 * Son TRES módulos, gestionados uno a uno desde el panel de KARDEX:
 *
 *  1. `verificacion_promedio` — comprueba el promedio general del estudiante
 *     contra el mínimo reglamentario (`excelencia.nota_minima`, 80 por
 *     defecto). Con el promedio en o por encima del mínimo el trámite avanza a
 *     `promedio_verificado`; por debajo, queda en `rechazado` (estado
 *     terminal) y el estudiante no habilita la modalidad. El promedio
 *     verificado se consolida en el perfil del estudiante
 *     (`estudiantes.promedio_global`).
 *  2. `resolucion_hcc` — registra el número y la fecha de la Resolución del
 *     HCC que habilita al estudiante; el trámite avanza a
 *     `resolucion_hcc_registrada`.
 *  3. `reporte` — genera el acta de Excelencia Académica y titula al
 *     estudiante (`titulado`).
 *
 * El detalle de cada módulo (promedio declarado y verificado, resolución con
 * su fecha) se conserva en `hitos.modulos`, que es lo que consultan el
 * seguimiento del estudiante y el acta de titulación.
 */
class ExcelenciaService
{
    /**
     * Nombre de la modalidad de titulación por Excelencia Académica.
     *
     * Es la clave con la que se registra su flujo en la máquina de estados y
     * la que usan los servicios para distinguirla del resto de modalidades.
     */
    public const MODALIDAD = 'Excelencia Académica';

    /**
     * Módulo de verificación del promedio general del estudiante.
     */
    public const MODULO_VERIFICACION = 'verificacion_promedio';

    /**
     * Módulo de registro de la Resolución del HCC.
     */
    public const MODULO_RESOLUCION = 'resolucion_hcc';

    /**
     * Módulo del reporte final (acta de titulación).
     */
    public const MODULO_REPORTE = 'reporte';

    /**
     * Estado objetivo de cada módulo cuando se registra satisfactoriamente.
     *
     * `verificacion_promedio` no tiene estado fijo: con el promedio en o por
     * encima del mínimo avanza a `promedio_verificado` y por debajo queda en
     * `rechazado` (ver `destinoVerificacion()`).
     *
     * @var array<string, string>
     */
    private const MODULOS = [
        self::MODULO_VERIFICACION => 'promedio_verificado',
        self::MODULO_RESOLUCION => 'resolucion_hcc_registrada',
        self::MODULO_REPORTE => 'titulado',
    ];

    /**
     * Estados terminales que permiten volver a solicitar la modalidad.
     *
     * Un estudiante rechazado por promedio puede optar de nuevo por otra
     * modalidad (o por esta misma si mejora su promedio).
     *
     * @var array<int, string>
     */
    private const TERMINALES = ['rechazado', 'titulado'];

    public function __construct(
        private readonly TramiteService $tramites,
        private readonly TramiteStateService $stateService,
    ) {}

    /**
     * Crea la solicitud de Excelencia Académica del estudiante.
     *
     * El promedio declarado por el estudiante queda registrado en
     * `hitos.modulos.verificacion_promedio.promedio_declarado`: KARDEX lo
     * verifica después y puede corregirlo (el valor verificado se guarda en
     * `promedio`).
     *
     * @param  array<string, mixed>  $validated  Datos validados de la solicitud.
     *
     * @throws \DomainException Si la modalidad no está configurada, el
     *                          estudiante no tiene perfil o ya tiene una
     *                          solicitud de Excelencia Académica en curso.
     */
    public function crearSolicitud(array $validated, User $user): Tramite
    {
        $modalidad = Modalidad::query()
            ->where('nombre', self::MODALIDAD)
            ->where('activo', true)
            ->first();

        if ($modalidad === null) {
            throw new \DomainException('La modalidad '.self::MODALIDAD.' no está configurada en el sistema.');
        }

        $estudiante = $user->estudiante;

        if ($estudiante === null) {
            throw new \DomainException('Debe completar su perfil de estudiante primero.');
        }

        $enCurso = Tramite::query()
            ->where('id_estudiante', $estudiante->id_estudiante)
            ->where('id_modalidad', $modalidad->id_modalidad)
            ->whereNotIn('estado_actual', self::TERMINALES)
            ->exists();

        if ($enCurso) {
            throw new \DomainException('Ya tienes una solicitud de Excelencia Académica en curso. Avanza desde el seguimiento de tu trámite.');
        }

        $tramite = $this->tramites->crear(
            [
                'id_modalidad' => (int) $modalidad->id_modalidad,
                'documentos' => [],
            ],
            $user
        );

        $this->registrarSolicitud($tramite, (float) $validated['promedio']);

        return $tramite;
    }

    /**
     * Actualiza el promedio declarado por el estudiante antes de la
     * verificación de KARDEX.
     *
     * Sirve tanto para declarar la nota cuando KARDEX asignó la modalidad
     * (trámite sin promedio declarado) como para corregirla mientras siga
     * pendiente de verificación. Una vez verificado, el valor queda cerrado.
     *
     * @param  array<string, mixed>  $validated  Datos validados de la solicitud.
     * @return array Trámite formateado.
     *
     * @throws ValidationException
     */
    public function actualizarPromedioDeclarado(int $id, array $validated, User $user): array
    {
        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if ($tramite->modalidad->nombre !== self::MODALIDAD) {
            throw ValidationException::withMessages([
                'modulo' => ['El flujo de Excelencia Académica solo aplica a la modalidad Excelencia Académica.'],
            ]);
        }

        $estudiante = $user->estudiante;

        if ($estudiante === null || (int) $tramite->id_estudiante !== (int) $estudiante->id_estudiante) {
            throw ValidationException::withMessages([
                'datos' => ['Solo el estudiante titular del trámite puede declarar su promedio.'],
            ]);
        }

        if ($tramite->estado_actual !== 'solicitud_presentada') {
            throw ValidationException::withMessages([
                'datos' => ['El promedio ya fue verificado por KARDEX; no es posible modificarlo.'],
            ]);
        }

        $hitos = $tramite->hitos ?? [];
        $modulos = $hitos['modulos'] ?? [];
        $verificacion = $modulos[self::MODULO_VERIFICACION] ?? [];
        $verificacion['promedio_declarado'] = (float) $validated['promedio'];
        $verificacion['nota_minima'] = self::notaMinima();
        $modulos[self::MODULO_VERIFICACION] = $verificacion;
        $hitos['modulos'] = $modulos;

        $tramite->update(['hitos' => $hitos]);

        return $this->formatear($tramite);
    }

    /**
     * Registra un módulo del flujo de Excelencia Académica y avanza el estado
     * del trámite al que corresponde.
     *
     * @param  string  $modulo  Identificador del módulo (ver `self::MODULOS`).
     * @param  array<string, mixed>  $datos  Datos del formulario del módulo.
     * @return array Trámite formateado (con siguientes estados y secuencia).
     *
     * @throws ValidationException
     */
    public function guardarModulo(int $id, string $modulo, array $datos, User $actor): array
    {
        // La transición, el registro en `hitos` y la consolidación del promedio
        // verificado en el perfil del estudiante van juntos: un fallo a mitad
        // del guardado no debe dejar el trámite en un estado sin su registro.
        return DB::transaction(fn () => $this->resolverModulo($id, $modulo, $datos, $actor));
    }

    /**
     * Cuerpo del guardado del módulo, ejecutado dentro de una transacción.
     *
     * @param  array<string, mixed>  $datos
     *
     * @throws ValidationException
     */
    private function resolverModulo(int $id, string $modulo, array $datos, User $actor): array
    {
        if (! array_key_exists($modulo, self::MODULOS)) {
            throw ValidationException::withMessages([
                'datos' => ['El módulo del flujo de Excelencia Académica no es reconocido.'],
            ]);
        }

        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if ($tramite->modalidad->nombre !== self::MODALIDAD) {
            throw ValidationException::withMessages([
                'modulo' => ['El flujo de Excelencia Académica solo aplica a la modalidad Excelencia Académica.'],
            ]);
        }

        if ($this->stateService->esTerminal($tramite)) {
            throw ValidationException::withMessages([
                'datos' => ['El trámite finalizó; no es posible registrar más módulos.'],
            ]);
        }

        $destino = match ($modulo) {
            self::MODULO_VERIFICACION => $this->destinoVerificacion($datos),
            self::MODULO_RESOLUCION => $this->destinoResolucion($datos),
            self::MODULO_REPORTE => $this->destinoReporte($tramite),
        };

        $this->transicionar($tramite, $destino['estado'], $destino['observacion'], $actor);

        // `accion` es un control del formulario, no un dato del módulo.
        unset($datos['accion']);

        $this->persistirModulo($tramite, $modulo, $datos, $destino);

        event(new EstadoTramiteCambiado($tramite, $destino['estado'], $destino['observacion']));

        return $this->formatear($tramite);
    }

    /**
     * Módulo 1 — verificación del promedio general.
     *
     * El promedio lo digita KARDEX (por defecto, el declarado por el
     * estudiante) y puede corregirlo: es la nota que se consolida en el perfil
     * del estudiante. Si queda por debajo del mínimo reglamentario, la
     * solicitud se RECHAZA y el flujo de titulación termina aquí.
     *
     * @param  array<string, mixed>  $datos
     * @return array{estado:string, observacion:string, promedio:?float, aprobado:bool}
     *
     * @throws ValidationException
     */
    private function destinoVerificacion(array $datos): array
    {
        $minimo = self::notaMinima();
        [$rangoMin, $rangoMax] = config('excelencia.rango_nota', [0, 100]);

        $valor = $datos['promedio'] ?? null;

        if ($valor === null || $valor === '' || ! is_numeric($valor)) {
            throw ValidationException::withMessages([
                'datos' => ['Ingrese el promedio general del estudiante.'],
            ]);
        }

        $nota = (float) $valor;

        if ($nota < $rangoMin || $nota > $rangoMax) {
            throw ValidationException::withMessages([
                'datos' => ["El promedio debe estar entre {$rangoMin} y {$rangoMax}."],
            ]);
        }

        $aprobado = $nota >= $minimo;

        return [
            'estado' => $aprobado ? 'promedio_verificado' : 'rechazado',
            'promedio' => $nota,
            'aprobado' => $aprobado,
            'observacion' => $aprobado
                ? "Promedio verificado: {$nota} (mínimo de {$minimo}). "
                    .'El estudiante habilita su titulación por Excelencia Académica.'
                : "Promedio verificado: {$nota}, inferior al mínimo de {$minimo}. "
                    .'La solicitud no procede y el estudiante no habilita la modalidad.',
        ];
    }

    /**
     * Módulo 2 — registro de la Resolución del HCC que habilita al estudiante.
     *
     * @param  array<string, mixed>  $datos
     * @return array{estado:string, observacion:string}
     *
     * @throws ValidationException
     */
    private function destinoResolucion(array $datos): array
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

        return [
            'estado' => 'resolucion_hcc_registrada',
            'observacion' => "Resolución N.º {$resolucion} del HCC registrada para la Excelencia Académica.",
        ];
    }

    /**
     * Módulo 3 — reporte final: el acta de Excelencia Académica titula al
     * estudiante.
     *
     * Exige que el promedio haya sido verificado: sin la nota registrada no
     * hay nada que titular.
     *
     * @return array{estado:string, observacion:string}
     *
     * @throws ValidationException
     */
    private function destinoReporte(Tramite $tramite): array
    {
        $verificacion = $tramite->hitos['modulos'][self::MODULO_VERIFICACION] ?? [];

        if (! isset($verificacion['promedio'])) {
            throw ValidationException::withMessages([
                'datos' => ['Registre primero la verificación del promedio del estudiante.'],
            ]);
        }

        return [
            'estado' => 'titulado',
            'observacion' => 'Acta de Excelencia Académica generada. Estudiante titulado.',
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
     * Guarda los datos del formulario en `hitos.modulos`, igual que el resto de
     * flujos, para que el seguimiento del estudiante y el acta de titulación
     * muestren lo que registró KARDEX.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<string, mixed>  $destino
     */
    private function persistirModulo(Tramite $tramite, string $modulo, array $datos, array $destino): void
    {
        $hitos = $tramite->fresh()?->hitos ?? [];

        $modulos = $hitos['modulos'] ?? [];
        $guardado = array_replace($modulos[$modulo] ?? [], $datos);

        if ($modulo === self::MODULO_VERIFICACION) {
            $guardado['promedio'] = $destino['promedio'];
            $guardado['nota_minima'] = self::notaMinima();
            $guardado['verificado'] = true;
            $guardado['fecha_verificacion'] = Carbon::today()->toDateString();
        }

        $modulos[$modulo] = $guardado;
        $hitos['modulos'] = $modulos;
        $hitos[$destino['estado']] = Carbon::today()->toDateString();

        $tramite->update(['hitos' => $hitos]);

        // El promedio verificado se consolida en el perfil del estudiante.
        if ($modulo === self::MODULO_VERIFICACION && $destino['promedio'] !== null) {
            Estudiante::query()
                ->whereKey($tramite->id_estudiante)
                ->update(['promedio_global' => $destino['promedio']]);
        }
    }

    /**
     * Registra la solicitud del estudiante: la fecha de presentación y el
     * promedio declarado, que KARDEX verifica (y puede corregir) después.
     */
    private function registrarSolicitud(Tramite $tramite, float $promedio): void
    {
        $hitos = $tramite->hitos ?? [];

        $hitos['solicitud_presentada'] = Carbon::today()->toDateString();

        $modulos = $hitos['modulos'] ?? [];
        $verificacion = $modulos[self::MODULO_VERIFICACION] ?? [];
        $verificacion['promedio_declarado'] = $promedio;
        $verificacion['nota_minima'] = self::notaMinima();
        $modulos[self::MODULO_VERIFICACION] = $verificacion;
        $hitos['modulos'] = $modulos;

        $tramite->update(['hitos' => $hitos]);
    }

    /**
     * Trámite formateado con el estudiante y la modalidad cargados, listo para
     * la respuesta de la API.
     */
    private function formatear(Tramite $tramite): array
    {
        $tramite->refresh()->load(['modalidad', 'estudiante']);

        return $this->tramites->formatear($tramite);
    }

    /**
     * Promedio mínimo para optar por la modalidad (`config/excelencia.php`).
     */
    public static function notaMinima(): int
    {
        return (int) config('excelencia.nota_minima', 80);
    }

    /** Indica si un valor de formulario trae contenido. */
    private function informado(mixed $valor): bool
    {
        return is_string($valor) ? trim($valor) !== '' : $valor !== null;
    }
}
