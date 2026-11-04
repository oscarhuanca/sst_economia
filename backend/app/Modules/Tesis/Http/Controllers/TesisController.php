<?php

namespace App\Modules\Tesis\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tesis\Http\Requests\ActualizarPerfilTesisRequest;
use App\Modules\Tesis\Http\Requests\PublicacionDocumentoRequest;
use App\Modules\Tesis\Http\Requests\ReenviarDocumentoRequest;
use App\Modules\Tesis\Http\Requests\ReenviarPerfilRequest;
use App\Modules\Tesis\Http\Requests\SolicitarFechaDefensaRequest;
use App\Modules\Tesis\Http\Requests\SolicitudTesisRequest;
use App\Modules\Tesis\Services\TesisService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Controlador del flujo del estudiante en el Módulo de Tesis de Grado.
 *
 * Capa delgada: solicitud (con documentos PDF opcionales), reenvío del perfil
 * y consulta de configuración del módulo. La lógica vive en TesisService y la
 * autorización por rol está en las rutas y en cada FormRequest.
 */
class TesisController extends Controller
{
    public function __construct(private readonly TesisService $tesisService) {}

    /**
     * POST /api/tesis/solicitud
     */
    public function store(SolicitudTesisRequest $request): JsonResponse
    {
        try {
            $tramite = $this->tesisService->crearSolicitud($request->validated(), $request->user());

            return response()->json($tramite, 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * PUT /api/tesis/{id}/perfil
     */
    public function actualizarPerfil(ActualizarPerfilTesisRequest $request, int $id): JsonResponse
    {
        try {
            $tramite = $this->tesisService->actualizarPerfil($request->validated(), $id, $request->user());

            return response()->json($tramite);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No autorizado'], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->validator->errors()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * POST /api/tesis/{id}/reenviar
     */
    public function reenviar(ReenviarPerfilRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        try {
            $tramite = $this->tesisService->reenviar(
                $id,
                $request->user(),
                $validated['perfil'] ?? null,
                $validated['titulo'] ?? '',
                $validated['observaciones'] ?? null
            );

            return response()->json($tramite);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No autorizado'], 403);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->validator->errors()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * POST /api/tesis/{id}/reenviar-documento
     */
    public function reenviarDocumento(ReenviarDocumentoRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        try {
            $tramite = $this->tesisService->reenviarDocumentoFinal(
                $id,
                $request->user(),
                $validated['documento_final'] ?? null,
                $validated['observaciones'] ?? null
            );

            return response()->json($tramite);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No autorizado'], 403);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first()], 422);
        }
    }

    /**
     * POST /api/tesis/{id}/publicacion
     */
    public function subirPublicacion(PublicacionDocumentoRequest $request, int $id): JsonResponse
    {
        try {
            $tramite = $this->tesisService->subirPublicacion(
                $id,
                $request->user(),
                $request->file('documento_publicacion'),
                $request->validated('observaciones')
            );

            return response()->json($tramite);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No autorizado'], 403);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first()], 422);
        }
    }

    /**
     * POST /api/tesis/{id}/solicitar-fecha-defensa
     */
    public function solicitarFechaDefensa(SolicitarFechaDefensaRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        try {
            $tramite = $this->tesisService->solicitarFechaDefensa(
                $id,
                $request->user(),
                $validated['fecha_sugerida'] ?? null
            );

            return response()->json($tramite);
        } catch (AuthorizationException $e) {
            return response()->json(['message' => 'No autorizado'], 403);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->validator->errors()->first()], 422);
        }
    }

    /**
     * GET /api/tesis/config
     */
    public function config(Request $request): JsonResponse
    {
        return response()->json([
            'plazo_presentacion_meses' => config('tesis.plazo_presentacion_meses'),
            'dias_correccion' => config('tesis.dias_correccion'),
            'dias_remodalidad' => config('tesis.dias_remodalidad'),
            'tipos_documento' => config('tesis.tipos_documento'),
            'tipos_documento_publicacion' => config('tesis.tipos_documento_publicacion'),
            'estado_reenvio' => $this->tesisService->estadoReenvio(),
        ]);
    }
}
