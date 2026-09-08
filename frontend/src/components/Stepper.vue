<template>
  <div class="w-full">
    <div class="flex items-center justify-between relative">
      <!-- Línea de fondo -->
      <div class="absolute top-4 left-0 w-full h-1 bg-gray-200 z-0"></div>
      <!-- Línea de progreso -->
      <div class="absolute top-4 left-0 h-1 bg-blue-600 z-0 transition-all duration-500" :style="{ width: progressWidth }"></div>

      <div v-for="(paso, index) in pasos" :key="index" class="relative z-10 flex flex-col items-center">
        <div 
          class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-colors"
          :class="getCircleClass(index)"
        >
          {{ index + 1 }}
        </div>
        <span class="mt-2 text-xs text-center font-medium max-w-[80px]" :class="getTextClass(index)">
          {{ paso.nombre }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  estados: { type: Array, required: true }, // Lista de estados del historial
  estadoActual: { type: String, required: true },
  modalidad: { type: String, required: true }
});

// Mapeo de estados a pasos visuales (Simplificado para Examen de Grado como ejemplo)
const pasosMap = {
  'Examen de Grado': [
    'solicitud_presentada', 'certificacion_acreditacion', 'inscripcion_pagada', 
    'espera_de_sorteo', 'tema_sorteado', 'examen_en_curso', 'acta_registrada', 'aprobado'
  ],
  'Tesis de Grado': [
    'solicitud_presentada', 'perfil_presentado', 'perfil_aprobado', 
    'investigacion_en_desarrollo', 'documento_final_presentado', 'defensa_en_curso', 'aprobado'
  ]
};

const pasos = computed(() => {
  const estadosBase = pasosMap[props.modalidad] || [];
  return estadosBase.map(e => ({ 
    id: e, 
    nombre: e.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) 
  }));
});

const currentIndex = computed(() => {
  return pasos.value.findIndex(p => p.id === props.estadoActual);
});

const progressWidth = computed(() => {
  if (currentIndex.value === -1) return '0%';
  const total = pasos.value.length - 1;
  return `${(currentIndex.value / total) * 100}%`;
});

const getCircleClass = (index) => {
  if (index < currentIndex.value) return 'bg-blue-600 border-blue-600 text-white'; // Completado
  if (index === currentIndex.value) return 'bg-white border-blue-600 text-blue-600'; // Actual
  return 'bg-white border-gray-300 text-gray-400'; // Pendiente
};

const getTextClass = (index) => {
  return index <= currentIndex.value ? 'text-blue-600' : 'text-gray-400';
};
</script>