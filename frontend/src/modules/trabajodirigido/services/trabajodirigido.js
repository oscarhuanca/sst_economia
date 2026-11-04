import http from '@/core/http/client';

/**
 * Servicio del Módulo de Trabajo Dirigido (lado del estudiante).
 *
 * Encapsula los endpoints del flujo oficial del trabajo dirigido: presentación
 * de la solicitud con el título obligatorio y los 3 PDFs opcionales, corrección
 * del perfil mientras se verifica, reenvío del perfil tras su rechazo, reenvío
 * del documento final insuficiente, subida del trabajo dirigido y solicitud de
 * fecha de defensa. El trámite activo se lee con el servicio genérico de
 * trámites (comparten la misma cola).
 */
export const trabajoDirigidoService = {
    /** Presenta la solicitud con FormData: `titulo` (obligatorio) y los 3 PDFs (opcionales). */
    crearSolicitud(formData) {
        return http.post('/trabajo-dirigido/solicitud', formData);
    },
    /** Corrige el título y sube los PDFs opcionales del perfil (solicitud en verificación). */
    actualizarPerfil(id, formData) {
        return http.post(`/trabajo-dirigido/${id}/perfil`, formData);
    },
    /** Reenvía el perfil corregido tras su rechazo (FormData con `perfil`). */
    reenviarPerfil(id, formData) {
        return http.post(`/trabajo-dirigido/${id}/reenviar`, formData);
    },
    /** Reenvía el documento final corregido tras ser insuficiente (FormData con `documento_final`). */
    reenviarDocumento(id, formData) {
        return http.post(`/trabajo-dirigido/${id}/reenviar-documento`, formData);
    },
    /** Sube el trabajo dirigido (publicación) tras aprobar la defensa. */
    subirPublicacion(id, formData) {
        return http.post(`/trabajo-dirigido/${id}/publicacion`, formData);
    },
    /** Solicita una fecha para la defensa del trabajo dirigido (la programa Kardex). */
    solicitarFechaDefensa(id, fechaSugerida) {
        return http.post(`/trabajo-dirigido/${id}/solicitar-fecha-defensa`, { fecha_sugerida: fechaSugerida || null });
    },
    /** Configuración del módulo (plazo de presentación, días de corrección). */
    config() {
        return http.get('/trabajo-dirigido/config');
    },
};
