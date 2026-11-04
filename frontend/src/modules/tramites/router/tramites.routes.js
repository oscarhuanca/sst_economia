// Roles con acceso al panel de revisión y gestión de trámites.
// El tablero de revisión (/kardex/tramites) es de la gestión académica; la gestión
// individual (/tramites/:id/gestion) avanza estados y el docente ve tutorías.
const ROLES_TRAMITES = ['kardex', 'secretaria', 'direccion', 'admin', 'docente'];

/**
 * Rutas del módulo de trámites de titulación.
 *
 * Incluye el tablero de revisión (Kardex/dirección), la gestión individual de
 * cada trámite y el panel de tutorías del docente.
 */
export default [
    {
        path: '/docente',
        name: 'DocenteTutorias',
        component: () => import('@/modules/tramites/views/DocenteTutorias.vue'),
        meta: { requiresAuth: true, roles: ['docente'] }
    },
    {
        path: '/kardex/tramites',
        name: 'RevisionKardex',
        component: () => import('@/modules/tramites/views/RevisionKardex.vue'),
        meta: { requiresAuth: true, roles: ROLES_TRAMITES }
    },
    {
        // Panel de trámites que finalizaron su flujo. Reutiliza la vista de
        // revisión con la pestaña "concluidos" forzada mediante prop.
        path: '/kardex/tramites/concluidos',
        name: 'RevisionKardexConcluidos',
        component: () => import('@/modules/tramites/views/RevisionKardex.vue'),
        props: { soloConcluidos: true },
        meta: { requiresAuth: true, roles: ROLES_TRAMITES }
    },
    {
        path: '/tramites/:id/gestion',
        name: 'GestionTramite',
        component: () => import('@/modules/tramites/views/GestionTramite.vue'),
        meta: { requiresAuth: true, roles: ROLES_TRAMITES }
    },
];