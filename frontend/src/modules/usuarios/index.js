/**
 * Fachada pública del módulo de gestión de usuarios (panel de administración).
 *
 * Contrato del módulo: lo único que otros módulos pueden importar de aquí
 * (`@/modules/usuarios`). Las rutas internas son privadas.
 */
import routes from './router/usuarios.routes';

export default {
    nombre: 'usuarios',
    rutas: routes,
};