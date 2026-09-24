// ============================================================================
// Formato y presentación del módulo Kardex.
// ============================================================================

/**
 * Convierte una fecha ISO (YYYY-MM-DD o ISO completa) a texto legible local.
 *
 * @param {string|null} iso Fecha ISO-8601 o null.
 * @returns {string} Fecha en formato dd/mm/aaaa o '' si no hay fecha.
 */
export function fechaLegible(iso) {
  if (!iso) return '';
  const fecha = new Date(`${String(iso).slice(0, 10)}T00:00:00`);
  if (Number.isNaN(fecha.getTime())) return '';
  return fecha.toLocaleDateString('es-PE');
}

/**
 * Convierte una fecha ISO a DD-MM-AAAA (formato de credenciales).
 *
 * @param {string|null} iso Fecha ISO-8601 o null.
 * @returns {string} Fecha en formato dd-mm-aaaa o '' si no hay fecha.
 */
export function fechaCredencial(iso) {
  const fecha = String(iso || '').slice(0, 10);
  return fecha ? fecha.split('-').reverse().join('-') : '';
}

/**
 * Iniciales del postulante para el avatar.
 *
 * @param {Object} p Postulante con `nombres` y `apellidos`.
 * @returns {string} Iniciales (ej. "RQ").
 */
export function iniciales(p) {
  const a = String(p?.nombres || '').trim();
  const b = String(p?.apellidos || '').trim();
  return `${a.charAt(0) || ''}${b.charAt(0) || ''}`.toUpperCase() || '?';
}