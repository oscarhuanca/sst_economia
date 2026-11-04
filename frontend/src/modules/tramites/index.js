/**
 * Fachada pública del módulo de trámites de titulación.
 *
 * Contrato del módulo: lo único que otros módulos pueden importar de aquí
 * (`@/modules/tramites`). Expone rutas, store, componentes reutilizables
 * (línea de tiempo, estadísticas por modalidad) y utilidades de estado.
 */
import routes from './router/tramites.routes';

export { useTramitesStore } from './stores/tramites';
export { default as TimelineTramite } from './components/TimelineTramite.vue';
export { default as EstadisticasModalidades } from './components/EstadisticasModalidades.vue';
export { default as EstadoBadge } from './components/EstadoBadge.vue';
export { default as CountdownTitulacion } from './components/CountdownTitulacion.vue';
export * from './utils/estados';

export default {
    nombre: 'tramites',
    rutas: routes,
};