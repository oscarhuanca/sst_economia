/**
 * Fachada pública del módulo de estudiantes (portal del estudiante).
 *
 * Contrato del módulo: lo único que otros módulos pueden importar de aquí
 * (`@/modules/estudiantes`). Las rutas internas son privadas.
 */
import routes from './router/estudiantes.routes';

export { useEstudianteStore } from './stores/estudiante';

export default {
    nombre: 'estudiantes',
    rutas: routes,
};