import http from '@/core/http/client';

/**
 * Servicio de la solicitud de Examen de Grado del estudiante.
 *
 * La solicitud no lleva datos ni documentos: KARDEX verifica los intentos y
 * registra después la Resolución del HCC que habilita al estudiante.
 */
export const examenService = {
    /** Presenta la solicitud de Examen de Grado. */
    crearSolicitud() {
        return http.post('/examen/solicitud');
    },
};
