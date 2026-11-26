// ============================================================================
// Flujo de Excelencia Académica gestionado por KARDEX.
//
// Describe la modalidad como una secuencia de 6 módulos:
//
//  1. `inicio_tramite` — el estudiante presentó la solicitud con los tres PDF
//     obligatorios; KARDEX puede verlos desde la vista del trámite.
//  2. `designacion_tribunal` — se registra la Resolución del HCC que designa
//     al Tribunal de Evaluación (número, fecha y PDF opcional).
//  3. `informe_tribunal` — se registra el Informe de Evaluación del Tribunal
//     con el Tribunal 1 y el Tribunal 2 elegidos del catálogo de docentes, y su
//     PDF opcional.
//  4. `resolucion_hcc` — se registran las calificaciones por área y la
//     calificación final ponderada del postulante.
//  5. `acta_lectura` — se genera el acta y se registra la fecha de su lectura.
//  6. `reporte` — se genera el reporte final y el estudiante queda `titulado`.
//
// El estado actual del trámite es la fuente de verdad del avance.
// Espejo de `App\Modules\Excelencia\Services\ExcelenciaService`.
// ============================================================================

// Módulos del flujo en orden de ejecución.
export const MODULOS_FLUJO_EXCELENCIA = [
  {
    id: 'inicio_tramite',
    label: 'Inicio de Trámite',
    estado: 'solicitud_presentada',
    pct: 20,
    icon: 'inbox',
    badge: 'Solicitud Presentada',
  },
  {
    id: 'designacion_tribunal',
    label: 'Designación de Tribunal de Evaluación',
    estado: 'tribunal_designado',
    pct: 36,
    icon: 'users',
    badge: 'Tribunal Designado',
  },
  {
    id: 'informe_tribunal',
    label: 'Informe de Evaluación del Tribunal',
    estado: 'informe_tribunal_registrado',
    pct: 52,
    icon: 'clipboard',
    badge: 'Informe Registrado',
  },
  {
    id: 'resolucion_hcc',
    label: 'Calificación',
    estado: 'resolucion_hcc_registrada',
    pct: 68,
    icon: 'shield',
    badge: 'Calificación Registrada',
  },
  {
    id: 'acta_lectura',
    label: 'Generación y lectura del acta',
    estado: 'titulado',
    pct: 100,
    icon: 'file-text',
    badge: 'Titulado',
  },
  {
    id: 'reporte',
    label: 'Reporte',
    estado: 'titulado',
    pct: 100,
    icon: 'file-text',
    badge: 'Reporte',
  },
];

/** Secuencia de estados del flujo, en orden. */
export const ORDEN_ESTADOS = [
  'solicitud_presentada',
  'tribunal_designado',
  'informe_tribunal_registrado',
  'resolucion_hcc_registrada',
  'titulado',
];

const PORCENTAJES = [20, 36, 52, 68, 100];

/** Rango y mínimo de nota (espejo de `config/excelencia.php`, solo legado). */
export const RANGO_NOTA = [0, 100];
export const NOTA_MINIMA = 80;

/**
 * PDFs que el estudiante adjunta al presentar la solicitud. KARDEX los
 * recepciona antes de continuar con el flujo.
 */
export const TIPOS_DOCUMENTO_EXCELENCIA = [
  { tipo: 'nota_solicitud', etiqueta: 'Nota de Solicitud' },
  { tipo: 'certificado_calificaciones', etiqueta: 'Certificado de Calificaciones' },
  { tipo: 'record_academico', etiqueta: 'Récord Académico' },
];

/** PDFs presentados por el estudiante, ordenados según el catálogo del módulo. */
export function documentosSolicitud(tramite) {
  const orden = TIPOS_DOCUMENTO_EXCELENCIA.map((d) => d.tipo);

  return [...(tramite?.documentos || [])]
    .filter((documento) => orden.includes(documento.tipo_documento))
    .sort((a, b) => orden.indexOf(a.tipo_documento) - orden.indexOf(b.tipo_documento));
}

export const AREAS_CALIFICACION = [
  { campo: 'calificacion_cuantitativa', etiqueta: 'Área Cuantitativa', porcentaje: 40 },
  { campo: 'calificacion_teoria_economica', etiqueta: 'Área de Teoría Económica', porcentaje: 40 },
  { campo: 'calificacion_materias_complementarias', etiqueta: 'Área de materias complementarias', porcentaje: 20 },
];

export function calificacionFinal(datos = {}) {
  const valores = AREAS_CALIFICACION.map(({ campo }) => datos[campo]);

  if (valores.some((valor) => valor === '' || valor === null || valor === undefined || !Number.isFinite(Number(valor)))) {
    return null;
  }

  const total = AREAS_CALIFICACION.reduce(
    (suma, area, indice) => suma + Number(valores[indice]) * (area.porcentaje / 100),
    0,
  );

  return Math.round((total + Number.EPSILON) * 100) / 100;
}

export function formatoCalificacion(valor) {
  if (valor === null || valor === undefined || valor === '') return '—';
  const numero = Number(valor);
  return Number.isFinite(numero) ? numero.toFixed(2) : '—';
}

/** Opciones del resultado de la evaluación del tribunal. */
export const RESULTADOS_EVALUACION = [
  { valor: 'correcto', etiqueta: 'Correcto', texto: 'Aprueba la Titulación por Excelencia del postulante' },
  { valor: 'faltante', etiqueta: 'Faltante', texto: 'Observado por falta de documentacion' },
  { valor: 'incorrecto', etiqueta: 'Incorrecto', texto: 'El promedio calculado no es mayor o igual a 80 puntos' },
];

export function resultadoEvaluacion(valor) {
  return RESULTADOS_EVALUACION.find((opcion) => opcion.valor === valor) || null;
}

export const MODULO_INICIO = 'inicio_tramite';
export const MODULO_DESIGNACION = 'designacion_tribunal';
export const MODULO_INFORME = 'informe_tribunal';
export const MODULO_RESOLUCION = 'resolucion_hcc';
export const MODULO_ACTA = 'acta_lectura';
export const MODULO_REPORTE = 'reporte';

/** Módulo heredado (ya no se registra, se conserva solo para datos antiguos). */
export const MODULO_VERIFICACION = 'verificacion_promedio';

/** Indica si el trámite pertenece a la modalidad de Excelencia Académica. */
export function esFlujoExcelencia(tramite) {
  return tramite?.modalidad?.nombre === 'Excelencia Académica';
}

/**
 * Estado de cada módulo del flujo para el trámite dado.
 *
 * @returns {Array<{id,label,estado,pct,icon,badge,completado,enProgreso}>}
 */
export function modulosExcelencia(tramite) {
  const actual = tramite?.estado_actual;
  const estadoNorm = actual === 'acta_lectura_registrada' ? 'titulado' : actual;
  const indice = ORDEN_ESTADOS.indexOf(estadoNorm);
  const actaHecha = Boolean(tramite?.hitos?.modulos?.[MODULO_ACTA]?.verificado) || estadoNorm === 'titulado';

  return MODULOS_FLUJO_EXCELENCIA.map((m, i) => {
    if (m.id === MODULO_ACTA) {
      return { ...m, completado: actaHecha, enProgreso: actual === 'resolucion_hcc_registrada' && !actaHecha };
    }
    if (m.id === MODULO_REPORTE) {
      return { ...m, completado: actaHecha, enProgreso: false };
    }
    return {
      ...m,
      completado: indice >= i,
      enProgreso: indice >= 0 && indice === i - 1,
    };
  });
}

/** Porcentaje de avance (0-100) según el estado del trámite. */
export function pctExcelencia(tramite) {
  const actual = tramite?.estado_actual === 'acta_lectura_registrada' ? 'titulado' : tramite?.estado_actual;
  const indice = ORDEN_ESTADOS.indexOf(actual);
  if (indice === -1) return 0;
  return PORCENTAJES[indice] ?? 0;
}

/**
 * Índice del primer módulo pendiente, o -1 si el flujo ya no admite más avances.
 */
export function proximoModuloPendiente(tramite) {
  if (tramite?.estado_actual === 'rechazado') return -1;
  return modulosExcelencia(tramite).findIndex((m) => !m.completado && m.id !== MODULO_REPORTE);
}

/** Datos guardados de un módulo (desde `hitos.modulos`). */
export function datosDeModulo(tramite, moduloId) {
  return tramite?.hitos?.modulos?.[moduloId] || null;
}

/** Resolución HCC de designación del Tribunal de Evaluación (paso 2). */
export function designacionTribunal(tramite) {
  const datos = datosDeModulo(tramite, MODULO_DESIGNACION) || {};

  return {
    numero_resolucion: datos.numero_resolucion || null,
    fecha_resolucion: datos.fecha_resolucion || null,
    resolucion_tribunal: datos.resolucion_tribunal || null,
  };
}

/** Informe de Evaluación del Tribunal (paso 3). */
export function informeTribunal(tramite) {
  const datos = datosDeModulo(tramite, MODULO_INFORME) || {};

  return {
    tribunal_1: datos.tribunal_1 || null,
    tribunal_2: datos.tribunal_2 || null,
    informe_evaluacion_tribunal: datos.informe_evaluacion_tribunal || null,
  };
}

/** Calificación registrada para el trámite (paso 4). */
export function resolucionHcc(tramite) {
  const datos = datosDeModulo(tramite, MODULO_RESOLUCION) || {};

  return {
    ...Object.fromEntries(AREAS_CALIFICACION.map(({ campo }) => [campo, datos[campo] ?? null])),
    calificacion_final: datos.calificacion_final ?? null,
  };
}

/** Acta generada y fecha de lectura registradas en el trámite (paso 5). */
export function actaLectura(tramite) {
  const datos = datosDeModulo(tramite, MODULO_ACTA) || {};

  return {
    fecha_lectura: datos.fecha_lectura || null,
    generada: Boolean(datos.verificado),
  };
}

/** Alias legado: la resolución que antes se mostraba como de admisión. */
export function resolucionAdmision(tramite) {
  return designacionTribunal(tramite);
}

/** Helpers legados: el promedio ya no interviene en el flujo. */
export function promedioDeclarado() {
  return null;
}

export function promedioVerificado() {
  return null;
}

export function resultadoAdmision() {
  return null;
}

/** Indica si el módulo tiene un informe PDF adjunto. */
export function tieneInforme(datos, campo) {
  return Boolean(datos?.[campo]?.nombre);
}

/** Nombre legible de un docente del catálogo. */
export function nombreDocente(docente) {
  if (!docente) return '';
  const nombre = docente.nombres || docente.nombre || '';
  const apellidos = docente.apellidos || '';

  return [nombre, apellidos].filter(Boolean).join(' ').trim();
}

/**
 * Valida los datos de un módulo antes de guardarlos.
 *
 * Reproduce las reglas de `ExcelenciaService` para avisar en el formulario sin
 * consumir la llamada: el backend vuelve a validarlas y es quien manda.
 */
export function validarModulo(moduloId, datos = {}) {
  const errores = [];
  const val = (v) => String(v ?? '').trim() !== '';

  switch (moduloId) {
    case MODULO_INICIO:
      break;

    case MODULO_DESIGNACION:
      if (!val(datos.numero_resolucion)) {
        errores.push('Ingrese el número de Resolución del HCC de designación del Tribunal.');
      }
      if (!val(datos.fecha_resolucion)) {
        errores.push('Seleccione la fecha de la Resolución de designación.');
      }
      if (datos.confirmado !== true) {
        errores.push('Marque la verificación de la designación del Tribunal.');
      }
      break;

    case MODULO_INFORME: {
      const t1 = datos.tribunal_1;
      const t2 = datos.tribunal_2;

      if (!t1 || !val(t1.id_docente) || !val(t1.nombre)) {
        errores.push('Seleccione el Tribunal 1 del catálogo de docentes.');
      }
      if (!t2 || !val(t2.id_docente) || !val(t2.nombre)) {
        errores.push('Seleccione el Tribunal 2 del catálogo de docentes.');
      }
      if (t1 && t2 && String(t1.id_docente) === String(t2.id_docente)) {
        errores.push('Un mismo docente no puede ocupar los dos cargos.');
      }
      if (!resultadoEvaluacion(datos.resultado_evaluacion)) {
        errores.push('Seleccione el resultado de la evaluación del tribunal.');
      }
      if (datos.confirmado !== true) {
        errores.push('Marque la verificación del Informe de Evaluación del Tribunal.');
      }
      break;
    }

    case MODULO_RESOLUCION: {
      for (const area of AREAS_CALIFICACION) {
        const valor = datos[area.campo];

        if (valor === '' || valor === null || valor === undefined || !Number.isFinite(Number(valor))) {
          errores.push(`Ingrese la calificación de ${area.etiqueta}.`);
        } else if (Number(valor) < 0 || Number(valor) > 100) {
          errores.push(`La calificación de ${area.etiqueta} debe estar entre 0 y 100.`);
        }
      }
      if (datos.confirmado !== true) {
        errores.push('Marque la verificación de las calificaciones.');
      }
      break;
    }

    case MODULO_ACTA:
      if (!val(datos.fecha_lectura)) {
        errores.push('Seleccione la fecha de lectura del acta.');
      }
      if (datos.confirmado !== true) {
        errores.push('Marque la verificación de la generación y lectura del acta.');
      }
      break;

    case MODULO_REPORTE:
      break;

    default:
      errores.push('Módulo no reconocido.');
  }

  return { ok: errores.length === 0, errores, advertencias: [] };
}
