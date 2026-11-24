<?php

namespace App\Modules\Tramites\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tramites\Http\Requests\CrearSolicitudExamenRequest;
use App\Modules\Tramites\Services\ExamenService;
use Illuminate\Http\JsonResponse;

/**
 * Solicitud de Examen de Grado presentada por el estudiante.
 *
 * Capa delgada: las reglas de negocio (modalidad activa, intentos disponibles y
 * solicitudes en curso) viven en ExamenService. La autorización por rol está
 * declarada en la ruta y en el FormRequest.
 */
class ExamenSolicitudController extends Controller
{
    public function __construct(
        private readonly ExamenService $examenService,
    ) {}

    /**
     * POST /api/examen/solicitud
     */
    public function store(CrearSolicitudExamenRequest $request): JsonResponse
    {
        try {
            $documentos = $request->validated()['documentos'] ?? [];
            $tramite = $this->examenService->crearSolicitud($request->user(), $documentos);

            return response()->json($tramite, 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
