// ============================================================================
// Utilidades del flujo oficial del TRABAJO DIRIGIDO (lado del estudiante).
//
// Adaptador del núcleo compartido (`@/modules/tramites/utils/flujo`): el flujo
// de estados es el mismo que el de Tesis de Grado; lo único que cambia son los
// textos, que hablan de "trabajo dirigido" y "perfil de proyecto".
// ============================================================================
import {
  FASES_FLUJO,
  countdownDe as countdownBase,
  crearDescripciones,
  faseDe,
  puedeSolicitarFechaDefensa,
  reoptarInfo,
} from '@/modules/tramites/utils/flujo';

/** Nombre de la modalidad a la que corresponde este adaptador. */
export const MODALIDAD = 'Trabajo Dirigido';

/** Fases del flujo oficial del estudiante. */
export const FASES_TRABAJO_DIRIGIDO = FASES_FLUJO;

/** Explicación amigable de cada estado para el seguimiento del estudiante. */
export const DESCRIPCION_ESTADO = crearDescripciones('trabajo dirigido', 'perfil de proyecto');

export {
  ESTADOS_SOLICITAR_DEFENSA,
  faseDe,
  puedeSolicitarFechaDefensa,
  reoptarInfo,
} from '@/modules/tramites/utils/flujo';

/**
 * Devuelve la fecha límite activa para mostrar la cuenta regresiva según el
 * estado actual del trámite.
 *
 * @param {Object} tramite Trámite con `estado_actual` y `hitos`.
 * @returns {{titulo:string, fechaInicio:string, fechaLimite:string, descripcion:string}|null}
 */
export function countdownDe(tramite) {
  return countdownBase(tramite, 'trabajo dirigido');
}
