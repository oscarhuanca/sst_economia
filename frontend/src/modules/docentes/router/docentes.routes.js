/**
 * Rutas del módulo de docentes (Registro Académico).
 */
export default [
    {
        path: '/admin/docentes',
        name: 'Docentes',
        component: () => import('@/modules/docentes/views/DocentesView.vue'),
        meta: { requiresAuth: true, roles: ['admin'] }
    },
];