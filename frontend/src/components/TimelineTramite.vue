<template>
  <div v-if="tramite" class="w-full">
    <!-- Encabezado -->
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
      <div>
        <h3 class="text-xl font-bold text-gray-800">Seguimiento de mi Titulación</h3>
        <p class="text-gray-600">
          Modalidad: <span class="font-bold text-blue-600">{{ tramite.modalidad?.nombre }}</span>
        </p>
      </div>
      <span
        class="px-3 py-1 rounded-full text-xs font-bold uppercase"
        :class="statusBadgeClass"
      >
        {{ statusLabel }}
      </span>
    </div>

    <!-- Barra de progreso general -->
    <div class="mb-6">
      <div class="flex justify-between text-xs text-gray-500 mb-1">
        <span>Inicio</span>
        <span class="font-semibold text-blue-600">{{ Math.round(progreso * 100) }}%</span>
        <span>Final</span>
      </div>
      <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
        <div
          class="h-3 bg-gradient-to-r from-blue-500 to-blue-700 rounded-full transition-all duration-500"
          :style="{ width: progreso * 100 + '%' }"
        ></div>
      </div>
    </div>

    <!-- Línea de tiempo vertical -->
    <ol class="relative border-l-2 border-gray-200 ml-4 space-y-6">
      <li v-for="(paso, index) in pasosConHistoria" :key="paso.id" class="relative pl-8">
        <span
          class="absolute -left-[13px] top-0 w-6 h-6 rounded-full border-2 flex items-center justify-center"
          :class="dotClass(paso)"
        >
          <svg v-if="paso.completado || paso.actual" class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </span>

        <div
          class="rounded-lg border p-3"
          :class="paso.actual ? 'border-blue-500 bg-blue-50' : paso.completado ? 'border-green-200 bg-white' : 'border-gray-200 bg-white opacity-70'"
        >
          <div class="flex items-center gap-2">
            <h4 class="font-bold" :class="paso.actual ? 'text-blue-700' : 'text-gray-800'">
              {{ formatoEstado(paso.id) }}
            </h4>
            <span v-if="paso.actual" class="bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">Actual</span>
            <span v-if="paso.completado" class="text-green-600 text-[10px] font-bold px-2 py-0.5 rounded-full bg-green-100 uppercase">Completado</span>
          </div>

          <p v-if="paso.historial" class="text-sm text-gray-600 mt-1">
            <span v-if="paso.historial.observaciones">“{{ paso.historial.observaciones }}”</span>
            <span v-else>Estado registrado.</span>
          </p>

          <div v-if="paso.historial" class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 mt-2">
            <span v-if="paso.historial.responsable">
              👤 {{ paso.historial.responsable.nombres }} {{ paso.historial.responsable.apellidos }}
              ({{ rolLabel(paso.historial.responsable.rol) }})
            </span>
            <span v-if="paso.historial.created_at">🗓 {{ fecha(paso.historial.created_at) }}</span>
          </div>
        </div>
      </li>
    </ol>

    <!-- Próximos pasos -->
    <div v-if="siguientes.length" class="mt-8">
      <h4 class="font-bold text-gray-700 mb-2">Próximos pasos en tu trámite:</h4>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="siguiente in siguientes"
          :key="siguiente"
          class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1.5 rounded-full border border-blue-300"
        >
          → {{ formatoEstado(siguiente) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    tramite: { type: Object, required: true }
});

const ROLE_LABELS = {
    admin: 'Administrador',
    estudiante: 'Estudiante',
    docente: 'Docente',
    kardex: 'Kardex',
    secretaria: 'Secretaría',
    direccion: 'Dirección',
    concejo: 'Concejo'
};

const secuencia = computed(() => props.tramite.secuencia || []);
const estadoActual = computed(() => props.tramite.estado_actual);
const siguientes = computed(() => props.tramite.siguientes_estados || []);
const historicos = computed(() => props.tramite.estados || []);

const currentIndex = computed(() => secuencia.value.indexOf(estadoActual.value));

const progreso = computed(() => {
    if (currentIndex.value === -1) return 0;
    const total = secuencia.value.length - 1;
    if (total <= 0) return 1;
    return Math.min(currentIndex.value / total, 1);
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
    if (paso.actual) return 'bg-blue-600 border-blue-600 shadow-md';
    if (paso.completado) return 'bg-green-500 border-green-500';
    if (paso.id === 'rechazado') return 'bg-red-100 border-red-300';
    return 'bg-white border-gray-300';
};

const statusLabel = computed(() => {
    const actual = estadoActual.value;
    if (actual === 'aprobado') return 'Aprobado';
    if (actual === 'reprobado' || actual === 'rechazado' || actual === 'reprobado_ausencia') return 'Rechazado';
    return 'En Proceso';
});

const statusBadgeClass = computed(() => {
    const actual = estadoActual.value;
    if (actual === 'aprobado') return 'bg-green-100 text-green-700 border border-green-300';
    if (actual === 'reprobado' || actual === 'rechazado' || actual === 'reprobado_ausencia') return 'bg-red-100 text-red-700 border border-red-300';
    return 'bg-blue-100 text-blue-700 border border-blue-300';
});

const formatoEstado = (nombre) => {
    return String(nombre || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (l) => l.toUpperCase());
};

const rolLabel = (rol) => ROLE_LABELS[rol] || rol || 'Sistema';

const fecha = (fechaISO) => {
    if (!fechaISO) return '';
    return new Date(fechaISO).toLocaleString('es-BO', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};
</script>