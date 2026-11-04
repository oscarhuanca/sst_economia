// ============================================================================
// Utilidades del flujo oficial de titulación por módulos (núcleo compartido).
//
// Mapea cada estado de la máquina de estados (backend) a su fase del flujo, una
// descripción legible y qué hito (fecha) alimenta la cuenta regresiva.
//
// Lo comparten las modalidades que avanzan por el flujo de titulación por
// módulos (Tesis de Grado y Trabajo Dirigido): lo único que cambia son los
// textos, que se generan aquí a partir del registro de modalidades.
// ============================================================================

/**
 * Fases del flujo oficial del estudiante (comunes a todas las modalidades del
 * flujo de titulación por módulos).
 */
export const FASES_FLUJO = [
  { id: 'solicitud', label: 'Solicitud', icon: 'file-text', estados: ['solicitud_presentada'] },
  { id: 'consejo', label: 'Consejo Universitario', icon: 'shield', estados: ['pendiente_concejo_universitario', 'perfil_aprobado', 'perfil_rechazado'] },
  { id: 'investigacion', label: 'Investigación', icon: 'book', estados: ['tutor_asignado', 'tema_aprobado', 'investigacion_en_desarrollo', 'documento_final_presentado'] },
  { id: 'revision', label: 'Tribunal Revisor', icon: 'eye', estados: ['comision_revisora', 'tribunal_asignado', 'suficiente', 'insuficiente'] },
  { id: 'defensa', label: 'Defensa', icon: 'award', estados: ['solicitud_fecha_defensa', 'defensa_programada', 'defensa_en_curso', 'defensa_aprobada', 'correcciones_90_dias', 'aprobado', 'reprobado'] },
  { id: 'publicacion', label: 'Publicación', icon: 'graduation', estados: ['publicado'] },
  { id: 'reporte', label: 'Reporte', icon: 'bar-chart', estados: ['titulado'] },
];

// Estados desde los que el estudiante puede solicitar (o re-solicitar) su fecha
// de defensa.
export const ESTADOS_SOLICITAR_DEFENSA = ['suficiente', 'correcciones_90_dias', 'solicitud_fecha_defensa'];

/**
 * Explicación amigable de cada estado para el seguimiento del estudiante.
 *
 * Los textos se generan con la terminología de la modalidad: `{titulo}` es el
 * nombre del trabajo ("tesis" / "trabajo dirigido") y `{perfil}` el del primer
 * módulo ("perfil de tesis" / "perfil de proyecto").
 *
 * @param {string} titulo  Nombre del trabajo de la modalidad.
 * @param {string} perfil  Nombre del módulo de perfil de la modalidad.
 * @returns {Object<string,string>} Descripción por estado interno.
 */
export function crearDescripciones(titulo, perfil) {
  return {
    solicitud_presentada: 'Tu solicitud fue presentada. Espera la verificación de la documentación.',
    pendiente_concejo_universitario: 'Tu solicitud está pendiente de la evaluación del Consejo Universitario.',
    perfil_aprobado: `El Consejo Universitario aprobó tu ${perfil}. Se asignará un tutor.`,
    perfil_rechazado: 'El Consejo Universitario rechazó tu perfil. Corrígelo y reenvíalo.',
    tutor_asignado: 'Se te asignó un tutor. Tu investigación está por comenzar.',
    tema_aprobado: 'El Consejo Universitario aprobó tu tema y se te asignó un tutor. Continúa con el desarrollo de tu investigación.',
    investigacion_en_desarrollo: 'Periodo de investigación y desarrollo del documento final dentro del plazo establecido.',
    documento_final_presentado: 'Presentaste el documento final. Pasa a la revisión del Tribunal Revisor.',
    comision_revisora: 'El Tribunal Revisor está evaluando tu documento final.',
    tribunal_asignado: 'Se designó el tribunal revisor que evaluará tu documento final.',
    suficiente: `Tu ${titulo} fue aprobada por el Tribunal Revisor. Solicita tu fecha de defensa.`,
    insuficiente: 'El Tribunal Revisor calificó tu documento como insuficiente. Revisa tu trabajo, corrige las observaciones y vuelve a presentarlo.',
    solicitud_fecha_defensa: `Tu ${titulo} fue aprobada por el Tribunal Revisor. Solicita tu fecha de defensa; Kardex la programará.`,
    defensa_programada: 'Tu fecha de defensa está programada. Prepárate para la sustentación.',
    defensa_en_curso: 'Tu defensa está en curso. La Comisión evaluará el resultado.',
    defensa_aprobada: `¡Excelente! Aprobaste tu defensa de ${titulo} con éxito.`,
    publicado: 'Tu trabajo de investigación fue publicado. Kardex generará el reporte final.',
    correcciones_90_dias: 'Tu defensa no fue aprobada en esta oportunidad. Cuentas con 90 días para corregir y volver a solicitar una fecha de defensa.',
    aprobado: `¡Felicitaciones! Tu ${titulo} de grado fue aprobada.`,
    titulado: '¡Felicidades! Tu trámite de titulación concluyó: estás titulado.',
    reprobado: `Tu ${titulo} no fue aprobada. Consulta las alternativas disponibles.`,
    rechazado: 'Tu solicitud fue rechazada definitivamente. Consulta las alternativas disponibles.',
  };
}

/**
 * Indica si el estudiante puede solicitar (o re-solicitar) su fecha de defensa
 * en el estado actual de su trámite.
 *
 * @param {string} estado Estado interno del trámite.
 * @returns {boolean} true si el botón "Solicitar Fecha de Defensa" debe estar
 *                    disponible.
 */
export function puedeSolicitarFechaDefensa(estado) {
  return ESTADOS_SOLICITAR_DEFENSA.includes(estado);
}

/**
 * Regla de re-opción de modalidad tras una reprobación: el estudiante vuelve a
 * quedar habilitado cuando venció el plazo de correcciones (90 días) o el plazo
 * máximo de presentación (365 días). El backend es la fuente de verdad; aquí se
 * replica para informar al estudiante cuándo podrá volver a solicitar.
 *
 * @param {Object|null} tramite Trámite con `hitos`.
 * @param {{diasCorreccion?:number, diasRemodalidad?:number}} [cfg] Plazos del módulo.
 * @returns {{puede:boolean, fechaHabilitacion:string|null}} true si ya puede
 *          volver a presentar su solicitud, con la fecha de habilitación.
 */
export function reoptarInfo(tramite, cfg = {}) {
  const diasCorreccion = cfg.diasCorreccion || 90;
  const diasRemodalidad = cfg.diasRemodalidad || 365;

  if (!tramite) return { puede: true, fechaHabilitacion: null };

  const hitos = tramite.hitos || {};

  // Misma regla que el backend: vence el plazo de correcciones (90 días) o el
  // plazo máximo de presentación (365 días). Fecha límite con fallback desde la
  // fecha de reprobación.
  let limiteCorreccion = hitos.limite_correccion;
  let limitePresentacion = hitos.limite_presentacion;

  if (hitos.reprobado) {
    const reprobado = new Date(hitos.reprobado + 'T00:00:00');
    if (!limiteCorreccion) {
      limiteCorreccion = new Date(reprobado.getTime() + diasCorreccion * 86400000).toISOString().slice(0, 10);
    }
    if (!limitePresentacion) {
      limitePresentacion = new Date(reprobado.getTime() + diasRemodalidad * 86400000).toISOString().slice(0, 10);
    }
  }

  const candidatas = [];
  const agregar = (iso) => {
    if (iso) candidatas.push(new Date(iso + 'T00:00:00'));
  };
  agregar(limiteCorreccion);
  agregar(limitePresentacion);

  if (!candidatas.length) return { puede: false, fechaHabilitacion: null };

  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  const minima = new Date(Math.min(...candidatas.map((f) => f.getTime())));

  return {
    puede: hoy >= minima,
    fechaHabilitacion: minima.toISOString().slice(0, 10),
  };
}

/**
 * Devuelve la fase a la que pertenece un estado del flujo.
 *
 * @param {string} estado Nombre interno del estado.
 * @returns {{id:string,label:string,icon:string,index:number}}
 */
export function faseDe(estado) {
  const index = FASES_FLUJO.findIndex((f) => f.estados.includes(estado));
  if (index === -1) return { id: '', label: '', icon: 'clock', index: -1 };
  const { id, label, icon } = FASES_FLUJO[index];
  return { id, label, icon, index };
}

/**
 * Devuelve la fecha límite activa para mostrar la cuenta regresiva según el
 * estado actual del trámite.
 *
 * @param {Object} tramite  Trámite con `estado_actual` y `hitos`.
 * @param {string} [titulo] Nombre del trabajo (texto de la descripción).
 * @returns {{titulo:string, fechaInicio:string, fechaLimite:string, descripcion:string}|null}
 *          Configuración del countdown o null si no aplica.
 */
export function countdownDe(tramite, titulo = 'tesis') {
  const estado = tramite?.estado_actual;
  const hitos = tramite?.hitos || {};

  if (estado === 'investigacion_en_desarrollo' && hitos.limite_presentacion) {
    return {
      titulo: 'Plazo de presentación del documento final',
      fechaInicio: hitos.perfil_aprobado || null,
      fechaLimite: hitos.limite_presentacion,
      descripcion: 'Tienes este plazo para presentar el documento final desde la aprobación de tu perfil.',
    };
  }

  if (estado === 'correcciones_90_dias' && hitos.limite_correccion) {
    return {
      titulo: 'Plazo para volver a solicitar la defensa',
      fechaInicio: hitos.correcciones_90_dias || null,
      fechaLimite: hitos.limite_correccion,
      descripcion: `Tienes este plazo para corregir tu ${titulo} y volver a solicitar una fecha de defensa. Si vence, tu ${titulo} quedará reprobada.`,
    };
  }

  return null;
}
