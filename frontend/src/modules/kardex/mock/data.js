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
    'pendiente_concejo_universitario',
    'perfil_aprobado',
    'tutor_asignado',
    'investigacion_en_desarrollo',
    'documento_final_presentado',
    'comision_revisora',
    'suficiente',
    'solicitud_fecha_defensa',
    'defensa_programada',
    'defensa_en_curso',
    'aprobado',
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

// Estados siguientes permitidos por estado (espejo del backend, Tesis y general).
export const SIGUIENTES_POR_ESTADO = {
  solicitud_presentada: ['pendiente_concejo_universitario'],
  pendiente_concejo_universitario: ['perfil_aprobado', 'perfil_rechazado'],
  perfil_aprobado: ['tutor_asignado'],
  perfil_rechazado: ['investigacion_en_desarrollo'],
  tutor_asignado: ['investigacion_en_desarrollo'],
  investigacion_en_desarrollo: ['documento_final_presentado'],
  documento_final_presentado: ['comision_revisora'],
  comision_revisora: ['suficiente', 'insuficiente'],
  suficiente: ['solicitud_fecha_defensa'],
  insuficiente: ['comision_revisora'],
  solicitud_fecha_defensa: ['defensa_programada'],
  defensa_programada: ['defensa_en_curso'],
  defensa_en_curso: ['aprobado', 'reprobado'],
  correcciones_90_dias: ['solicitud_fecha_defensa'],
  examen_programado: ['defensa_en_curso'],
  aprobado: [],
  reprobado: [],
  rechazado: [],
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
      estado_actual: 'investigacion_en_desarrollo',
      observaciones: null,
      hitos: { perfil_aprobado: '2026-06-10', tutor_asignado: '2026-06-18', limite_presentacion: '2027-06-10' },
      created_at: '2026-05-11T08:00:00.000000Z',
      updated_at: '2026-09-01T12:30:00.000000Z',
      modalidad: { id_modalidad: 2, nombre: 'Tesis de Grado' },
      estudiante: { id_estudiante: 103, ci: '6654321', nombres: 'Carlos Alberto', apellidos: 'Mamani Ticona', registro_universitario: 'RU-2018-0112' },
      estados: [
        { nombre_estado: 'solicitud_presentada', descripcion: 'Solicitud presentada', created_at: '2026-05-11T08:00:00.000000Z' },
        { nombre_estado: 'pendiente_concejo_universitario', descripcion: 'Consejo Universitario', created_at: '2026-05-14T09:00:00.000000Z' },
        { nombre_estado: 'perfil_aprobado', descripcion: 'Perfil aprobado', created_at: '2026-06-10T10:00:00.000000Z' },
        { nombre_estado: 'investigacion_en_desarrollo', descripcion: 'Investigación en desarrollo', created_at: '2026-06-18T11:00:00.000000Z' },
      ],
      siguientes_estados: SIGUIENTES_POR_ESTADO['investigacion_en_desarrollo'],
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
      estado_actual: 'defensa_programada',
      observaciones: null,
      hitos: { perfil_aprobado: '2026-02-08', tutor_asignado: '2026-02-15', limite_presentacion: '2027-02-08' },
      created_at: '2026-01-20T08:00:00.000000Z',
      updated_at: '2026-09-18T09:00:00.000000Z',
      modalidad: { id_modalidad: 2, nombre: 'Tesis de Grado' },
      estudiante: { id_estudiante: 104, ci: '3344521', nombres: 'María Fernanda', apellidos: 'Lima Villanueva', registro_universitario: 'RU-2017-0344' },
      estados: [
        { nombre_estado: 'solicitud_presentada', descripcion: 'Solicitud presentada', created_at: '2026-01-20T08:00:00.000000Z' },
        { nombre_estado: 'perfil_aprobado', descripcion: 'Perfil aprobado', created_at: '2026-02-08T10:00:00.000000Z' },
        { nombre_estado: 'investigacion_en_desarrollo', descripcion: 'Investigación en desarrollo', created_at: '2026-02-15T11:00:00.000000Z' },
        { nombre_estado: 'documento_final_presentado', descripcion: 'Documento final presentado', created_at: '2026-08-30T09:00:00.000000Z' },
        { nombre_estado: 'comision_revisora', descripcion: 'Comisión Revisora', created_at: '2026-09-02T09:00:00.000000Z' },
        { nombre_estado: 'suficiente', descripcion: 'Suficiente', created_at: '2026-09-12T09:00:00.000000Z' },
        { nombre_estado: 'solicitud_fecha_defensa', descripcion: 'Fecha de defensa solicitada', created_at: '2026-09-13T09:00:00.000000Z' },
        { nombre_estado: 'defensa_programada', descripcion: 'Defensa programada', created_at: '2026-09-18T09:00:00.000000Z' },
      ],
      siguientes_estados: SIGUIENTES_POR_ESTADO['defensa_programada'],
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

// Estados terminales que dejan de considerarse "en curso" en el resumen.
const ESTADOS_TERMINALES = ['aprobado', 'reprobado', 'reprobado_ausencia', 'rechazado'];

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