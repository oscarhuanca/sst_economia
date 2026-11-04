import { createRouter, createWebHistory } from 'vue-router';
import { moduleRoutes } from '@/modules';
import { registerAuthGuards } from '@/modules/auth';

/**
 * Router de la aplicación.
 *
 * COMPOSICIÓN RAÍZ: Une las rutas expuestas por los módulos en un solo punto
 * y registra los interceptores y guardias de navegación.
 */
const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) return savedPosition;
        return { top: 0 };
    },
    routes: [
        { path: '/', redirect: '/login' },
        ...moduleRoutes,
        { path: '/:pathMatch(.*)*', redirect: '/login' },
    ],
});

const RECARGA_CHUNK_KEY = 'spa:chunk-reload';

// Captura errores de importación de módulos dinámicos (Vite / NS_ERROR)
router.onError((error, to) => {
    console.error('[router] Fallo de navegación hacia', to?.fullPath, error);

    const mensaje = error?.message || '';
    const falloDeChunk = /dynamically imported module|ChunkLoadError|Importing a module script failed|NS_ERROR/i.test(mensaje);
    
    if (!falloDeChunk || !to?.fullPath) return;
    if (sessionStorage.getItem(RECARGA_CHUNK_KEY) === to.fullPath) return;

    sessionStorage.setItem(RECARGA_CHUNK_KEY, to.fullPath);
    window.location.assign(to.fullPath);
});

router.afterEach(() => {
    sessionStorage.removeItem(RECARGA_CHUNK_KEY);
});

// Guardias de autenticación y roles
registerAuthGuards(router);

export default router;