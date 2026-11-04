<?php

namespace App\Modules\Reportes\Http\Controllers;

use App\Modules\Reportes\Services\ReporteService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controlador de reportes de titulación.
 *
 * Expone la descarga del reporte PDF consolidado de un trámite.
 */
class ReporteController extends Controller
{
    public function __construct(
        private readonly ReporteService $reporte,
    ) {}

    /**
     * GET /api/tramites/{id}/reporte (roles de gestión).
     *
     * @return StreamedResponse PDF con el reporte del trámite.
     */
    public function descargar(Request $request, int $id): StreamedResponse
    {
        return $this->reporte->descargar($id);
    }
}
