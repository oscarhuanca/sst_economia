/**
 * Rutas del Módulo de Examen de Grado (flujo del estudiante).
 *
 * El estudiante accede al módulo desde `/estudiante/examen`, donde presenta su
 * solicitud y sigue el avance que registra KARDEX.
 */
export default [
    {
        path: '/estudiante/examen',
        name: 'ExamenGrado',
        component: () => import('@/modules/examen/views/ExamenGradoView.vue'),
        meta: { requiresAuth: true, roles: ['estudiante'] },
    },
];
