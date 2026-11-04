/**
 * Guardias de navegación del dominio de autenticación.
 */
import { useAuthStore } from '../stores/auth';

export const HOME_BY_ROL = {
  admin: '/admin',
  estudiante: '/estudiante',
  kardex: '/kardex',
  secretaria: '/kardex',
  direccion: '/kardex',
  docente: '/docente',
};

export function homeForRol(rol) {
  return HOME_BY_ROL[rol] || '/estudiante';
}

/**
 * Instala las guardias globales de navegación en el router de la aplicación.
 */
export function registerAuthGuards(router) {
  router.beforeEach(async (to) => {
    const authStore = useAuthStore();

    // 1. Redirección si intenta ir a /login teniendo sesión activa
    if (to.path === '/login' && authStore.isAuthenticated) {
      try {
        const sesionValida = await authStore.validarSesion();
        if (sesionValida) {
          return { path: homeForRol(authStore.user?.rol) };
        }
      } catch {
        authStore.limpiarSesion();
      }
    }

    // 2. Validación de rutas protegidas (con control de timeout/excepción)
    if (to.meta.requiresAuth) {
      let sesionValida = false;
      try {
        sesionValida = await authStore.validarSesion();
      } catch (error) {
        console.error('[Guard] Error al revalidar la sesión:', error);
        sesionValida = false;
      }

      if (!sesionValida) {
        authStore.limpiarSesion();
        return { path: '/login', query: { redirect: to.fullPath } };
      }
    }

    // 3. Control de acceso por rol 'admin'
    if (to.meta.requiresAdmin && !authStore.isAdmin) {
      return { path: homeForRol(authStore.user?.rol) };
    }

    // 4. Control de acceso por lista de roles explícitos
    if (to.meta.roles && Array.isArray(to.meta.roles)) {
      const userRol = authStore.user?.rol;
      if (!to.meta.roles.includes(userRol)) {
        return { path: homeForRol(userRol) };
      }
    }

    return true;
  });
}