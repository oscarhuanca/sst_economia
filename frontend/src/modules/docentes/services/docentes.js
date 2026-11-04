import http from '@/core/http/client';

/**
 * Servicio de docentes (registro académico independiente).
 * Encapsula el CRUD administrativo de la tabla `docentes` y el catálogo
 * compacto para los roles de gestión.
 */
export const docentesService = {
    /** Lista paginada de docentes (solo admin). */
    index(params = {}) {
        return http.get('/docentes', { params: { page: 1, per_page: 20, ...params } });
    },
    /** Crea un docente en el registro académico (solo admin). */
    crear(datos) {
        return http.post('/docentes', datos);
    },
    /** Actualiza un docente (solo admin). */
    actualizar(id, datos) {
        return http.put(`/docentes/${id}`, datos);
    },
    /** Elimina un docente del registro (solo admin). */
    eliminar(id) {
        return http.delete(`/docentes/${id}`);
    },
    /** Catálogo compacto de docentes (roles de gestión). */
    catalogo() {
        return http.get('/docentes/catalogo');
    },
};