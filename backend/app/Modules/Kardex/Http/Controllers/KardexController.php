<?php

namespace App\Modules\Kardex\Http\Controllers;

use App\Modules\Kardex\Http\Requests\AsignarModalidadRequest;
use App\Modules\Kardex\Http\Requests\BuscarPostulanteRequest;
use App\Modules\Kardex\Http\Requests\ConsultarPostulanteRequest;
use App\Modules\Kardex\Http\Requests\GenerarCredencialesRequest;
use App\Modules\Kardex\Http\Requests\PublicarDocumentoFinalTesisRequest;
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
 * por CI/RU, generación de credenciales (primera etapa), verificación y
 * asignación de modalidad (segunda etapa), consulta del flujo actual del
 * postulante y verificación de unicidad del título de la tesis de grado.
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
     * Primera etapa: genera las credenciales de acceso del postulante (una vez),
     * sin iniciar ningún trámite.
     */
    public function generarCredenciales(GenerarCredencialesRequest $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            ...$this->kardex->generarCredenciales($request->validated('identificador')),
        ], 201);
    }

    /**
     * Segunda etapa: verifica la cuenta del postulante y asigna la modalidad.
     */
    public function asignarModalidad(AsignarModalidadRequest $request): JsonResponse
    {
        return response()->json([
            'ok' => true,
            ...$this->kardex->asignarModalidad($request->validated()),
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
     * Publica el documento final de tesis (PDF) desde KARDEX.
     */
    public function publicarDocumentoFinal(PublicarDocumentoFinalTesisRequest $request, int $id): JsonResponse
    {
        $tramite = $this->kardex->publicarDocumentoFinalTesis(
            $id,
            $request->user(),
            $request->file('documento_publicacion'),
            $request->validated('observaciones')
        );

        return response()->json(['ok' => true, 'tramite' => $tramite], 201);
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
