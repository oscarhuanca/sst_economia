/**
 * Fachada pública del módulo Dashboard de KARDEX.
 *
 * Contrato del módulo: lo único que otros módulos pueden importar de aquí
 * (`@/modules/kardex`). Expone rutas, store, catálogo de modalidades asignables
 * y utilidades de formato.
 */
import routes from './router/kardex.routes';

export { useKardexStore, MODALIDADES_ASIGNABLES } from './stores/kardex';
export { fechaLegible, iniciales } from './utils/formato';

export default {
    nombre: 'kardex',
    rutas: routes,
};