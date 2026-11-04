// ============================================================================
// Adaptador del flujo oficial de la TESIS DE GRADO (lado del estudiante).
//
// Toda la lógica vive en el núcleo compartido (`@/modules/tramites/utils/
// flujo`); aquí solo se declara la terminología de esta modalidad y se
// reexporta la fachada que consumen las vistas de Tesis.
// ============================================================================
import {
  FASES_FLUJO,
  countdownDe as countdownBase,
  crearDescripciones,
} from '@/modules/tramites/utils/flujo';

/** Nombre de la modalidad a la que corresponde este adaptador. */
export const MODALIDAD = 'Tesis de Grado';

/** Fases del flujo oficial del estudiante. */
export const FASES_TESIS = FASES_FLUJO;

/** Explicación amigable de cada estado para el seguimiento del estudiante. */
export const DESCRIPCION_ESTADO = crearDescripciones('tesis', 'perfil de tesis');

export {
  ESTADOS_SOLICITAR_DEFENSA,
  faseDe,
  puedeSolicitarFechaDefensa,
  reoptarInfo,
} from '@/modules/tramites/utils/flujo';

/**
 * Devuelve la fecha límite activa para mostrar la cuenta regresiva según el
 * estado actual de la tesis.
 *
 * @param {Object} tramite Trámite con `estado_actual` y `hitos`.
 * @returns {{titulo:string, fechaInicio:string, fechaLimite:string, descripcion:string}|null}
 *          Configuración del countdown o null si no aplica.
 */
export function countdownDe(tramite) {
  return countdownBase(tramite, 'tesis');
}
