<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Tramite;
use App\Models\DocumentoAdjunto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TramiteController extends Controller
{
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

        $tramite = Tramite::create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_modalidad' => $validated['id_modalidad'],
            'estado_actual' => 'pendiente_revision'
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
                'tamanio_kb' => round($doc['archivo']->getSize() / 1024, 2)
            ]);
        }

        return response()->json($tramite->load('modalidad', 'documentos'), 201);
    }

    // Para Kardex/Dirección: Ver solicitudes pendientes
    public function pendientes(Request $request)
    {
        if (!in_array($request->user()->rol, ['kardex', 'secretaria', 'direccion', 'admin'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return Tramite::with(['estudiante.user', 'modalidad', 'documentos'])
            ->where('estado_actual', 'pendiente_revision')
            ->get();
    }

    // Para Kardex/Dirección: Aprobar o Rechazar documentación inicial
    public function revisar(Request $request, $id)
    {
        if (!in_array($request->user()->rol, ['kardex', 'secretaria', 'direccion', 'admin'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'accion' => 'required|in:aprobar,rechazar',
            'observaciones' => 'nullable|string'
        ]);

        $tramite = Tramite::findOrFail($id);
        $tramite->estado_actual = $validated['accion'] === 'aprobar' ? 'documentacion_ok' : 'rechazado';
        $tramite->observaciones = $validated['observaciones'];
        $tramite->save();

        return response()->json($tramite);
    }
}
