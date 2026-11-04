// ============================================================================
// Catálogo de roles del sistema (transversal a todos los dominios).
//
// Vive en `core/` precisamente para que ningún módulo de dominio sea la fuente
// de verdad de los roles: ui/, layout/, usuarios, tramites, etc. lo consumen
// sin crear acoplamientos entre dominios.
// ============================================================================

// Etiquetas legibles para cada rol del sistema.
export const ROLE_LABELS = {
  admin: 'Administrador',
  estudiante: 'Estudiante',
  docente: 'Docente',
  kardex: 'Kardex',
  secretaria: 'Secretaría',
  direccion: 'Dirección',
};

// Roles de gestión académica: validan la documentación inicial, asignan tutor
// y avanzan los trámites por el flujo de estados.
export const ROLES_GESTION = ['admin', 'kardex', 'secretaria', 'direccion'];

/**
 * Comprueba si un rol pertenece a un grupo de roles del sistema.
 *
 * @param {string} rol Rol interno del usuario.
 * @param {string[]} lista Grupos como `ROLES_GESTION`.
 * @returns {boolean} true si el rol está incluido en la lista.
 */
export function perteneceRol(rol, lista) {
  return lista.includes(rol);
}

// Colores (clases Tailwind) para la insignia de cada rol.
export const ROLE_TONES = {
  admin: 'bg-orange-500/20 text-orange-300 border-orange-500/30',
  estudiante: 'bg-white/5 text-slate-300 border-white/20',
  docente: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
  kardex: 'bg-red-500/20 text-red-300 border-red-500/30',
  secretaria: 'bg-amber-500/20 text-amber-300 border-amber-500/30',
  direccion: 'bg-orange-500/20 text-orange-400 border-orange-500/30',
};

/**
 * Traduce un rol a su etiqueta legible.
 *
 * @param {string} rol Rol interno del usuario.
 * @returns {string} Etiqueta legible (o 'Sistema' por defecto).
 */
export function rolLabel(rol) {
  return ROLE_LABELS[rol] || rol || 'Sistema';
}

/**
 * Devuelve las clases de color de un rol.
 *
 * @param {string} rol Rol interno del usuario.
 * @returns {string} Clases Tailwind para la insignia, con un gris por defecto.
 */
export function toneRol(rol) {
  return ROLE_TONES[rol] || 'bg-white/5 text-slate-400 border-white/20';
}