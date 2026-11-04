<?php

namespace App\Modules\Reportes\Services;

use App\Models\Tramite;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Generación del reporte de titulación en PDF.
 *
 * Consolida los datos del trámite (postulante, modalidad, tutor, tema, tribunal,
 * defensa e historial) en una vista Blade y la renderiza como PDF con DomPDF.
 * Es el artefacto del módulo "Reporte" del flujo de titulación.
 */
class ReporteService
{
    /** Columnas estrictas del trámite que alimentan el reporte. */
    private const COLUMNAS = [
        'id_tramite', 'id_estudiante', 'id_modalidad', 'id_tutor',
        'estado_actual', 'observaciones', 'hitos', 'created_at', 'updated_at',
    ];

    /** Eager-loads con proyección de columnas (sin datos sensibles). */
    private const CARGAS = [
        'modalidad:id_modalidad,nombre,descripcion,duracion_maxima_meses',
        'estudiante:id_estudiante,ci,nombres,apellidos,registro_universitario,email,telefono,promedio_global',
        'tutor:id_docente,nombre,apellidos,email,telefono,materia,ci',
        'estados:id_estado,id_tramite,nombre_estado,descripcion,observaciones,created_at',
        'documentos:id_documento,id_tramite,tipo_documento,nombre_archivo,created_at',
    ];

    /**
     * Etiqueta legible de cada estado interno para el reporte.
     *
     * @var array<string, string>
     */
    private const ETIQUETAS_ESTADO = [
        'solicitud_presentada' => 'Solicitud presentada',
        'pendiente_concejo_universitario' => 'Pendiente del Consejo Universitario',
        'perfil_rechazado' => 'Perfil rechazado',
        'perfil_aprobado' => 'Perfil aprobado',
        'tutor_asignado' => 'Tutor asignado',
        'tema_aprobado' => 'Tema aprobado',
        'investigacion_en_desarrollo' => 'Investigación en desarrollo',
        'documento_final_presentado' => 'Documento final presentado',
        'comision_revisora' => 'Comisión revisora',
        'insuficiente' => 'Calificación insuficiente',
        'correcciones_90_dias' => 'Correcciones (90 días)',
        'suficiente' => 'Calificación suficiente',
        'solicitud_fecha_defensa' => 'Fecha de defensa solicitada',
        'defensa_programada' => 'Defensa programada',
        'defensa_en_curso' => 'Defensa en curso',
        'tribunal_asignado' => 'Tribunal asignado',
        'defensa_aprobada' => 'Defensa aprobada',
        'publicado' => 'Publicado',
        'reporte_generado' => 'Reporte generado',
        'aprobado' => 'Aprobado',
        'titulado' => 'Titulado',
        'reprobado' => 'Reprobado',
        'rechazado' => 'Rechazado',
    ];

    /**
     * Genera y descarga el reporte PDF del trámite indicado.
     *
     * @param  int  $id  Identificador del trámite.
     * @return StreamedResponse Respuesta PDF descargable.
     */
    public function descargar(int $id): StreamedResponse
    {
        $tramite = Tramite::query()
            ->select(self::COLUMNAS)
            ->with(self::CARGAS)
            ->findOrFail($id);

        $pdf = Pdf::loadView('tramites.reporte', $this->datos($tramite))
            ->setPaper('a4', 'portrait');

        return $pdf->download("reporte_titulacion_{$tramite->id_tramite}.pdf");
    }

    /**
     * Arma los datos que consume la vista del reporte.
     *
     * @return array<string, mixed>
     */
    private function datos(Tramite $tramite): array
    {
        $modulos = $tramite->hitos['modulos'] ?? [];

        return [
            'sistema' => config('app.name', 'Sistema de Seguimiento de Titulación'),
            'emitido' => Carbon::now()->format('d/m/Y H:i'),
            'tramite' => $tramite,
            'estudiante' => $tramite->estudiante,
            'modalidad' => $tramite->modalidad,
            'tutor' => $tramite->tutor,
            'modulos' => $modulos,
            'hitos' => $tramite->hitos ?? [],
            'historial' => $tramite->estados->sortBy('created_at'),
            'estado' => $tramite->estado_actual,
            'etiquetaEstado' => self::ETIQUETAS_ESTADO[$tramite->estado_actual] ?? $tramite->estado_actual,
            'etiquetasEstado' => self::ETIQUETAS_ESTADO,
        ];
    }
}
