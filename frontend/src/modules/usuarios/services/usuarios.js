import http from '@/core/http/client';

/**
 * Servicio de gestión de usuarios (Panel de Administración).
 * Encapsula el CRUD de la matriz de actores (listado, alta, actualización y
 * baja lógica), el alta automática de credenciales para estudiantes ya
 * registrados (CREAR USUARIO) y el catálogo de docentes activos.
 */
export const usuariosService = {
    /** Lista los usuarios activos de la plataforma (respuesta paginada { data, meta }). */
    index(params = {}) {
        return http.get('/usuarios', { params: { page: 1, per_page: 20, ...params } });
    },
    /** Totales de usuarios activos por rol (tarjetas del panel de administración). */
    estadisticas() {
        return http.get('/usuarios/estadisticas');
    },
    /** Crea un usuario manualmente (rol no estudiante; el username lo asigna el admin). */
    crear(datos) {
        return http.post('/usuarios', datos);
    },
    /** Actualiza un usuario (datos de acceso, rol y, opcionalmente, contraseña). */
    actualizar(id, datos) {
        return http.put(`/usuarios/${id}`, datos);
    },
    /**
     * Alta automática de la cuenta de acceso de un estudiante ya registrado.
     * El admin solo aporta el identificador (CI o registro universitario);
     * el sistema genera username y contraseña y los devuelve una única vez.
     */
    altaEstudiante(identificador) {
        return http.post('/usuarios/alta', { identificador });
    },
    /** Da de baja lógica a un usuario. */
    eliminar(id) {
        return http.delete(`/usuarios/${id}`);
    },
    /** Lista los usuarios con rol docente (para el selector de tutor). */
    docentes() {
        return http.get('/usuarios/docentes');
    },
};