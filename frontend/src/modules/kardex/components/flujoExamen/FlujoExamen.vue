<template>
  <div class="space-y-5">
    <!-- Resumen del trámite + barra de progreso -->
    <div class="card p-5">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div
            class="w-12 h-12 rounded-full bg-orange-500/15 ring-1 ring-orange-500/40 flex items-center justify-center text-orange-300 font-extrabold"
          >
            {{ iniciales(estudiante) }}
          </div>
          <div>
            <h3 class="font-extrabold text-white">
              {{ estudiante.nombres }} {{ estudiante.apellidos }}
            </h3>
            <p class="text-xs text-slate-400">
              CI {{ estudiante.ci }} · {{ tramite?.modalidad?.nombre }}
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2.5">
          <EstadoBadge :estado="tramite?.estado_actual" />
        </div>
      </div>

      <div class="mt-6">
        <BarraProgresoTitulacion
          :progreso="progreso"
          :modulos="modulos"
          :estado-actual="tramite?.estado_actual"
        />
      </div>

      <div class="mt-5 grid sm:grid-cols-3 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Intentos utilizados</dt>
          <dd class="text-2xl font-extrabold text-white mt-0.5">
            {{ usados }}
            <span class="text-xs font-semibold text-slate-400">de {{ maximoIntentos }}</span>
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Intentos restantes</dt>
          <dd class="text-2xl font-extrabold mt-0.5" :class="restantes > 0 ? 'text-emerald-300' : 'text-red-400'">
            {{ restantes }}
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Fecha de la solicitud</dt>
          <dd class="text-sm font-semibold text-white mt-1">{{ fechaInicio || '—' }}</dd>
        </div>
      </div>
    </div>

    <!-- Hito inicial: la postulación del estudiante -->
    <div class="card p-5 border border-emerald-500/30">
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-300">
            <AppIcon name="inbox" :size="18" />
          </div>
          <div>
            <h4 class="font-bold text-white uppercase">Solicitud Presentada</h4>
            <p class="text-xs text-slate-400">Punto de partida del examen de grado</p>
          </div>
        </div>
        <span
          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide border bg-emerald-500/20 text-emerald-300 border-emerald-500/30"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
          Completado
        </span>
      </div>
      <p v-if="fechaInicio" class="mt-3 text-xs text-slate-400">
        Trámite presentado el <span class="font-semibold text-slate-300">{{ fechaInicio }}</span>
      </p>
    </div>

    <!-- Reapertura: el estudiante reprobó y puede volver a rendir el examen -->
    <div v-if="reabrible" class="card p-5 border border-amber-500/40">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-300">
            <AppIcon name="alert-triangle" :size="18" />
          </div>
          <div>
            <h4 class="font-bold text-white uppercase">El estudiante debe volver a rendir el examen</h4>
            <p class="text-xs text-slate-400 mt-1 max-w-xl leading-relaxed">
              {{ motivoReapertura }} Al reabrir, el trámite vuelve a
              <span class="font-semibold text-slate-300">Habilitado</span>, se reinician los dos
              sorteos y la asignación de notas, y se consume un intento
              <span class="font-semibold text-slate-300">(quedan {{ restantes }} de {{ maximoIntentos }})</span>.
              <span v-if="restantes === 0" class="block mt-1 text-red-300">
                Ya no quedan intentos: para continuar debe registrarse una nueva postulación.
              </span>
            </p>
          </div>
        </div>
        <button
          type="button"
          class="btn-warm px-5"
          :disabled="cargando || restantes === 0"
          @click="reabrir"
        >
          <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="pencil" :size="18" />
          <span class="uppercase tracking-wide">Reabrir examen</span>
        </button>
      </div>
    </div>

    <!-- 6 módulos del flujo, cada uno bloqueado hasta cerrar el anterior -->
    <div class="grid lg:grid-cols-2 gap-4">
      <template v-for="(m, i) in modulos" :key="m.id">
        <ModuloIntentosExamen
          v-if="m.id === 'verificacion_intentos'"
          :completado="m.completado"
          :en-progreso="i === proximoPendiente"
          :bloqueado="bloqueado(i)"
          :cargando="i === proximoPendiente && guardando"
          :intentos="usados"
          :maximo="maximoIntentos"
          :registro="m.registro"
          @guardar="(datos) => guardar(m.id, datos)"
        />
        <ModuloHabilitacionExamen
          v-else-if="m.id === 'habilitacion_examen'"
          :completado="m.completado"
          :en-progreso="i === proximoPendiente"
          :bloqueado="bloqueado(i)"
          :cargando="i === proximoPendiente && guardando"
          :datos="m.datos"
          :registro="m.registro"
          @guardar="(datos) => guardar(m.id, datos)"
        />
        <ModuloSorteoExamen
          v-else-if="m.id === 'sorteo_teorico' || m.id === 'sorteo_especialidad'"
          :numero="i + 1"
          :titulo="m.label"
          :descripcion="descripcionDe(m)"
          :icono="m.icon"
          :modulo-id="m.id"
          :completado="m.completado"
          :en-progreso="i === proximoPendiente"
          :bloqueado="bloqueado(i)"
          :cargando="i === proximoPendiente && guardando"
          :datos="m.datos"
          :registro="m.registro"
          @guardar="(datos) => guardar(m.id, datos)"
        />
        <ModuloAsignacionNotas
          v-else-if="m.id === 'asignacion_notas'"
          :completado="m.completado"
          :en-progreso="i === proximoPendiente"
          :bloqueado="bloqueado(i)"
          :cargando="i === proximoPendiente && guardando"
          :datos="m.datos"
          :registro="m.registro"
          :sorteo-teorico="registroDe('sorteo_teorico')"
          :sorteo-especialidad="registroDe('sorteo_especialidad')"
          @guardar="(datos) => guardar(m.id, datos)"
        />
        <ModuloReporteExamen
          v-else
          :completado="m.completado"
          :en-progreso="i === proximoPendiente"
          :bloqueado="bloqueado(i)"
          :cargando="i === proximoPendiente && guardando"
          :tramite="tramite"
          @guardar="(datos) => guardar(m.id, datos)"
        />
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useKardexStore } from '../../stores/kardex';
import {
  MAXIMO_INTENTOS,
  MODULOS_FLUJO_EXAMEN,
  NOTA_MINIMA,
  esReabrible,
  intentosRestantes,
  intentosUsados,
  modulosExamen,
  pctExamen,
  proximoModuloPendiente,
  validarModulo,
} from '@/modules/examen/utils/flujoExamen';
import { fechaLegible, iniciales } from '@/modules/kardex/utils/formato';
import { EstadoBadge } from '@/modules/tramites';
import { useToastStore } from '@/core/stores/toast';
import AppIcon from '@/ui/AppIcon.vue';
import BarraProgresoTitulacion from '../flujo/BarraProgresoTitulacion.vue';
import ModuloIntentosExamen from './ModuloIntentosExamen.vue';
import ModuloHabilitacionExamen from './ModuloHabilitacionExamen.vue';
import ModuloSorteoExamen from './ModuloSorteoExamen.vue';
import ModuloAsignacionNotas from './ModuloAsignacionNotas.vue';
import ModuloReporteExamen from './ModuloReporteExamen.vue';

const props = defineProps({
  tramite: { type: Object, default: null },
});

const emit = defineEmits(['guardado']);

const kardexStore = useKardexStore();
const toastStore = useToastStore();

const guardando = ref(false);

const estudiante = computed(() => props.tramite?.estudiante || {});
const progreso = computed(() => pctExamen(props.tramite));
const proximoPendiente = computed(() => proximoModuloPendiente(props.tramite));
const reabrible = computed(() => esReabrible(props.tramite));
const usados = computed(() => intentosUsados(props.tramite));
const restantes = computed(() => intentosRestantes(props.tramite));
const maximoIntentos = MAXIMO_INTENTOS;

const modulos = computed(() =>
  modulosExamen(props.tramite).map((m) => ({
    ...m,
    datos: props.tramite?.hitos?.modulos?.[m.id] || null,
  })),
);

const fechaInicio = computed(() =>
  fechaLegible(props.tramite?.hitos?.solicitud_presentada || props.tramite?.created_at),
);

/**
 * Texto que explica por qué el trámite quedó reabrible.
 *
 * Las notas se asignan en el módulo 5 y cierran los dos exámenes a la vez: si
 * alguna quedó por debajo del mínimo (o el estudiante no asistió al sorteo), el
 * trámite queda en un estado reabrible. Titularse exige aprobar ambos exámenes,
 * por eso `examen_teorico_reprobado` puede coexistir con una nota aprobada de
 * especialidad (y viceversa).
 */
const motivoReapertura = computed(() => {
  const notas = props.tramite?.hitos?.modulos?.asignacion_notas || {};
  const teorico = registroDe('sorteo_teorico');
  const especialidad = registroDe('sorteo_especialidad');
  const asistioT = asistioA(teorico);
  const asistioE = asistioA(especialidad);
  const notaT = Number(notas.nota_teorico);
  const notaE = Number(notas.nota_especialidad);
  const teoricoOk = asistioT && !Number.isNaN(notaT) && notaT >= NOTA_MINIMA;
  const especialidadOk = asistioE && !Number.isNaN(notaE) && notaE >= NOTA_MINIMA;
  const especialidadPrimero = props.tramite?.estado_actual === 'examen_especialidad_reprobado';

  if (teoricoOk && !especialidadOk) {
    return especialidadPrimero
      ? detalleReapertura('especialidad', asistioE, notas.nota_especialidad)
      : 'Aprobó el examen teórico, pero el examen de especialidad no fue aprobado.';
  }

  if (!teoricoOk && especialidadOk) {
    return 'Aprobó el examen de especialidad, pero el examen teórico no fue aprobado.';
  }

  return especialidadPrimero
    ? detalleReapertura('especialidad', asistioE, notas.nota_especialidad)
    : detalleReapertura('teorico', asistioT, notas.nota_teorico);
});

function detalleReapertura(examen, asistio, nota) {
  const etiqueta = examen === 'teorico' ? 'el examen teórico' : 'el examen de especialidad';
  if (!asistio) return `El estudiante no asistió al sorteo de ${etiqueta}.`;
  if (nota === null || nota === undefined || nota === '') {
    return `El estudiante reprobó ${etiqueta}.`;
  }
  return `El estudiante reprobó ${etiqueta} con ${nota} puntos.`;
}

/** Último registro del historial de una etapa del flujo. */
function registroDe(etapa) {
  return modulos.value.find((m) => m.id === etapa)?.registro || null;
}

function asistioA(registro) {
  return ['asistio', 'aprobado', 'reprobado'].includes(registro?.resultado);
}

/** Un módulo sólo se habilita cuando es el siguiente pendiente. */
function bloqueado(indice) {
  if (reabrible.value) return true;
  return indice !== proximoPendiente.value;
}

function descripcionDe(m) {
  return m.id === 'sorteo_teorico'
    ? 'Sorteo de bolo y fecha del examen teórico. No asistir reproba el examen, pero el estudiante puede seguir al sorteo de especialidad; la nota se registra en Asignación de Notas.'
    : 'Sorteo de bolo y fecha del examen de especialidad. No asistir reproba el examen; la nota se registra en Asignación de Notas.';
}

async function guardar(moduloId, datos) {
  const modulo = MODULOS_FLUJO_EXAMEN.find((m) => m.id === moduloId);
  const contexto = moduloId === 'verificacion_intentos'
    ? { intentos: usados.value, maximo: maximoIntentos }
    : moduloId === 'asignacion_notas'
      ? {
          asistio_teorico: asistioA(registroDe('sorteo_teorico')),
          asistio_especialidad: asistioA(registroDe('sorteo_especialidad')),
        }
      : {};

  const res = validarModulo(moduloId, datos, contexto);

  if (!res.ok) {
    toastStore.error(res.errores.join(' · '));
    return;
  }

  guardando.value = true;
  const actualizado = await kardexStore.guardarModuloExamen(moduloId, datos);
  guardando.value = false;

  if (!actualizado) {
    toastStore.error(kardexStore.errorMessage || 'No se pudo guardar el módulo.');
    return;
  }

  if (moduloId === 'asignacion_notas' && esReabrible(actualizado)) {
    toastStore.warning('Notas asignadas: al menos un examen quedó REPROBADO.');
  } else {
    toastStore.success(`Módulo completado — estado: ${modulo.badge}`);
  }
  emit('guardado', actualizado);
}

async function reabrir() {
  guardando.value = true;
  const actualizado = await kardexStore.reabrirExamen(
    'El estudiante solicitó volver a rendir el examen de grado.',
  );
  guardando.value = false;

  if (!actualizado) {
    toastStore.error(kardexStore.errorMessage || 'No se pudo reabrir el examen.');
    return;
  }

  toastStore.success('Examen reabierto. El estudiante vuelve a sortear desde Habilitado.');
  emit('guardado', actualizado);
}
</script>
