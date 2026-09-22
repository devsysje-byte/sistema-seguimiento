// ============================================================================
// Utilidades del flujo oficial de Tesis de Grado (lado del estudiante).
//
// Mapea cada estado de la máquina de estados (backend) a su fase del flujo,
// una descripción legible y qué hito (fecha) alimenta la cuenta regresiva.
// ============================================================================

// Fases del flujo oficial del estudiante.
export const FASES_TESIS = [
  { id: 'solicitud', label: 'Solicitud', icon: 'file-text', estados: ['solicitud_presentada'] },
  { id: 'consejo', label: 'Consejo Universitario', icon: 'shield', estados: ['pendiente_concejo_universitario', 'perfil_aprobado', 'perfil_rechazado'] },
  { id: 'investigacion', label: 'Investigación', icon: 'book', estados: ['tutor_asignado', 'investigacion_en_desarrollo', 'documento_final_presentado'] },
  { id: 'revision', label: 'Comisión Revisora', icon: 'eye', estados: ['comision_revisora', 'suficiente', 'insuficiente'] },
  { id: 'defensa', label: 'Defensa', icon: 'award', estados: ['solicitud_fecha_defensa', 'defensa_programada', 'defensa_en_curso', 'correcciones_90_dias', 'aprobado', 'reprobado'] },
];

// Explicación amigable de cada estado para el seguimiento del estudiante.
export const DESCRIPCION_ESTADO = {
  solicitud_presentada: 'Tu solicitud fue presentada. Espera la verificación de la documentación.',
  pendiente_concejo_universitario: 'Tu solicitud está pendiente de la evaluación del Consejo Universitario.',
  perfil_aprobado: 'El Consejo Universitario aprobó tu perfil de tesis. Se asignará un tutor.',
  perfil_rechazado: 'El Consejo Universitario rechazó tu perfil. Corrígelo y reenvíalo.',
  tutor_asignado: 'Se te asignó un tutor. Tu investigación está por comenzar.',
  investigacion_en_desarrollo: 'Periodo de investigación y desarrollo del documento final dentro del plazo establecido.',
  documento_final_presentado: 'Presentaste el documento final. Pasa a la revisión de la Comisión Revisora.',
  comision_revisora: 'La Comisión Revisora está evaluando tu documento final.',
  suficiente: 'La Comisión Revisora calificó tu documento como suficiente. Solicita tu fecha de defensa.',
  insuficiente: 'La Comisión Revisora calificó tu documento como insuficiente. Revisa tu trabajo, corrige las observaciones y vuelve a presentarlo a la Comisión Revisora.',
  solicitud_fecha_defensa: 'Tu tesis fue aprobada por la Comisión Revisora. Solicita tu fecha de defensa; Kardex la programará.',
  defensa_programada: 'Tu fecha de defensa está programada. Prepárate para la sustentación.',
  defensa_en_curso: 'Tu defensa está en curso. La Comisión evaluará el resultado.',
  correcciones_90_dias: 'Debes realizar las correcciones indicadas dentro del plazo establecido (90 días).',
  aprobado: '¡Felicitaciones! Tu tesis de grado fue aprobada.',
  reprobado: 'Tu tesis no fue aprobada. Consulta las alternativas disponibles.',
  rechazado: 'Tu solicitud fue rechazada definitivamente. Consulta las alternativas disponibles.',
};

/**
 * Devuelve la fase a la que pertenece un estado del flujo de tesis.
 *
 * @param {string} estado Nombre interno del estado.
 * @returns {{id:string,label:string,icon:string,index:number}}
 */
export function faseDe(estado) {
  const index = FASES_TESIS.findIndex((f) => f.estados.includes(estado));
  if (index === -1) return { id: '', label: '', icon: 'clock', index: -1 };
  const { id, label, icon } = FASES_TESIS[index];
  return { id, label, icon, index };
}

/**
 * Devuelve la fecha límite activa para mostrar la cuenta regresiva según el
 * estado actual de la tesis.
 *
 * @param {Object} tramite Trámite con `estado_actual` y `hitos`.
 * @returns {{titulo:string, fechaInicio:string, fechaLimite:string, descripcion:string}|null}
 *          Configuración del countdown o null si no aplica.
 */
export function countdownDe(tramite) {
  const estado = tramite?.estado_actual;
  const hitos = tramite?.hitos || {};

  if (estado === 'investigacion_en_desarrollo' && hitos.limite_presentacion) {
    return {
      titulo: 'Plazo de presentación del documento final',
      fechaInicio: hitos.perfil_aprobado || null,
      fechaLimite: hitos.limite_presentacion,
      descripcion: 'Tienes este plazo para presentar el documento final desde la aprobación de tu perfil.',
    };
  }

  if (estado === 'correcciones_90_dias' && hitos.limite_correccion) {
    return {
      titulo: 'Plazo de correcciones',
      fechaInicio: hitos.correcciones_90_dias || null,
      fechaLimite: hitos.limite_correccion,
      descripcion: 'Tienes este plazo para corregir y reenviar tu documento.',
    };
  }

  return null;
}