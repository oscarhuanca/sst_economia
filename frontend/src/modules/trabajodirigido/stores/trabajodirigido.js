import { defineStore } from 'pinia';
import { trabajoDirigidoService } from '../services/trabajodirigido';

/**
 * Store del Módulo de Trabajo Dirigido.
 *
 * Conserva la configuración del módulo (plazos leídos del `.env` del backend)
 * y encapsula las acciones del estudiante (solicitud, corrección y reenvío del
 * perfil). El trámite activo se sigue gestionando con el store genérico de
 * trámites.
 */
export const useTrabajoDirigidoStore = defineStore('trabajodirigido', {
    state: () => ({
        config: {
            plazo_presentacion_meses: 12,
            dias_correccion: 90,
            dias_remodalidad: 365,
            tipos_documento: {},
            tipos_documento_publicacion: {},
            estado_reenvio: 'pendiente_concejo_universitario',
        },
        cargandoConfig: false,
        enviando: false,
    }),
    actions: {
        /**
         * Carga la configuración del módulo desde GET /api/trabajo-dirigido/config.
         *
         * @returns {Promise<Object>} Configuración cargada (con fallback local).
         */
        async cargarConfig() {
            this.cargandoConfig = true;
            try {
                const { data } = await trabajoDirigidoService.config();
                this.config = { ...this.config, ...data };
            } catch (error) {
                // Se conservan los valores por defecto del estado.
            } finally {
                this.cargandoConfig = false;
            }
            return this.config;
        },
        /**
         * Presenta la solicitud con el título del trabajo dirigido (obligatorio) y
         * los 3 documentos PDF (opcionales).
         *
         * @param {FormData} formData `titulo` y documentos `documentos[...]`.
         * @returns {Promise<Object>} Trámite creado.
         */
        async crearSolicitud(formData) {
            this.enviando = true;
            try {
                const response = await trabajoDirigidoService.crearSolicitud(formData);
                this.enviando = false;
                return response;
            } catch (error) {
                this.enviando = false;
                throw error;
            }
        },
        /**
         * Corrige el título del perfil y sube los PDFs opcionales mientras la
         * solicitud sigue en verificación.
         *
         * @param {number|string} id Identificador del trámite.
         * @param {FormData} formData `titulo` y documentos `documentos[...]` opcionales.
         * @returns {Promise<Object>} Trámite actualizado.
         */
        async actualizarPerfil(id, formData) {
            this.enviando = true;
            try {
                const response = await trabajoDirigidoService.actualizarPerfil(id, formData);
                this.enviando = false;
                return response;
            } catch (error) {
                this.enviando = false;
                throw error;
            }
        },
        /**
         * Reenvía el perfil corregido con un título diferente al rechazado.
         *
         * @param {number|string} id Identificador del trámite.
         * @param {FormData|null} formData FormData con `titulo`, `perfil` y `observaciones`.
         * @returns {Promise<Object>} Trámite actualizado.
         */
        async reenviarPerfil(id, formData) {
            this.enviando = true;
            try {
                const response = await trabajoDirigidoService.reenviarPerfil(id, formData);
                this.enviando = false;
                return response;
            } catch (error) {
                this.enviando = false;
                throw error;
            }
        },
        /**
         * Reenvía el documento final corregido tras su calificación insuficiente.
         *
         * @param {number|string} id Identificador del trámite.
         * @param {FormData|null} formData FormData con `documento_final` y `observaciones`.
         * @returns {Promise<Object>} Trámite actualizado.
         */
        async reenviarDocumento(id, formData) {
            this.enviando = true;
            try {
                const response = await trabajoDirigidoService.reenviarDocumento(id, formData);
                this.enviando = false;
                return response;
            } catch (error) {
                this.enviando = false;
                throw error;
            }
        },
        /**
         * Solicita una fecha para la defensa del trabajo dirigido.
         *
         * @param {number|string} id Identificador del trámite.
         * @param {string|null} fechaSugerida Fecha sugerida (YYYY-MM-DD) o null.
         * @returns {Promise<Object>} Trámite actualizado.
         */
        async solicitarFechaDefensa(id, fechaSugerida) {
            this.enviando = true;
            try {
                const response = await trabajoDirigidoService.solicitarFechaDefensa(id, fechaSugerida);
                this.enviando = false;
                return response;
            } catch (error) {
                this.enviando = false;
                throw error;
            }
        },
        /**
         * Sube el trabajo dirigido (publicación) tras aprobar la defensa.
         *
         * @param {number|string} id Identificador del trámite.
         * @param {FormData} formData `documento_publicacion` y `observaciones` opcionales.
         * @returns {Promise<Object>} Trámite actualizado.
         */
        async subirPublicacion(id, formData) {
            this.enviando = true;
            try {
                const response = await trabajoDirigidoService.subirPublicacion(id, formData);
                this.enviando = false;
                return response;
            } catch (error) {
                this.enviando = false;
                throw error;
            }
        },
    },
});
