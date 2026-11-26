/**
 * Fachada pública del Módulo de Excelencia Académica.
 *
 * Contrato del módulo: lo único que otros módulos pueden importar de aquí
 * (`@/modules/excelencia`). Expone rutas, store y las utilidades del flujo
 * (módulos, progreso y validación), que son el espejo frontend de
 * `App\Modules\Excelencia\Services\ExcelenciaService`.
 */
import routes from './router/excelencia.routes';

export { useExcelenciaStore } from './stores/excelencia';
export {
    AREAS_CALIFICACION,
    MODULOS_FLUJO_EXCELENCIA,
    MODULO_ACTA,
    MODULO_INICIO,
    MODULO_DESIGNACION,
    MODULO_INFORME,
    MODULO_REPORTE,
    MODULO_RESOLUCION,
    MODULO_VERIFICACION,
    NOTA_MINIMA,
    ORDEN_ESTADOS,
    RANGO_NOTA,
    RESULTADOS_EVALUACION,
    actaLectura,
    calificacionFinal,
    datosDeModulo,
    designacionTribunal,
    documentosSolicitud,
    esFlujoExcelencia,
    formatoCalificacion,
    informeTribunal,
    modulosExcelencia,
    nombreDocente,
    pctExcelencia,
    promedioDeclarado,
    promedioVerificado,
    proximoModuloPendiente,
    resultadoEvaluacion,
    resolucionAdmision,
    resolucionHcc,
    validarModulo,
} from './utils/flujoExcelencia';

export default {
    nombre: 'excelencia',
    rutas: routes,
};
