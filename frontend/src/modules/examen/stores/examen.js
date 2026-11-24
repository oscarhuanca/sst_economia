import { defineStore } from 'pinia';
import { examenService } from '../services/examen';

/**
 * Store del Módulo de Examen de Grado.
 *
 * Encapsula la única acción del estudiante en este módulo: presentar la
 * solicitud. El avance del trámite se sigue con el store genérico de trámites.
 */
export const useExamenStore = defineStore('examen', {
    state: () => ({
        enviando: false,
    }),
    actions: {
        /**
         * Presenta la solicitud de Examen de Grado con los PDF adjuntos.
         *
         * @returns {Promise<Object>} Trámite creado.
         */
        async crearSolicitud(formData) {
            this.enviando = true;
            try {
                const response = await examenService.crearSolicitud(formData);
                this.enviando = false;
                return response;
            } catch (error) {
                this.enviando = false;
                throw error;
            }
        },
    },
});
