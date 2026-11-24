// ============================================================================
// Resumen del flujo para el panel de trámites (Solicitudes en Proceso y
// Trámites Concluidos).
//
// Las listas de /tramites/pendientes y /tramites/concluidos no traen
// `secuencia` ni `estados`, así que el avance genérico por secuencia siempre da
// 0%. Aquí se usa la función de progreso de CADA modalidad y los nombres de sus
// módulos, que son los mismos que se muestran en sus flujos de titulación.
// ============================================================================

import { formatoEstado, progresoEstado } from './estados';
import {
  configDeTramite,
  estadoBadgeTitulacion,
  modulosTitulacion,
  pctTitulacion,
} from './flujoTitulacion';
import {
  ESTADOS_REAPERIBLES,
  MODULOS_FLUJO_EXAMEN,
  esFlujoExamen,
  modulosExamen,
  pctExamen,
} from '@/modules/examen/utils/flujoExamen';
import {
  esFlujoExcelencia,
  modulosExcelencia,
  pctExcelencia,
} from '@/modules/excelencia/utils/flujoExcelencia';

const ETIQUETA_SOLICITUD = 'Solicitud Presentada';

const BADGES_PERFIL = {
  solicitud_presentada: ETIQUETA_SOLICITUD,
  pendiente_concejo_universitario: 'Perfil Remitido a HCC',
  perfil_rechazado: 'Perfil Rechazado',
};

function badgeDeModulo(modulos, estado) {
  return modulos.find((m) => m.estado === estado)?.badge || formatoEstado(estado);
}

function moduloEnCurso(modulos, pct) {
  if (!modulos.length || pct === 0) return null;
  return modulos.find((m) => m.enProgreso) || modulos.find((m) => !m.completado) || null;
}

function completadosPorPorcentaje(modulos, pct, tramite) {
  return modulos.map((m) => ({
    ...m,
    completado: pct >= m.pct,
    datos: tramite?.hitos?.modulos?.[m.id] || null,
  })).map((m, i, lista) => ({
    ...m,
    enProgreso: !m.completado && pct > (lista[i - 1]?.pct ?? 0),
  }));
}

/** Resumen del flujo de titulación (Tesis de Grado / Trabajo Dirigido). */
function resumenTitulacion(tramite) {
  const modulos = modulosTitulacion(tramite);
  const pct = pctTitulacion(tramite);
  const badgeEstado = estadoBadgeTitulacion(tramite);

  return {
    flujo: 'titulacion',
    pct,
    conTutor: true,
    modulos,
    etapa: moduloEnCurso(modulos, pct)?.label || ETIQUETA_SOLICITUD,
    badge: BADGES_PERFIL[badgeEstado] || badgeDeModulo(modulos, badgeEstado),
  };
}

/**
 * Resumen de Examen de Grado. El progreso oficial se calcula con
 * `historial_examen`, que las listas no traen: en ese caso se deduce del estado
 * actual usando los porcentajes de los módulos del flujo.
 */
function resumenExamen(tramite) {
  const conHistorial = Boolean(tramite?.historial_examen?.length);
  let pct;
  let modulos;

  if (conHistorial) {
    pct = pctExamen(tramite);
    modulos = modulosExamen(tramite).map((m) => ({
      ...m,
      datos: tramite?.hitos?.modulos?.[m.id] || null,
    }));
  } else {
    const estado = tramite?.estado_actual;
    pct = estado === 'titulado'
      ? 100
      : MODULOS_FLUJO_EXAMEN.find((m) => m.estado === estado)?.pct
        || (ESTADOS_REAPERIBLES.includes(estado) ? 88 : 0);
    modulos = completadosPorPorcentaje(MODULOS_FLUJO_EXAMEN, pct, tramite);
  }

  return {
    flujo: 'examen',
    pct,
    conTutor: false,
    modulos,
    etapa: moduloEnCurso(modulos, pct)?.label || ETIQUETA_SOLICITUD,
    badge: badgeDeModulo(modulos, tramite?.estado_actual),
  };
}

/** Resumen de Excelencia Académica. */
function resumenExcelencia(tramite) {
  const modulos = modulosExcelencia(tramite);
  const pct = pctExcelencia(tramite);

  return {
    flujo: 'excelencia',
    pct,
    conTutor: false,
    modulos,
    etapa: moduloEnCurso(modulos, pct)?.label || ETIQUETA_SOLICITUD,
    badge: badgeDeModulo(modulos, tramite?.estado_actual),
  };
}

/**
 * Avance y etapas del trámite según la modalidad a la que pertenece.
 *
 * @param {Object|null} tramite Trámite de alguna de las listas del panel.
 * @returns {{flujo:string, pct:number, conTutor:boolean, etapa:string,
 *            badge:string, modulos:Array}}
 */
export function resumenFlujo(tramite) {
  if (esFlujoExamen(tramite)) return resumenExamen(tramite);
  if (esFlujoExcelencia(tramite)) return resumenExcelencia(tramite);
  if (configDeTramite(tramite)) return resumenTitulacion(tramite);

  return {
    flujo: 'generico',
    pct: progresoEstado(tramite),
    conTutor: Boolean(tramite?.tutor),
    modulos: [],
    etapa: formatoEstado(tramite?.estado_actual) || ETIQUETA_SOLICITUD,
    badge: formatoEstado(tramite?.estado_actual),
  };
}
