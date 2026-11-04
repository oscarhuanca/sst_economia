// ============================================================================
// Flujo de titulación por módulos (núcleo compartido).
//
// Describe el flujo de titulación como una secuencia de 6 módulos gestionados
// por KARDEX. Cada módulo se completa desde un formulario propio y, al
// guardarse, avanza el trámite a su estado objetivo, registra sus datos en
// `hitos.modulos` y recalcula el porcentaje de la barra de progreso.
//
// El primer hito (Solicitud Presentada) no es un módulo: es el punto de partida
// del flujo (0%).
//
// Lo comparten las modalidades que avanzan por este flujo: Tesis de Grado y
// Trabajo Dirigido. Lo que cambia entre ellas NO está disperso por el código:
// todo vive en el registro `MODALIDADES_TITULACION`, de modo que añadir una
// modalidad nueva es describirla allí y Nada más.
// ============================================================================

// Porcentaje de avance por estado interno del trámite. Incluye los estados del
// flujo anterior (legado) para no romper las líneas de tiempo ya registradas.
const PCT_POR_ESTADO = {
  solicitud_presentada: 0,
  pendiente_concejo_universitario: 5,
  perfil_rechazado: 5,
  perfil_aprobado: 20,
  tutor_asignado: 30,
  tema_aprobado: 40,
  investigacion_en_desarrollo: 45,
  documento_final_presentado: 48,
  comision_revisora: 50,
  insuficiente: 50,
  correcciones_90_dias: 50,
  suficiente: 52,
  solicitud_fecha_defensa: 55,
  defensa_programada: 57,
  defensa_en_curso: 62,
  tribunal_asignado: 60,
  defensa_aprobada: 75,
  publicado: 90,
  reporte_generado: 90,
  aprobado: 100,
  titulado: 100,
  reprobado: 75,
  rechazado: 20,
};

// Estados de error que se muestran tal cual (no se traducen a un módulo).
export const ESTADOS_ERROR_TITULACION = ['rechazado', 'reprobado', 'perfil_rechazado', 'insuficiente'];

// Roles del tribunal revisor (lista dinámica del módulo 3).
export const ROLES_TRIBUNAL = ['presidente', 'vocal', 'secretario'];

export const RANGO_NOTA_DEFENSA = [0, 100];

// Nota mínima para aprobar la defensa: con menos de 51 puntos el trámite queda
// REPROBADO y el estudiante debe volver a solicitar la modalidad de graduación
// (respetando los plazos reglamentarios).
export const NOTA_MINIMA_DEFENSA = 51;

/**
 * Registro de las modalidades que avanzan por el flujo de titulación por
 * módulos.
 *
 * Es la única fuente de verdad de lo que distingue a una modalidad de otra:
 * el identificador y la etiqueta de su módulo de perfil, el nombre del trabajo
 * en los textos y el catálogo de documentos que el estudiante adjunta.
 *
 * @type {Object<string, Object>}
 */
export const MODALIDADES_TITULACION = {
  'Tesis de Grado': {
    nombre: 'Tesis de Grado',
    // Identificador del módulo de perfil en el flujo y en `hitos.modulos`.
    moduloPerfil: 'perfil_tesis',
    // Nombre del módulo de perfil en la interfaz de KARDEX.
    etiquetaPerfil: 'Perfil de Tesis',
    // Nombre del módulo de perfil en minúscula (textos del estudiante).
    etiquetaPerfilCorto: 'perfil de tesis',
    // Cómo se llama el trabajo de la modalidad ("tesis" / "trabajo dirigido").
    etiquetaTitulo: 'tesis',
    // Cómo se llama el trabajo en el título del documento (mayúscula inicial).
    etiquetaTituloCorto: 'Tesis',
    // Artículo que acompaña al nombre del trabajo ("una" / "un").
    articulo: 'una',
    // Etiquetas de los campos que muestran el título del trabajo.
    tituloLabel: 'Título de tesis de grado',
    tituloLabelCorto: 'Título de tesis',
    // Documentos que el estudiante adjunta en la fase de solicitud.
    documentosPerfil: [
      { tipo: 'nota_solicitud', etiqueta: 'Nota de Solicitud' },
      { tipo: 'certificado_notas', etiqueta: 'Certificado de Notas' },
      { tipo: 'perfil_tesis', etiqueta: 'Perfil de Tesis de Grado' },
    ],
    // Etiqueta de los títulos parecidos que reporta la verificación de unicidad.
    similares: { singular: 'tesis similar', plural: 'tesis similares' },
    // Cómo se llama el documento que el estudiante publica tras la defensa.
    etiquetaPublicacion: 'Trabajo de Investigación',
    // Motivo que KARDEX registra al rechazar un perfil por título repetido.
    motivoTituloRepetido:
      'Existen trabajos de grado registrados con el mismo título de tesis. '
      + 'Debe presentar su perfil nuevamente con un título de tesis diferente.',
  },
  'Trabajo Dirigido': {
    nombre: 'Trabajo Dirigido',
    moduloPerfil: 'perfil_proyecto',
    etiquetaPerfil: 'Perfil de Proyecto',
    etiquetaPerfilCorto: 'perfil de proyecto',
    etiquetaTitulo: 'trabajo dirigido',
    etiquetaTituloCorto: 'Trabajo Dirigido',
    articulo: 'un',
    tituloLabel: 'Título del trabajo dirigido',
    tituloLabelCorto: 'Título del trabajo dirigido',
    documentosPerfil: [
      { tipo: 'nota_solicitud', etiqueta: 'Nota de Solicitud' },
      { tipo: 'certificado_notas', etiqueta: 'Certificado de Notas' },
      { tipo: 'perfil_proyecto', etiqueta: 'Perfil de Proyecto' },
    ],
    // Etiqueta de los títulos parecidos que reporta la verificación de unicidad.
    similares: { singular: 'trabajo dirigido similar', plural: 'trabajos dirigidos similares' },
    // Cómo se llama el documento que el estudiante publica tras la defensa.
    etiquetaPublicacion: 'Trabajo Dirigido',
    motivoTituloRepetido:
      'Existen trabajos de grado registrados con el mismo título de trabajo dirigido. '
      + 'Debe presentar su perfil nuevamente con un título de trabajo dirigido diferente.',
  },
};

/**
 * Configuración de una modalidad del flujo de titulación.
 *
 * @param {string} modalidad Nombre de la modalidad.
 * @returns {Object|null} Configuración o null si la modalidad no usa este flujo.
 */
export function configDe(modalidad) {
  return MODALIDADES_TITULACION[modalidad] || null;
}

/**
 * Configuración del flujo que corresponde al trámite dado.
 *
 * @param {Object|null} tramite Trámite con `modalidad.nombre`.
 * @returns {Object|null} Configuración o null si el trámite no usa este flujo.
 */
export function configDeTramite(tramite) {
  return configDe(tramite?.modalidad?.nombre);
}

/**
 * Modalidad a la que pertenece un módulo del flujo.
 *
 * Permite resolver la configuración cuando solo se conoce el identificador del
 * módulo (p. ej. `perfil_proyecto`).
 *
 * @param {string} moduloId Identificador del módulo.
 * @returns {Object|null} Configuración o null si el módulo no es del flujo.
 */
export function configDeModulo(moduloId) {
  return Object.values(MODALIDADES_TITULACION)
    .find((config) => config.moduloPerfil === moduloId) || null;
}

/**
 * Módulos del flujo en orden de ejecución para una modalidad.
 *
 * @param {Object} config Configuración de la modalidad.
 * @returns {Array<{id:string,label:string,estado:string,pct:number,icon:string,badge:string}>}
 */
export function modulosDe(config) {
  return [
    {
      id: config.moduloPerfil,
      label: config.etiquetaPerfil,
      estado: 'perfil_aprobado',
      pct: 20,
      icon: 'file-text',
      badge: 'Perfil Aprobado',
    },
    { id: 'aprobacion_tema', label: 'Aprobación del Tema', estado: 'tema_aprobado', pct: 40, icon: 'book', badge: 'Tema Aprobado' },
    { id: 'tribunal_revisor', label: 'Tribunal Revisor', estado: 'tribunal_asignado', pct: 60, icon: 'users', badge: 'Tribunal Asignado' },
    { id: 'defensa', label: 'Defensa', estado: 'defensa_aprobada', pct: 75, icon: 'award', badge: 'Defensa Aprobada' },
    { id: 'publicacion', label: 'Publicación', estado: 'publicado', pct: 90, icon: 'globe', badge: 'Publicado' },
    { id: 'reporte', label: 'Reporte', estado: 'titulado', pct: 100, icon: 'file-text', badge: 'Titulado' },
  ];
}

/** Indica si el trámite usa el flujo de titulación por módulos. */
export function esFlujoTitulacion(tramite) {
  return configDeTramite(tramite) !== null;
}

/** Porcentaje de avance (0-100) según el estado actual del trámite. */
export function pctTitulacion(tramite) {
  return PCT_POR_ESTADO[tramite?.estado_actual] ?? 0;
}

/** Estado objetivo de un módulo (interno). */
export function estadoDeModulo(moduloId, modalidad) {
  const config = configDe(modalidad) || configDeModulo(moduloId);
  if (!config) return null;
  return modulosDe(config).find((m) => m.id === moduloId)?.estado || null;
}

/** Devuelve el módulo (config) al que pertenece un estado. */
export function moduloDeEstado(estado, modalidad) {
  const config = configDe(modalidad);
  if (!config) return null;
  return modulosDe(config).find((m) => m.estado === estado) || null;
}

/**
 * Badge del flujo: el estado interno que debe mostrarse en la insignia
 * "Estado actual del flujo" (Perfil Aprobado, Tema Aprobado, ...).
 */
export function estadoBadgeTitulacion(tramite) {
  const estado = tramite?.estado_actual;
  if (!estado) return 'solicitud_presentada';
  if (ESTADOS_ERROR_TITULACION.includes(estado)) return estado;
  const config = configDeTramite(tramite);
  if (!config) return estado;
  const pct = PCT_POR_ESTADO[estado] ?? 0;
  let badge = 'solicitud_presentada';
  for (const m of modulosDe(config)) {
    if (pct >= m.pct) badge = m.estado;
  }
  return badge;
}

/** Datos guardados de un módulo (desde hitos.modulos). */
export function datosDeModulo(tramite, moduloId) {
  return tramite?.hitos?.modulos?.[moduloId] || null;
}

/**
 * Título del trabajo propuesto por el estudiante para su perfil.
 *
 * Es el texto que KARDEX verifica contra los demás trabajos antes de aprobar
 * el perfil; si el trámite aún no registró título, se recurre al tema
 * confirmado en el módulo de aprobación del tema.
 *
 * @param {Object|null} tramite Trámite del flujo de titulación.
 * @returns {string} Título presentado (cadena vacía si aún no hay).
 */
export function tituloPerfil(tramite) {
  const config = configDeTramite(tramite);
  const modulo = config?.moduloPerfil;
  const titulo = (modulo ? datosDeModulo(tramite, modulo)?.titulo : null)
    || tramite?.hitos?.modulos?.aprobacion_tema?.tema_investigacion
    || '';
  return String(titulo).trim();
}

/**
 * Último rechazo del perfil registrado por KARDEX.
 *
 * El motivo queda en el historial de estados del trámite (`observaciones`), que
 * es lo que el estudiante consulta en su seguimiento; la fecha se toma del
 * hito correspondiente.
 *
 * @param {Object|null} tramite Trámite del flujo de titulación.
 * @returns {{fecha: string|null, observacion: string, titulo: string}|null}
 *          Rechazo del perfil o null si nunca fue rechazado.
 */
export function rechazoPerfil(tramite) {
  const delHistorial = (tramite?.estados || [])
    .filter((e) => e.nombre_estado === 'perfil_rechazado')
    .map((e) => ({ ...e, fecha: String(e.created_at || '').slice(0, 10) }))
    .sort((a, b) => String(a.created_at).localeCompare(String(b.created_at)))
    .pop();

  if (!delHistorial) return null;

  return {
    fecha: delHistorial.fecha || tramite?.hitos?.perfil_rechazado || null,
    observacion: String(delHistorial.observaciones || '').trim(),
    titulo: tituloPerfil(tramite),
  };
}

/**
 * Estado de cada módulo del flujo para el trámite dado.
 *
 * @returns {Array<{id,label,estado,pct,icon,badge,completado,actual,bloqueado,datos}>}
 */
export function modulosTitulacion(tramite) {
  const config = configDeTramite(tramite);
  if (!config) return [];

  const modulos = modulosDe(config);
  const pct = pctTitulacion(tramite);
  const hitos = tramite?.hitos || {};

  return modulos.map((m, i) => {
    const previo = modulos[i - 1]?.pct ?? 0;
    const enProgreso = pct > previo && pct < m.pct;
    return {
      ...m,
      completado: pct >= m.pct || Boolean(hitos[m.estado]),
      actual: (pct >= previo && pct < m.pct) && !(pct >= m.pct),
      enProgreso,
      bloqueado: false,
      datos: datosDeModulo(tramite, m.id),
    };
  });
}

/**
 * Índice del primer módulo pendiente (el formulario que debe habilitarse).
 * -1 cuando el flujo terminó (titulado) o quedó bloqueado por errores.
 */
export function proximoModuloPendiente(tramite) {
  const config = configDeTramite(tramite);
  if (!config) return -1;
  const pct = pctTitulacion(tramite);
  if (ESTADOS_ERROR_TITULACION.includes(tramite?.estado_actual)) return -1;
  return modulosDe(config).findIndex((m) => pct < m.pct);
}

/** Indica si el trámite llegó al final del flujo (titulado u otro terminal). */
export function flujoConcluido(tramite) {
  return ['titulado', 'aprobado'].includes(tramite?.estado_actual);
}

// Etiquetas legibles de los roles del tribunal revisor.
const ETIQUETAS_ROL_TRIBUNAL = {
  presidente: 'Presidente',
  vocal: 'Vocal',
  secretario: 'Secretario',
};

/** Convierte una fecha ISO (YYYY-MM-DD o ISO completa) a texto legible local. */
function fechaLegible(iso) {
  if (!iso) return '';
  const fecha = new Date(`${String(iso).slice(0, 10)}T00:00:00`);
  if (Number.isNaN(fecha.getTime())) return '';
  return fecha.toLocaleDateString('es-BO', { day: '2-digit', month: 'short', year: 'numeric' });
}

/** Devuelve solo los campos con valor informado (descarta los vacíos). */
function filas(campos) {
  return campos.filter((c) => c.valor !== null && c.valor !== undefined && c.valor !== '');
}

/**
 * Fecha en que se registró un módulo del flujo: la del último registro del estado
 * objetivo en el historial y, como respaldo, el hito con el nombre del estado.
 *
 * @param {Object} tramite Trámite con `estados` y `hitos`.
 * @param {string} estado  Estado objetivo del módulo.
 * @returns {string|null} Fecha ISO o null si el módulo aún no se registró.
 */
function fechaDeModulo(tramite, estado) {
  const delHistorial = (tramite?.estados || [])
    .filter((e) => e.nombre_estado === estado)
    .map((e) => e.created_at)
    .sort()
    .pop();
  return delHistorial || tramite?.hitos?.[estado] || null;
}

/**
 * Campos que Kardex registró en cada módulo del flujo, en texto legible.
 *
 * Traduce la forma en que Kardex guarda los datos (`hitos.modulos`) a filas
 * etiqueta/valor para el estudiante. Los módulos sin datos (o aún no
 * registrados) devuelven una lista vacía.
 *
 * @param {Object} config   Configuración de la modalidad.
 * @param {string} moduloId Identificador del módulo del flujo.
 * @param {Object|null} datos Datos guardados del módulo (`hitos.modulos`).
 * @param {Object|null} tramite Trámite (para el nombre del tutor asignado).
 * @returns {Array<{etiqueta:string, valor:*}>} Filas con la actualización del módulo.
 */
function camposDeModulo(config, moduloId, datos, tramite) {
  if (!datos) return [];

  switch (moduloId) {
    case config.moduloPerfil:
      return filas([
        { etiqueta: config.tituloLabelCorto, valor: datos.titulo },
        ...config.documentosPerfil.map((d) => {
          const item = datos.documentos?.[d.tipo];
          if (!item) return { etiqueta: d.etiqueta, valor: null };
          const archivo = item.archivo?.nombre;
          return {
            etiqueta: d.etiqueta,
            valor: `${item.marcado ? 'Verificado' : 'Pendiente'}${archivo ? ` · ${archivo}` : ''}`,
          };
        }),
        { etiqueta: 'Observaciones', valor: datos.observaciones },
      ]);

    case 'aprobacion_tema':
      return filas([
        { etiqueta: 'N.º de Resolución del HCC', valor: datos.numero_resolucion },
        { etiqueta: 'Fecha de la resolución', valor: fechaLegible(datos.fecha_resolucion) },
        { etiqueta: 'Tema de investigación', valor: datos.tema_investigacion },
        {
          etiqueta: 'Tutor asignado',
          valor:
            tramite?.tutor
              ? `${tramite.tutor.nombres} ${tramite.tutor.apellidos}`
              : datos.tutor_id
                ? `Docente #${datos.tutor_id}`
                : null,
        },
        { etiqueta: 'Resolución de designación del tutor', valor: datos.resolucion_tutor?.nombre },
      ]);

    case 'tribunal_revisor':
      return filas([
        {
          etiqueta: 'Nota de Suficiencia del tutor',
          valor: datos.nota_suficiencia_tutor?.nombre
            || (datos.nota_suficiencia_tutor_entregada ? 'Entregada' : null),
        },
        {
          etiqueta: 'Resolución de designación del tribunal revisor',
          valor: datos.resolucion_tribunal?.nombre
            || (datos.resolucion_tribunal_entregada ? 'Confirmada' : null),
        },
        { etiqueta: 'N.º de Resolución del HCC', valor: datos.numero_resolucion },
        { etiqueta: 'Fecha de la resolución', valor: fechaLegible(datos.fecha_resolucion) },
        ...ROLES_TRIBUNAL.map((rol) => {
          const miembro = (datos.tribunal || []).find((t) => t.rol === rol);
          const nota = miembro?.nota_suficiencia?.nombre
            || (miembro?.nota_suficiencia_entregada ? 'Entregada' : null);
          return {
            etiqueta: ETIQUETAS_ROL_TRIBUNAL[rol] || rol,
            valor: [miembro?.nombre, nota].filter(Boolean).join(' · ') || null,
          };
        }),
      ]);

    case 'defensa':
      return filas([
        {
          etiqueta: '8 empastados y CD',
          valor: datos.empastados_cd_entregados ? 'Entregados' : null,
        },
        { etiqueta: 'N.º de Resolución de Aprobación Final', valor: datos.numero_resolucion },
        { etiqueta: 'Fecha de la defensa', valor: fechaLegible(datos.fecha_defensa) },
        {
          etiqueta: 'Nota final',
          valor: datos.nota_final === '' || datos.nota_final === null || datos.nota_final === undefined
            ? null
            : `${datos.nota_final} / 100`,
        },
      ]);

    case 'reporte':
      return filas([
        { etiqueta: 'Fecha de generación', valor: fechaLegible(datos.generado) },
        { etiqueta: 'Formato', valor: datos.formato ? String(datos.formato).toUpperCase() : null },
      ]);

    case 'publicacion':
      return filas([
        { etiqueta: config.etiquetaPublicacion || 'Trabajo de investigación', valor: datos.archivo?.nombre },
      ]);

    default:
      return [];
  }
}

/**
 * Actualizaciones que Kardex ha registrado en el trámite, listas para el
 * estudiante: cada módulo del flujo con su estado, su fecha y el detalle que
 * Kardex cargó. No incluye nada ajeno a este trámite: solo los módulos
 * registrados en `hitos.modulos` y los estados de su propio historial.
 *
 * @param {Object|null} tramite Trámite del estudiante.
 * @returns {Array<{id:string,label:string,estado:string,badge:string,icon:string,
 *                  fecha:string|null,campos:Array<{etiqueta:string,valor:*}>}>}
 *          Módulos registrados, en el orden del flujo; vacío si aún no hay ninguno.
 */
export function actualizacionesKardex(tramite) {
  const config = configDeTramite(tramite);
  if (!config) return [];

  const modulos = tramite?.hitos?.modulos || {};

  return modulosDe(config).map((m) => {
    const datos = modulos[m.id] || null;
    const fecha = fechaDeModulo(tramite, m.estado);
    return {
      id: m.id,
      label: m.label,
      estado: m.estado,
      badge: m.badge,
      icon: m.icon,
      fecha: fecha ? String(fecha).slice(0, 10) : null,
      campos: camposDeModulo(config, m.id, datos, tramite),
    };
  }).filter((a) => a.campos.length > 0 || a.fecha !== null);
}

/**
 * Valida los datos de un módulo antes de guardar.
 *
 * El perfil es el módulo con decisión: con `accion: rechazar` solo exige la
 * observación (el motivo que verá el estudiante) y con `accion: aprobar` valida
 * el título presentado y los 3 documentos verificados (el PDF adjunto es
 * opcional).
 *
 * La defensa se registra en tres pasos: `paso: 1` valida la entrega de los 8
 * empastados y el CD; `paso: 2` la Resolución de Aprobación Final y la fecha de
 * la defensa; `paso: 3` (o sin `paso`, guardado completo) exige además la nota
 * final.
 *
 * @param {string} moduloId   Identificador del módulo.
 * @param {Object} datos      Datos del formulario del módulo.
 * @param {string} [modalidad] Modalidad (deducida del módulo si se omite).
 * @returns {{ok:boolean, errores:string[], advertencias:string[]}} Resultado de la validación.
 */
export function validarModulo(moduloId, datos = {}, modalidad = null) {
  const config = configDe(modalidad) || configDeModulo(moduloId);
  const errores = [];
  const advertencias = [];
  const val = (v) => String(v ?? '').trim() !== '';

  switch (moduloId) {
    case config?.moduloPerfil: {
      if (datos.accion === 'rechazar') {
        if (!val(datos.observaciones)) {
          errores.push('Indique la observación del rechazo del perfil.');
        }
        break;
      }

      if (!val(datos.titulo) || String(datos.titulo).trim().length < 10) {
        errores.push(`Ingrese el ${config.tituloLabel.toLowerCase()} presentado por el estudiante (mínimo 10 caracteres).`);
      }

      const docs = datos.documentos || {};
      for (const d of config.documentosPerfil) {
        const item = docs[d.tipo];
        if (!item?.marcado) {
          errores.push(`Marque la verificación de: ${d.etiqueta}`);
        }
      }
      break;
    }
    case 'aprobacion_tema':
      if (!val(datos.numero_resolucion)) errores.push('Ingrese el número de Resolución del HCC.');
      if (!val(datos.fecha_resolucion)) errores.push('Seleccione la fecha de la Resolución.');
      if (!val(datos.tema_investigacion) || String(datos.tema_investigacion).trim().length < 5) {
        errores.push('Ingrese el tema de investigación (mínimo 5 caracteres).');
      }
      if (!val(datos.tutor_id)) errores.push('Seleccione el tutor asignado.');
      break;
    case 'tribunal_revisor':
      if (!datos.nota_suficiencia_tutor_entregada) {
        errores.push('Confirme que el tutor entregó la Nota de Suficiencia.');
      }
      if (!datos.resolucion_tribunal_entregada) {
        errores.push('Confirme que se cuenta con la Resolución de designación del tribunal revisor.');
      }
      if (!val(datos.numero_resolucion)) errores.push('Ingrese el número de Resolución del HCC.');
      if (!val(datos.fecha_resolucion)) errores.push('Seleccione la fecha de la Resolución.');
      if (!Array.isArray(datos.tribunal) || datos.tribunal.length < ROLES_TRIBUNAL.length) {
        errores.push('Complete los miembros del tribunal revisor (Presidente, Vocal y Secretario).');
      } else {
        ROLES_TRIBUNAL.forEach((rol) => {
          const miembro = datos.tribunal.find((t) => t.rol === rol);
          const etiquetaRol = rol.charAt(0).toUpperCase() + rol.slice(1);
          if (!miembro || !val(miembro.nombre)) {
            errores.push(`Complete el nombre del ${etiquetaRol}.`);
          }
          if (!miembro?.nota_suficiencia_entregada) {
            errores.push(`Confirme que el ${etiquetaRol} entregó su Nota de Suficiencia.`);
          }
        });
      }
      break;
    case 'defensa': {
      if (!datos.empastados_cd_entregados) {
        errores.push('Confirme que el estudiante presentó los 8 empastados y el CD de su tesis de grado.');
      }
      const paso = Number(datos.paso ?? 3);
      if (paso >= 2) {
        if (!val(datos.numero_resolucion)) errores.push('Ingrese el número de Resolución de Aprobación Final.');
        if (!val(datos.fecha_defensa)) errores.push('Seleccione la fecha de la defensa.');
      }
      if (paso >= 3) {
        if (datos.nota_final === null || datos.nota_final === undefined || datos.nota_final === '') {
          errores.push('Ingrese la nota final de la defensa.');
        } else {
          const nota = Number(datos.nota_final);
          if (Number.isNaN(nota) || nota < RANGO_NOTA_DEFENSA[0] || nota > RANGO_NOTA_DEFENSA[1]) {
            errores.push(`La nota debe estar entre ${RANGO_NOTA_DEFENSA[0]} y ${RANGO_NOTA_DEFENSA[1]}.`);
          } else if (nota < NOTA_MINIMA_DEFENSA) {
            // Una nota reprobada es válida: deja el trámite en estado
            // terminal y el estudiante debe re-solicitar la modalidad.
            advertencias.push(
              `Con ${nota} puntos la defensa queda REPROBADO `
              + `(mínimo de aprobación: ${NOTA_MINIMA_DEFENSA}). El flujo de titulación `
              + 'termina aquí y el estudiante deberá volver a solicitar la modalidad de graduación.',
            );
          }
        }
      }
      break;
    }
    case 'reporte':
      break;
    case 'publicacion':
      if (!val(datos.archivo?.nombre)) {
        errores.push('Debe existir un documento de publicación subido por el estudiante.');
      }
      break;
    default:
      errores.push('Módulo no reconocido.');
  }

  return { ok: errores.length === 0, errores, advertencias };
}
