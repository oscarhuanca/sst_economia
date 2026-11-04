/**
 * Fachada pública del Módulo de Trabajo Dirigido.
 *
 * Contrato del módulo: lo único que otros módulos pueden importar de aquí
 * (`@/modules/trabajodirigido`). Expone rutas, store y utilidades del flujo.
 * Reutiliza el dominio genérico de trámites (store, línea de tiempo y etiquetas
 * de estado) desde su fachada.
 */
import routes from './router/trabajodirigido.routes';

export { useTrabajoDirigidoStore } from './stores/trabajodirigido';
export {
    FASES_TRABAJO_DIRIGIDO,
    DESCRIPCION_ESTADO,
    faseDe,
    countdownDe,
    puedeSolicitarFechaDefensa,
    reoptarInfo,
} from './utils/flujo';
export {
    MODULOS_FLUJO_TITULACION,
    DOCUMENTOS_PERFIL,
    MODULO_PERFIL,
    MOTIVO_TITULO_REPETIDO,
    esFlujoTitulacion,
    modulosTitulacion,
    tituloTrabajoDirigido,
    rechazoPerfilTrabajoDirigido,
} from './utils/flujoTitulacion';

export default {
    nombre: 'trabajodirigido',
    rutas: routes,
};
