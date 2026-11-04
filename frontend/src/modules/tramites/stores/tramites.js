import { defineStore } from 'pinia';
import { tramitesService } from '../services/tramites';

/**
 * Store de trámites de titulación.
 *
 * Centraliza el estado relacionado con trámites: modalidades disponibles,
 * trámites pendientes, estadísticas, trámite activo, tutorías del docente y
 * catálogo de docentes. Usa caché temporal (`_ts`) para evitar peticiones
 * repetidas cuando estén vigentes.
 *
 * NOTA: el perfil académico del estudiante vive en el store `estudiantes`
 * (módulo Estudiantes); aquí solo se gestiona lo estrictamente del dominio
 * de trámites.
 */
export const useTramitesStore = defineStore('tramites', {
    state: () => ({
        modalidades: [],                                                        // Modalidades activas disponibles.
        tramitesPendientes: [],                                                 // Solicitudes en proceso (Kardex/Dirección).
        tramitesConcluidos: [],                                                  // Trámites que finalizaron su flujo.
        metaPendientes: null,                                                   // Meta { per_page, has_more } de la última página de pendientes.
        metaConcluidos: null,                                                   // Meta de la última página de concluidos.
        paginaPendientes: 1,                                                    // Página actual de pendientes.
        paginaConcluidos: 1,                                                    // Página actual de concluidos.
        estadisticas: { totales: { aprobados: 0, reprobados: 0, total: 0 }, porModalidad: [] },
        tramiteActivo: null,                                                    // Trámite más reciente del estudiante.
        tutorias: [],                                                           // Trámites donde el docente es tutor.
        docentes: [],                                                           // Usuarios con rol docente (para asignar tutor).
        cargandoTramite: false,                                                 // true mientras se carga el trámite activo.
        _ts: {},                                                                // Marcas de tiempo de la última carga por clave.
    }),
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
         * Carga la lista de modalidades activas desde GET /api/modalidades.
         *
         * @param {boolean} [force=false] Si es true ignora la caché.
         * @returns {Promise<void>}
         */
        async cargarModalidades(force = false) {
            if (!force && this._fresco('modalidades', 300000)) return this.modalidades;
            try {
                const { data } = await tramitesService.modalidades();
                this.modalidades = data;
                this._ts.modalidades = Date.now();
            } catch (error) {
                this.modalidades = [];
            }
        },
        /**
         * Carga el trámite activo del estudiante desde GET /api/estudiante/tramite-activo.
         *
         * @param {boolean} [silencioso=false] En modo silencioso (seguimiento en
         *        segundo plano) no mueve el indicador de carga ni descarta el
         *        último trámite conocido si la petición falla, para que un fallo
         *        puntual de red no vacíe la vista del estudiante.
         * @returns {Promise<Object|null>} Trámite activo o null si no existe.
         */
        async cargarTramiteActivo(silencioso = false) {
            if (!silencioso) this.cargandoTramite = true;
            try {
                const { data } = await tramitesService.tramiteActivo();
                this.tramiteActivo = data?.id_tramite ? data : null;
            } catch (error) {
                if (!silencioso) this.tramiteActivo = null;
            } finally {
                if (!silencioso) this.cargandoTramite = false;
            }
            return this.tramiteActivo;
        },
        /**
         * Crea un nuevo trámite enviando un FormData multipart a POST /api/tramites.
         *
         * @param {FormData} formData Contiene `id_modalidad` y los documentos `documentos[...]`.
         * @returns {Promise<Object>} Respuesta de la API (trámite creado).
         */
        async iniciarTramite(formData) {
            const response = await tramitesService.crear(formData);
            await this.cargarTramiteActivo();
            return response;
        },
        /**
         * Carga las solicitudes pendientes desde GET /api/tramites/pendientes (primera página).
         *
         * @param {boolean} [force=false] Si es true ignora la caché.
         * @returns {Promise<void>}
         */
        async cargarPendientes(force = false) {
            if (!force && this._fresco('pendientes', 30000)) return this.tramitesPendientes;
            await this.irPagina('pendientes', 1);
            return this.tramitesPendientes;
        },
        /**
         * Carga los trámites concluidos desde GET /api/tramites/concluidos (primera página).
         *
         * @param {boolean} [force=false] Si es true ignora la caché.
         * @returns {Promise<void>}
         */
        async cargarConcluidos(force = false) {
            if (!force && this._fresco('concluidos', 30000)) return this.tramitesConcluidos;
            await this.irPagina('concluidos', 1);
            return this.tramitesConcluidos;
        },
        /**
         * Solicita una página concreta de pendientes o concluidos (paginación lazy).
         *
         * @param {'pendientes'|'concluidos'} pestana Lista a paginar.
         * @param {number} pagina Número de página a cargar.
         * @returns {Promise<void>}
         */
        async irPagina(pestana, pagina) {
            const esConcluidos = pestana === 'concluidos';
            const params = { page: pagina, per_page: 20 };
            const { data } = esConcluidos
                ? await tramitesService.concluidos(params)
                : await tramitesService.pendientes(params);
            if (esConcluidos) {
                this.tramitesConcluidos = data.data;
                this.metaConcluidos = data.meta;
                this.paginaConcluidos = pagina;
            } else {
                this.tramitesPendientes = data.data;
                this.metaPendientes = data.meta;
                this.paginaPendientes = pagina;
            }
            this._ts[pestana] = Date.now();
        },
        /**
         * Carga las estadísticas de aprobados/reprobados por modalidad desde
         * GET /api/tramites/estadisticas.
         *
         * @param {boolean} [force=false] Si es true ignora la caché.
         * @returns {Promise<Object>} Estructura `{ totales, porModalidad }`.
         */
        async cargarEstadisticas(force = false) {
            if (!force && this._fresco('estadisticas', 30000)) return this.estadisticas;
            try {
                const { data } = await tramitesService.estadisticas();
                this.estadisticas = data;
                this._ts.estadisticas = Date.now();
            } catch (error) {
                this.estadisticas = { totales: { aprobados: 0, reprobados: 0, total: 0 }, porModalidad: [] };
            }
            return this.estadisticas;
        },
        /**
         * Aprueba o rechaza la documentación inicial de un trámite vía
         * POST /api/tramites/{id}/revisar; refresca la lista de pendientes.
         *
         * @param {number|string} id          Identificador del trámite.
         * @param {string} accion              'aprobar' | 'rechazar'.
         * @param {string} observaciones       Motivo/comentario del revisor.
         * @returns {Promise<Object>} Trámite actualizado.
         */
        async revisarTramite(id, accion, observaciones) {
            const { data } = await tramitesService.revisar(id, accion, observaciones);
            await this.cargarPendientes(true);
            return data;
        },
        /**
         * Carga las tutorías del docente autenticado desde GET /api/tutorias.
         *
         * @param {boolean} [force=false] Si es true ignora la caché.
         * @returns {Promise<void>}
         */
        async cargarTutorias(force = false) {
            if (!force && this._fresco('tutorias', 30000)) return this.tutorias;
            try {
                const { data } = await tramitesService.tutorias();
                this.tutorias = data.data;
                this._ts.tutorias = Date.now();
            } catch (error) {
                this.tutorias = [];
            }
        },
        /**
         * Carga la lista de docentes activos desde GET /api/usuarios/docentes.
         *
         * @param {boolean} [force=false] Si es true ignora la caché.
         * @returns {Promise<void>}
         */
        async cargarDocentes(force = false) {
            if (!force && this._fresco('docentes', 300000)) return this.docentes;
            try {
                const { data } = await tramitesService.docentes();
                this.docentes = data;
                this._ts.docentes = Date.now();
            } catch (error) {
                this.docentes = [];
            }
        },
        /**
         * Asigna (o reasigna) el tutor de un trámite vía
         * POST /api/tramites/{id}/asignar-tutor.
         *
         * @param {number|string} id      Identificador del trámite.
         * @param {number|string} idTutor Identificador del usuario docente.
         * @returns {Promise<Object>} Trámite actualizado con el tutor asignado.
         */
        async asignarTutor(id, idTutor) {
            const { data } = await tramitesService.asignarTutor(id, idTutor);
            this.cargarDocentes(true);
            return data;
        }
    }
});