<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Tramite;
use App\Models\EstadoTramite;
use App\Models\DocumentoAdjunto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\TramiteStateService;
use Exception;
class TramiteController extends Controller
{
    // Endpoint para que el estudiante vea su trámite activo con su línea de tiempo
    public function tramiteActivo(Request $request, TramiteStateService $stateService)
    {
        $user = $request->user();
        $estudiante = $user->estudiante;

        if (!$estudiante) {
            return response()->json(null);
        }

        $tramite = Tramite::with(['modalidad', 'estudiante.user', 'documentos', 'estados.responsable', 'tutor'])
            ->where('id_estudiante', $estudiante->id_estudiante)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$tramite) {
            return response()->json(null);
        }

        return response()->json($this->formatTramite($tramite, $stateService));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $estudiante = $user->estudiante; // Asumiendo que ya completó su perfil

        if (!$estudiante) {
            return response()->json(['message' => 'Debe completar su perfil de estudiante primero'], 400);
        }

        $validated = $request->validate([
            'id_modalidad' => 'required|exists:modalidades,id_modalidad',
            'documentos' => 'required|array',
            'documentos.*.archivo' => 'required|file|mimes:pdf|max:5120', // Max 5MB
            'documentos.*.tipo' => 'required|string',
        ]);

        return DB::transaction(function () use ($estudiante, $user, $validated) {
            $tramite = Tramite::create([
                'id_estudiante' => $estudiante->id_estudiante,
                'id_modalidad' => $validated['id_modalidad'],
                'estado_actual' => 'solicitud_presentada',
            ]);

            // Registrar el estado inicial en el historial (línea de tiempo)
            EstadoTramite::create([
                'id_tramite' => $tramite->id_tramite,
                'nombre_estado' => 'solicitud_presentada',
                'descripcion' => 'Solicitud presentada por el estudiante',
                'id_usuario_responsable' => $user->id_usuario,
                'observaciones' => 'Documentación inicial enviada.',
            ]);

            // Subir documentos
            foreach ($validated['documentos'] as $doc) {
                $path = $doc['archivo']->store('documentos_tramites', 'public');
                DocumentoAdjunto::create([
                    'id_tramite' => $tramite->id_tramite,
                    'id_usuario_subio' => $user->id_usuario,
                    'tipo_documento' => $doc['tipo'],
                    'nombre_archivo' => $doc['archivo']->getClientOriginalName(),
                    'ruta_archivo' => $path,
                    'tamanio_kb' => round($doc['archivo']->getSize() / 1024, 2),
                ]);
            }

            return response()->json($tramite->load('modalidad', 'documentos', 'estados'), 201);
        });
    }

    // Para Kardex/Dirección: Ver solicitudes en proceso
    public function pendientes(Request $request)
    {
        if (!in_array($request->user()->rol, ['kardex', 'secretaria', 'direccion', 'admin'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return Tramite::with(['estudiante.user', 'modalidad', 'documentos', 'tutor'])
            ->whereNotIn('estado_actual', ['aprobado', 'reprobado', 'rechazado', 'reprobado_ausencia'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Resumen estadístico de trámites aprobados y reprobados por modalidad
    public function estadisticas(Request $request)
    {
        if (!in_array($request->user()->rol, ['admin', 'kardex', 'secretaria', 'direccion'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $aprobadosEstados = ['aprobado'];
        $reprobadosEstados = ['rechazado', 'reprobado', 'reprobado_ausencia'];

        $porModalidad = Tramite::with('modalidad')
            ->get()
            ->groupBy('id_modalidad')
            ->map(function ($tramites, $idModalidad) use ($aprobadosEstados, $reprobadosEstados) {
                $primero = $tramites->first();

                return [
                    'id_modalidad' => $idModalidad,
                    'nombre' => $primero->modalidad->nombre ?? 'Sin modalidad',
                    'aprobados' => $tramites->whereIn('estado_actual', $aprobadosEstados)->count(),
                    'reprobados' => $tramites->whereIn('estado_actual', $reprobadosEstados)->count(),
                    'total' => $tramites->count(),
                ];
            })
            ->sortBy('nombre')
            ->values();

        $totales = [
            'aprobados' => $porModalidad->sum('aprobados'),
            'reprobados' => $porModalidad->sum('reprobados'),
            'total' => $porModalidad->sum('total'),
        ];

        return response()->json([
            'totales' => $totales,
            'porModalidad' => $porModalidad,
        ]);
    }

    // Para Kardex/Dirección: Aprobar o Rechazar documentación inicial
    public function revisar(Request $request, $id, TramiteStateService $stateService)
    {
        if (!in_array($request->user()->rol, ['kardex', 'secretaria', 'direccion', 'admin'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'accion' => 'required|in:aprobar,rechazar',
            'observaciones' => 'nullable|string'
        ]);

        $tramite = Tramite::with('modalidad')->findOrFail($id);

        if ($tramite->estado_actual !== 'solicitud_presentada') {
            return response()->json(['message' => 'Este trámite ya fue revisado o avanza por otra vía'], 400);
        }

        try {
            if ($validated['accion'] === 'aprobar') {
                $siguientes = $stateService->getSiguientesEstados($tramite);
                $nuevoEstado = $siguientes[0] ?? null;

                if (!$nuevoEstado) {
                    return response()->json(['message' => 'No se pudo determinar el siguiente estado'], 400);
                }

                $historial = $stateService->transicionar(
                    $tramite,
                    $nuevoEstado,
                    $validated['observaciones'] ?? 'Documentación inicial aprobada.',
                    $request->user()->id_usuario
                );
            } else {
                $historial = $stateService->transicionar(
                    $tramite,
                    'rechazado',
                    $validated['observaciones'] ?? 'Solicitud rechazada.',
                    $request->user()->id_usuario
                );
            }

            return response()->json($this->formatTramite($tramite->load('modalidad', 'estudiante.user', 'documentos', 'estados.responsable', 'tutor'), $stateService));
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function transicionar(Request $request, $id, TramiteStateService $stateService)
    {
        // Solo roles autorizados pueden cambiar estados
        if (!in_array($request->user()->rol, ['kardex', 'secretaria', 'direccion', 'admin', 'concejo'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'nuevo_estado' => 'required|string',
            'observaciones' => 'nullable|string',
        ]);

        $tramite = Tramite::with('modalidad')->findOrFail($id);

        try {
            $historial = $stateService->transicionar(
                $tramite,
                $validated['nuevo_estado'],
                $validated['observaciones'],
                $request->user()->id_usuario
            );
            return response()->json($this->formatTramite($tramite->refresh()->load('modalidad', 'estudiante.user', 'documentos', 'estados.responsable', 'tutor'), $stateService), 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    // Asignar (o cambiar) el docente tutor de un trámite
    public function asignarTutor(Request $request, $id, TramiteStateService $stateService)
    {
        if (!in_array($request->user()->rol, ['kardex', 'secretaria', 'direccion', 'admin'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'id_tutor' => 'required|exists:users,id_usuario',
        ]);

        $tutor = User::findOrFail($validated['id_tutor']);
        if ($tutor->rol !== 'docente') {
            return response()->json(['message' => 'El tutor debe ser un usuario con rol docente'], 422);
        }

        $tramite = Tramite::with('modalidad')->findOrFail($id);
        $tramite->update(['id_tutor' => $tutor->id_usuario]);

        return response()->json($this->formatTramite($tramite->load('modalidad', 'estudiante.user', 'documentos', 'estados.responsable', 'tutor'), $stateService));
    }

    // Panel del docente tutor: trámites donde es tutor
    public function tutorias(Request $request, TramiteStateService $stateService)
    {
        if ($request->user()->rol !== 'docente') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $tramites = Tramite::with(['modalidad', 'estudiante.user', 'documentos', 'estados.responsable', 'tutor'])
            ->where('id_tutor', $request->user()->id_usuario)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tramites->map(fn ($t) => $this->formatTramite($t, $stateService)));
    }

    public function show(Request $request, $id, TramiteStateService $stateService)
    {
        // Ver detalle completo del trámite con su historial
        $tramite = Tramite::with(['modalidad', 'estudiante.user', 'documentos', 'estados.responsable', 'tutor'])
            ->findOrFail($id);

        // Los estudiantes solo pueden ver sus propios trámites
        if ($request->user()->rol === 'estudiante') {
            $user = $request->user();
            if (!$user->estudiante || $tramite->id_estudiante !== $user->estudiante->id_estudiante) {
                return response()->json(['message' => 'No autorizado'], 403);
            }
        }

        return response()->json($this->formatTramite($tramite, $stateService));
    }

    private function formatTramite(Tramite $tramite, TramiteStateService $stateService): array
    {
        $data = $tramite->toArray();
        $data['siguientes_estados'] = $stateService->getSiguientesEstados($tramite);
        $data['secuencia'] = $stateService->getSecuenciaEstados($tramite->modalidad->nombre);
        return $data;
    }
}