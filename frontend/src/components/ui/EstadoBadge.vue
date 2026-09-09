<template>
  <span
    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border whitespace-nowrap"
    :class="tone.soft"
  >
    <span class="w-1.5 h-1.5 rounded-full" :class="tone.dot"></span>
    {{ formato }}
  </span>
</template>

<script setup>
import { computed } from 'vue';
import { formatoEstado, toneEstado } from '../../utils/estados';

const props = defineProps({
  estado: { type: String, required: true },
  upper: { type: Boolean, default: false },
});

const tone = computed(() => toneEstado(props.estado));
const formato = computed(() => {
  const t = formatoEstado(props.estado);
  return props.upper ? t.toUpperCase() : t;
});
</script>