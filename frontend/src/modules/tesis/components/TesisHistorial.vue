<template>
  <div v-if="pasos.length" class="card overflow-hidden">
    <div class="p-6">
      <h3 class="font-bold text-stone-800 inline-flex items-center gap-2">
        <span class="w-8 h-8 rounded-lg bg-amber-50 ring-1 ring-amber-200 flex items-center justify-center text-amber-600">
          <AppIcon name="layers" :size="17" />
        </span>
        Flujo seguido por tu tesis
      </h3>
      <p class="text-sm text-stone-500 mt-1">
        Cada paso que tu trámite ha seguido hasta su estado actual.
      </p>

      <ol class="relative mt-5 ml-2 space-y-4">
        <li v-for="(paso, index) in pasos" :key="index" class="relative pl-10">
          <span
            v-if="index < pasos.length - 1"
            class="absolute left-[15px] top-7 bottom-[-1rem] w-px bg-stone-200"
          ></span>
          <span
            class="absolute left-[7px] top-3 w-4 h-4 rounded-full ring-4 ring-white border-2 flex items-center justify-center"
            :class="dotClass(paso)"
          >
            <span v-if="paso.actual" class="w-1.5 h-1.5 rounded-full bg-indigo-600 animate-pulse"></span>
          </span>

          <div class="rounded-xl p-3.5 ring-1" :class="cardClass(paso)">
            <div class="flex flex-wrap items-center gap-2">
              <h4 class="font-bold text-sm" :class="titleClass(paso)">{{ paso.etiqueta }}</h4>
              <span v-if="paso.actual" class="bg-indigo-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide">
                Actual
              </span>
            </div>

            <p v-if="paso.descripcion" class="text-sm text-stone-600 mt-1">{{ paso.descripcion }}</p>
            <p v-if="paso.observaciones" class="text-sm text-stone-600 mt-1 italic">“{{ paso.observaciones }}”</p>

            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-stone-500 mt-2">
              <span v-if="paso.fecha" class="inline-flex items-center gap-1">
                <AppIcon name="calendar" :size="12" />
                {{ formatoFecha(paso.fecha) }}
              </span>
              <span v-if="paso.responsable" class="inline-flex items-center gap-1">
                <AppIcon name="user" :size="12" />
                {{ paso.responsable.nombres }} {{ paso.responsable.apellidos }}
                ({{ rolLabel(paso.responsable.rol) }})
              </span>
            </div>
          </div>
        </li>
      </ol>
    </div>
  </div>
</template>

<script setup>
// Línea de tiempo del flujo de Tesis de Grado (lado del estudiante).
// Muestra el historial de estados que el trámite siguió desde la solicitud hasta
// el resultado, con fecha, responsable y observaciones de cada transición.
import { computed } from 'vue';
import { rolLabel } from '@/core/roles';
import { formatoEstado, toneEstado } from '@/modules/tramites';
import { DESCRIPCION_ESTADO } from '../utils/flujo';
import AppIcon from '@/ui/AppIcon.vue';

const props = defineProps({
  tramite: { type: Object, required: true },
});

// Historial cronológico de estados con dato derivado para la línea de tiempo.
const pasos = computed(() => {
  const estadoActual = props.tramite?.estado_actual;
  const estados = [...(props.tramite?.estados || [])]
    .sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

  if (!estados.length) return [];

  const ultimoIndexPorEstado = {};
  estados.forEach((e, i) => {
    ultimoIndexPorEstado[e.nombre_estado] = i;
  });

  return estados.map((e, i) => {
    const tono = toneEstado(e.nombre_estado);
    return {
      nombre: e.nombre_estado,
      etiqueta: formatoEstado(e.nombre_estado),
      descripcion: DESCRIPCION_ESTADO[e.nombre_estado] || e.descripcion || '',
      observaciones: e.observaciones || '',
      fecha: e.created_at || null,
      responsable: e.responsable || null,
      actual:
        e.nombre_estado === estadoActual && ultimoIndexPorEstado[e.nombre_estado] === i,
      tono,
    };
  });
});

// Clases del punto según si el paso está completado o es el vigente.
const dotClass = (paso) => {
  if (paso.actual) return 'bg-white border-2 border-indigo-600';
  return `border-2 ${paso.tono.dot}`;
};

// Clases de la tarjeta según el estado del paso.
const cardClass = (paso) => {
  if (paso.actual) return 'bg-indigo-50/70 ring-indigo-200 shadow-md';
  return 'ring-stone-200 bg-white';
};

// Clases del título según el estado del paso.
const titleClass = (paso) => {
  if (paso.actual) return 'text-indigo-700';
  if (paso.nombre === 'aprobado') return 'text-emerald-700';
  if (paso.tono.dot.includes('rose')) return 'text-rose-600';
  if (paso.tono.dot.includes('amber')) return 'text-amber-700';
  return 'text-stone-700';
};

/** Formatea una fecha ISO en formato largo local (dd de mes de año). */
const formatoFecha = (fechaIso) => {
  if (!fechaIso) return '';
  const f = new Date(fechaIso);
  if (Number.isNaN(f.getTime())) return '';
  return f.toLocaleDateString('es-BO', { day: 'numeric', month: 'long', year: 'numeric' });
};
</script>