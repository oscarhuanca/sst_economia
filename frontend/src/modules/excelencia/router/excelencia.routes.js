/**
 * Rutas del Módulo de Excelencia Académica (flujo del estudiante).
 *
 * El estudiante accede al módulo desde `/estudiante/excelencia`, donde
 * presenta su solicitud y sigue el avance que registra KARDEX.
 */
export default [
    {
        path: '/estudiante/excelencia',
        name: 'ExcelenciaGrado',
        component: () => import('@/modules/excelencia/views/ExcelenciaGradoView.vue'),
        meta: { requiresAuth: true, roles: ['estudiante'] },
    },
];
