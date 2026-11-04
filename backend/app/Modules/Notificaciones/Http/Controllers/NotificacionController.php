<?php

namespace App\Modules\Notificaciones\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de notificaciones internas del usuario autenticado.
 *
 * Atiende la consulta de avisos recientes y el marcado como leídas (individual
 * o masiva). Todo opera sobre el usuario autenticado mediante tokens.
 */
class NotificacionController extends Controller
{
    /**
     * GET /api/notificaciones
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id_usuario;

        $notificaciones = Notificacion::where('id_usuario', $userId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $noLeidas = Notificacion::where('id_usuario', $userId)
            ->where('leida', false)
            ->count();

        return response()->json([
            'no_leidas' => $noLeidas,
            'notificaciones' => $notificaciones,
        ]);
    }

    /**
     * PATCH /api/notificaciones/{id}
     */
    public function marcarLeida(Request $request, int $id): JsonResponse
    {
        $notificacion = Notificacion::where('id_usuario', $request->user()->id_usuario)
            ->findOrFail($id);

        $notificacion->update(['leida' => true]);

        return response()->json($notificacion);
    }

    /**
     * POST /api/notificaciones/leer-todas
     */
    public function marcarTodasLeidas(Request $request): JsonResponse
    {
        Notificacion::where('id_usuario', $request->user()->id_usuario)
            ->where('leida', false)
            ->update(['leida' => true]);

        return response()->json(['no_leidas' => 0]);
    }
}