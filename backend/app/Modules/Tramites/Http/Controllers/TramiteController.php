<?php

namespace App\Modules\Tramites\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tramites\Http\Requests\AsignarTutorRequest;
use App\Modules\Tramites\Http\Requests\CrearTramiteRequest;
use App\Modules\Tramites\Http\Requests\GuardarModuloExamenRequest;
use App\Modules\Tramites\Http\Requests\GuardarModuloFlujoRequest;
use App\Modules\Tramites\Http\Requests\ReabrirExamenRequest;
use App\Modules\Tramites\Http\Requests\RevisarTramiteRequest;
use App\Modules\Tramites\Http\Requests\TransicionarTramiteRequest;
use App\Modules\Tramites\Services\ExamenService;
use App\Modules\Tramites\Services\TramiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de trámites de titulación.
 *
 * Expone el API de trámites: creación, revisión, transiciones, asignación de
 * tutor, paneles de consulta y estadísticas. Capa delgada: toda la lógica vive
 * en \App\Modules\Tramites\Services\TramiteService y la autorización por rol
 * está declarada en las rutas.
 */
class TramiteController extends Controller
{
    public function __construct(
        private readonly TramiteService $tramiteService,
        private readonly ExamenService $examenService,
    ) {}

    /**
     * GET /api/estudiante/tramite-activo
     */
    public function tramiteActivo(Request $request): JsonResponse
    {
        return response()->json($this->tramiteService->activoDe($request->user()));
    }

    /**
     * POST /api/tramites
     */
    public function store(CrearTramiteRequest $request): JsonResponse
    {
        try {
            $tramite = $this->tramiteService->crear($request->validated(), $request->user());

            return response()->json($tramite, 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /api/tramites/pendientes (roles de gestión)
     */
    public function pendientes(Request $request): JsonResponse
    {
        // Paginación por defecto con `per_page` acotado y búsqueda opcional `q`.
        return response()->json($this->tramiteService->pendientes(
            $request->integer('per_page') ?: null,
            $request->query('q')
        ));
    }

    /**
     * GET /api/tramites/concluidos (roles de gestión)
     */
    public function concluidos(Request $request): JsonResponse
    {
        return response()->json($this->tramiteService->concluidos(
            $request->integer('per_page') ?: null,
            $request->query('q')
        ));
    }

    /**
     * GET /api/tramites/estadisticas (roles de gestión)
     */
    public function estadisticas(): JsonResponse
    {
        return response()->json($this->tramiteService->estadisticas());
    }

    /**
     * POST /api/tramites/{id}/revisar (roles de gestión)
     */
    public function revisar(RevisarTramiteRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        return response()->json(
            $this->tramiteService->revisar(
                $id,
                $validated['accion'],
                $validated['observaciones'] ?? null,
                $request->user()
            )
        );
    }

    /**
     * POST /api/tramites/{id}/transicionar (roles de gestión: secretaría/kardex)
     */
    public function transicionar(TransicionarTramiteRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        return response()->json(
            $this->tramiteService->transicionar(
                $id,
                $validated['nuevo_estado'],
                $validated['observaciones'] ?? null,
                $request->user()
            ),
            201
        );
    }

    /**
     * POST /api/tramites/{id}/asignar-tutor (roles de gestión)
     */
    public function asignarTutor(AsignarTutorRequest $request, int $id): JsonResponse
    {
        return response()->json(
            $this->tramiteService->asignarTutor($id, $request->validated()['id_tutor'])
        );
    }

    /**
     * POST /api/tramites/{id}/flujo/{modulo} (roles de gestión)
     *
     * Guarda un módulo del flujo de titulación: valida y registra sus datos en
     * `hitos.modulos` y avanza el trámite al estado objetivo del módulo.
     */
    public function guardarModuloFlujo(GuardarModuloFlujoRequest $request, int $id, string $modulo): JsonResponse
    {
        $validated = $request->validated();

        $adjuntos = array_filter([
            'resolucion_tutor' => $request->file('resolucion_tutor'),
            'resolucion_tribunal' => $request->file('resolucion_tribunal'),
            'nota_suficiencia_tutor' => $request->file('nota_suficiencia_tutor'),
            'nota_suficiencia_presidente' => $request->file('nota_suficiencia_presidente'),
            'nota_suficiencia_vocal' => $request->file('nota_suficiencia_vocal'),
            'nota_suficiencia_secretario' => $request->file('nota_suficiencia_secretario'),
        ]);

        return response()->json(
            $this->tramiteService->guardarModuloFlujo(
                $id,
                $modulo,
                $validated['datos'],
                $request->user(),
                $adjuntos
            ),
            201
        );
    }

    /**
     * POST /api/tramites/{id}/examen/{modulo} (roles de gestión)
     *
     * Guarda un módulo del flujo de Examen de Grado y avanza el trámite al
     * estado que corresponde. Los módulos de sorteo (`sorteo_teorico` y
     * `sorteo_especialidad`) registran la asistencia con el tema y la fecha;
     * la nota de ambos exámenes se asigna después, en `asignacion_notas`.
     */
    public function guardarModuloExamen(GuardarModuloExamenRequest $request, int $id, string $modulo): JsonResponse
    {
        $validated = $request->validated();

        return response()->json(
            $this->examenService->guardarModulo(
                $id,
                $modulo,
                $validated['datos'],
                $request->user()
            ),
            201
        );
    }

    /**
     * POST /api/tramites/{id}/examen/reabrir (roles de gestión)
     *
     * Devuelve el trámite a `habilitado_examen` para que el estudiante pueda
     * volver a sortear el examen, consumiendo un intento.
     */
    public function reabrirExamen(ReabrirExamenRequest $request, int $id): JsonResponse
    {
        return response()->json(
            $this->examenService->reabrirSolicitud(
                $id,
                $request->validated()['observaciones'] ?? null,
                $request->user()
            )
        );
    }

    /**
     * GET /api/tutorias (solo docente) — paginado.
     */
    public function tutorias(Request $request): JsonResponse
    {
        return response()->json(
            $this->tramiteService->tutoriasDe($request->user(), $request->integer('per_page') ?: null)
        );
    }

    /**
     * GET /api/tramites/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json($this->tramiteService->mostrar($id, $request->user()));
    }
}
