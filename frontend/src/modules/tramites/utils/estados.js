// ============================================================================
// Utilidades del dominio de trámites: estados del flujo de titulación.
//
// Solo contiene clasificaciones de estados (terminales, éxito, error), la
// conversión a texto legible, los tonos de color (clases Tailwind) y helpers de
// progreso. Los helpers de ROLES ya NO viven aquí: usan `@/core/roles`.
// ============================================================================

// Estados que cierran el flujo de un trámite (no permiten avanzar).
export const ESTADOS_TERMINALES = ['aprobado', 'reprobado', 'rechazado', 'titulado'];

// Estados terminales POR MODALIDAD, para los flujos que no concluyen en el mismo
// punto que el resto. Espejo de `TramiteStateService::TERMINALES_POR_MODALIDAD`.
//
// En Examen de Grado la aprobación no es terminal (todavía falta emitir el
// reporte) y reprobar NO cierra el flujo, porque el estudiante puede volver a
// solicitar el examen consumiendo otro intento.
const TERMINALES_POR_MODALIDAD = {
  'Examen de Grado': ['rechazado', 'titulado'],
};

// Estados considerados de éxito/aprobación con tono verde.
export const ESTADOS_OK = [
  'aprobado',
  'suficiente',
  'perfil_aprobado',
  'tema_aprobado',
  'tribunal_asignado',
  'defensa_aprobada',
  'publicado',
  'reporte_generado',
  'titulado',
  'monografia_aprobada',
  'conformidad_tutor',
  // Examen de Grado
  'intentos_verificados',
  'habilitado_examen',
  'examen_teorico_aprobado',
  'notas_habilitadas',
  'reporte_examen_generado',
  // Excelencia Académica
  'promedio_verificado',
  'resolucion_hcc_registrada',
];

// Estados de error/rechazo con tono rojo.
export const ESTADOS_ERROR = [
  'rechazado',
  'reprobado',
  'perfil_rechazado',
  'insuficiente',
  'monografia_rechazada',
  // Examen de Grado
  'examen_teorico_reprobado',
  'examen_especialidad_reprobado',
  'examen_ambos_reprobados',
];

/**
 * Indica si un estado cierra el flujo de la modalidad indicada.
 *
 * La mayoría de las modalidades concluyen en los estados globales, pero el
 * Examen de Grado tiene su propio punto de cierre: aprobar no es el final
 * porque falta el reporte.
 *
 * @param {string} estado          Nombre interno del estado.
 * @param {string} [modalidad]     Nombre de la modalidad de titulación.
 * @returns {boolean} Si el trámite ya no puede avanzar.
 */
export function esTerminal(estado, modalidad) {
  const terminales = TERMINALES_POR_MODALIDAD[modalidad] || ESTADOS_TERMINALES;
  return terminales.includes(estado);
}

// Etiquetas legibles de los tipos de documentos de un trámite. Cubre la
// documentación oficial de todas las modalidades; con fallback al tipo crudo.
export const ETIQUETAS_DOCUMENTO = {
  nota_solicitud: 'Nota de Solicitud',
  certificado_notas: 'Certificado de Notas',
  carta_solicitud: 'Carta de Solicitud',
  certificado_conclusion: 'Certificado de Conclusión de Estudios',
  perfil_tesis: 'Perfil de Tesis de Grado',
  documento_final: 'Documento Final',
};

/**
 * Convierte el tipo interno de un documento a una etiqueta legible.
 *
 * @param {string} tipo Tipo interno (ej. "perfil_tesis").
 * @returns {string} Etiqueta legible (con fallback formateado).
 */
export function etiquetaDocumento(tipo) {
  return ETIQUETAS_DOCUMENTO[tipo]
    || String(tipo || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (l) => l.toUpperCase());
}

/**
 * Convierte un nombre de estado a texto legible (ej. "solicitud_presentada"
 * -> "Solicitud Presentada").
 *
 * @param {string} nombre Nombre interno del estado (snake_case).
 * @returns {string} Etiqueta formateada para mostrar al usuario.
 */
export function formatoEstado(nombre) {
  return String(nombre || '')
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (l) => l.toUpperCase());
}

/**
 * Devuelve las clases Tailwind de color correspondientes al tono de un estado
 * (verde = éxito, rojo = error, índigo = neutro/en proceso).
 *
 * @param {string} estado Nombre interno del estado.
 * @returns {{soft: string, solid: string, bar: string, dot: string}} Clases
 *          para fondo suave, sólido, barra de progreso e indicador puntual.
 */
export function toneEstado(estado) {
  if (estado === 'correcciones_90_dias') {
    return {
      soft: 'bg-amber-500/20 text-amber-300 border-amber-500/30',
      solid: 'bg-amber-500',
      bar: 'bg-orange',
      dot: 'bg-amber-500',
    };
  }
  if (ESTADOS_OK.includes(estado)) {
    return {
      soft: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
      solid: 'bg-emerald-500',
      bar: 'bg-gradient-to-r from-emerald-400 to-teal-500',
      dot: 'bg-emerald-500',
    };
  }
  if (ESTADOS_ERROR.includes(estado)) {
    return {
      soft: 'bg-red-900/50 text-red-400 border-red-500/30',
      solid: 'bg-red-600',
      bar: 'bg-gradient-to-r from-red-500 to-rose-600',
      dot: 'bg-red-500',
    };
  }
  return {
    soft: 'bg-white/5 text-orange-200 border-white/20',
    solid: 'bg-orange-500',
    bar: 'bg-orange',
    dot: 'bg-orange-500',
  };
}

/**
 * Calcula el porcentaje de avance de un trámite según su posición dentro de la
 * secuencia completa de estados de la modalidad.
 *
 * @param {Object} tramite Trámite con `secuencia` (array) y `estado_actual`.
 * @returns {number} Porcentaje 0-100.
 */
export function progresoEstado(tramite) {
  // Los estados terminales cierran el flujo: aunque el estado no esté en la
  // secuencia de la modalidad (ej. "rechazado"), el trámite ya concluyó
  // y el avance es del 100%. La terminalidad depende de la modalidad: en Examen
  // de Grado "aprobado" todavía debe generar el reporte.
  if (esTerminal(tramite?.estado_actual, tramite?.modalidad?.nombre)) return 100;
  const idx = (tramite?.secuencia || []).indexOf(tramite?.estado_actual);
  if (idx === -1 || !tramite?.secuencia?.length) return 0;
  return Math.min(Math.round(((idx + 1) / tramite.secuencia.length) * 100), 100);
}

/**
 * Resumen global de un estado para insignias: Aprobado, Rechazado o En Proceso.
 *
 * @param {string} estado Nombre interno del estado.
 * @returns {{label: string, tone: string}} Etiqueta y clases de color.
 */
export function statusGlobal(estado) {
  if (estado === 'aprobado' || estado === 'titulado') return { label: 'Aprobado', tone: toneEstado(estado).soft };
  if (ESTADOS_ERROR.includes(estado)) return { label: 'Rechazado', tone: toneEstado(estado).soft };
  return { label: 'En Proceso', tone: 'bg-white/5 text-orange-200 border-white/20' };
}