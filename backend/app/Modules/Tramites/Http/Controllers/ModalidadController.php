<?php

namespace App\Modules\Tramites\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Modalidad;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de modalidades de titulación.
 */
class ModalidadController extends Controller
{
    /**
     * GET /api/modalidades
     */
    public function index(): JsonResponse
    {
        return response()->json(
            Modalidad::where('activo', true)->orderBy('nombre')->get()
        );
    }
}