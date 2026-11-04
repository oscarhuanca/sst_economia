/**
 * Rutas del Módulo de Tesis de Grado (flujo del estudiante).
 *
 * El estudiante accede al módulo desde `/estudiante/tesis`. La gestión de cada
 * trámite sigue usando las rutas del módulo genérico de trámites.
 */
export default [
    {
        path: '/estudiante/tesis',
        name: 'TesisGrado',
        component: () => import('@/modules/tesis/views/TesisGradoView.vue'),
        meta: { requiresAuth: true, roles: ['estudiante'] }
    },
];