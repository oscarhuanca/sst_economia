import http from '@/core/http/client';

function anexarDatosFormData(formData, valor, prefijo) {
    if (Array.isArray(valor)) {
        valor.forEach((item, indice) => {
            anexarDatosFormData(formData, item, `${prefijo}[${indice}]`);
        });
        return;
    }

    if (valor !== null && typeof valor === 'object') {
        Object.entries(valor).forEach(([clave, item]) => {
            anexarDatosFormData(formData, item, `${prefijo}[${clave}]`);
        });
        return;
    }

    if (valor === null || valor === undefined) return;

    formData.append(prefijo, typeof valor === 'boolean' ? (valor ? '1' : '0') : valor);
}

/**
 * Servicio del Dashboard de KARDEX.
 *
 * Encapsula los endpoints del módulo backend Kardex: búsqueda de postulantes
 * por CI o registro universitario, asignación de modalidad con generación
 * automática de credenciales y consulta del flujo del postulante. Las vistas y
 * el store del módulo lo usan en lugar de llamar a la API directamente.
 */
export const kardexService = {
    /** Lista las modalidades de titulación activas (catálogo compartido). */
    modalidades() {
        return http.get('/modalidades');
    },
    /** Busca un postulante por CI o registro universitario. */
    buscarPostulante(identificador) {
        return http.post('/kardex/postulantes/buscar', { identificador });
    },
    /** Asigna la modalidad al postulante y genera sus credenciales (una vez). */
    asignarModalidad(identificador, idModalidad) {
        return http.post('/kardex/postulantes/asignar-modalidad', {
            identificador,
            id_modalidad: idModalidad,
        });
    },
    /** Consulta el flujo actual del postulante (trámite más reciente). */
    consultar(identificador) {
        return http.get('/kardex/postulantes/consultar', { params: { identificador } });
    },
    /**
     * Guarda un módulo del flujo de titulación y avanza el estado del trámite.
     *
     * Si el módulo adjunta PDFs opcionales (por ejemplo, la Resolución de
     * designación del tutor o las Notas de Suficiencia) se envía como
     * multipart; en caso contrario se mantiene el envío JSON habitual.
     *
     * @param {number} id          Identificador del trámite.
     * @param {string} modulo      Módulo del flujo.
     * @param {Object} datos       Datos validados del formulario.
     * @param {Object<string, File>} archivos PDFs opcionales del módulo por campo.
     */
    guardarModuloFlujo(id, modulo, datos, archivos = {}) {
        const adjuntos = Object.entries(archivos || {}).filter(([, archivo]) => Boolean(archivo));

        if (!adjuntos.length) {
            return http.post(`/tramites/${id}/flujo/${modulo}`, { datos });
        }

        const formData = new FormData();
        anexarDatosFormData(formData, datos, 'datos');
        adjuntos.forEach(([campo, archivo]) => formData.append(campo, archivo));

        return http.post(`/tramites/${id}/flujo/${modulo}`, formData);
    },
    /**
     * Guarda un módulo del flujo de Examen de Grado.
     *
     * Los sorteos son de un solo paso (asistencia, tema y fecha); las notas no
     * se registran aquí, sino en el módulo `asignacion_notas`, que cierra los
     * dos exámenes con su nota final.
     *
     * @param {number} id     Identificador del trámite.
     * @param {string} modulo Módulo del flujo (ver `flujoExamen.js`).
     * @param {Object} datos  Datos validados del formulario.
     */
    guardarModuloExamen(id, modulo, datos) {
        return http.post(`/tramites/${id}/examen/${modulo}`, { datos });
    },
    /**
     * Guarda un módulo del flujo de Excelencia Académica.
     *
     * Los tres módulos son gestionados por KARDEX: verificación del promedio y
     * Resolución de admisión, registro de la Resolución del HCC con la lectura
     * del acta y reporte final, que titula al estudiante. Los informes PDF son
     * opcionales y viajan como archivos del request.
     *
     * @param {number} id     Identificador del trámite.
     * @param {string} modulo Módulo del flujo (ver `flujoExcelencia.js`).
     * @param {Object} datos  Datos validados del formulario.
     * @param {Object<string, File>} archivos PDFs opcionales del módulo por campo.
     */
    guardarModuloExcelencia(id, modulo, datos, archivos = {}) {
        const adjuntos = Object.entries(archivos || {}).filter(([, archivo]) => Boolean(archivo));

        if (!adjuntos.length) {
            return http.post(`/tramites/${id}/excelencia/${modulo}`, { datos });
        }

        const formData = new FormData();
        anexarDatosFormData(formData, datos, 'datos');
        adjuntos.forEach(([campo, archivo]) => formData.append(campo, archivo));

        return http.post(`/tramites/${id}/excelencia/${modulo}`, formData);
    },
    /**
     * Configuración del módulo de Excelencia Académica (promedio mínimo y rango
     * válido de nota).
     *
     * @returns {Promise} Respuesta con `{ modalidad, nota_minima, rango_nota }`.
     */
    configuracionExcelencia() {
        return http.get('/excelencia/config');
    },
    /**
     * Reabre un trámite de Examen de Grado reprobado o con ausencia para que el
     * estudiante vuelva a sortear el examen. Consume un intento.
     *
     * @param {number} id            Identificador del trámite.
     * @param {string} [observaciones] Motivo de la reapertura.
     */
    reabrirExamen(id, observaciones = null) {
        return http.post(`/tramites/${id}/examen/reabrir`, { observaciones });
    },
    /**
     * Verifica la unicidad de un título de tesis de grado.
     *
     * @param {string} titulo          Título a verificar.
     * @param {number} [excluirTramite] Trámite a omitir de la comparación.
     * @param {number} [excluirEstudiante] Estudiante cuyos trámites se omiten.
     */
    verificarTituloTesis(titulo, excluirTramite = null, excluirEstudiante = null) {
        return http.get('/kardex/titulos-tesis', {
            params: {
                titulo,
                excluir_tramite: excluirTramite || undefined,
                excluir_estudiante: excluirEstudiante || undefined,
            },
        });
    },
    /** Resumen estadístico del dashboard (modalidades, estudiantes y tutores). */
    resumen() {
        return http.get('/kardex/resumen');
    },
    /** Reportes estadísticos por modalidad, mes, año o rango de fechas. */
    reportes(filtros = {}) {
        return http.get('/kardex/reportes', { params: filtros });
    },
};