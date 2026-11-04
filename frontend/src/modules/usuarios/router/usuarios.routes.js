/**
 * Rutas del módulo de gestión de usuarios (Panel de Administración).
 */
export default [
    {
        path: '/admin',
        name: 'AdminDashboard',
        component: () => import('@/modules/usuarios/views/AdminDashboard.vue'),
        meta: { requiresAuth: true, requiresAdmin: true }
    },
];