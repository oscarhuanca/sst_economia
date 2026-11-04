import { defineStore } from 'pinia';
import { tesisService } from '../services/tesis';

/**
 * Store del Módulo de Tesis de Grado.
 *
 * Conserva la configuración del módulo (plazos leídos del `.env` del backend)
 * y encapsula las acciones del estudiante (solicitud y reenvío del perfil).
 * El trámite activo se sigue gestionando con el store genérico de trámites.
 */
export const useTesisStore = defineStore('tesis', {
    state: () => ({
        config: {
            plazo_presentacion_meses: 12,
            dias_correccion: 90,
            dias_remodalidad: 365,
            tipos_documento: {},
            estado_reenvio: 'pendiente_concejo_universitario',
        },
        cargandoConfig: false,
        enviando: false,
    }),
    actions: {
        /**
         * Carga la configuración del módulo desde GET /api/tesis/config.
         *
         * @returns {Promise<Object>} Configuración cargada (con fallback local).
         */
        async cargarConfig() {
            this.cargandoConfig = true;
            try {
                const { data } = await tesisService.config();
                this.config = { ...this.config, ...data };
            } catch (error) {
                // Se conservan los valores por defecto del estado.
            } finally {
                this.cargandoConfig = false;
            }
            return this.config;
        },
        /**
         * Presenta la solicitud con el título de tesis (obligatorio) y los 3
         * documentos PDF (opcionales).
         *
         * @param {FormData} formData `titulo` y documentos `documentos[...]` (nota, certificado, perfil).
         * @returns {Promise<Object>} Trámite creado.
         */
        async crearSolicitud(formData) {
            this.enviando = true;
            try {
                const response = await tesisService.crearSolicitud(formData);
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
         * @param {number|string} id Identificador del trámite de tesis.
         * @param {FormData} formData `titulo` y documentos `documentos[...]` opcionales.
         * @returns {Promise<Object>} Trámite actualizado.
         */
        async actualizarPerfil(id, formData) {
            this.enviando = true;
            try {
                const response = await tesisService.actualizarPerfil(id, formData);
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
         * @param {number|string} id Identificador del trámite de tesis.
         * @param {FormData|null} formData FormData con `titulo`, `perfil` y `observaciones`.
         * @returns {Promise<Object>} Trámite actualizado.
         */
        async reenviarPerfil(id, formData) {
            this.enviando = true;
            try {
                const response = await tesisService.reenviarPerfil(id, formData);
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
         * @param {number|string} id Identificador del trámite de tesis.
         * @param {FormData|null} formData FormData con `documento_final` y `observaciones`.
         * @returns {Promise<Object>} Trámite actualizado.
         */
        async reenviarDocumento(id, formData) {
            this.enviando = true;
            try {
                const response = await tesisService.reenviarDocumento(id, formData);
                this.enviando = false;
                return response;
            } catch (error) {
                this.enviando = false;
                throw error;
            }
        },
        /**
         * Solicita una fecha para la defensa de la tesis.
         *
         * @param {number|string} id Identificador del trámite de tesis.
         * @param {string|null} fechaSugerida Fecha sugerida (YYYY-MM-DD) o null.
         * @returns {Promise<Object>} Trámite actualizado.
         */
        async solicitarFechaDefensa(id, fechaSugerida) {
            this.enviando = true;
            try {
                const response = await tesisService.solicitarFechaDefensa(id, fechaSugerida);
                this.enviando = false;
                return response;
            } catch (error) {
                this.enviando = false;
                throw error;
            }
        },
        /**
         * Sube el trabajo de investigación (publicación) tras aprobar la defensa.
         *
         * @param {number|string} id Identificador del trámite de tesis.
         * @param {FormData} formData `documento_publicacion` y `observaciones` opcionales.
         * @returns {Promise<Object>} Trámite actualizado.
         */
        async subirPublicacion(id, formData) {
            this.enviando = true;
            try {
                const response = await tesisService.subirPublicacion(id, formData);
                this.enviando = false;
                return response;
            } catch (error) {
                this.enviando = false;
                throw error;
            }
        },
    },
});