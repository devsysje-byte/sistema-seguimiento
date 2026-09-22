export const ESTADOS_TERMINALES = ['aprobado', 'reprobado', 'rechazado', 'reprobado_ausencia'];

export const ESTADOS_OK = [
  'aprobado',
  'documentacion_ok',
  'perfil_aprobado',
  'tema_aprobado',
  'monografia_aprobada',
  'conformidad_tutor',
];

export const ESTADOS_ERROR = [
  'rechazado',
  'reprobado',
  'reprobado_ausencia',
  'perfil_rechazado',
  'monografia_rechazada',
];

export function formatoEstado(nombre) {
  return String(nombre || '')
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (l) => l.toUpperCase());
}

export function toneEstado(estado) {
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

export function progresoEstado(tramite) {
  const idx = (tramite?.secuencia || []).indexOf(tramite?.estado_actual);
  if (idx === -1 || !tramite?.secuencia?.length) return 0;
  return Math.min(Math.round(((idx + 1) / tramite.secuencia.length) * 100), 100);
}

export function statusGlobal(estado) {
  if (estado === 'aprobado') return { label: 'Aprobado', tone: toneEstado(estado).soft };
  if (ESTADOS_ERROR.includes(estado)) return { label: 'Rechazado', tone: toneEstado(estado).soft };
  return { label: 'En Proceso', tone: 'bg-sky-50 text-sky-700 border-sky-200' };
}

export const ROLE_LABELS = {
  admin: 'Administrador',
  estudiante: 'Estudiante',
  docente: 'Docente',
  kardex: 'Kardex',
  secretaria: 'Secretaría',
  direccion: 'Dirección',
  concejo: 'Concejo',
};

export const ROLE_TONES = {
  admin: 'bg-rose-50 text-rose-700 border-rose-200',
  estudiante: 'bg-sky-50 text-sky-700 border-sky-200',
  docente: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  kardex: 'bg-violet-50 text-violet-700 border-violet-200',
  secretaria: 'bg-amber-50 text-amber-700 border-amber-200',
  direccion: 'bg-indigo-50 text-indigo-700 border-indigo-200',
  concejo: 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
};

export function rolLabel(rol) {
  return ROLE_LABELS[rol] || rol || 'Sistema';
}

export function toneRol(rol) {
  return ROLE_TONES[rol] || 'bg-slate-100 text-slate-700 border-slate-200';
}