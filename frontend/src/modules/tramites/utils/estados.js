// ============================================================================
// Utilidades del dominio de trámites: estados del flujo de titulación.
//
// Solo contiene clasificaciones de estados (terminales, éxito, error), la
// conversión a texto legible, los tonos de color (clases Tailwind) y helpers de
// progreso. Los helpers de ROLES ya NO viven aquí: usan `@/core/roles`.
// ============================================================================

// Estados que cierran el flujo de un trámite (no permiten avanzar).
export const ESTADOS_TERMINALES = ['aprobado', 'reprobado', 'rechazado', 'reprobado_ausencia'];

// Estados considerados de éxito/aprobación con tono verde.
export const ESTADOS_OK = [
  'aprobado',
  'suficiente',
  'perfil_aprobado',
  'tema_aprobado',
  'monografia_aprobada',
  'conformidad_tutor',
];

// Estados de error/rechazo con tono rojo.
export const ESTADOS_ERROR = [
  'rechazado',
  'reprobado',
  'reprobado_ausencia',
  'perfil_rechazado',
  'insuficiente',
  'monografia_rechazada',
];

// Etiquetas legibles de los tipos de documentos de un trámite. Cubre la
// documentación oficial de todas las modalidades; con fallback al tipo crudo.
export const ETIQUETAS_DOCUMENTO = {
  nota_solicitud: 'Nota de Solicitud',
  certificado_notas: 'Certificado de Notas',
  carta_solicitud: 'Carta de Solicitud',
  perfil_tesis: 'Perfil de Tesis de Grado',
};

/**
 * Convierte el tipo interno de un documento a una etiqueta legible.
 *
 * @param {string} tipo Tipo interno (ej. "perfil_tesis").
 * @returns {string} Etiqueta legible (con fallback formateado).
 */
export function etiquetaDocumento(tipo) {
  return ETIQUETAS_DOCUMENTO[tipo]
    || String(tipo || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (l) => l.toUpperCase());
}

/**
 * Convierte un nombre de estado a texto legible (ej. "solicitud_presentada"
 * -> "Solicitud Presentada").
 *
 * @param {string} nombre Nombre interno del estado (snake_case).
 * @returns {string} Etiqueta formateada para mostrar al usuario.
 */
export function formatoEstado(nombre) {
  return String(nombre || '')
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (l) => l.toUpperCase());
}

/**
 * Devuelve las clases Tailwind de color correspondientes al tono de un estado
 * (verde = éxito, rojo = error, índigo = neutro/en proceso).
 *
 * @param {string} estado Nombre interno del estado.
 * @returns {{soft: string, solid: string, bar: string, dot: string}} Clases
 *          para fondo suave, sólido, barra de progreso e indicador puntual.
 */
export function toneEstado(estado) {
  if (estado === 'correcciones_90_dias') {
    return {
      soft: 'bg-amber-50 text-amber-700 border-amber-200',
      solid: 'bg-amber-500',
      bar: 'bg-amber-500',
      dot: 'bg-amber-500',
    };
  }
  if (ESTADOS_OK.includes(estado)) {
    return {
      soft: 'bg-emerald-50 text-emerald-700 border-emerald-200',
      solid: 'bg-emerald-500',
      bar: 'bg-emerald-500',
      dot: 'bg-emerald-500',
    };
  }
  if (ESTADOS_ERROR.includes(estado)) {
    return {
      soft: 'bg-rose-50 text-rose-700 border-rose-200',
      solid: 'bg-rose-500',
      bar: 'bg-rose-500',
      dot: 'bg-rose-500',
    };
  }
  return {
    soft: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    solid: 'bg-indigo-500',
    bar: 'bg-gradient-to-r from-indigo-500 to-violet-500',
    dot: 'bg-indigo-500',
  };
}

/**
 * Calcula el porcentaje de avance de un trámite según su posición dentro de la
 * secuencia completa de estados de la modalidad.
 *
 * @param {Object} tramite Trámite con `secuencia` (array) y `estado_actual`.
 * @returns {number} Porcentaje 0-100.
 */
export function progresoEstado(tramite) {
  // Los estados terminales cierran el flujo: aunque el estado no esté en la
  // secuencia de la modalidad (ej. "rechazado"), el trámite ya concluyó
  // y el avance es del 100%.
  if (ESTADOS_TERMINALES.includes(tramite?.estado_actual)) return 100;
  const idx = (tramite?.secuencia || []).indexOf(tramite?.estado_actual);
  if (idx === -1 || !tramite?.secuencia?.length) return 0;
  return Math.min(Math.round(((idx + 1) / tramite.secuencia.length) * 100), 100);
}

/**
 * Resumen global de un estado para insignias: Aprobado, Rechazado o En Proceso.
 *
 * @param {string} estado Nombre interno del estado.
 * @returns {{label: string, tone: string}} Etiqueta y clases de color.
 */
export function statusGlobal(estado) {
  if (estado === 'aprobado') return { label: 'Aprobado', tone: toneEstado(estado).soft };
  if (ESTADOS_ERROR.includes(estado)) return { label: 'Rechazado', tone: toneEstado(estado).soft };
  return { label: 'En Proceso', tone: 'bg-sky-50 text-sky-700 border-sky-200' };
}