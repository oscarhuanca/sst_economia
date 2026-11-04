import { defineStore } from 'pinia';
import { kardexService } from '../services/kardex';
import { tramitesService } from '@/modules/tramites/services/tramites';
import { MODALIDADES_MOCK, buscarMock, consultarMock, asignarMock, transicionarMock, asignarTutorMock, resumenMock, aplicarModuloMock, verificarTituloMock, guardarModuloExamenMock, guardarModuloExcelenciaMock, reabrirExamenMock } from '../mock/data';

// Modalidades que el KARDEX puede asignar a un postulante.
export const MODALIDADES_ASIGNABLES = ['Tesis de Grado', 'Trabajo Dirigido', 'Examen de Grado', 'Excelencia Académica'];

/**
 * Store del Dashboard de KARDEX.
 *
 * Centraliza la búsqueda de postulantes por CI/RU, la asignación de modalidad
 * con generación de credenciales y la consulta del flujo. Cuando la API no
 * responde (backend apagado o error de servidor) conmuta a "modo simulado"
 * usando datos de demostración para que el dashboard sea demostrable.
 */
export const useKardexStore = defineStore('kardex', {
    state: () => ({
        modalidades: [],            // Modalidades activas disponibles.
        postulante: null,           // Postulante encontrado (búsqueda/asignación).
        credenciales: null,         // { generadas, ya_existia, username, password }.
        tramite: null,              // Trámite actual del postulante (consulta/asignación).
        resumen: null,              // Resumen estadístico del dashboard (modalidades/estudiantes/tutores).
        verificacionTitulo: null,   // Resultado de verificar la unicidad de un título de tesis.
        verificandoTitulo: false,   // true mientras se verifica un título.
        simulado: false,            // true cuando los datos provienen del respaldo demo.
        cargando: false,            // true mientras se ejecuta una petición.
        errorMessage: null,         // Mensaje del último error mostrado en la vista.
        _ts: {},                    // Marcas de tiempo de caché.
    }),
    actions: {
        /**
         * Comprueba si una clave de caché sigue vigente.
         *
         * @returns {boolean} true si la caché aún es válida.
         */
        _fresco(clave, ttl) {
            return this._ts[clave] && Date.now() - this._ts[clave] < ttl;
        },
        /**
         * Normaliza el error de una petición: mensaje legible o activación del
         * modo simulado cuando la API no está disponible.
         *
         * @param {Error} error Error de axios.
         * @returns {null} Siempre null (convención de las acciones).
         */
        _manejarError(error) {
            // Solo hay respaldo simulado cuando la API no responde (red caída o
            // backend apagado). Un 5xx es un error real del servidor: si se
            // tratara como "sin conexión" la UI mostraría datos de demostración
            // y ocultaría el fallo.
            if (!error?.response) {
                this.simulado = true;
                return null;
            }

            this.simulado = false;
            this.errorMessage =
                error.response.data?.message ||
                error.response.data?.errors?.identificador?.[0] ||
                error.response.data?.errors?.email?.[0] ||
                error.response.data?.errors?.id_modalidad?.[0] ||
                'No se pudo completar la operación. Intente nuevamente.';
            return null;
        },
        /** Carga la lista de modalidades activas (con caché breve). */
        async cargarModalidades(force = false) {
            if (!force && this._fresco('modalidades', 300000)) return this.modalidades;
            try {
                const { data } = await kardexService.modalidades();
                this.modalidades = Array.isArray(data) ? data : [];
                this._ts.modalidades = Date.now();
            } catch (error) {
                if (this._manejarError(error) === null && !this.errorMessage) {
                    this.modalidades = [...MODALIDADES_MOCK];
                }
            }
        },
        /**
         * Carga el resumen estadístico del dashboard desde GET /kardex/resumen.
         * En modo simulado (API no disponible) usa el respaldo de demostración.
         */
        async cargarResumen(force = false) {
            if (!force && this._fresco('resumen', 30000)) return this.resumen;
            try {
                const { data } = await kardexService.resumen();
                this.simulado = false;
                this.resumen = data.resumen;
                this._ts.resumen = Date.now();
            } catch (error) {
                if (this._manejarError(error) === null && !this.errorMessage) {
                    this.resumen = resumenMock();
                }
            }
            return this.resumen;
        },
        /**
         * Busca un postulante por CI o registro universitario.
         *
         * @param {string} identificador CI o R.U.
         * @returns {Promise<Object|null>} Postulante o null (con `errorMessage`).
         */
        async buscarPostulante(identificador) {
            this.cargando = true;
            this.errorMessage = null;
            try {
                const { data } = await kardexService.buscarPostulante(identificador);
                this.simulado = false;
                this.postulante = data.postulante;
                this.credenciales = null;
                this.tramite = null;
                return this.postulante;
            } catch (error) {
                if (this._manejarError(error) === null && this.simulado) {
                    const resultado = buscarMock(identificador);
                    if (resultado) {
                        this.postulante = resultado.postulante;
                        this.credenciales = null;
                        this.tramite = null;
                        return this.postulante;
                    }
                    this.postulante = null;
                    this.errorMessage = 'No se encontró un postulante con ese CI o registro universitario.';
                    return null;
                }
                this.postulante = null;
                return null;
            } finally {
                this.cargando = false;
            }
        },
        /**
         * Asigna la modalidad de titulación al postulante y genera (o recupera)
         * sus credenciales de acceso.
         *
         * @returns {Promise<Object|null>} Respuesta `{ postulante, credenciales, tramite }`.
         */
        async asignarModalidad(identificador, idModalidad) {
            this.cargando = true;
            this.errorMessage = null;
            try {
                const { data } = await kardexService.asignarModalidad(identificador, idModalidad);
                this.simulado = false;
                this.postulante = data.postulante;
                this.credenciales = data.credenciales;
                this.tramite = data.tramite;
                return data;
            } catch (error) {
                if (this._manejarError(error) === null && this.simulado) {
                    const resultado = asignarMock(identificador, idModalidad);
                    if (resultado) {
                        this.postulante = resultado.postulante;
                        this.credenciales = resultado.credenciales;
                        this.tramite = resultado.tramite;
                        return resultado;
                    }
                    this.errorMessage = 'No se encontró un postulante con ese CI o registro universitario.';
                    return null;
                }
                return null;
            } finally {
                this.cargando = false;
            }
        },
        /**
         * Consulta el flujo actual del postulante (trámite más reciente).
         *
         * @param {string} identificador CI o R.U.
         * @returns {Promise<Object|null>} Respuesta `{ postulante, tramite }`.
         */
        async consultar(identificador) {
            this.cargando = true;
            this.errorMessage = null;
            try {
                const { data } = await kardexService.consultar(identificador);
                this.simulado = false;
                this.postulante = data.postulante;
                this.tramite = data.tramite;
                return data;
            } catch (error) {
                if (this._manejarError(error) === null && this.simulado) {
                    const resultado = consultarMock(identificador);
                    if (resultado) {
                        this.postulante = resultado.postulante;
                        this.tramite = resultado.tramite;
                        return resultado;
                    }
                    this.postulante = null;
                    this.tramite = null;
                    this.errorMessage = 'No se encontró un postulante con ese CI o registro universitario.';
                    return null;
                }
                this.postulante = null;
                return null;
            } finally {
                this.cargando = false;
            }
        },
        /**
         * Refresca en segundo plano el postulante ya visible (auto-actualización
         * de la pantalla). A diferencia de `consultar` no toca `cargando` ni
         * `errorMessage` y, si la petición falla, conserva los datos que ya están
         * en pantalla: la actualización automática nunca debe reemplazar un
         * trámite real por el respaldo de demostración.
         *
         * @param {string} identificador CI o R.U. del postulante consultado.
         * @returns {Promise<Object|null>} Datos nuevos o null si no se pudo refrescar.
         */
        async refrescar(identificador) {
            if (!identificador || this.cargando) return null;
            try {
                const { data } = await kardexService.consultar(identificador);
                this.simulado = false;
                this.postulante = data.postulante;
                this.tramite = data.tramite;
                return data;
            } catch (error) {
                return null;
            }
        },
        /**
         * Actualiza el estado del trámite (avanza por el flujo). En modo
         * simulado muta el trámite local de demostración.
         *
         * @returns {Promise<Object|null>} Trámite actualizado.
         */
        async actualizarEstado(idTramite, nuevoEstado, observaciones) {
            this.cargando = true;
            this.errorMessage = null;
            try {
                if (this.simulado && this.tramite) {
                    // Espejo de la regla del TramiteService: Investigación en
                    // Desarrollo no comienza sin un tutor asignado.
                    if (nuevoEstado === 'investigacion_en_desarrollo' && !this.tramite.id_tutor) {
                        this.errorMessage = 'Debe asignar un tutor antes de pasar a Investigación en Desarrollo.';
                        return null;
                    }
                    const actualizado = transicionarMock(this.tramite, nuevoEstado, observaciones);
                    this.tramite = actualizado;
                    return this.tramite;
                }
                const { data } = await tramitesService.transicionar(idTramite, nuevoEstado, observaciones);
                this.tramite = data;
                return this.tramite;
            } catch (error) {
                this.errorMessage =
                    error?.response?.data?.message ||
                    error?.response?.data?.errors?.nuevo_estado?.[0] ||
                    'No se pudo actualizar el estado del trámite.';
                return null;
            } finally {
                this.cargando = false;
            }
        },
        /**
         * Asigna (o reasigna) el tutor de un trámite vía
         * POST /api/tramites/{id}/asignar-tutor. En modo simulado actualiza el
         * trámite local de demostración.
         *
         * @param {number|string} id      Identificador del trámite.
         * @param {number|string} idTutor Identificador del docente.
         * @returns {Promise<Object|null>} Trámite actualizado.
         */
        async asignarTutor(idTramite, idTutor) {
            this.cargando = true;
            this.errorMessage = null;
            try {
                if (this.simulado && this.tramite) {
                    const actualizado = asignarTutorMock(this.tramite, idTutor);
                    this.tramite = actualizado;
                    return this.tramite;
                }
                const { data } = await tramitesService.asignarTutor(idTramite, idTutor);
                this.tramite = data;
                return this.tramite;
            } catch (error) {
                this.errorMessage =
                    error?.response?.data?.message ||
                    error?.response?.data?.errors?.id_tutor?.[0] ||
                    'No se pudo asignar el tutor.';
                return null;
            } finally {
                this.cargando = false;
            }
        },
        /**
         * Guarda un módulo del flujo de titulación: registra los datos del
         * formulario en `hitos.modulos`, avanza el estado del trámite al estado
         * objetivo del módulo y refresca el trámite del postulante. En modo
         * simulado muta el trámite local de demostración.
         *
         * @param {string} moduloId Identificador del módulo (perfil_tesis, ...).
         * @param {Object} datos    Datos validados del formulario del módulo.
         * @param {Object<string, File>} archivos PDFs opcionales del módulo.
         * @returns {Promise<Object|null>} Trámite actualizado.
         */
        async guardarModuloFlujo(moduloId, datos, archivos = {}) {
            this.cargando = true;
            this.errorMessage = null;
            try {
                if (!this.tramite) return null;
                if (this.simulado) {
                    const metadatos = (archivo) => ({ nombre: archivo.name, tamanio: archivo.size });
                    const datosMock = { ...datos };

                    if (archivos.resolucion_tutor) {
                        datosMock.resolucion_tutor = metadatos(archivos.resolucion_tutor);
                    }

                    if (moduloId === 'tribunal_revisor') {
                        if (archivos.resolucion_tribunal) {
                            datosMock.resolucion_tribunal = metadatos(archivos.resolucion_tribunal);
                        }

                        if (archivos.nota_suficiencia_tutor) {
                            datosMock.nota_suficiencia_tutor = metadatos(archivos.nota_suficiencia_tutor);
                        }

                        datosMock.tribunal = (datos.tribunal || []).map((miembro) => {
                            const archivo = archivos[`nota_suficiencia_${miembro.rol}`];
                            return archivo
                                ? { ...miembro, nota_suficiencia: metadatos(archivo) }
                                : miembro;
                        });
                    }

                    const actualizado = aplicarModuloMock(this.tramite, moduloId, datosMock);
                    this.tramite = actualizado;
                    return this.tramite;
                }
                const { data } = await kardexService.guardarModuloFlujo(
                    this.tramite.id_tramite,
                    moduloId,
                    datos,
                    archivos,
                );
                this.tramite = data;
                return this.tramite;
            } catch (error) {
                this.errorMessage =
                    error?.response?.data?.message ||
                    error?.response?.data?.errors?.datos?.[0] ||
                    'No se pudo guardar el módulo del flujo.';
                return null;
            } finally {
                this.cargando = false;
            }
        },
        /**
         * Verifica la unicidad de un título de tesis de grado: indica si ya
         * existe otro trámite con el mismo título y lista los trabajos más
         * parecidos. En modo simulado usa el respaldo de demostración.
         *
         * @param {string} titulo           Título a verificar.
         * @param {number|null} excluirTramite Trámite a omitir de la comparación.
         * @param {number|null} excluirEstudiante Estudiante cuyos trámites se omiten.
         * @returns {Promise<Object|null>} Verificación `{ consulta, unico, total, coincidencias }`.
         */
        async verificarTitulo(titulo, excluirTramite = null, excluirEstudiante = null) {
            this.verificandoTitulo = true;
            this.errorMessage = null;
            try {
                const { data } = await kardexService.verificarTituloTesis(titulo, excluirTramite, excluirEstudiante);
                this.simulado = false;
                this.verificacionTitulo = data.verificacion;
                return data.verificacion;
            } catch (error) {
                if (this._manejarError(error) === null && this.simulado) {
                    this.verificacionTitulo = verificarTituloMock(titulo, excluirTramite, excluirEstudiante);
                    return this.verificacionTitulo;
                }
                this.verificacionTitulo = null;
                return null;
            } finally {
                this.verificandoTitulo = false;
            }
        },
        /**
         * Guarda un módulo del flujo de Examen de Grado y avanza el estado del
         * trámite. La respuesta ya incluye `historial_examen`, que es la fuente
         * de verdad del avance del panel: los sorteos de un intento anterior no
         * cuentan después de una reapertura.
         *
         * @param {string} moduloId Identificador del módulo (sorteo_teorico, ...).
         * @param {Object} datos    Datos validados del formulario del módulo.
         * @returns {Promise<Object|null>} Trámite actualizado.
         */
        async guardarModuloExamen(moduloId, datos) {
            this.cargando = true;
            this.errorMessage = null;
            try {
                if (!this.tramite) return null;

                // Respaldo de demostración: sin API el módulo avanza contra los
                // datos simulados, incluida la fila del historial de examen.
                if (this.simulado) {
                    this.tramite = guardarModuloExamenMock(this.tramite, moduloId, datos);
                    return this.tramite;
                }

                const { data } = await kardexService.guardarModuloExamen(
                    this.tramite.id_tramite,
                    moduloId,
                    datos,
                );
                this.tramite = data;
                return this.tramite;
            } catch (error) {
                this.errorMessage = this._mensajeDeError(error, 'No se pudo guardar el módulo del examen.');
                return null;
            } finally {
                this.cargando = false;
            }
        },
        /**
         * Guarda un módulo del flujo de Excelencia Académica y avanza el estado
         * del trámite (verificación y admisión, Resolución del HCC con lectura
         * del acta o reporte final, que titula al estudiante).
         *
         * @param {string} moduloId Identificador del módulo.
         * @param {Object} datos    Datos validados del formulario del módulo.
         * @param {Object<string, File>} archivos PDFs opcionales del módulo.
         * @returns {Promise<Object|null>} Trámite actualizado.
         */
        async guardarModuloExcelencia(moduloId, datos, archivos = {}) {
            this.cargando = true;
            this.errorMessage = null;
            try {
                if (!this.tramite) return null;

                // Respaldo de demostración: sin API el módulo avanza contra los
                // datos simulados.
                if (this.simulado) {
                    const metadatos = (archivo) => ({ nombre: archivo.name, tamanio: archivo.size });
                    const datosMock = { ...datos };

                    if (archivos.informe_verificacion) {
                        datosMock.informe_verificacion = metadatos(archivos.informe_verificacion);
                    }

                    if (archivos.informe_lectura_acta) {
                        datosMock.informe_lectura_acta = metadatos(archivos.informe_lectura_acta);
                    }

                    this.tramite = guardarModuloExcelenciaMock(this.tramite, moduloId, datosMock);
                    return this.tramite;
                }

                const { data } = await kardexService.guardarModuloExcelencia(
                    this.tramite.id_tramite,
                    moduloId,
                    datos,
                    archivos,
                );
                this.tramite = data;
                return this.tramite;
            } catch (error) {
                this.errorMessage = this._mensajeDeError(error, 'No se pudo guardar el módulo de Excelencia Académica.');
                return null;
            } finally {
                this.cargando = false;
            }
        },
        /**
         * Reabre el trámite de Examen de Grado para que el estudiante vuelva a
         * sortear el examen. El backend consume un intento y devuelve el trámite
         * en `habilitado_examen`.
         *
         * @param {string} [observaciones] Motivo de la reapertura.
         * @returns {Promise<Object|null>} Trámite actualizado.
         */
        async reabrirExamen(observaciones = null) {
            this.cargando = true;
            this.errorMessage = null;
            try {
                if (!this.tramite) return null;

                if (this.simulado) {
                    this.tramite = reabrirExamenMock(this.tramite, observaciones);
                    return this.tramite;
                }

                const { data } = await kardexService.reabrirExamen(this.tramite.id_tramite, observaciones);
                this.tramite = data;
                return this.tramite;
            } catch (error) {
                this.errorMessage = this._mensajeDeError(error, 'No se pudo reabrir el examen.');
                return null;
            } finally {
                this.cargando = false;
            }
        },
        /**
         * Extrae el primer mensaje de validación de una respuesta 422.
         *
         * Los módulos de examen reportan el error en la clave que corresponde al
         * campo fallido (`datos`, `numero_intentos`, `observaciones`, `modulo`,
         * `estado_actual`), y no siempre en `datos`.
         *
         * @param {Error} error    Error de la petición.
         * @param {string} defecto Mensaje si la respuesta no trae detalles.
         * @returns {string} Mensaje para el toast.
         */
        _mensajeDeError(error, defecto) {
            const data = error?.response?.data;
            const errores = data?.errors || {};
            const primero = Object.values(errores).find((lista) => Array.isArray(lista) && lista.length);

            // Las funciones simuladas lanzan un objeto plano con `message`.
            return data?.message || primero?.[0] || error?.message || defecto;
        },
        /**
         * Limpia la verificación de título.
         */
        limpiarVerificacionTitulo() {
            this.verificacionTitulo = null;
        },
        /**
         * Obtiene reporte estadístico filtrado por modalidad, mes, año o rango de fechas.
         */
        async obtenerReporte(filtros = {}) {
            this.cargando = true;
            this.errorMessage = null;
            try {
                const { data } = await kardexService.reportes(filtros);
                this.simulado = false;
                return data.reporte || null;
            } catch (error) {
                this._manejarError(error);
                if (this.simulado && !this.errorMessage) {
                    return {
                        totales: { total: 0, en_proceso: 0, finalizados: 0, fallos: 0 },
                        por_modalidad: [],
                        por_estado: [],
                        por_mes: [],
                        listado: [],
                    };
                }
                return null;
            } finally {
                this.cargando = false;
            }
        },
        /** Limpia el estado de la búsqueda/consulta actual. */
        limpiar() {
            this.postulante = null;
            this.credenciales = null;
            this.tramite = null;
            this.errorMessage = null;
        },
    },
});