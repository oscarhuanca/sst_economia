// ============================================================================
// Adaptador del flujo de titulación por módulos para la TESIS DE GRADO.
//
// Toda la lógica vive en el núcleo compartido (`@/modules/tramites/utils/
// flujoTitulacion`); aquí solo se declara lo que es propio de esta modalidad
// (el módulo de perfil, sus documentos y sus textos) y se reexporta la
// fachada que consumen las vistas de Tesis.
// ============================================================================
import {
  configDe,
  configDeModulo,
  configDeTramite,
  datosDeModulo,
  estadoBadgeTitulacion,
  estadoDeModulo,
  esFlujoTitulacion,
  flujoConcluido,
  modulosDe,
  modulosTitulacion,
  moduloDeEstado,
  pctTitulacion,
  proximoModuloPendiente,
  rechazoPerfil,
  tituloPerfil,
  actualizacionesKardex,
  validarModulo,
  ESTADOS_ERROR_TITULACION,
  NOTA_MINIMA_DEFENSA,
  RANGO_NOTA_DEFENSA,
  ROLES_TRIBUNAL,
} from '@/modules/tramites/utils/flujoTitulacion';

/** Nombre de la modalidad a la que corresponde este adaptador. */
export const MODALIDAD = 'Tesis de Grado';

/** Configuración de la modalidad. */
export const CONFIGURACION = configDe(MODALIDAD);

/** Módulos del flujo en orden de ejecución. */
export const MODULOS_FLUJO_TITULACION = modulosDe(CONFIGURACION);

/**
 * Documentos verificados en el módulo de perfil (checkboxes con PDF adjunto).
 * Son los mismos tipos que el estudiante adjunta en su solicitud
 * (`config/tesis.php`), para que KARDEX revise lo realmente presentado.
 */
export const DOCUMENTOS_PERFIL = CONFIGURACION.documentosPerfil;

/** Título de tesis presentado por el estudiante con su perfil. */
export const MODULO_PERFIL = CONFIGURACION.moduloPerfil;

/**
 * Motivo que KARDEX registra al rechazar un perfil por título repetido. Es el
 * texto que ve el estudiante en su flujo, por lo que debe explicar que debe
 * presentar otro perfil con un título diferente.
 */
export const MOTIVO_TITULO_REPETIDO = CONFIGURACION.motivoTituloRepetido;

export {
  ESTADOS_ERROR_TITULACION,
  NOTA_MINIMA_DEFENSA,
  RANGO_NOTA_DEFENSA,
  ROLES_TRIBUNAL,
  configDe,
  configDeModulo,
  configDeTramite,
  datosDeModulo,
  estadoBadgeTitulacion,
  estadoDeModulo,
  esFlujoTitulacion,
  flujoConcluido,
  modulosTitulacion,
  moduloDeEstado,
  pctTitulacion,
  proximoModuloPendiente,
  actualizacionesKardex,
  validarModulo,
};

/** Título de tesis propuesto por el estudiante para su perfil. */
export function tituloPerfilTesis(tramite) {
  return tituloPerfil(tramite);
}

/** Último rechazo del perfil de tesis registrado por KARDEX. */
export function rechazoPerfilTesis(tramite) {
  return rechazoPerfil(tramite);
}
