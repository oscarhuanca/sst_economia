/**
 * Fachada pública del módulo de notificaciones.
 *
 * Contrato del módulo: lo único que otros módulos pueden importar de aquí
 * (`@/modules/notificaciones`). No define rutas propias; expone la campana de
 * avisos y su store.
 */
export { default as NotificationBell } from './components/NotificationBell.vue';
export { useNotificacionesStore } from './stores/notificaciones';

export default {
    nombre: 'notificaciones',
    rutas: [],
};