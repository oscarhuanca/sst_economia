// ============================================================================
// Flujo de Examen de Grado gestionado por KARDEX.
//
// Describe la modalidad como una secuencia de 8 módulos. A diferencia de la
// Tesis, aquí NO hay tutor ni tribunal: dos sorteos de bolo, un módulo que
// registra la Resolución de aprobación del resultado del teórico, un módulo que
// registra la Resolución de asignación de notas, un módulo de ASIGNACIÓN DE
// NOTAS que cierra ambos exámenes juntos y un reporte final, y el estudiante
// puede REABRIR el trámite cuando quedó con alguno de los dos exámenes sin
// aprobar, consumiendo otro intento.
//
// El alcance de la reapertura depende de qué examen no se aprobó:
//   - Reprobar UN SOLO examen reabre únicamente ese examen: la nota del examen
//     aprobado se CONSERVA (llega en `hitos.examen_aprobado`) y la Resolución
//     del HCC en curso sigue valiendo.
//   - Reprobar AMBOS exige una NUEVA Resolución del HCC y rendir de nuevo los
//     dos exámenes.
//
// La nota de los dos exámenes se registra DESPUÉS de los dos sorteos (módulo 7
// `asignacion_notas`), no dentro de cada tarjeta de sorteo: recién ahí se
// decide si el estudiante aprobó o reprobó.
//
// No asistir al sorteo de bolos de cualquiera de los dos exámenes REPROBA ese
// examen automáticamente, pero no detiene el flujo: tras el teórico el
// estudiante puede registrar la Resolución de aprobación y pasar al sorteo del
// examen de especialidad.
//
// Por eso el avance NO se deduce del estado actual (que tras una reapertura
// vuelve atrás) sino del historial especializado `historial_examen`, que el
// backend expone como `tramite.historial_examen`. Los módulos de sorteo solo
// cuentan los registros posteriores a la última reapertura: los sorteos de un
// intento anterior ya no valen, salvo el del examen conservado, que se
// sintetiza desde `hitos.examen_aprobado`.
//
// Espejo de `App\Modules\Tramites\Services\ExamenService`.
// ============================================================================

// Módulos del flujo en orden de ejecución.
//
// `porIntento` marca los módulos que se repiten en cada intento (los sorteos y
// la asignación de notas): su validación se hace contra el historial del
// intento en curso, mientras que la verificación de intentos y la habilitación
// se mantienen tras reabrir.
export const MODULOS_FLUJO_EXAMEN = [
  {
    id: 'verificacion_intentos',
    label: 'Verificación de Intentos',
    estado: 'intentos_verificados',
    pct: 15,
    icon: 'clipboard',
    badge: 'Intentos Verificados',
  },
  {
    id: 'habilitacion_examen',
    label: 'Habilitación del Estudiante',
    estado: 'habilitado_examen',
    pct: 30,
    icon: 'shield',
    badge: 'Estudiante Habilitado',
  },
  {
    id: 'sorteo_teorico',
    label: 'Sorteo del Examen Teórico',
    estado: 'examen_teorico_sorteado',
    pct: 43,
    icon: 'calendar',
    badge: 'Examen Teórico',
    porIntento: true,
  },
  {
    id: 'resolucion_resultado_teorico',
    label: 'Resolución de Aprobación del Resultado',
    estado: 'examen_teorico_aprobado',
    pct: 57,
    icon: 'file-text',
    badge: 'Resultado Teórico Aprobado',
    porIntento: true,
  },
  {
    id: 'sorteo_especialidad',
    label: 'Sorteo del Examen de Especialidad',
    estado: 'examen_especialidad_sorteado',
    pct: 68,
    icon: 'award',
    badge: 'Examen de Especialidad',
    porIntento: true,
  },
  {
    id: 'resolucion_asignacion_notas',
    label: 'Resolución de Asignación de Notas',
    estado: 'notas_habilitadas',
    pct: 76,
    icon: 'file-text',
    badge: 'Asignación de Notas Habilitada',
    porIntento: true,
  },
  {
    id: 'asignacion_notas',
    label: 'Asignación de Notas',
    estado: 'notas_asignadas',
    pct: 88,
    icon: 'file-text',
    badge: 'Notas Asignadas',
    porIntento: true,
  },
  {
    id: 'reporte',
    label: 'Reporte',
    estado: 'titulado',
    pct: 100,
    icon: 'file-text',
    badge: 'Estudiante Titulado',
  },
];

/**
 * Resultados del historial con los que un módulo queda RESUELTO.
 *
 * Un sorteo se cierra con la asistencia: `asistio` registra tema y fecha, y
 * `no_asistio` lo cierra también, porque no asistir reprueba el examen
 * automáticamente. La Resolución de aprobación del teórico cierra con
 * `aprobado`; la Resolución de asignación de notas con `resolucion_registrada`.
 * Las notas se asignan en el módulo 7, que cierra con `aprobado` (los dos
 * exámenes con 51 o más) o `reprobado`. El reporte cierra el flujo: generarlo
 * titula al estudiante.
 */
const RESULTADOS_RESUELTOS = {
  verificacion_intentos: ['verificado'],
  habilitacion_examen: ['habilitado'],
  sorteo_teorico: ['asistio', 'no_asistio', 'aprobado', 'reprobado'],
  resolucion_resultado_teorico: ['aprobado'],
  sorteo_especialidad: ['asistio', 'no_asistio', 'aprobado', 'reprobado'],
  resolucion_asignacion_notas: ['resolucion_registrada'],
  asignacion_notas: ['aprobado', 'reprobado'],
  reporte: ['titulado'],
};

/** Estados desde los que el estudiante puede volver a rendir el examen. */
export const ESTADOS_REAPERIBLES = [
  'examen_teorico_reprobado',
  'examen_especialidad_reprobado',
  'examen_ambos_reprobados',
];

/** Etiquetas de cada módulo de sorteo, para los mensajes de la tarjeta. */
export const ETIQUETAS_SORTEOS = {
  sorteo_teorico: 'examen teórico',
  sorteo_especialidad: 'examen de especialidad',
};

/** Módulos de sorteo de bolo: registran asistencia, tema y fecha. */
export const MODULOS_SORTEOS = ['sorteo_teorico', 'sorteo_especialidad'];

/** Clave de `hitos.examen_aprobado` para el sorteo de cada examen. */
const EXAMENES_CONSERVADOS = {
  sorteo_teorico: 'examen_teorico',
  sorteo_especialidad: 'examen_especialidad',
};

/** Rango y mínimo de nota del examen (espejo de `config/examen.php`). */
export const RANGO_NOTA = [0, 100];
export const NOTA_MINIMA = 51;
export const MAXIMO_INTENTOS = 3;

/** Indica si el trámite pertenece a la modalidad de Examen de Grado. */
export function esFlujoExamen(tramite) {
  return tramite?.modalidad?.nombre === 'Examen de Grado';
}

/** Historial especializado del examen, en orden cronológico. */
export function historialExamen(tramite) {
  return [...(tramite?.historial_examen || [])].sort((a, b) =>
    String(a.created_at || '').localeCompare(String(b.created_at || ''))
    || Number(a.id_historial_examen || 0) - Number(b.id_historial_examen || 0),
  );
}

/**
 * Resoluciones del HCC registradas en el historial, en orden cronológico, con
 * la vigente señalada.
 *
 * `hitos.examen_aprobado` concentra los exámenes aprobados de la resolución
 * vigente. Las resoluciones anteriores quedan sin aprobados: el cambio de
 * resolución solo procede cuando AMBOS exámenes salieron reprobados, así que
 * en una resolución superada nunca hubo un examen aprobado.
 */
export function resolucionesHistorial(tramite) {
  const habilitaciones = historialExamen(tramite).filter(
    (f) => f.etapa === 'habilitacion_examen',
  );

  return habilitaciones.map((h, i) => ({
    ...h,
    vigente: i === habilitaciones.length - 1,
  }));
}

/**
 * Registros del historial que pertenecen al intento en curso.
 *
 * Todo lo anterior a la última reapertura queda fuera: es el intento que ya se
 * consumió. La verificación de intentos y la habilitación no se reconsideran
 * (para eso `modulosExamen` consulta el historial completo), solo los sorteos.
 */
function registrosDelIntento(tramite) {
  const filas = historialExamen(tramite);
  let corte = -1;

  filas.forEach((f, i) => {
    if (f.etapa === 'reapertura') corte = i;
  });

  return corte === -1 ? filas : filas.slice(corte + 1);
}

/**
 * Estado de cada módulo del flujo para el trámite dado.
 *
 * @param {Object|null} tramite Trámite de Examen de Grado.
 * @returns {Array<{id,label,estado,pct,icon,badge,completado,registro,reabierto}>}
 *          Un elemento por módulo, en el orden del flujo.
 */
export function modulosExamen(tramite) {
  const todas = historialExamen(tramite);
  const delIntento = registrosDelIntento(tramite);
  const conservados = tramite?.hitos?.examen_aprobado || null;

  return MODULOS_FLUJO_EXAMEN.map((m) => {
    const fuente = m.porIntento ? delIntento : todas;
    const registros = fuente.filter((f) => f.etapa === m.id);
    let registro = registros.length ? registros[registros.length - 1] : null;

    // Un examen ya aprobado en la resolución en curso no vuelve a sortearse: su
    // sorteo se muestra completado con la nota conservada en `examen_aprobado`.
    const claveConservado = EXAMENES_CONSERVADOS[m.id];
    const conservado = claveConservado ? conservados?.[claveConservado] : null;
    if (!registro && conservado) {
      registro = {
        resultado: 'aprobado',
        tema: conservado.tema,
        fecha_examen: conservado.fecha_examen,
        nota: conservado.nota,
        conservado: true,
      };
    }

    // Tras una reapertura por especialidad el teórico sigue aprobado, pero su
    // Resolución de aprobación pertenece al intento anterior. Se sintetiza con
    // el último registro del historial para que la tarjeta no vuelva a pedirla.
    if (
      !registro
      && m.id === 'resolucion_resultado_teorico'
      && conservados?.examen_teorico
    ) {
      const previos = todas.filter((f) => f.etapa === m.id);
      const previo = previos.length ? previos[previos.length - 1] : null;

      registro = {
        resultado: 'aprobado',
        numero_resolucion: previo?.numero_resolucion ?? null,
        fecha_resolucion: previo?.fecha_resolucion ?? null,
        conservado: true,
      };
    }

    let completado = Boolean(registro)
      && (RESULTADOS_RESUELTOS[m.id] || []).includes(registro.resultado);

    // La habilitación cuenta la Resolución del HCC vigente: se requiere una
    // resolución por cada solicitud completa. Una re-solicitud COMPLETA (ambos
    // exámenes reprobados) deja la habilitación pendiente hasta registrar una
    // NUEVA resolución; una re-solicitud parcial conserva la vigente.
    if (m.id === 'habilitacion_examen') {
      const numResoluciones = todas.filter((f) => f.etapa === 'habilitacion_examen').length;
      const numSolicitudesCompletas = 1 + todas.filter(
        (f) => f.etapa === 'reapertura' && f.resultado === 're_solicitud',
      ).length;
      completado = Boolean(registro) && numResoluciones >= numSolicitudesCompletas;
    }

    return {
      ...m,
      registro,
      // Un registro de un intento anterior no cuenta: tras reabrir el
      // módulo vuelve a estar pendiente.
      completado,
      // Un registro a medio registrar (resultado aún no terminal) está en curso.
      enProgreso: Boolean(registro) && !(RESULTADOS_RESUELTOS[m.id] || []).includes(registro.resultado),
      reabierto: m.porIntento && todas.some((f) => f.etapa === 'reapertura')
        && !registro,
    };
  });
}

/** Porcentaje de avance (0-100) según los módulos ya resueltos. */
export function pctExamen(tramite) {
  const modulos = modulosExamen(tramite);
  const resueltos = modulos.filter((m) => m.completado).length;

  return Math.round((resueltos / modulos.length) * 100);
}

/**
 * Índice del primer módulo pendiente, o -1 si el flujo ya no admite más avances
 * (trámite rechazado, titulado o esperando una reapertura).
 *
 * Un módulo `enProgreso` (sorteo con la asistencia registrada pero sin nota)
 * SIGUE siendo el pendiente: es precisamente el que espera la nota, así que no
 * puede saltarse. Si se ignorara, la tarjeta quedaría bloqueada y la nota no
 * podría registrarse nunca.
 */
export function proximoModuloPendiente(tramite) {
  if (ESTADOS_REAPERIBLES.includes(tramite?.estado_actual)) return -1;
  if (tramite?.estado_actual === 'rechazado' || tramite?.estado_actual === 'titulado') return -1;

  return modulosExamen(tramite).findIndex((m) => !m.completado);
}

/** Indica si el estudiante puede volver a sortear el examen. */
export function esReabrible(tramite) {
  return ESTADOS_REAPERIBLES.includes(tramite?.estado_actual);
}

/** Número de intentos ya consumidos por el estudiante. */
export function intentosUsados(tramite) {
  return Number(tramite?.estudiante?.numero_intentos ?? 0);
}

/** Intentos que le quedan al estudiante, sin negativo. */
export function intentosRestantes(tramite, maximo = MAXIMO_INTENTOS) {
  return Math.max(0, maximo - intentosUsados(tramite));
}

/** Datos guardados de un módulo (desde `hitos.modulos`). */
export function datosDeModulo(tramite, moduloId) {
  return tramite?.hitos?.modulos?.[moduloId] || null;
}

/**
 * Valida los datos de un módulo antes de guardarlos.
 *
 * Reproduce las reglas de `ExamenService` para avisar en el formulario sin
 * consumir la llamada: el backend vuelve a validarlas y es quien manda.
 *
 * @param {string} moduloId Identificador del módulo.
 * @param {Object} datos    Datos del formulario del módulo.
 * @param {Object} [contexto] `{ intentos, maximo }` para el módulo 1 y
 *        `{ asistio_teorico, asistio_especialidad }` para `asignacion_notas`.
 * @returns {{ok:boolean, errores:string[], advertencias:string[]}} Resultado de la
 *          validación. Una nota reprobada NO es un error: el backend la registra
 *          como `reprobado` y lo que corresponde es una advertencia, para que
 *          KARDEX confirme la nota antes de cerrar el módulo.
 */
export function validarModulo(moduloId, datos = {}, contexto = {}) {
  const errores = [];
  const advertencias = [];
  const val = (v) => String(v ?? '').trim() !== '';
  const etiqueta = ETIQUETAS_SORTEOS[moduloId] || 'examen';

  switch (moduloId) {
    case 'verificacion_intentos': {
      const maximo = contexto.maximo ?? MAXIMO_INTENTOS;
      const usados = Number(contexto.intentos ?? 0);

      if (datos.accion === 'rechazar') {
        if (usados < maximo) {
          errores.push(
            `El estudiante lleva ${usados} de ${maximo} intentos permitidos. `
            + 'Solo se rechaza la solicitud cuando los intentos están agotados.',
          );
        }
        if (!val(datos.observaciones)) {
          errores.push('Indique el motivo por el que se rechaza la solicitud.');
        }
        break;
      }

      if (usados >= maximo) {
        errores.push(
          `El estudiante agotó los ${maximo} intentos permitidos. `
          + 'Rechace la solicitud en lugar de habilitarla.',
        );
      }
      break;
    }

    case 'habilitacion_examen':
      if (!val(datos.numero_resolucion)) errores.push('Ingrese el número de Resolución del HCC.');
      if (!val(datos.fecha_resolucion)) errores.push('Seleccione la fecha de la Resolución.');
      break;

    case 'resolucion_resultado_teorico':
      if (!val(datos.numero_resolucion)) errores.push('Ingrese el número de Resolución del HCC.');
      if (!val(datos.fecha_resolucion)) errores.push('Seleccione la fecha de la Resolución.');
      break;

    case 'resolucion_asignacion_notas':
      if (!val(datos.numero_resolucion)) {
        errores.push('Ingrese el número de Resolución de asignación de notas.');
      }
      if (!val(datos.fecha_resolucion)) {
        errores.push('Seleccione la fecha de la Resolución de asignación de notas.');
      }
      break;

    case 'sorteo_teorico':
    case 'sorteo_especialidad': {
      if (datos.asistio === false) {
        advertencias.push(
          `La falta de asistencia al sorteo del ${etiqueta} lo deja REPROBADO automáticamente, `
          + `sin nota.${moduloId === 'sorteo_teorico'
            ? ' El estudiante podrá igual asistir al sorteo del examen de especialidad.'
            : ''} `
          + 'Podrá re-solicitar el examen de grado consumiendo otro intento.',
        );
        break;
      }

      if (datos.asistio !== true) {
        errores.push(`Indique si el estudiante asistió al sorteo del ${etiqueta}.`);
        break;
      }
      if (!val(datos.tema)) errores.push(`Ingrese el tema sorteado para el ${etiqueta}.`);
      if (!val(datos.fecha_examen)) errores.push(`Seleccione la fecha del ${etiqueta}.`);
      break;
    }

    case 'asignacion_notas': {
      const revisarNota = (valor, nombre, asistio) => {
        // El examen no se rindió: la ausencia ya lo dejó reprobado, sin nota.
        if (asistio === false) return;

        // Un examen conservado ya fue aprobado y asciende su nota automáticamente:
        // el módulo no pide nota nueva ni la puede reprobar.
        if (asistio === 'conservado') return;

        if (valor === '' || valor === null || valor === undefined) {
          if (asistio === true) errores.push(`Ingrese la nota del ${nombre}.`);
          return;
        }

        const nota = Number(valor);
        if (Number.isNaN(nota) || nota < RANGO_NOTA[0] || nota > RANGO_NOTA[1]) {
          errores.push(`La nota del ${nombre} debe estar entre ${RANGO_NOTA[0]} y ${RANGO_NOTA[1]}.`);
        } else if (nota < NOTA_MINIMA) {
          advertencias.push(
            `Con ${nota} puntos el ${nombre} queda REPROBADO (mínimo ${NOTA_MINIMA}). `
            + 'El módulo se cerrará como reprobado y el estudiante podrá re-solicitar el examen '
            + 'de grado consumiendo otro intento.',
          );
        }
      };

      revisarNota(datos.nota_teorico, ETIQUETAS_SORTEOS.sorteo_teorico, contexto.asistio_teorico);
      revisarNota(
        datos.nota_especialidad,
        ETIQUETAS_SORTEOS.sorteo_especialidad,
        contexto.asistio_especialidad,
      );
      break;
    }

    case 'reporte':
      break;

    default:
      errores.push('Módulo no reconocido.');
  }

  return { ok: errores.length === 0, errores, advertencias };
}
