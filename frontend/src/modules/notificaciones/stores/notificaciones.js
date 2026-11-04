import { defineStore } from 'pinia';
import { notificacionesService } from '../services/notificaciones';

/**
 * Store de notificaciones del usuario autenticado.
 *
 * Mantiene la lista de avisos recientes, el contador de no leídas y el estado de
 * carga. Aplica una caché temporal (`_ts`) para evitar peticiones repetidas.
 */
export const useNotificacionesStore = defineStore('notificaciones', {
    state: () => ({
        items: [],          // Notificaciones recientes del usuario.
        noLeidas: 0,        // Cantidad de notificaciones sin leer.
        cargando: false,    // true mientras se está consultando la API.
        _ts: {},            // Marca de tiempo de la última carga por clave.
    }),
    getters: {
        // Indica si el usuario tiene notificaciones pendientes de leer.
        tienesPendientes: (state) => state.noLeidas > 0,
    },
    actions: {
        /**
         * Comprueba si una clave de caché sigue vigente según un TTL en milisegundos.
         *
         * @param {string} clave Identificador de la caché.
         * @param {number} ttl   Duración máxima de vigencia en ms.
         * @returns {boolean} true si la caché aún es válida.
         */
        _fresco(clave, ttl) {
            return this._ts[clave] && Date.now() - this._ts[clave] < ttl;
        },
        /**
         * Carga las notificaciones desde GET /api/notificaciones.
         *
         * @param {boolean} [force=false] Si es true ignora la caché y fuerza la petición.
         * @returns {Promise<{items: Array, noLeidas: number}>} Estado cargado.
         */
        async cargar(force = false) {
            if (!force && this._fresco('notificaciones', 60000)) return { items: this.items, noLeidas: this.noLeidas };
            this.cargando = true;
            try {
                const { data } = await notificacionesService.index();
                this.items = data.notificaciones;
                this.noLeidas = data.no_leidas;
                this._ts.notificaciones = Date.now();
            } catch (error) {
                // Ante un error, degrada el estado a "sin notificaciones".
                this.items = [];
                this.noLeidas = 0;
            } finally {
                this.cargando = false;
            }
            return { items: this.items, noLeidas: this.noLeidas };
        },
        /**
         * Marca una notificación como leída, primero de forma optimista en el
         * estado local y luego persistiéndola vía PATCH /api/notificaciones/{id}.
         * Si la petición falla, recarga el estado real desde la API.
         *
         * @param {number|string} id Identificador de la notificación (`id_notificacion`).
         * @returns {Promise<void>}
         */
        async marcarLeida(id) {
            const notificacion = this.items.find((n) => n.id_notificacion === id);
            if (notificacion && !notificacion.leida) {
                notificacion.leida = true;
                this.noLeidas = Math.max(0, this.noLeidas - 1);
            }
            try {
                await notificacionesService.marcarLeida(id);
            } catch (error) {
                await this.cargar(true);
            }
        },
        /**
         * Marca todas las notificaciones como leídas vía POST /api/notificaciones/leer-todas.
         *
         * @returns {Promise<void>}
         */
        async marcarTodasLeidas() {
            try {
                await notificacionesService.marcarTodasLeidas();
                this.items.forEach((n) => (n.leida = true));
                this.noLeidas = 0;
            } catch (error) {
                /* sin cambios, se reintenta con la próxima carga */
            }
        },
    },
});