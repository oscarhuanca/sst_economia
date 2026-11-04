/**
 * Rutas del Módulo de Trabajo Dirigido (flujo del estudiante).
 *
 * El estudiante accede al módulo desde `/estudiante/trabajo-dirigido`. La
 * gestión de cada trámite sigue usando las rutas del módulo genérico de
 * trámites.
 */
export default [
    {
        path: '/estudiante/trabajo-dirigido',
        name: 'TrabajoDirigido',
        component: () => import('@/modules/trabajodirigido/views/TrabajoDirigidoView.vue'),
        meta: { requiresAuth: true, roles: ['estudiante'] }
    },
];
