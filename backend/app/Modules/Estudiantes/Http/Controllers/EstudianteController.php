<?php

namespace App\Modules\Estudiantes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Estudiantes\Http\Requests\RegistroEstudianteRequest;
use App\Modules\Estudiantes\Http\Requests\StoreEstudianteRequest;
use App\Modules\Estudiantes\Services\EstudianteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador del perfil de estudiante.
 *
 * Expone el auto-registro público del estudiante (desde el login), la gestión
 * administrativa del perfil (crear estudiantes, listarlos y consultar quiénes
 * aún no tienen cuenta de acceso) y la consulta en modo lectura del perfil del
 * estudiante autenticado. La lógica se delega en
 * \App\Modules\Estudiantes\Services\EstudianteService.
 */
class EstudianteController extends Controller
{
    public function __construct(private readonly EstudianteService $estudianteService) {}

    /**
     * GET /api/estudiantes (solo admin) — listado paginado de estudiantes.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->estudianteService->index(
                $request->integer('per_page', 20),
                $request->string('q')?->toString()
            )
        );
    }

    /**
     * POST /api/estudiantes/registro (público) — auto-registro del estudiante.
     *
     * Crea el perfil aislado del estudiante. Las credenciales de acceso se
     * generan posteriormente por la instancia académica (CREAR USUARIO).
     */
    public function registro(RegistroEstudianteRequest $request): JsonResponse
    {
        $estudiante = $this->estudianteService->crear($request->validated());

        return response()->json($estudiante, 201);
    }

    /**
     * POST /api/estudiantes (solo admin) — alta administrativa del perfil.
     */
    public function store(StoreEstudianteRequest $request): JsonResponse
    {
        $estudiante = $this->estudianteService->crear($request->validated());

        return response()->json($estudiante, 201);
    }

    /**
     * GET /api/estudiantes/sin-usuario (solo admin) — candidatos a CREAR USUARIO.
     */
    public function sinUsuario(Request $request): JsonResponse
    {
        return response()->json(
            $this->estudianteService->sinUsuario(
                $request->integer('per_page', 20),
                $request->string('q')?->toString()
            )
        );
    }

    /**
     * GET /api/estudiante/perfil (autenticado, solo lectura).
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($this->estudianteService->perfilDe($request->user()));
    }
}