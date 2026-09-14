<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    /**
     * Avisos recientes del usuario autenticado más el contador de no leídas.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $notificaciones = Notificacion::where('id_usuario', $user->id_usuario)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $noLeidas = Notificacion::where('id_usuario', $user->id_usuario)
            ->where('leida', false)
            ->count();

        return response()->json([
            'no_leidas' => $noLeidas,
            'notificaciones' => $notificaciones,
        ]);
    }

    /**
     * Marcar una notificación como leída.
     */
    public function marcarLeida(Request $request, $id)
    {
        $notificacion = Notificacion::where('id_usuario', $request->user()->id_usuario)
            ->findOrFail($id);

        $notificacion->update(['leida' => true]);

        return response()->json($notificacion);
    }

    /**
     * Marcar todas las notificaciones del usuario como leídas.
     */
    public function marcarTodasLeidas(Request $request)
    {
        Notificacion::where('id_usuario', $request->user()->id_usuario)
            ->where('leida', false)
            ->update(['leida' => true]);

        return response()->json(['no_leidas' => 0]);
    }
}