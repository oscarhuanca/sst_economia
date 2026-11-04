/**
 * Rutas del módulo de autenticación.
 *
 * En este módulo solo vive la pantalla de login; la guardia global del router
 * se encarga de bloquear el resto de la aplicación cuando no hay sesión.
 */
export default [
    {
        path: '/login',
        name: 'Login',
        component: () => import('@/modules/auth/views/LoginView.vue'),
    },
];