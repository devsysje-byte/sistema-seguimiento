<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EstudianteController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->rol !== 'estudiante') {
            return response()->json(['message' => 'Solo estudiantes pueden registrar este perfil'], 403);
        }

        $validated = $request->validate([
            'codigo_universitario' => [
                'required',
                Rule::unique('estudiantes', 'codigo_universitario')
                    ->ignore($user->estudiante?->id_estudiante, 'id_estudiante'),
            ],
            'plan_estudios' => 'required|string',
            'fecha_conclusion_plan' => 'required|date',
            'promedio_global' => 'required|numeric|between:0,100',
        ]);

        $estudiante = $user->estudiante()->updateOrCreate(
            ['id_usuario' => $user->id_usuario],
            $validated
        );

        return response()->json($estudiante->fresh());
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $estudiante = Estudiante::where('id_usuario', $user->id_usuario)->first();
        return response()->json($estudiante);
    }
}
