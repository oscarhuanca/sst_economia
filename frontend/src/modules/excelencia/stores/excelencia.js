import { defineStore } from 'pinia';
import { excelenciaService } from '../services/excelencia';

/**
 * Store del Módulo de Excelencia Académica.
 *
 * Conserva la configuración del módulo (promedio mínimo reglamentario, leído
 * del backend) y encapsula la acción del estudiante: presentar la solicitud
 * con su promedio general declarado. El avance del trámite se sigue con el
 * store genérico de trámites.
 */
export const useExcelenciaStore = defineStore('excelencia', {
    state: () => ({
        config: {
            modalidad: 'Excelencia Académica',
            nota_minima: 80,
            rango_nota: [0, 100],
        },
        cargandoConfig: false,
        enviando: false,
        guardandoPromedio: false,
    }),
    actions: {
        /**
         * Carga la configuración del módulo desde GET /api/excelencia/config.
         *
         * @returns {Promise<Object>} Configuración cargada (con fallback local).
         */
        async cargarConfig() {
            this.cargandoConfig = true;
            try {
                const { data } = await excelenciaService.config();
                this.config = { ...this.config, ...data };
            } catch (error) {
                // Se conservan los valores por defecto del estado.
            } finally {
                this.cargandoConfig = false;
            }
            return this.config;
        },
        /**
         * Presenta la solicitud de Excelencia Académica.
         *
         * @param {number|string} promedio      Promedio general declarado.
         * @param {string|null}  observaciones Observación opcional del estudiante.
         * @returns {Promise<Object>} Trámite creado.
         */
        async crearSolicitud(promedio, observaciones = null) {
            this.enviando = true;
            try {
                const response = await excelenciaService.crearSolicitud(promedio, observaciones);
                this.enviando = false;
                return response;
            } catch (error) {
                this.enviando = false;
                throw error;
            }
        },
        /**
         * Declara o corrige el promedio general mientras KARDEX aún no lo
         * verifica. Devuelve el trámite actualizado con el nuevo valor.
         *
         * @param {number} idTramite Identificador del trámite.
         * @param {number} promedio  Promedio general declarado.
         * @returns {Promise<Object>} Trámite actualizado.
         */
        async actualizarPromedioDeclarado(idTramite, promedio) {
            this.guardandoPromedio = true;
            try {
                const { data } = await excelenciaService.actualizarPromedio(idTramite, promedio);
                return data;
            } finally {
                this.guardandoPromedio = false;
            }
        },
    },
});
