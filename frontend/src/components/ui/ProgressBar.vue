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
import { computed } from 'vue';

const props = defineProps({
  value: { type: Number, default: 0 },
  barClass: { type: String, default: 'bg-gradient-to-r from-indigo-500 to-violet-500' },
});
</script>