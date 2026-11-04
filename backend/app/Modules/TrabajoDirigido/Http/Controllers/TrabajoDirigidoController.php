<?php

namespace App\Modules\TrabajoDirigido\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TrabajoDirigido\Http\Requests\ActualizarPerfilTrabajoDirigidoRequest;
use App\Modules\TrabajoDirigido\Http\Requests\PublicacionTrabajoDirigidoRequest;
use App\Modules\TrabajoDirigido\Http\Requests\ReenviarDocumentoTrabajoDirigidoRequest;
use App\Modules\TrabajoDirigido\Http\Requests\ReenviarPerfilTrabajoDirigidoRequest;
use App\Modules\TrabajoDirigido\Http\Requests\SolicitarFechaDefensaTrabajoDirigidoRequest;
use App\Modules\TrabajoDirigido\Http\Requests\SolicitudTrabajoDirigidoRequest;
use App\Modules\TrabajoDirigido\Services\TrabajoDirigidoService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Controlador del flujo del estudiante en el Módulo de Trabajo Dirigido.
 *
 * Capa delgada: solicitud (con documentos PDF opcionales), reenvío del perfil
 * y consulta de configuración del módulo. La lógica vive en
 * TrabajoDirigidoService y la autorización por rol está en las rutas y en cada
 * FormRequest.
 */
class TrabajoDirigidoController extends Controller
{
    public function __construct(private readonly TrabajoDirigidoService $trabajoDirigidoService) {}

    /**
     * POST /api/trabajo-dirigido/solicitud
     */
    public function store(SolicitudTrabajoDirigidoRequest $request): JsonResponse
    {
        try {
            $tramite = $this->trabajoDirigidoService->crearSolicitud($request->validated(), $request->user());

            return response()->json($tramite, 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * POST /api/trabajo-dirigido/{id}/perfil
     */
    public function actualizarPerfil(ActualizarPerfilTrabajoDirigidoRequest $request, int $id): JsonResponse
    {
        try {
            $tramite = $this->trabajoDirigidoService->actualizarPerfil($request->validated(), $id, $request->user());

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
     * POST /api/trabajo-dirigido/{id}/reenviar
     */
    public function reenviar(ReenviarPerfilTrabajoDirigidoRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        try {
            $tramite = $this->trabajoDirigidoService->reenviar(
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
     * POST /api/trabajo-dirigido/{id}/reenviar-documento
     */
    public function reenviarDocumento(ReenviarDocumentoTrabajoDirigidoRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        try {
            $tramite = $this->trabajoDirigidoService->reenviarDocumentoFinal(
                $id,
                $request->user(),
                $validated['documento_final'] ?? null,
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
     * POST /api/trabajo-dirigido/{id}/publicacion
     */
    public function subirPublicacion(PublicacionTrabajoDirigidoRequest $request, int $id): JsonResponse
    {
        try {
            $tramite = $this->trabajoDirigidoService->subirPublicacion(
                $id,
                $request->user(),
                $request->file('documento_publicacion'),
                $request->validated('observaciones')
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
     * POST /api/trabajo-dirigido/{id}/solicitar-fecha-defensa
     */
    public function solicitarFechaDefensa(SolicitarFechaDefensaTrabajoDirigidoRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        try {
            $tramite = $this->trabajoDirigidoService->solicitarFechaDefensa(
                $id,
                $request->user(),
                $validated['fecha_sugerida'] ?? null
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
     * GET /api/trabajo-dirigido/config
     */
    public function config(Request $request): JsonResponse
    {
        return response()->json([
            'plazo_presentacion_meses' => config('trabajodirigido.plazo_presentacion_meses'),
            'dias_correccion' => config('trabajodirigido.dias_correccion'),
            'dias_remodalidad' => config('trabajodirigido.dias_remodalidad'),
            'tipos_documento' => config('trabajodirigido.tipos_documento'),
            'tipos_documento_publicacion' => config('trabajodirigido.tipos_documento_publicacion'),
            'estado_reenvio' => $this->trabajoDirigidoService->estadoReenvio(),
        ]);
    }
}
