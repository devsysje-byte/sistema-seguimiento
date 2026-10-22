// ============================================================================
// Flujo de Excelencia Académica gestionado por KARDEX.
//
// Describe la modalidad como una secuencia de 3 módulos. No hay tutor ni
// tribunal: la Excelencia Académica se titula por promedio sobresaliente.
//
//  1. `verificacion_promedio` — KARDEX verifica el promedio general del
//     estudiante (el que declaró al solicitar) contra el mínimo reglamentario.
//     Con el promedio en o por encima del mínimo el trámite avanza a
//     `promedio_verificado`; por debajo queda RECHAZADO y el estudiante no
//     opta a la modalidad. El promedio verificado se consolida en el perfil del
//     estudiante (`estudiantes.promedio_global`).
//  2. `resolucion_hcc` — se registra el número y la fecha de la Resolución del
//     HCC que habilita al estudiante (`resolucion_hcc_registrada`).
//  3. `reporte` — se emite el acta de Excelencia Académica y el estudiante
//     queda `titulado`.
//
// A diferencia del Examen de Grado, aquí el flujo es estrictamente lineal (la
// única rama es el rechazo por promedio), así que el estado actual del trámite
// es la fuente de verdad del avance: cada módulo queda cerrado al alcanzar su
// estado objetivo.
//
// Espejo de `App\Modules\Excelencia\Services\ExcelenciaService`.
// ============================================================================

// Módulos del flujo en orden de ejecución.
export const MODULOS_FLUJO_EXCELENCIA = [
  {
    id: 'verificacion_promedio',
    label: 'Verificación del Promedio',
    estado: 'promedio_verificado',
    pct: 35,
    icon: 'clipboard',
    badge: 'Promedio Verificado',
  },
  {
    id: 'resolucion_hcc',
    label: 'Resolución del HCC',
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
 * Resolución del HCC registrada para el trámite.
 *
 * @returns {{numero_resolucion:string|null, fecha_resolucion:string|null}}
 */
export function resolucionHcc(tramite) {
  const datos = datosDeModulo(tramite, MODULO_RESOLUCION) || {};

  return {
    numero_resolucion: datos.numero_resolucion || null,
    fecha_resolucion: datos.fecha_resolucion || null,
  };
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
 *          validación. Un promedio por debajo del mínimo NO es un error: el
 *          backend lo registra como `rechazado` y lo que corresponde es una
 *          advertencia, para que KARDEX confirme la nota antes de cerrar el
 *          módulo.
 */
export function validarModulo(moduloId, datos = {}) {
  const errores = [];
  const advertencias = [];
  const val = (v) => String(v ?? '').trim() !== '';

  switch (moduloId) {
    case MODULO_VERIFICACION: {
      if (!val(datos.promedio)) {
        errores.push('Ingrese el promedio general del estudiante.');
        break;
      }

      const nota = Number(datos.promedio);

      if (Number.isNaN(nota) || nota < RANGO_NOTA[0] || nota > RANGO_NOTA[1]) {
        errores.push(`El promedio debe estar entre ${RANGO_NOTA[0]} y ${RANGO_NOTA[1]}.`);
        break;
      }

      if (nota < NOTA_MINIMA) {
        advertencias.push(
          `Con ${nota} de promedio el estudiante NO alcanza el mínimo de ${NOTA_MINIMA}: `
          + 'el módulo se cerrará como RECHAZADO y el flujo de titulación terminará.',
        );
      }
      break;
    }

    case MODULO_RESOLUCION:
      if (!val(datos.numero_resolucion)) errores.push('Ingrese el número de Resolución del HCC.');
      if (!val(datos.fecha_resolucion)) errores.push('Seleccione la fecha de la Resolución.');
      break;

    case MODULO_REPORTE:
      break;

    default:
      errores.push('Módulo no reconocido.');
  }

  return { ok: errores.length === 0, errores, advertencias };
}
