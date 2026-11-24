import http from '@/core/http/client';

/**
 * Servicio de la solicitud de Examen de Grado del estudiante.
 *
 * La solicitud envía un FormData con los dos PDF requeridos (solicitud y
 * certificado de conclusión). KARDEX verifica los documentos, revisa los
 * intentos y registra después la Resolución del HCC que habilita al estudiante.
 */
export const examenService = {
    /** Presenta la solicitud de Examen de Grado con su documentación adjunta. */
    crearSolicitud(formData) {
        return http.post('/examen/solicitud', formData);
    },
};
