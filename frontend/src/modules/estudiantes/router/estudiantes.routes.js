/**
 * Rutas del módulo de estudiantes (Portal del Estudiante).
 */
export default [
    {
        path: '/estudiante',
        name: 'EstudianteDashboard',
        component: () => import('@/modules/estudiantes/views/EstudianteDashboard.vue'),
        meta: { requiresAuth: true }
    },
];