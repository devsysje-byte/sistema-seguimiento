<!-- src/components/Stepper.vue -->
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
        <span class="mt-2 text-xs text-center font-medium max-w-[100px]" :class="getTextClass(index)">
          {{ paso.nombre }}
        </span>
        <!-- Mostrar observación si existe en el paso actual -->
        <div v-if="index === currentIndex && paso.observaciones" class="mt-1">
          <span class="text-xs text-gray-500 bg-yellow-50 px-2 py-1 rounded inline-block max-w-[150px] truncate" :title="paso.observaciones">
            📝 {{ paso.observaciones }}
          </span>
        </div>
      </div>
    </div>
    
    <!-- Información de la modalidad -->
    <div class="mt-4 text-center text-sm text-gray-600">
      Modalidad: <span class="font-semibold text-blue-600">{{ modalidad }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  estados: { 
    type: Array, 
    required: true,
    default: () => [] 
  },
  estadoActual: { 
    type: String, 
    required: true,
    default: '' 
  },
  modalidad: { 
    type: String, 
    required: true,
    default: 'Sin modalidad' 
  }
});

// Procesar los estados recibidos
const pasos = computed(() => {
  if (!props.estados || props.estados.length === 0) {
    return [];
  }
  
  return props.estados.map(estado => {
    // Si el estado es un string, convertirlo a objeto
    if (typeof estado === 'string') {
      return {
        id: estado,
        nombre: formatearNombre(estado),
        observaciones: null
      };
    }
    // Si es un objeto, usarlo directamente
    return {
      id: estado.nombre || estado.id || '',
      nombre: formatearNombre(estado.nombre || estado.id || ''),
      observaciones: estado.observaciones || null
    };
  });
});

// Formatear nombre para mostrarlo bonito
const formatearNombre = (nombre) => {
  if (!nombre) return '';
  return nombre
    .replace(/_/g, ' ')
    .replace(/\b\w/g, l => l.toUpperCase());
};

// Índice del estado actual
const currentIndex = computed(() => {
  if (!props.estadoActual || pasos.value.length === 0) return 0;
  
  const index = pasos.value.findIndex(p => 
    p.id === props.estadoActual || 
    p.nombre === formatearNombre(props.estadoActual)
  );
  return index !== -1 ? index : 0;
});

// Ancho de la barra de progreso
const progressWidth = computed(() => {
  if (pasos.value.length === 0) return '0%';
  const total = pasos.value.length - 1;
  if (total === 0) return '0%';
  return `${(currentIndex.value / total) * 100}%`;
});

// Clase para el círculo
const getCircleClass = (index) => {
  if (index < currentIndex.value) {
    return 'bg-blue-600 border-blue-600 text-white'; // Completado
  }
  if (index === currentIndex.value) {
    return 'bg-white border-blue-600 text-blue-600 shadow-lg'; // Actual
  }
  return 'bg-white border-gray-300 text-gray-400'; // Pendiente
};

// Clase para el texto
const getTextClass = (index) => {
  return index <= currentIndex.value ? 'text-blue-600 font-semibold' : 'text-gray-400';
};
</script>

<style scoped>
.shadow-lg {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
}

.transition-all {
  transition: all 0.3s ease-in-out;
}

.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media (max-width: 640px) {
  .max-w-\[100px\] {
    max-width: 60px;
    font-size: 9px;
  }
  .w-8 {
    width: 24px;
    height: 24px;
    font-size: 10px;
  }
}
</style>