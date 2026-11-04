import { defineStore } from 'pinia';
import { authService } from '../services/auth';

/**
 * Store de autenticación.
 *
 * Gestiona la sesión del usuario (datos y token) manteniéndolos sincronizados
 * con localStorage, e implementa el ingreso y cierre de sesión contra la API.
 */
export const useAuthStore = defineStore('auth', {
    // Estado inicial: restaura la sesión desde localStorage si existía.
    // Un valor corrupto (JSON inválido/truncado de una sesión antigua) no debe
    // lanzar una excepción aquí: eso tumba el montaje de TODA la aplicación.
    state: () => {
        let user = null;
        try {
            const raw = localStorage.getItem('user');
            user = raw ? JSON.parse(raw) : null;
        } catch {
            user = null;
        }
        return {
            user,
            token: localStorage.getItem('token') || null,
        };
    },
    getters: {
        // true si el usuario autenticado tiene rol admin.
        isAdmin: (state) => state.user?.rol === 'admin',
        // true mientras exista token almacenado (sesión considerada activa).
        isAuthenticated: (state) => !!state.token,
    },
    actions: {
        /**
         * Limpia el estado en memoria y el almacenamiento local de la sesión.
         *
         * @returns {void}
         */
        limpiarSesion() {
            this.token = null;
            this.user = null;
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            localStorage.removeItem('perfilEstudiante');
        },
        /**
         * Garantiza que la sesión sea utilizable antes de entrar a una ruta
         * protegida. Si hay token pero el usuario no está resuelto en memoria,
         * lo revalida contra GET /api/me en lugar de confiar solo en
         * localStorage (un token revocado o un `user` corrupto deben
         * comprobarse contra la API).
         *
         * @returns {Promise<boolean>} true si la sesión es utilizable.
         */
        async validarSesion() {
            if (!this.token) return false;
            if (this.user) return true;
            try {
                const { data } = await authService.me();
                this.user = data;
                localStorage.setItem('user', JSON.stringify(data));
                return true;
            } catch {
                this.limpiarSesion();
                return false;
            }
        },
        /**
         * Inicia sesión con username y contraseña contra POST /api/login.
         *
         * @param {string} username Usuario de acceso (autogenerado en estudiantes).
         * @param {string} password Contraseña sin cifrar.
         * @returns {Promise<boolean|string>} `true` si el ingreso fue exitoso; en
         *          caso contrario, el mensaje de error devuelto por la API.
         */
        async login(username, password) {
            try {
                const { data } = await authService.login(username, password);
                this.token = data.access_token;
                this.user = data.user;
                localStorage.setItem('token', this.token);
                localStorage.setItem('user', JSON.stringify(this.user));
                return true;
            } catch (error) {
                return error.response?.data?.message || 'Error al iniciar sesión';
            }
        },
        /**
         * Cierra la sesión: invalida el token en la API y limpia el estado y
         * localStorage (incluido el perfil de estudiante en caché).
         *
         * @returns {Promise<void>}
         */
        async logout() {
            try { await authService.logout(); } catch(e){}
            this.limpiarSesion();
        }
    }
});