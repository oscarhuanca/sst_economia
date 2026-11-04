<?php

namespace App\Modules\Excelencia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Excelencia\Http\Requests\ActualizarPromedioExcelenciaRequest;
use App\Modules\Excelencia\Http\Requests\CrearSolicitudExcelenciaRequest;
use App\Modules\Excelencia\Http\Requests\GuardarModuloExcelenciaRequest;
use App\Modules\Excelencia\Services\ExcelenciaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador del Módulo de Excelencia Académica.
 *
 * Expone el API del módulo: la solicitud del estudiante (con su promedio
 * declarado) y el guardado de los tres módulos que KARDEX completa (verificación
 * del promedio, Resolución del HCC y reporte final). Capa delgada: toda la
 * lógica vive en \App\Modules\Excelencia\Services\ExcelenciaService y la
 * autorización por rol está declarada en las rutas.
 */
class ExcelenciaController extends Controller
{
    public function __construct(
        private readonly ExcelenciaService $excelenciaService,
    ) {}

    /**
     * POST /api/excelencia/solicitud
     */
    public function store(CrearSolicitudExcelenciaRequest $request): JsonResponse
    {
        try {
            $tramite = $this->excelenciaService->crearSolicitud($request->validated(), $request->user());

            return response()->json($tramite, 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * PUT /api/excelencia/solicitud/{id}/promedio
     *
     * Declara o corrige el promedio general antes de que KARDEX lo verifique.
     */
    public function actualizarPromedio(ActualizarPromedioExcelenciaRequest $request, int $id): JsonResponse
    {
        return response()->json(
            $this->excelenciaService->actualizarPromedioDeclarado(
                $id,
                $request->validated(),
                $request->user()
            )
        );
    }

    /**
     * POST /api/tramites/{id}/excelencia/{modulo} (roles de gestión)
     *
     * Guarda un módulo del flujo de Excelencia Académica y avanza el trámite
     * al estado que corresponde.
     */
    public function guardarModulo(GuardarModuloExcelenciaRequest $request, int $id, string $modulo): JsonResponse
    {
        $validated = $request->validated();

        $adjuntos = array_filter([
            'informe_verificacion' => $request->file('informe_verificacion'),
            'informe_lectura_acta' => $request->file('informe_lectura_acta'),
        ]);

        return response()->json(
            $this->excelenciaService->guardarModulo(
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
     * GET /api/excelencia/config
     */
    public function config(Request $request): JsonResponse
    {
        return response()->json([
            'modalidad' => ExcelenciaService::MODALIDAD,
            'nota_minima' => config('excelencia.nota_minima'),
            'rango_nota' => config('excelencia.rango_nota'),
        ]);
    }
}
