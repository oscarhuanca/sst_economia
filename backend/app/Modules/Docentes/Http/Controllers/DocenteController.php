<?php

namespace App\Modules\Docentes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Docentes\Http\Requests\StoreDocenteRequest;
use App\Modules\Docentes\Http\Requests\UpdateDocenteRequest;
use App\Modules\Docentes\Services\DocenteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador del registro académico de docentes.
 *
 * Expone el CRUD administrativo de la tabla `docentes` y el catálogo compacto
 * para los roles de gestión. Toda la lógica se delega en
 * \App\Modules\Docentes\Services\DocenteService.
 */
class DocenteController extends Controller
{
    public function __construct(private readonly DocenteService $docenteService) {}

    /**
     * GET /api/docentes (solo admin) — listado paginado de docentes.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->docenteService->index(
                $request->integer('per_page', 20),
                $request->string('q')?->toString()
            )
        );
    }

    /**
     * GET /api/docentes/catalogo (roles de gestión) — catálogo compacto.
     */
    public function catalogo(): JsonResponse
    {
        return response()->json($this->docenteService->catalogo());
    }

    /**
     * POST /api/docentes (solo admin) — alta de un docente.
     */
    public function store(StoreDocenteRequest $request): JsonResponse
    {
        $docente = $this->docenteService->crear($request->validated());

        return response()->json($docente, 201);
    }

    /**
     * PUT/PATCH /api/docentes/{id} (solo admin) — actualización de un docente.
     */
    public function update(UpdateDocenteRequest $request, int $id): JsonResponse
    {
        $docente = $this->docenteService->actualizar($id, $request->validated());

        return response()->json($docente);
    }

    /**
     * DELETE /api/docentes/{id} (solo admin) — baja del registro.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->docenteService->eliminar($id);

        return response()->json(['message' => 'Docente eliminado']);
    }
}