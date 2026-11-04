/**
 * Fachada pública del módulo de layout (shell de sesión).
 *
 * Contrato del módulo: lo único que otros módulos pueden importar de aquí
 * (`@/modules/layout`). No define rutas propias.
 */
export { default as AppShell } from './AppShell.vue';

export default {
    nombre: 'layout',
    rutas: [],
};