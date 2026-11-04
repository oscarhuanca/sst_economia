import http from '@/core/http/client';

/**
 * Servicio del Módulo de Tesis de Grado (lado del estudiante).
 *
 * Encapsula los endpoints del flujo oficial de la tesis: presentación de la
 * solicitud con el título obligatorio y los 3 PDFs opcionales, reenvío del
 * perfil tras su rechazo y consulta de la configuración del módulo. El trámite
 * activo se lee con el servicio genérico de trámites (comparten la misma cola).
 */
export const tesisService = {
    /** Presenta la solicitud con FormData: `titulo` (obligatorio) y los 3 PDFs (opcionales). */
    crearSolicitud(formData) {
        return http.post('/tesis/solicitud', formData);
    },
    /** Corrige el título y sube los PDFs opcionales del perfil (solicitud en verificación). */
    actualizarPerfil(id, formData) {
        return http.post(`/tesis/${id}/perfil`, formData);
    },
    /** Reenvía el perfil corregido tras su rechazo (FormData con `perfil`). */
    reenviarPerfil(id, formData) {
        return http.post(`/tesis/${id}/reenviar`, formData);
    },
    /** Reenvía el documento final corregido tras ser insuficiente (FormData con `documento_final`). */
    reenviarDocumento(id, formData) {
        return http.post(`/tesis/${id}/reenviar-documento`, formData);
    },
    /** Solicita una fecha para la defensa de la tesis (la programa Kardex). */
    solicitarFechaDefensa(id, fechaSugerida) {
        return http.post(`/tesis/${id}/solicitar-fecha-defensa`, { fecha_sugerida: fechaSugerida || null });
    },
    /** Sube el trabajo de investigación (publicación) tras aprobar la defensa. */
    subirPublicacion(id, formData) {
        return http.post(`/tesis/${id}/publicacion`, formData);
    },
    /** Configuración del módulo (plazo de presentación, días de corrección). */
    config() {
        return http.get('/tesis/config');
    },
};