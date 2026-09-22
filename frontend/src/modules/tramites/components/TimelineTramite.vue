<template>
  <div v-if="tramite">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
      <div>
        <h1 class="text-xl font-bold text-slate-900">{{ title }}</h1>
        <p class="text-sm text-slate-500">
          Modalidad:
          <span class="font-bold text-indigo-600">{{ tramite.modalidad?.nombre }}</span>
        </p>
      </div>
      <span
        class="px-3 py-1.5 rounded-full text-xs font-bold border inline-flex items-center gap-1.5"
        :class="statusGlobal(tramite.estado_actual).tone"
      >
        <AppIcon :name="statusIcon" :size="13" />
        {{ statusGlobal(tramite.estado_actual).label }}
      </span>
    </div>

    <div class="mb-6">
      <div class="flex justify-between text-xs text-slate-500 mb-1.5">
        <span class="font-semibold">Inicio</span>
        <span class="font-bold text-indigo-600">{{ progreso }}%</span>
        <span class="font-semibold">Final</span>
      </div>
      <ProgressBar :value="progreso" :bar-class="tone.bar" />
    </div>

    <ol class="relative ml-3 space-y-4">
      <li v-for="paso in pasosConHistoria" :key="paso.id" class="relative pl-9">
        <span
          class="absolute -left-[9px] top-3 w-4.5 h-4.5 rounded-full ring-4 ring-white border flex items-center justify-center"
          :class="dotClass(paso)"
        >
          <svg v-if="paso.completado" class="w-2 h-2 text-white" fill="none" stroke="currentColor" stroke-width="3.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <span v-else-if="paso.actual" class="w-1.5 h-1.5 rounded-full bg-indigo-600 animate-pulse"></span>
        </span>

        <div
          class="rounded-xl p-3.5 ring-1 transition"
          :class="cardClass(paso)"
        >
          <div class="flex items-center gap-2">
            <h4 class="font-bold" :class="titleClass(paso)">
              {{ formatoEstado(paso.id) }}
            </h4>
            <span v-if="paso.actual" class="bg-indigo-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide">Actual</span>
            <span v-if="paso.completado" class="bg-emerald-100 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide">Completado</span>
          </div>

          <p v-if="paso.historial" class="text-sm text-slate-600 mt-1">
            <span v-if="paso.historial.observaciones">“{{ paso.historial.observaciones }}”</span>
            <span v-else>Estado registrado.</span>
          </p>

          <div v-if="paso.historial" class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500 mt-2">
            <span v-if="paso.historial.responsable" class="inline-flex items-center gap-1">
              <AppIcon name="user" :size="12" />
              {{ paso.historial.responsable.nombres }} {{ paso.historial.responsable.apellidos }}
              ({{ rolLabel(paso.historial.responsable.rol) }})
            </span>
            <span v-if="paso.historial.created_at" class="inline-flex items-center gap-1">
              <AppIcon name="calendar" :size="12" />
              {{ fecha(paso.historial.created_at) }}
            </span>
          </div>
        </div>
      </li>
    </ol>

    <div v-if="siguientes.length" class="mt-8 rounded-xl bg-gradient-to-br from-indigo-50 to-violet-50 ring-1 ring-indigo-100 p-4">
      <h4 class="font-bold text-indigo-800 mb-2 inline-flex items-center gap-2">
        <AppIcon name="trending-up" :size="16" />
        Próximos pasos en tu trámite:
      </h4>
      <div class="flex flex-wrap gap-2 mt-2">
        <span v-for="siguiente in siguientes" :key="siguiente"
              class="bg-white text-indigo-700 text-sm font-semibold px-3 py-1.5 rounded-full ring-1 ring-indigo-200 shadow-sm inline-flex items-center gap-1">
          <AppIcon name="chevron-right" :size="13" />
          {{ formatoEstado(siguiente) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { formatoEstado, rolLabel, toneEstado, statusGlobal, progresoEstado } from '../utils/estados';
import AppIcon from './ui/AppIcon.vue';
import ProgressBar from './ui/ProgressBar.vue';

const props = defineProps({
    tramite: { type: Object, required: true },
    title: { type: String, default: 'Seguimiento de mi Titulación' },
});

const secuencia = computed(() => props.tramite.secuencia || []);
const estadoActual = computed(() => props.tramite.estado_actual);
const siguientes = computed(() => props.tramite.siguientes_estados || []);
const historicos = computed(() => props.tramite.estados || []);
const progreso = computed(() => progresoEstado(props.tramite));
const tone = computed(() => toneEstado(estadoActual.value));

const currentIndex = computed(() => secuencia.value.indexOf(estadoActual.value));

const statusIcon = computed(() => {
    const actual = estadoActual.value;
    if (actual === 'aprobado') return 'award';
    if (['reprobado', 'rechazado', 'reprobado_ausencia'].includes(actual)) return 'x-circle';
    return 'clock';
});

const pasosConHistoria = computed(() => {
    return secuencia.value.map((id) => {
        const index = secuencia.value.indexOf(id);
        const historial = historicos.value
            .filter((h) => h.nombre_estado === id)
            .sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
            .pop();

        return {
            id,
            index,
            historial,
            actual: id === estadoActual.value,
            completado: currentIndex.value !== -1 && index < currentIndex.value,
        };
    });
});

const dotClass = (paso) => {
    if (paso.actual) return 'bg-white border-2 border-indigo-600';
    if (paso.completado) return 'bg-emerald-500 border-emerald-500';
    if (paso.id === 'rechazado') return 'bg-rose-100 border-rose-300';
    return 'bg-white border-slate-300';
};

const cardClass = (paso) => {
    if (paso.actual) return 'bg-indigo-50/70 ring-indigo-200 shadow-md';
    if (paso.completado) return 'ring-emerald-200 bg-white';
    return 'ring-slate-200 bg-white opacity-75';
};

const titleClass = (paso) => {
    if (paso.actual) return 'text-indigo-700';
    if (paso.completado) return 'text-emerald-700';
    return 'text-slate-500';
};

const fecha = (fechaISO) => {
    if (!fechaISO) return '';
    return new Date(fechaISO).toLocaleString('es-BO', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};
</script>