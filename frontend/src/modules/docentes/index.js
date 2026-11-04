/**
 * Fachada pública del módulo de docentes (registro académico).
 *
 * Contrato del módulo: lo único que otros módulos pueden importar de aquí
 * (`@/modules/docentes`). Las rutas internas son privadas.
 */
import routes from './router/docentes.routes';

export { docentesService } from './services/docentes';

export default {
    nombre: 'docentes',
    rutas: routes,
};