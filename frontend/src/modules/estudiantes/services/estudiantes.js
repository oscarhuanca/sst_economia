import http from '@/core/http/client';

/**
 * Servicio de estudiantes. Encapsula los endpoints del dominio estudiante:
 * el auto-registro público del estudiante (desde el login), la consulta en
 * modo lectura de su perfil y la gestión administrativa del perfil (alta y
 * listados) que alimenta el panel del admin.
 */
export const estudianteService = {
    /** Auto-registro del estudiante desde el login (endpoint público). */
    registro(datos) {
        return http.post('/estudiantes/registro', datos);
    },
    /** Devuelve el perfil aislado del estudiante autenticado (o null si no existe). */
    perfil() {
        return http.get('/estudiante/perfil');
    },

    /** Listado paginado de estudiantes (solo admin). */
    index(params) {
        return http.get('/estudiantes', { params });
    },
    /** Crea el perfil aislado de un estudiante (solo admin, botón CREAR ESTUDIANTE). */
    crear(datos) {
        return http.post('/estudiantes', datos);
    },
    /** Listado paginado de estudiantes SIN cuenta de acceso (solo admin, CREAR USUARIO). */
    sinUsuario(params) {
        return http.get('/estudiantes/sin-usuario', { params });
    },
};