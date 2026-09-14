<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        return User::with('estudiante')->where('activo', true)->get();
    }

    public function docentes(Request $request)
    {
        if (!in_array($request->user()->rol, ['kardex', 'secretaria', 'direccion', 'admin'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        return User::where('rol', 'docente')->where('activo', true)->orderBy('nombres')->get();
    }

    public function store(Request $request)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'ci' => 'required|unique:users',
            'nombres' => 'required',
            'apellidos' => 'required',
            'email' => 'required|email|unique:users',
            'telefono' => 'nullable|string',
            'rol' => 'required|in:admin,estudiante,docente,kardex,secretaria,direccion,concejo',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json($user, 201);
    }

    public function update(Request $request, $id)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'ci' => 'required|unique:users,ci,' . $user->id_usuario . ',id_usuario',
            'nombres' => 'required',
            'apellidos' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id_usuario . ',id_usuario',
            'telefono' => 'nullable|string',
            'rol' => 'required|in:admin,estudiante,docente,kardex,secretaria,direccion,concejo',
            'password' => 'nullable|min:6',
        ]);

        $data = collect($validated)->except('password')->all();
        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        if ($request->input('estudiante') && $request->user()->rol === 'admin' && $validated['rol'] === 'estudiante') {
            $estudianteData = $request->validate([
                'estudiante.codigo_universitario' => 'nullable|string',
                'estudiante.plan_estudios' => 'nullable|string',
                'estudiante.fecha_conclusion_plan' => 'nullable|date',
                'estudiante.promedio_global' => 'nullable|numeric|between:0,100',
                'estudiante.estado' => 'nullable|in:activo,inactivo',
            ]);

            $perfil = collect($estudianteData['estudiante'] ?? [])
                ->map(fn ($value) => $value === '' || $value === null ? null : $value)
                ->all();

            $user->estudiante()->updateOrCreate([], $perfil);
        }

        return $user->load('estudiante');
    }

    public function destroy(Request $request, $id)
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $user = User::findOrFail($id);
        $user->update(['activo' => false]); // Baja lógica

        return response()->json(['message' => 'Usuario dado de baja']);
    }
}
