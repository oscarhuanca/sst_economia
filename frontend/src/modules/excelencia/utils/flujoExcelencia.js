// ============================================================================
// Flujo de Excelencia Académica gestionado por KARDEX.
//
// Describe la modalidad como una secuencia de 3 módulos. No hay tutor ni
// tribunal: la Excelencia Académica se titula por promedio sobresaliente.
//
//  1. `verificacion_promedio` — KARDEX verifica el promedio general del
//     estudiante (el que declaró al solicitar) contra el mínimo reglamentario,
//     registra la Resolución del HCC que admite o rechaza la solicitud y
//     confirma la revisión. Con resultado ACEPTADO avanza a
//     `promedio_verificado`; con RECHAZADO, o con un promedio inferior al
//     mínimo, queda rechazado y el estudiante no opta a la modalidad. El
//     promedio verificado se consolida en el perfil del estudiante
//     (`estudiantes.promedio_global`).
//  2. `resolucion_hcc` — se registran el número y la fecha de la Resolución del
//     HCC de lectura del acta, la fecha de lectura ante el tribunal y el PDF
//     opcional del informe (`resolucion_hcc_registrada`).
//  3. `reporte` — se emite el acta de Excelencia Académica y el estudiante
//     queda `titulado`.
//
// A diferencia del Examen de Grado, aquí el flujo es estrictamente lineal (la
// única rama es el rechazo de la admisión), así que el estado actual del
// trámite es la fuente de verdad del avance: cada módulo queda cerrado al
// alcanzar su estado objetivo.
//
// Espejo de `App\Modules\Excelencia\Services\ExcelenciaService`.
// ============================================================================

// Módulos del flujo en orden de ejecución.
export const MODULOS_FLUJO_EXCELENCIA = [
  {
    id: 'verificacion_promedio',
    label: 'Verificación y Admisión',
    estado: 'promedio_verificado',
    pct: 35,
    icon: 'clipboard',
    badge: 'Promedio Verificado',
  },
  {
    id: 'resolucion_hcc',
    label: 'Resolución del HCC y Lectura del Acta',
    estado: 'resolucion_hcc_registrada',
    pct: 70,
    icon: 'shield',
    badge: 'Resolución Registrada',
  },
  {
    id: 'reporte',
    label: 'Reporte Final',
    estado: 'titulado',
    pct: 100,
    icon: 'file-text',
    badge: 'Estudiante Titulado',
  },
];

/**
 * Secuencia de estados del flujo, en orden.
 *
 * El módulo `i` queda cerrado cuando el trámite alcanza `ORDEN_ESTADOS[i + 1]`.
 */
export const ORDEN_ESTADOS = [
  'solicitud_presentada',
  'promedio_verificado',
  'resolucion_hcc_registrada',
  'titulado',
];

/** Rango y mínimo de nota (espejo de `config/excelencia.php`). */
export const RANGO_NOTA = [0, 100];
export const NOTA_MINIMA = 80;

/** Módulo en el que KARDEX registra el promedio verificado. */
export const MODULO_VERIFICACION = 'verificacion_promedio';

/** Módulo en el que KARDEX registra la Resolución del HCC. */
export const MODULO_RESOLUCION = 'resolucion_hcc';

/** Módulo del reporte final (acta de titulación). */
export const MODULO_REPORTE = 'reporte';

/** Indica si el trámite pertenece a la modalidad de Excelencia Académica. */
export function esFlujoExcelencia(tramite) {
  return tramite?.modalidad?.nombre === 'Excelencia Académica';
}

/**
 * Estado de cada módulo del flujo para el trámite dado.
 *
 * @param {Object|null} tramite Trámite de Excelencia Académica.
 * @returns {Array<{id,label,estado,pct,icon,badge,completado,enProgreso}>}
 *          Un elemento por módulo, en el orden del flujo.
 */
export function modulosExcelencia(tramite) {
  const indice = ORDEN_ESTADOS.indexOf(tramite?.estado_actual);

  return MODULOS_FLUJO_EXCELENCIA.map((m, i) => {
    const completado = indice > i;

    return {
      ...m,
      completado,
      enProgreso: indice === i,
    };
  });
}

/** Porcentaje de avance (0-100) según el estado del trámite. */
export function pctExcelencia(tramite) {
  const indice = ORDEN_ESTADOS.indexOf(tramite?.estado_actual);

  if (indice === -1) return 0;

  return [0, 35, 70, 100][indice] ?? 0;
}

/**
 * Índice del primer módulo pendiente, o -1 si el flujo ya no admite más avances
 * (trámite rechazado o ya titulado).
 */
export function proximoModuloPendiente(tramite) {
  if (tramite?.estado_actual === 'rechazado' || tramite?.estado_actual === 'titulado') return -1;

  return modulosExcelencia(tramite).findIndex((m) => !m.completado);
}

/** Datos guardados de un módulo (desde `hitos.modulos`). */
export function datosDeModulo(tramite, moduloId) {
  return tramite?.hitos?.modulos?.[moduloId] || null;
}

/**
 * Promedio declarado por el estudiante al presentar la solicitud.
 *
 * @returns {number|null} El promedio declarado, o null si el trámite aún no
 *          lo tiene registrado.
 */
export function promedioDeclarado(tramite) {
  const valor = datosDeModulo(tramite, MODULO_VERIFICACION)?.promedio_declarado;

  return valor === undefined || valor === null || valor === '' ? null : Number(valor);
}

/**
 * Promedio verificado por KARDEX (el que se consolidó en el perfil del
 * estudiante).
 *
 * @returns {number|null} El promedio verificado, o null si todavía no se
 *          verificó.
 */
export function promedioVerificado(tramite) {
  const valor = datosDeModulo(tramite, MODULO_VERIFICACION)?.promedio;

  return valor === undefined || valor === null || valor === '' ? null : Number(valor);
}

/**
 * Resultado de la admisión registrado en el paso 1.
 *
 * @returns {'aceptado'|'rechazado'|null}
 */
export function resultadoAdmision(tramite) {
  const resultado = datosDeModulo(tramite, MODULO_VERIFICACION)?.resultado;

  return resultado === 'aceptado' || resultado === 'rechazado' ? resultado : null;
}

/**
 * Resolución del HCC que admite o rechaza la solicitud (paso 1).
 *
 * @returns {{numero_resolucion:string|null, fecha_resolucion:string|null}}
 */
export function resolucionAdmision(tramite) {
  const datos = datosDeModulo(tramite, MODULO_VERIFICACION) || {};

  return {
    numero_resolucion: datos.numero_resolucion || null,
    fecha_resolucion: datos.fecha_resolucion || null,
  };
}

/**
 * Resolución del HCC registrada para el trámite.
 *
 * @returns {{numero_resolucion:string|null, fecha_resolucion:string|null,
 *            fecha_lectura_acta:string|null}}
 */
export function resolucionHcc(tramite) {
  const datos = datosDeModulo(tramite, MODULO_RESOLUCION) || {};

  return {
    numero_resolucion: datos.numero_resolucion || null,
    fecha_resolucion: datos.fecha_resolucion || null,
    fecha_lectura_acta: datos.fecha_lectura_acta || null,
  };
}

/** Indica si el módulo tiene un informe PDF adjunto. */
export function tieneInforme(datos, campo) {
  return Boolean(datos?.[campo]?.nombre);
}

/**
 * Valida los datos de un módulo antes de guardarlos.
 *
 * Reproduce las reglas de `ExcelenciaService` para avisar en el formulario sin
 * consumir la llamada: el backend vuelve a validarlas y es quien manda.
 *
 * @param {string} moduloId Identificador del módulo.
 * @param {Object} datos    Datos del formulario del módulo.
 * @returns {{ok:boolean, errores:string[], advertencias:string[]}} Resultado de la
 *          validación. Un resultado RECHAZADO no es un error: el backend cierra
 *          el flujo y lo que corresponde es una advertencia para que KARDEX
 *          confirme la decisión antes de guardar.
 */
export function validarModulo(moduloId, datos = {}) {
  const errores = [];
  const advertencias = [];
  const val = (v) => String(v ?? '').trim() !== '';

  switch (moduloId) {
    case MODULO_VERIFICACION: {
      const resultado = datos.resultado;

      if (!val(datos.promedio)) {
        errores.push('Ingrese el promedio general del estudiante.');
      } else {
        const nota = Number(datos.promedio);

        if (Number.isNaN(nota) || nota < RANGO_NOTA[0] || nota > RANGO_NOTA[1]) {
          errores.push(`El promedio debe estar entre ${RANGO_NOTA[0]} y ${RANGO_NOTA[1]}.`);
        } else if (resultado === 'aceptado' && nota < NOTA_MINIMA) {
          errores.push(
            `No es posible ACEPTAR la solicitud: el promedio ${nota} es inferior al mínimo de ${NOTA_MINIMA}.`,
          );
        }
      }

      if (resultado !== 'aceptado' && resultado !== 'rechazado') {
        errores.push('Seleccione si la solicitud fue ACEPTADA o RECHAZADA.');
      } else if (resultado === 'rechazado') {
        advertencias.push(
          'La solicitud quedará RECHAZADA: el flujo terminará y el estudiante deberá optar por otra modalidad.',
        );
      }

      if (!val(datos.numero_resolucion)) errores.push('Ingrese el número de Resolución del HCC.');
      if (!val(datos.fecha_resolucion)) errores.push('Seleccione la fecha de la Resolución.');
      if (datos.confirmado !== true) {
        errores.push('Marque la verificación del promedio y de la Resolución del HCC.');
      }
      break;
    }

    case MODULO_RESOLUCION:
      if (!val(datos.numero_resolucion)) errores.push('Ingrese el número de Resolución del HCC.');
      if (!val(datos.fecha_resolucion)) errores.push('Seleccione la fecha de la Resolución.');
      if (!val(datos.fecha_lectura_acta)) errores.push('Seleccione la fecha de lectura del acta.');
      if (datos.confirmado !== true) {
        errores.push('Marque la verificación de la Resolución y de la lectura del acta.');
      }
      break;

    case MODULO_REPORTE:
      break;

    default:
      errores.push('Módulo no reconocido.');
  }

  return { ok: errores.length === 0, errores, advertencias };
}
