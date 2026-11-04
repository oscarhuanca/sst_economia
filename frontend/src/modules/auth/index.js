/**
 * Fachada pública del módulo de autenticación.
 *
 * Contrato del módulo: lo único que otros módulos pueden importar de aquí
 * (`@/modules/auth`). Las rutas internas son privadas.
 */
import routes from './router/auth.routes';

export { useAuthStore } from './stores/auth';
export { homeForRol, registerAuthGuards } from './guards';

export default {
    nombre: 'auth',
    rutas: routes,
};