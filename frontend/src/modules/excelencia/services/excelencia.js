import http from '@/core/http/client';

/**
 * Servicio del Módulo de Excelencia Académica (lado del estudiante).
 *
 * Encapsula el endpoint del flujo oficial: la solicitud con los tres PDF
 * adjuntos y la consulta de la configuración del módulo (promedio mínimo
 * reglamentario). El avance del flujo (recepción documental, verificación del
 * promedio, Resolución del HCC y reporte) lo registra KARDEX; el estudiante
 * solo presenta la solicitud y sigue su trámite.
 */
export const excelenciaService = {
    /** Presenta la solicitud con su documentación adjunta (FormData). */
    crearSolicitud(formData) {
        return http.post('/excelencia/solicitud', formData);
    },
    /**
     * Declara o corrige el promedio general antes de que KARDEX lo verifique.
     *
     * @param {number} idTramite Identificador del trámite.
     * @param {number} promedio  Promedio general declarado.
     */
    actualizarPromedio(idTramite, promedio) {
        return http.put(`/excelencia/solicitud/${idTramite}/promedio`, { promedio });
    },
    /** Configuración del módulo (promedio mínimo y rango válido de nota). */
    config() {
        return http.get('/excelencia/config');
    },
};
