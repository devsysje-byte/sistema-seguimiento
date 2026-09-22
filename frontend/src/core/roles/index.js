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
  concejo: 'Concejo',
};

// Colores (clases Tailwind) para la insignia de cada rol.
export const ROLE_TONES = {
  admin: 'bg-rose-50 text-rose-700 border-rose-200',
  estudiante: 'bg-sky-50 text-sky-700 border-sky-200',
  docente: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  kardex: 'bg-violet-50 text-violet-700 border-violet-200',
  secretaria: 'bg-amber-50 text-amber-700 border-amber-200',
  direccion: 'bg-indigo-50 text-indigo-700 border-indigo-200',
  concejo: 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
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
  return ROLE_TONES[rol] || 'bg-slate-100 text-slate-700 border-slate-200';
}