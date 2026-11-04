<?php

namespace App\Modules\Kardex\Http\Controllers;

use App\Modules\Kardex\Http\Requests\AsignarModalidadRequest;
use App\Modules\Kardex\Http\Requests\BuscarPostulanteRequest;
use App\Modules\Kardex\Http\Requests\ConsultarPostulanteRequest;
use App\Modules\Kardex\Http\Requests\VerificarTituloTesisRequest;
use App\Modules\Kardex\Services\KardexService;
use App\Modules\Kardex\Services\VerificacionTituloService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Controlador del Dashboard de KARDEX.
 *
 * Endpoints consumidos por el módulo frontal `kardex`: búsqueda de postulantes
 * por CI/RU, asignación de modalidad con generación de credenciales, consulta
 * del flujo actual del postulante y verificación de unicidad del título de la
 * tesis de grado bajo la modalidad asignada.
 */
class KardexController extends Controller
{
    public function __construct(
        private readonly KardexService $kardex,
        private readonly VerificacionTituloService $titulos,
    ) {}

    /**
     * Busca un postulante por CI o registro universitario.
     */
    public function buscar(BuscarPostulanteRequest $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            ...$this->kardex->buscarPostulante($request->validated('identificador')),
        ]);
    }

    /**
     * Asigna la modalidad y genera las credenciales de acceso (una sola vez).
     */
    public function asignarModalidad(AsignarModalidadRequest $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            ...$this->kardex->asignarModalidad($request->validated(), $request->user()),
        ], 201);
    }

    /**
     * Consulta el flujo actual del postulante (trámite más reciente).
     */
    public function consultar(ConsultarPostulanteRequest $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            ...$this->kardex->consultar($request->validated('identificador')),
        ]);
    }

    /**
     * Resumen del dashboard: totales por modalidad, estudiantes y tutores
     * para la vista principal del KARDEX.
     */
    public function resumen(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'resumen' => $this->kardex->resumen(),
        ]);
    }

    /**
     * Verifica la unicidad de un título de tesis de grado: informa si ya existe
     * otro trámite con el mismo título y lista los trabajos más parecidos.
     */
    public function verificarTitulo(VerificarTituloTesisRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return response()->json([
            'ok' => true,
            'verificacion' => $this->titulos->verificar(
                $validated['titulo'],
                isset($validated['excluir_tramite']) ? (int) $validated['excluir_tramite'] : null,
                isset($validated['excluir_estudiante']) ? (int) $validated['excluir_estudiante'] : null,
            ),
        ]);
    }

    /**
     * Reporte estadístico filtrable por modalidad, mes, año o rango de fechas.
     */
    public function reportes(Request $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'reporte' => $this->kardex->reportes($request->all()),
        ]);
    }
}
