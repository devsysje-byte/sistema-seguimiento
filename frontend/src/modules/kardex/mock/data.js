// ============================================================================
// Datos simulados del Dashboard de KARDEX.
//
// Se usan únicamente como respaldo de demostración: cuando la API de Laravel no
// está disponible (o devuelve un error de servidor), el store conmuta a "modo
// simulado" para poder probar el flujo completo (búsqueda por CI/RU, asignación
// de modalidad, generación de credenciales y avance de estados) sin backend.
// La API real es siempre la fuente de verdad cuando responde.
// ============================================================================

// Secuencia oficial de estados por modalidad (espejo del backend).
export const SECUENCIAS = {
  'Tesis de Grado': [
    'solicitud_presentada',
    'perfil_aprobado',
    'tema_aprobado',
    'tribunal_asignado',
    'defensa_aprobada',
    'reporte_generado',
    'titulado',
  ],
  'Trabajo Dirigido': [
    'solicitud_presentada',
    'perfil_aprobado',
    'tutor_asignado',
    'investigacion_en_desarrollo',
    'documento_final_presentado',
    'comision_revisora',
    'suficiente',
    'defensa_programada',
    'aprobado',
  ],
  'Examen de Grado': [
    'solicitud_presentada',
    'examen_programado',
    'defensa_en_curso',
    'aprobado',
  ],
};

// Estados siguientes permitidos por estado (espejo del backend).
export const SIGUIENTES_POR_ESTADO = {
  solicitud_presentada: ['pendiente_concejo_universitario', 'perfil_aprobado', 'perfil_rechazado'],
  pendiente_concejo_universitario: ['perfil_aprobado', 'perfil_rechazado'],
  perfil_aprobado: ['tema_aprobado', 'tutor_asignado'],
  perfil_rechazado: ['perfil_aprobado', 'tutor_asignado'],
  tutor_asignado: ['tema_aprobado', 'investigacion_en_desarrollo'],
  tema_aprobado: ['tribunal_asignado', 'investigacion_en_desarrollo'],
  investigacion_en_desarrollo: ['documento_final_presentado', 'tribunal_asignado'],
  documento_final_presentado: ['comision_revisora', 'tribunal_asignado'],
  comision_revisora: ['suficiente', 'insuficiente', 'tribunal_asignado'],
  suficiente: ['solicitud_fecha_defensa', 'tribunal_asignado', 'defensa_aprobada'],
  insuficiente: ['comision_revisora'],
  solicitud_fecha_defensa: ['defensa_programada', 'defensa_aprobada'],
  defensa_programada: ['defensa_en_curso', 'defensa_aprobada'],
  tribunal_asignado: ['defensa_aprobada', 'defensa_programada', 'defensa_en_curso'],
  defensa_en_curso: ['defensa_aprobada', 'aprobado', 'reprobado'],
  defensa_aprobada: ['reporte_generado', 'aprobado'],
  reporte_generado: ['titulado', 'aprobado'],
  correcciones_90_dias: ['solicitud_fecha_defensa', 'reprobado'],
  examen_programado: ['defensa_en_curso'],
  aprobado: [],
  reprobado: [],
  rechazado: [],
  titulado: [],
};

// Catálogo de modalidades activas (misma forma que GET /api/modalidades).
export const MODALIDADES_MOCK = [
  { id_modalidad: 1, nombre: 'Examen de Grado', activo: true },
  { id_modalidad: 2, nombre: 'Tesis de Grado', activo: true },
  { id_modalidad: 3, nombre: 'Trabajo Dirigido', activo: true },
  { id_modalidad: 4, nombre: 'Excelencia Académica', activo: true },
];

// Usuarios docentes (catálogo para asignar tutor, forma de GET /api/usuarios/docentes).
export const DOCENTES_MOCK = [
  { id_usuario: 5, nombres: 'Julia', apellidos: 'Paredes Villca', email: 'julia.paredes@upea.edu.bo', rol: 'docente' },
  { id_usuario: 6, nombres: 'Roberto', apellidos: 'Sánchez Calle', email: 'roberto.sanchez@upea.edu.bo', rol: 'docente' },
  { id_usuario: 7, nombres: 'Elena', apellidos: 'Aguilar Rojas', email: 'elena.aguilar@upea.edu.bo', rol: 'docente' },
];

// Postulantes de prueba con sus trámites (los que tengan `tramite`).
export const POSTULANTES_MOCK = [
  {
    id_estudiante: 101,
    ci: '5445112',
    nombres: 'Ramiro Antonio',
    apellidos: 'Quispe Mamani',
    registro_universitario: 'RU-2020-0087',
    fecha_nacimiento: '2001-09-20',
    email: 'ramiro.q@upea.edu.bo',
    telefono: '71234567',
    promedio_global: 78.5,
    tiene_cuenta: false,
    username: null,
    tramite: null,
  },
  {
    id_estudiante: 102,
    ci: '2273515',
    nombres: 'Anabel R.',
    apellidos: 'Choque Flores',
    registro_universitario: 'RU-2019-0231',
    fecha_nacimiento: '2002-03-14',
    email: 'anabel.c@upea.edu.bo',
    telefono: '68899001',
    promedio_global: 82.1,
    tiene_cuenta: true,
    username: 'Anabel_2273515',
    tramite: null,
  },
  {
    id_estudiante: 103,
    ci: '6654321',
    nombres: 'Carlos Alberto',
    apellidos: 'Mamani Ticona',
    registro_universitario: 'RU-2018-0112',
    fecha_nacimiento: '2000-11-05',
    email: 'carlos.m@upea.edu.bo',
    telefono: '70011223',
    promedio_global: 74.9,
    tiene_cuenta: true,
    username: 'Carlos_6654321',
    tramite: {
      id_tramite: 301,
      id_estudiante: 103,
      id_modalidad: 2,
      id_tutor: 5,
      tutor: { id_usuario: 5, nombres: 'Julia', apellidos: 'Paredes Villca', email: 'julia.paredes@upea.edu.bo', telefono: '70011223', rol: 'docente' },
      estado_actual: 'tema_aprobado',
      observaciones: null,
      hitos: {
        perfil_aprobado: '2026-06-10',
        tema_aprobado: '2026-06-24',
        limite_presentacion: '2027-06-10',
        modulos: {
          perfil_tesis: {
            documentos: {
              carta_solicitud: { marcado: true, archivo: { nombre: 'carta_solicitud_carlos.pdf' } },
              certificado_conclusion: { marcado: true, archivo: { nombre: 'certificado_conclusion_carlos.pdf' } },
              perfil_tesis: { marcado: true, archivo: { nombre: 'perfil_tesis_carlos.pdf' } },
            },
            observaciones: 'Documentación verificada y aprobada por Kardex.',
          },
          aprobacion_tema: {
            numero_resolucion: 'HCC-0245/2026',
            fecha_resolucion: '2026-06-24',
            tema_investigacion: 'Sistemas de riego eficientes para cultivos de altura en el altiplano boliviano.',
            tutor_id: 5,
          },
        },
      },
      created_at: '2026-05-11T08:00:00.000000Z',
      updated_at: '2026-09-01T12:30:00.000000Z',
      modalidad: { id_modalidad: 2, nombre: 'Tesis de Grado' },
      estudiante: { id_estudiante: 103, ci: '6654321', nombres: 'Carlos Alberto', apellidos: 'Mamani Ticona', registro_universitario: 'RU-2018-0112' },
      estados: [
        { nombre_estado: 'solicitud_presentada', descripcion: 'Solicitud presentada', created_at: '2026-05-11T08:00:00.000000Z' },
        { nombre_estado: 'perfil_aprobado', descripcion: 'Perfil aprobado', created_at: '2026-06-10T10:00:00.000000Z' },
        { nombre_estado: 'tema_aprobado', descripcion: 'Tema aprobado', created_at: '2026-06-24T11:00:00.000000Z' },
      ],
      siguientes_estados: SIGUIENTES_POR_ESTADO['tema_aprobado'],
      secuencia: SECUENCIAS['Tesis de Grado'],
    },
  },
  {
    id_estudiante: 104,
    ci: '3344521',
    nombres: 'María Fernanda',
    apellidos: 'Lima Villanueva',
    registro_universitario: 'RU-2017-0344',
    fecha_nacimiento: '1999-07-22',
    email: 'maria.l@upea.edu.bo',
    telefono: '69887766',
    promedio_global: 86.3,
    tiene_cuenta: true,
    username: 'Maria_3344521',
    tramite: {
      id_tramite: 302,
      id_estudiante: 104,
      id_modalidad: 2,
      id_tutor: 7,
      tutor: { id_usuario: 7, nombres: 'Elena', apellidos: 'Aguilar Rojas', email: 'elena.aguilar@upea.edu.bo', telefono: '69887766', rol: 'docente' },
      estado_actual: 'tribunal_asignado',
      observaciones: null,
      hitos: {
        perfil_aprobado: '2026-02-08',
        tema_aprobado: '2026-02-15',
        tribunal_asignado: '2026-08-20',
        modulos: {
          perfil_tesis: {
            documentos: {
              carta_solicitud: { marcado: true, archivo: { nombre: 'carta_solicitud_maria.pdf' } },
              certificado_conclusion: { marcado: true, archivo: { nombre: 'certificado_conclusion_maria.pdf' } },
              perfil_tesis: { marcado: true, archivo: { nombre: 'perfil_tesis_maria.pdf' } },
            },
            observaciones: '',
          },
          aprobacion_tema: {
            numero_resolucion: 'HCC-0031/2026',
            fecha_resolucion: '2026-02-15',
            tema_investigacion: 'Incidencia de la educación digital en el rendimiento académico universitario.',
            tutor_id: 7,
          },
          tribunal_revisor: {
            numero_resolucion: 'HCC-0158/2026',
            fecha_resolucion: '2026-08-20',
            tribunal: [
              { rol: 'presidente', nombre: 'Dr. Roberto Sánchez Calle' },
              { rol: 'vocal', nombre: 'Mgr. Julia Paredes Villca' },
              { rol: 'secretario', nombre: 'Lic. Elena Aguilar Rojas' },
            ],
          },
        },
      },
      created_at: '2026-01-20T08:00:00.000000Z',
      updated_at: '2026-09-18T09:00:00.000000Z',
      modalidad: { id_modalidad: 2, nombre: 'Tesis de Grado' },
      estudiante: { id_estudiante: 104, ci: '3344521', nombres: 'María Fernanda', apellidos: 'Lima Villanueva', registro_universitario: 'RU-2017-0344' },
      estados: [
        { nombre_estado: 'solicitud_presentada', descripcion: 'Solicitud presentada', created_at: '2026-01-20T08:00:00.000000Z' },
        { nombre_estado: 'perfil_aprobado', descripcion: 'Perfil aprobado', created_at: '2026-02-08T10:00:00.000000Z' },
        { nombre_estado: 'tema_aprobado', descripcion: 'Tema aprobado', created_at: '2026-02-15T11:00:00.000000Z' },
        { nombre_estado: 'tribunal_asignado', descripcion: 'Tribunal asignado', created_at: '2026-08-20T09:00:00.000000Z' },
      ],
      siguientes_estados: SIGUIENTES_POR_ESTADO['tribunal_asignado'],
      secuencia: SECUENCIAS['Tesis de Grado'],
    },
  },
];

// Estrategia de búsqueda por CI o R.U. (mismo criterio que el backend).
export function encontrarPostulante(identificador) {
  const clave = String(identificador || '').trim().toLowerCase();
  return POSTULANTES_MOCK.find(
    (p) =>
      String(p.ci).toLowerCase() === clave ||
      String(p.registro_universitario).toLowerCase() === clave,
  ) || null;
}

function clonar(valor) {
  return JSON.parse(JSON.stringify(valor));
}

export function usernameDeMock(p) {
  const primerNombre = String(p.nombres || '').trim().split(' ')[0] || 'Estudiante';
  return `${primerNombre.charAt(0).toUpperCase()}${primerNombre.slice(1).toLowerCase()}_${p.ci}`;
}

export function passwordDeMock(p) {
  return String(p.fecha_nacimiento || '').split('-').reverse().join('-');
}

// Simula POST /kardex/postulantes/buscar.
export function buscarMock(identificador) {
  const p = encontrarPostulante(identificador);
  if (!p) return null;
  return { postulante: clonar(p) };
}

// Simula GET /kardex/postulantes/consultar.
export function consultarMock(identificador) {
  const p = encontrarPostulante(identificador);
  if (!p) return null;
  return { postulante: clonar(p), tramite: p.tramite ? clonar(p.tramite) : null };
}

// Simula POST /kardex/postulantes/asignar-modalidad.
export function asignarMock(identificador, idModalidad) {
  const p = encontrarPostulante(identificador);
  if (!p) return null;

  const modalidad = MODALIDADES_MOCK.find((m) => m.id_modalidad === Number(idModalidad));

  if (p.tramite) {
    throw { message: 'El postulante ya tiene un trámite en curso para esta modalidad.' };
  }

  const credenciales = p.tiene_cuenta
    ? { generadas: false, ya_existia: true, username: p.username, password: null }
    : { generadas: true, ya_existia: false, username: usernameDeMock(p), password: passwordDeMock(p) };

  const tramite = {
    id_tramite: 900 + p.id_estudiante,
    id_estudiante: p.id_estudiante,
    id_modalidad: Number(idModalidad),
    id_tutor: null,
    estado_actual: 'solicitud_presentada',
    observaciones: null,
    hitos: {},
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
    modalidad: { id_modalidad: modalidad.id_modalidad, nombre: modalidad.nombre },
    estudiante: { id_estudiante: p.id_estudiante, ci: p.ci, nombres: p.nombres, apellidos: p.apellidos, registro_universitario: p.registro_universitario },
    estados: [
      { nombre_estado: 'solicitud_presentada', descripcion: 'Solicitud presentada', created_at: new Date().toISOString() },
    ],
    siguientes_estados: SIGUIENTES_POR_ESTADO['solicitud_presentada'],
    secuencia: SECUENCIAS[modalidad.nombre] || SECUENCIAS['Tesis de Grado'],
  };

  return {
    postulante: { ...clonar(p), tiene_cuenta: true, username: credenciales.username, tramite: clonar(tramite) },
    credenciales,
    tramite,
  };
}

// Simula POST /tramites/{id}/transicionar (avance de estados).
export function transicionarMock(tramite, nuevoEstado, observaciones) {
  const t = clonar(tramite);
  t.estado_actual = nuevoEstado;
  t.observaciones = observaciones || t.observaciones;
  t.updated_at = new Date().toISOString();
  t.estados = [...(t.estados || []), { nombre_estado: nuevoEstado, descripcion: nuevoEstado, created_at: new Date().toISOString() }];
  t.siguientes_estados = SIGUIENTES_POR_ESTADO[nuevoEstado] || [];
  if (['aprobado', 'reprobado', 'rechazado'].includes(nuevoEstado)) t.hitos = { ...(t.hitos || {}), [nuevoEstado]: new Date().toISOString().slice(0, 10) };
  if (nuevoEstado === 'tutor_asignado') t.hitos = { ...(t.hitos || {}), tutor_asignado: new Date().toISOString().slice(0, 10) };
  return t;
}

// Simula POST /tramites/{id}/asignar-tutor (asignación de tutor docente).
export function asignarTutorMock(tramite, idTutor) {
  const t = clonar(tramite);
  const tutor = DOCENTES_MOCK.find((d) => d.id_usuario === Number(idTutor));
  if (!tutor) {
    throw { message: 'El tutor seleccionado no existe o no es un docente válido.' };
  }
  t.id_tutor = tutor.id_usuario;
  t.tutor = { ...tutor };
  t.hitos = { ...(t.hitos || {}), tutor_asignado: new Date().toISOString().slice(0, 10) };
  t.updated_at = new Date().toISOString();
  return t;
}

// Estado objetivo de cada módulo del flujo de titulación (espejo del backend).
const ESTADOS_MODULOS = {
  perfil_tesis: 'perfil_aprobado',
  aprobacion_tema: 'tema_aprobado',
  tribunal_revisor: 'tribunal_asignado',
  defensa: 'defensa_aprobada',
  reporte: 'reporte_generado',
  publicacion: 'titulado',
};

// Simula POST /tramites/{id}/flujo/{modulo} (guardado de un módulo del flujo).
export function aplicarModuloMock(tramite, moduloId, datos) {
  const t = clonar(tramite);
  const estado = ESTADOS_MODULOS[moduloId];
  if (!estado) {
    throw { message: 'El módulo del flujo no es reconocido.' };
  }
  const hoy = new Date().toISOString().slice(0, 10);
  // La defensa se registra en dos pasos. La clave `paso` no se persiste (espejo
  // del TramiteService): el paso 1 guarda los datos sin transicionar el estado.
  const paso = Number(datos.paso ?? 2);
  const { paso: _paso, ...datosGuardar } = datos;
  t.updated_at = new Date().toISOString();
  t.hitos = {
    ...(t.hitos || {}),
    modulos: { ...(t.hitos?.modulos || {}), [moduloId]: datosGuardar },
  };
  if (moduloId === 'defensa' && paso === 1) {
    return t;
  }
  t.estado_actual = estado;
  t.hitos = { ...t.hitos, [estado]: hoy };
  if (moduloId === 'aprobacion_tema' && datos.tutor_id) {
    const tutor = DOCENTES_MOCK.find((d) => d.id_usuario === Number(datos.tutor_id));
    if (tutor) {
      t.id_tutor = tutor.id_usuario;
      t.tutor = { ...tutor };
    }
  }
  t.estados = [...(t.estados || []), { nombre_estado: estado, descripcion: estado, created_at: new Date().toISOString() }];
  t.siguientes_estados = SIGUIENTES_POR_ESTADO[estado] || [];
  return t;
}

// Estados terminales que dejan de considerarse "en curso" en el resumen.
const ESTADOS_TERMINALES = ['aprobado', 'reprobado', 'reprobado_ausencia', 'rechazado', 'titulado'];

const esEnCurso = (estado) => !ESTADOS_TERMINALES.includes(estado);

// Simula GET /kardex/resumen (resumen estadístico del dashboard).
export function resumenMock() {
  const tramites = POSTULANTES_MOCK
    .map((p) => p.tramite)
    .filter(Boolean);

  const totalPorModalidad = (idModalidad) =>
    tramites.filter((t) => t.id_modalidad === idModalidad);

  const modalidades = MODALIDADES_MOCK.map((m) => {
    const trámites = totalPorModalidad(m.id_modalidad);
    return {
      id_modalidad: m.id_modalidad,
      nombre: m.nombre,
      total: trámites.length,
      aprobados: trámites.filter((t) => t.estado_actual === 'aprobado').length,
      en_curso: trámites.filter((t) => esEnCurso(t.estado_actual)).length,
    };
  });

  return {
    modalidades,
    estudiantes: {
      total: POSTULANTES_MOCK.length,
      con_cuenta: POSTULANTES_MOCK.filter((p) => p.tiene_cuenta).length,
      con_tramite: tramites.length,
    },
    tutores: {
      total: DOCENTES_MOCK.length,
      activos: new Set(tramites.map((t) => t.id_tutor).filter(Boolean)).size,
    },
    totales: {
      tramites: tramites.length,
      aprobados: tramites.filter((t) => t.estado_actual === 'aprobado').length,
      en_curso: tramites.filter((t) => esEnCurso(t.estado_actual)).length,
    },
  };
}