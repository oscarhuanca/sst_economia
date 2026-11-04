import axios from 'axios';
import { API_URL } from '@/core/config/env';

/**
 * Cliente HTTP centralizado (axios) para toda la aplicación.
 *
 * - baseURL: apunta a la API de Laravel (leída desde `core/config/env`).
 * - Envía/recibe JSON y adjunta automáticamente el token Bearer a cada petición.
 *   NO se fija un Content-Type por defecto: axios añade `application/json` solo
 *   cuando el cuerpo es un objeto, y con FormData el navegador debe escribir
 *   `multipart/form-data` junto con su `boundary` (fijarlo a mano elimina el
 *   boundary y el backend recibe el body vacío).
 * - Detecta sesiones expiradas (401) y redirige a la pantalla de login.
 *
 * Los servicios de cada módulo (`src/modules/<dominio>/services`) usan esta instancia;
 * ningún componente debería importar axios directamente.
 */
const http = axios.create({
    baseURL: API_URL,
    headers: { 'Accept': 'application/json' }
});

// Interceptor de petición: agrega el header Authorization con el token de
// Sanctum almacenado en localStorage, si existe.
http.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

// Endpoints públicos: un 401 aquí significa "credenciales incorrectas", no
// "sesión caducada", así que NO deben destructurar la sesión local.
const RUTAS_PUBLICAS = ['/login', '/estudiantes/registro'];

const esRutaPublica = (url = '') => RUTAS_PUBLICAS.some((ruta) => url.includes(ruta));

// Interceptor de respuesta: cuando una petición autenticada devuelve 401, la
// sesión ya no es válida. Se limpian las credenciales y se vuelve al login con
// el router (navegación SPA) en lugar de recargar la página entera: un
// `window.location.href` abortaba las peticiones en vuelo y dejaba la pantalla
// en blanco durante el primer render tras autenticarse.
http.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401 && !esRutaPublica(error.config?.url)) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            localStorage.removeItem('perfilEstudiante');
            // Importación diferida: el router importa los módulos que importe
            // este cliente, así que una importación estática crearía un ciclo.
            import('@/router')
                .then(({ default: router }) => {
                    if (router.currentRoute.value.path !== '/login') {
                        return router.replace({ path: '/login' });
                    }
                    return undefined;
                })
                .catch(() => {});
        }
        return Promise.reject(error);
    }
);

export default http;