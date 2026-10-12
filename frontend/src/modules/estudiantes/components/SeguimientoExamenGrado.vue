<template>
  <div class="space-y-5">
    <!-- Resumen del trámite: estado global e intentos del examen -->
    <div class="card overflow-hidden">
      <div class="h-2 bg-orange"></div>
      <div class="p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="text-base font-extrabold text-white">Mi Examen de Grado</h2>
            <p class="text-xs text-slate-400">
              Estado:
              <span class="font-bold text-orange-300">{{ formatoEstado(estadoActual) }}</span>
            </p>
          </div>
          <div class="flex items-center gap-2 text-right">
            <div>
              <p class="text-[10px] uppercase tracking-wide text-slate-400 font-semibold">Intentos utilizados</p>
              <p class="text-lg font-extrabold text-white">
                {{ usados }} <span class="text-xs font-semibold text-slate-400">de {{ maximo }}</span>
              </p>
            </div>
            <span
              class="px-3 py-1.5 rounded-full text-xs font-bold border inline-flex items-center gap-1.5"
              :class="tone.soft"
            >
              <AppIcon :name="iconoEstado" :size="13" />
              {{ tone.label }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Intentos agotados: debe volver a solicitar otra modalidad -->
    <div
      v-if="intentosAgotados"
      class="rounded-2xl border border-red-500/40 bg-red-900/40 p-4 flex items-start gap-3"
      role="alert"
    >
      <AppIcon name="x-circle" :size="22" class="mt-0.5 shrink-0 text-red-400" />
      <p v-if="intentoActualEnCurso" class="text-sm font-semibold text-red-200 leading-relaxed">
        Llevas {{ usados }} de {{ maximo }} intentos del Examen de Grado, que son todos los
        permitidos. Esta es tu última oportunidad: si no apruebas la ronda en curso,
        deberás volver a solicitar <span class="text-white">otra modalidad de titulación</span>.
      </p>
      <p v-else class="text-sm font-semibold text-red-200 leading-relaxed">
        Agotaste tus {{ maximo }} intentos del Examen de Grado sin aprobarlo.
        Debes volver a solicitar <span class="text-white">otra modalidad de titulación</span>
        cuando Kardex lo habilite.
      </p>
    </div>

    <div class="grid lg:grid-cols-2 gap-4 items-start">
      <!-- Intentos realizados -->
      <div class="card overflow-hidden">
        <div class="h-2 bg-gradient-to-r from-slate-500 to-slate-400"></div>
        <div class="p-5">
          <h3 class="text-base font-extrabold text-white inline-flex items-center gap-2">
            <AppIcon name="clipboard" :size="18" class="text-orange-300" />
            Mis intentos del examen
          </h3>
          <p class="mt-1 text-xs text-slate-400">
            Cada ronda de exámenes registrada por Kardex.
          </p>

          <ol v-if="intentos.length" class="mt-4 space-y-3">
            <li
              v-for="it in intentos"
              :key="it.numero"
              class="rounded-xl bg-white/5 ring-1 ring-white/10 p-3.5"
              :class="{ 'ring-amber-500/40': it.enCurso }"
            >
              <div class="flex flex-wrap items-center justify-between gap-2">
                <h4 class="font-bold text-white">Intento {{ it.numero }}</h4>
                <div class="flex items-center gap-2">
                  <span
                    v-if="it.enCurso"
                    class="bg-orange-500/20 text-orange-200 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide ring-1 ring-orange-500/30"
                  >
                    En curso
                  </span>
                  <span
                    v-else
                    class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide ring-1"
                    :class="it.resultadoFinal ? 'bg-emerald-500/20 text-emerald-300 ring-emerald-500/30' : 'bg-red-900/50 text-red-400 ring-red-500/30'"
                  >
                    {{ it.resultadoFinal ? 'Aprobado' : 'Reprobado' }}
                  </span>
                  <span v-if="it.fecha" class="text-[11px] text-slate-400">
                    {{ fechaHora(it.fecha) }}
                  </span>
                </div>
              </div>

              <p v-if="it.resolucion" class="mt-1.5 text-xs text-slate-400">
                Resolución HCC:
                <span class="font-semibold text-slate-200">{{ it.resolucion }}</span>
              </p>

              <dl class="mt-2.5 grid sm:grid-cols-2 gap-2 text-xs">
                <div class="rounded-lg bg-white/5 px-2.5 py-2">
                  <dt class="dato-label">Examen teórico</dt>
                  <dd class="mt-0.5 font-semibold text-white leading-snug">
                    {{ it.teorico.tema || '—' }}
                  </dd>
                  <dd class="text-[10px] text-slate-400 mt-0.5">
                    <template v-if="it.teorico.fecha">Fecha: {{ fechaCorta(it.teorico.fecha) }} · </template>
                    <span :class="notaClase(it.teorico)">
                      {{ textoNota(it.teorico) }}
                    </span>
                  </dd>
                </div>
                <div class="rounded-lg bg-white/5 px-2.5 py-2">
                  <dt class="dato-label">Examen de especialidad</dt>
                  <dd class="mt-0.5 font-semibold text-white leading-snug">
                    {{ it.especialidad.tema || '—' }}
                  </dd>
                  <dd class="text-[10px] text-slate-400 mt-0.5">
                    <template v-if="it.especialidad.fecha">Fecha: {{ fechaCorta(it.especialidad.fecha) }} · </template>
                    <span :class="notaClase(it.especialidad)">
                      {{ textoNota(it.especialidad) }}
                    </span>
                  </dd>
                </div>
              </dl>

              <p v-if="it.detalle" class="mt-2.5 text-[11px] leading-relaxed text-slate-400">
                {{ it.detalle }}
              </p>
            </li>
          </ol>

          <div v-else class="mt-4 rounded-xl border border-dashed border-white/20 bg-white/5 px-3 py-4 text-center text-sm text-slate-400">
            Aún no hay intentos registrados. Kardex registrará cada ronda del examen aquí.
          </div>
        </div>
      </div>

      <!-- Actualizaciones realizadas por Kardex -->
      <div class="card overflow-hidden p-5">
        <h3 class="text-base font-extrabold text-white inline-flex items-center gap-2">
          <AppIcon name="trending-up" :size="18" class="text-orange-300" />
          Actualizaciones realizadas por Kardex
        </h3>
        <p class="mt-1 text-xs text-slate-400">
          Todas las actualizaciones del trámite, de la más antigua a la más reciente.
        </p>

        <ol v-if="actualizaciones.length" class="relative ml-2 mt-5 space-y-4 border-l-2 border-white/10 pl-6">
          <li v-for="a in actualizaciones" :key="a.id_estado" class="relative">
            <span
              class="absolute -left-[35px] top-1 w-3 h-3 rounded-full ring-4 ring-primary"
              :class="a.tone.dot"
            ></span>
            <h4 class="text-sm font-bold text-white">{{ formatoEstado(a.nombre_estado) }}</h4>
            <p class="text-sm text-slate-300 mt-0.5 leading-snug">
              {{ a.observaciones || 'Estado registrado.' }}
            </p>
            <p class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-slate-400">
              <span v-if="a.responsable" class="inline-flex items-center gap-1">
                <AppIcon name="user" :size="12" class="shrink-0" />
                {{ a.responsable.nombres }} {{ a.responsable.apellidos }}
                ({{ rolLabel(a.responsable.rol) }})
              </span>
              <span v-if="a.created_at" class="inline-flex items-center gap-1">
                <AppIcon name="calendar" :size="12" class="shrink-0" />
                {{ fechaHora(a.created_at) }}
              </span>
            </p>
          </li>
        </ol>

        <div
          v-else
          class="mt-4 rounded-xl border border-dashed border-white/20 bg-white/5 px-3 py-4 text-center text-sm text-slate-400"
        >
          Aún no hay actualizaciones registradas.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// Seguimiento del estudiante para la modalidad de Examen de Grado.
//
// Muestra: (1) el resumen de intentos consumidos, (2) TODAS las rondas del
// examen que registró Kardex (agrupadas por `numero_intentos` del historial
// especializado) y (3) TODAS las actualizaciones del trámite con su responsable
// y fecha. No muestra ni el módulo ni el estado del reporte: eso pertenece al
// panel de gestión.
//
// Si el estudiante agotó los intentos y no aprobó, se le indica que debe volver
// a solicitar otra modalidad de titulación.
import { computed } from 'vue';
import { formatoEstado, statusGlobal, toneEstado } from '@/modules/tramites';
import { rolLabel } from '@/core/roles';
import {
  MAXIMO_INTENTOS,
  historialExamen,
  intentosUsados,
} from '@/modules/examen/utils/flujoExamen';
import AppIcon from '@/ui/AppIcon.vue';

const props = defineProps({
  tramite: { type: Object, required: true },
});

const maximo = MAXIMO_INTENTOS;
const estadoActual = computed(() => props.tramite?.estado_actual);
const usados = computed(() => intentosUsados(props.tramite));

// `aprobado` (ambos exámenes rendidos) y `titulado` (reporte emitido) son los
// cierres de éxito del Examen de Grado: con ellos no corresponde el aviso.
const exito = computed(() => ['aprobado', 'titulado'].includes(estadoActual.value));
const intentosAgotados = computed(() => usados.value >= maximo && !exito.value);
const intentoActualEnCurso = computed(() => {
  const ultimo = intentos.value[intentos.value.length - 1];
  return Boolean(ultimo?.enCurso);
});

const tone = computed(() => statusGlobal(estadoActual.value));

/** Todas las actualizaciones del trámite en orden cronológico (de antiguo a nuevo). */
const actualizaciones = computed(() =>
  [...(props.tramite?.estados || [])]
    .sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
    .map((a) => ({ ...a, tone: toneEstado(a.nombre_estado) })),
);

const iconoEstado = computed(() => {
  if (exito.value) return 'award';
  if (['examen_teorico_reprobado', 'examen_especialidad_reprobado', 'examen_ambos_reprobados'].includes(estadoActual.value)) {
    return 'x-circle';
  }
  return 'clock';
});

/**
 * Agrupa el historial por intento (`numero_intentos` de cada fila) y resume cada
 * ronda con su resolución vigente, temas, fechas, notas y resultado.
 */
const intentos = computed(() => {
  const filas = historialExamen(props.tramite);
  const grupos = new Map();

  filas.forEach((f) => {
    const n = Number(f.numero_intentos ?? 1);
    if (!grupos.has(n)) grupos.set(n, []);
    grupos.get(n).push(f);
  });

  let resolucionCargada = '';
  const resultado = [];

  [...grupos.keys()]
    .sort((a, b) => a - b)
    .forEach((n) => {
      const grupo = grupos.get(n);
      const habilitacion = grupo.find((f) => f.etapa === 'habilitacion_examen');
      if (habilitacion?.numero_resolucion) resolucionCargada = habilitacion.numero_resolucion;

      const sorteoTeorico = grupo.find((f) => f.etapa === 'sorteo_teorico');
      const sorteoEspecialidad = grupo.find((f) => f.etapa === 'sorteo_especialidad');
      const asignacion = [...grupo].reverse().find((f) => f.etapa === 'asignacion_notas');
      const reapertura = grupo.find((f) => f.etapa === 'reapertura');
      const ultima = grupo[grupo.length - 1];

      const teorico = resumenExamen(sorteoTeorico, asignacion, 'examen_teorico');
      const especialidad = resumenExamen(sorteoEspecialidad, asignacion, 'examen_especialidad');

      resultado.push({
        numero: n,
        fecha: ultima?.created_at || '',
        resolucion: resolucionCargada,
        enCurso: !asignacion,
        resultadoFinal: Boolean(asignacion && asignacion.resultado === 'aprobado'),
        detalle: asignacion?.observaciones || reapertura?.observaciones || '',
        teorico,
        especialidad,
      });
    });

  return resultado;
});

/**
 * Resume un examen dentro de un intento: tema, fecha, nota y resultado.
 *
 * `conservado`: un examen ya aprobado en esta resolución no vuelve a sortearse
 * en los intentos siguientes, así que su tema/nota se leen de
 * `hitos.examen_aprobado` (fuente de verdad de los aprobados).
 */
function resumenExamen(sorteo, asignacion, clave) {
  const conservado = props.tramite?.hitos?.examen_aprobado?.[clave];
  const enCurso = !asignacion;

  if (sorteo && sorteo.resultado === 'no_asistio') {
    return { tema: sorteo.tema, fecha: sorteo.fecha_examen || '', nota: null, resultado: 'reprobado', sinRendir: true };
  }

  if (!sorteo && conservado) {
    return {
      tema: conservado.tema,
      fecha: conservado.fecha_examen || '',
      nota: conservado.nota,
      resultado: enCurso ? 'conservado' : 'aprobado',
      sinRendir: false,
    };
  }

  const nota = asignacion ? extraerNota(asignacion, clave === 'examen_teorico') : null;

  return {
    tema: sorteo?.tema || null,
    fecha: sorteo?.fecha_examen || '',
    nota,
    resultado: enCurso ? 'pendiente' : resultadoExamen(asignacion, clave),
    sinRendir: false,
  };
}

/** Aprobación de un examen según el estado con el que cerró la asignación. */
function resultadoExamen(asignacion, clave) {
  const estado = asignacion?.nombre_estado || '';
  if (estado === 'aprobado') return 'aprobado';
  if (clave === 'examen_teorico') return estado === 'examen_especialidad_reprobado' ? 'aprobado' : 'reprobado';
  return estado === 'examen_teorico_reprobado' ? 'aprobado' : 'reprobado';
}

/**
 * Nota del examen desde el registro de asignación de notas.
 *
 * La nota del teórico queda en la columna `nota` del registro; la del examen de
 * especialidad solo vive en la observación oficial que genera el backend
 * ("examen de especialidad: 78 puntos" / "no asistió").
 */
function extraerNota(asignacion, esTeorico) {
  if (esTeorico) {
    const nota = asignacion.nota;
    return nota !== null && nota !== undefined && nota !== '' ? String(nota) : null;
  }

  const texto = String(asignacion.observaciones || '');
  const coincidencia = texto.match(/examen de especialidad:\s*(?:([\d.]+)\s*puntos|no asistió)/i);
  return coincidencia?.[1] || null;
}

function textoNota(examen) {
  if (examen.sinRendir) return 'No asistió';
  if (examen.resultado === 'pendiente') return 'Nota pendiente';
  if (examen.resultado === 'conservado') return `Aprobado en intento anterior (Nota ${examen.nota})`;
  if (examen.nota === null) return examen.resultado === 'aprobado' ? 'Aprobado' : 'Reprobado';
  return `${examen.resultado === 'aprobado' ? 'Aprobado' : 'Reprobado'} — Nota ${examen.nota}`;
}

function notaClase(examen) {
  if (examen.resultado === 'aprobado' || examen.resultado === 'conservado') return 'text-emerald-300';
  if (examen.resultado === 'pendiente') return 'text-orange-300';
  return 'text-red-400';
}

/** Formatea una fecha ISO en formato corto local (dd mmm yyyy, hh:mm). */
function fechaHora(iso) {
  if (!iso) return '';
  return new Date(iso).toLocaleString('es-BO', {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
  });
}

/** Formatea una fecha de examen (YYYY-MM-DD) en formato corto. */
function fechaCorta(iso) {
  if (!iso) return '—';
  return new Date(iso + 'T00:00:00').toLocaleDateString('es-BO', {
    day: '2-digit', month: 'short', year: 'numeric',
  });
}
</script>