// ============================================================================
// Flujo de titulación por módulos (Tesis de Grado).
//
// Describe el flujo de titulación como una secuencia de 6 módulos gestionados
// por KARDEX. Cada módulo se completa desde un formulario propio y, al
// guardarse, avanza el trámite a su estado objetivo, registra sus datos en
// `hitos.modulos` y recalcula el porcentaje de la barra de progreso.
//
// El primer hito (Solicitud Presentada) no es un módulo: es el punto de partida
// del flujo (0%).
// ============================================================================

// Módulos del flujo en orden de ejecución.
export const MODULOS_FLUJO_TITULACION = [
  { id: 'perfil_tesis', label: 'Perfil de Tesis', estado: 'perfil_aprobado', pct: 20, icon: 'file-text', badge: 'Perfil Aprobado' },
  { id: 'aprobacion_tema', label: 'Aprobación del Tema', estado: 'tema_aprobado', pct: 40, icon: 'book', badge: 'Tema Aprobado' },
  { id: 'tribunal_revisor', label: 'Tribunal Revisor', estado: 'tribunal_asignado', pct: 60, icon: 'users', badge: 'Tribunal Asignado' },
  { id: 'defensa', label: 'Defensa', estado: 'defensa_aprobada', pct: 75, icon: 'award', badge: 'Defensa Aprobada' },
  { id: 'reporte', label: 'Reporte', estado: 'reporte_generado', pct: 90, icon: 'file-text', badge: 'Reporte Generado' },
  { id: 'publicacion', label: 'Publicación', estado: 'titulado', pct: 100, icon: 'globe', badge: 'Titulado' },
];

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
  reporte_generado: 90,
  aprobado: 100,
  titulado: 100,
  reprobado: 75,
  reprobado_ausencia: 75,
  rechazado: 20,
};

// Estados de error que se muestran tal cual (no se traducen a un módulo).
export const ESTADOS_ERROR_TITULACION = ['rechazado', 'reprobado', 'reprobado_ausencia', 'perfil_rechazado', 'insuficiente'];

// Documentos verificados en el módulo de perfil (checkboxes con PDF adjunto).
export const DOCUMENTOS_PERFIL = [
  { tipo: 'carta_solicitud', etiqueta: 'Carta de Solicitud' },
  { tipo: 'certificado_conclusion', etiqueta: 'Certificado de Conclusión de Estudios' },
  { tipo: 'perfil_tesis', etiqueta: 'Perfil de Tesis' },
];

// Roles del tribunal revisor (lista dinámica del módulo 3).
export const ROLES_TRIBUNAL = ['presidente', 'vocal', 'secretario'];

export const RANGO_NOTA_DEFENSA = [0, 100];

/** Indica si el trámite usa el flujo de titulación por módulos. */
export function esFlujoTitulacion(tramite) {
  return tramite?.modalidad?.nombre === 'Tesis de Grado';
}

/** Porcentaje de avance (0-100) según el estado actual del trámite. */
export function pctTitulacion(tramite) {
  return PCT_POR_ESTADO[tramite?.estado_actual] ?? 0;
}

/** Estado objetivo de un módulo (Interno). */
export function estadoDeModulo(moduloId) {
  return MODULOS_FLUJO_TITULACION.find((m) => m.id === moduloId)?.estado || null;
}

/** Devuelve el módulo (config) al que pertenece un estado. */
export function moduloDeEstado(estado) {
  return MODULOS_FLUJO_TITULACION.find((m) => m.estado === estado) || null;
}

/**
 * Badge del flujo: el estado interno que debe mostrarse en la insignia
 * "Estado actual del flujo" (Perfil Aprobado, Tema Aprobado, ...).
 */
export function estadoBadgeTitulacion(tramite) {
  const estado = tramite?.estado_actual;
  if (!estado) return 'solicitud_presentada';
  if (ESTADOS_ERROR_TITULACION.includes(estado)) return estado;
  const pct = PCT_POR_ESTADO[estado] ?? 0;
  let badge = 'solicitud_presentada';
  for (const m of MODULOS_FLUJO_TITULACION) {
    if (pct >= m.pct) badge = m.estado;
  }
  return badge;
}

/** Datos guardados de un módulo (desde hitos.modulos). */
export function datosDeModulo(tramite, moduloId) {
  return tramite?.hitos?.modulos?.[moduloId] || null;
}

/**
 * Estado de cada módulo del flujo para el trámite dado.
 *
 * @returns {Array<{id,label,estado,pct,icon,badge,completado,actual,bloqueado,datos}>}
 */
export function modulosTitulacion(tramite) {
  const pct = pctTitulacion(tramite);
  const hitos = tramite?.hitos || {};

  return MODULOS_FLUJO_TITULACION.map((m, i) => {
    const previo = MODULOS_FLUJO_TITULACION[i - 1]?.pct ?? 0;
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
  const pct = pctTitulacion(tramite);
  if (ESTADOS_ERROR_TITULACION.includes(tramite?.estado_actual)) return -1;
  return MODULOS_FLUJO_TITULACION.findIndex((m) => pct < m.pct);
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
 * @param {string} moduloId Identificador del módulo del flujo.
 * @param {Object|null} datos Datos guardados del módulo (`hitos.modulos`).
 * @param {Object} tramite Trámite (para el nombre del tutor asignado).
 * @returns {Array<{etiqueta:string, valor:*}>} Filas con la actualización del módulo.
 */
function camposDeModulo(moduloId, datos, tramite) {
  if (!datos) return [];

  switch (moduloId) {
    case 'perfil_tesis':
      return filas([
        ...DOCUMENTOS_PERFIL.map((d) => {
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
      ]);

    case 'tribunal_revisor':
      return filas([
        { etiqueta: 'N.º de Resolución del HCC', valor: datos.numero_resolucion },
        { etiqueta: 'Fecha de la resolución', valor: fechaLegible(datos.fecha_resolucion) },
        ...ROLES_TRIBUNAL.map((rol) => ({
          etiqueta: ETIQUETAS_ROL_TRIBUNAL[rol] || rol,
          valor: (datos.tribunal || []).find((t) => t.rol === rol)?.nombre,
        })),
      ]);

    case 'defensa':
      return filas([
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
        { etiqueta: 'Documento final', valor: datos.archivo?.nombre },
        { etiqueta: 'Fecha de titulación', valor: fechaLegible(datos.fecha_titulacion) },
        { etiqueta: 'Enlace de registro', valor: datos.enlace_registro },
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
 * @param {Object|null} tramite Trámite de Tesis de Grado del estudiante.
 * @returns {Array<{id:string,label:string,estado:string,badge:string,icon:string,
 *                  fecha:string|null,campos:Array<{etiqueta:string,valor:*}>}>}
 *          Módulos registrados, en el orden del flujo; vacío si aún no hay ninguno.
 */
export function actualizacionesKardex(tramite) {
  if (!esFlujoTitulacion(tramite)) return [];

  const modulos = tramite?.hitos?.modulos || {};

  return MODULOS_FLUJO_TITULACION.map((m) => {
    const datos = modulos[m.id] || null;
    const fecha = fechaDeModulo(tramite, m.estado);
    return {
      id: m.id,
      label: m.label,
      estado: m.estado,
      badge: m.badge,
      icon: m.icon,
      fecha: fecha ? String(fecha).slice(0, 10) : null,
      campos: camposDeModulo(m.id, datos, tramite),
    };
  }).filter((a) => a.campos.length > 0 || a.fecha !== null);
}

/**
 * Valida los datos de un módulo antes de guardar.
 *
 * La defensa se registra en dos pasos: `paso: 1` valida solo la Resolución de
 * Aprobación Final y la fecha de la defensa; `paso: 2` (o sin `paso`, guardado
 * completo) exige además la nota final.
 *
 * @param {string} moduloId Identificador del módulo.
 * @param {Object} datos    Datos del formulario del módulo.
 * @returns {{ok:boolean, errores:string[]}} Resultado de la validación.
 */
export function validarModulo(moduloId, datos = {}) {
  const errores = [];
  const val = (v) => String(v ?? '').trim() !== '';

  switch (moduloId) {
    case 'perfil_tesis': {
      const docs = datos.documentos || {};
      for (const d of DOCUMENTOS_PERFIL) {
        const item = docs[d.tipo];
        if (!item?.marcado) {
          errores.push(`Marque la verificación de: ${d.etiqueta}`);
        } else if (!val(item.archivo?.nombre)) {
          errores.push(`Adjunte el PDF de: ${d.etiqueta}`);
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
      if (!val(datos.numero_resolucion)) errores.push('Ingrese el número de Resolución del HCC.');
      if (!val(datos.fecha_resolucion)) errores.push('Seleccione la fecha de la Resolución.');
      if (!Array.isArray(datos.tribunal) || datos.tribunal.length < ROLES_TRIBUNAL.length) {
        errores.push('Complete los miembros del tribunal revisor (Presidente, Vocal y Secretario).');
      } else {
        ROLES_TRIBUNAL.forEach((rol) => {
          const miembro = datos.tribunal.find((t) => t.rol === rol);
          if (!miembro || !val(miembro.nombre)) {
            errores.push(`Complete el nombre del ${rol.charAt(0).toUpperCase() + rol.slice(1)}.`);
          }
        });
      }
      break;
    case 'defensa': {
      if (!val(datos.numero_resolucion)) errores.push('Ingrese el número de Resolución de Aprobación Final.');
      if (!val(datos.fecha_defensa)) errores.push('Seleccione la fecha de la defensa.');
      const paso = datos.paso ?? 2;
      if (paso !== 1) {
        if (datos.nota_final === null || datos.nota_final === undefined || datos.nota_final === '') {
          errores.push('Ingrese la nota final de la defensa.');
        } else {
          const nota = Number(datos.nota_final);
          if (Number.isNaN(nota) || nota < RANGO_NOTA_DEFENSA[0] || nota > RANGO_NOTA_DEFENSA[1]) {
            errores.push(`La nota debe estar entre ${RANGO_NOTA_DEFENSA[0]} y ${RANGO_NOTA_DEFENSA[1]}.`);
          }
        }
      }
      break;
    }
    case 'reporte':
      break;
    case 'publicacion':
      if (!val(datos.archivo?.nombre) && !val(datos.enlace_publico)) {
        errores.push('Adjunte el documento final en PDF o ingrese el enlace público.');
      }
      break;
    default:
      errores.push('Módulo no reconocido.');
  }

  return { ok: errores.length === 0, errores };
}