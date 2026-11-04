import http from '@/core/http/client';

/**
 * Servicio de notificaciones del usuario autenticado.
 * Encapsula la consulta de avisos recientes y el marcado de leídas individuales
 * o masivas contra la API.
 */
export const notificacionesService = {
    /** Lista las notificaciones recientes y el contador de no leídas. */
    index() {
        return http.get('/notificaciones');
    },
    /** Marca una notificación como leída. */
    marcarLeida(id) {
        return http.patch(`/notificaciones/${id}`, { leida: true });
    },
    /** Marca todas las notificaciones del usuario como leídas. */
    marcarTodasLeidas() {
        return http.post('/notificaciones/leer-todas');
    },
};