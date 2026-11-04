<?php

namespace App\Modules\Usuarios\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Usuarios\Http\Requests\AltaEstudianteRequest;
use App\Modules\Usuarios\Http\Requests\StoreUserRequest;
use App\Modules\Usuarios\Http\Requests\UpdateUserRequest;
use App\Modules\Usuarios\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de gestión de usuarios.
 *
 * Atiende el CRUD de usuarios y el listado de docentes. Las reglas de
 * autorización por rol están declaradas en las rutas (middleware `role`);
 * la lógica de negocio se delega en \App\Modules\Usuarios\Services\UserService.
 */
class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    /**
     * GET /api/usuarios (solo admin) — paginado.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->userService->index($request->integer('per_page') ?: null));
    }

    /**
     * GET /api/usuarios/estadisticas (solo admin) — totales por rol.
     */
    public function estadisticas(): JsonResponse
    {
        return response()->json($this->userService->estadisticas());
    }

    /**
     * GET /api/usuarios/docentes (roles de gestión)
     */
    public function docentes(): JsonResponse
    {
        return response()->json($this->userService->docentes());
    }

    /**
     * POST /api/usuarios (solo admin)
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $usuario = $this->userService->crear($request->validated());

        return response()->json($usuario, 201);
    }

    /**
     * POST /api/usuarios/alta (solo admin) — CREAR USUARIO.
     *
     * El administrador solo válida la cuenta: aporta el identificador (CI o
     * registro universitario) y el sistema genera las credenciales de acceso.
     */
    public function alta(AltaEstudianteRequest $request): JsonResponse
    {
        return response()->json(
            $this->userService->altaEstudiante($request->validated('identificador'))
        );
    }

    /**
     * PUT/PATCH /api/usuarios/{id} (solo admin)
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $usuario = $this->userService->actualizar($id, $request->validated());

        return response()->json($usuario);
    }

    /**
     * DELETE /api/usuarios/{id} (solo admin) — baja lógica.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->userService->desactivar($id);

        return response()->json(['message' => 'Usuario dado de baja']);
    }
}
