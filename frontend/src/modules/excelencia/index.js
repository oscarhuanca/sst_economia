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
    MODULOS_FLUJO_EXCELENCIA,
    MODULO_REPORTE,
    MODULO_RESOLUCION,
    MODULO_VERIFICACION,
    NOTA_MINIMA,
    ORDEN_ESTADOS,
    RANGO_NOTA,
    datosDeModulo,
    esFlujoExcelencia,
    modulosExcelencia,
    pctExcelencia,
    promedioDeclarado,
    promedioVerificado,
    proximoModuloPendiente,
    resolucionHcc,
    validarModulo,
} from './utils/flujoExcelencia';

export default {
    nombre: 'excelencia',
    rutas: routes,
};
