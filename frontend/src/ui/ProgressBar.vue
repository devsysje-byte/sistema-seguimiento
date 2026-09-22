<template>
  <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
    <div
      class="h-2 rounded-full transition-all duration-500"
      :class="barClass"
      :style="{ width: Math.min(Math.max(value, 0), 100) + '%' }"
    ></div>
  </div>
</template>

<script setup>
// Barra de progreso simple.
// Muestra una barra redondeada cuyo ancho equivale a `value` (contenido entre
// 0 y 100) y acepta clases extra para personalizar el color.
import { computed } from 'vue';

const props = defineProps({
  value: { type: Number, default: 0 },                    // Porcentaje (0-100).
  barClass: { type: String, default: 'bg-gradient-to-r from-indigo-500 to-violet-500' },
});
</script>