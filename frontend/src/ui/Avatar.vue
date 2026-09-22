<template>
  <span
    class="inline-flex items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-white font-bold uppercase select-none"
    :class="[sizeClasses[size] || sizeClasses[10], extra]"
  >
    {{ iniciales }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  nombres: { type: String, default: '' },
  apellidos: { type: String, default: '' },
  size: { type: Number, default: 10 },
  extra: { type: [String, Array], default: '' },
});

const sizeClasses = {
  8: 'w-8 h-8 text-[11px]',
  10: 'w-10 h-10 text-sm',
  12: 'w-12 h-12 text-base',
  14: 'w-14 h-14 text-lg',
  16: 'w-16 h-16 text-xl',
};

const iniciales = computed(() => {
  const a = (props.nombres || '').trim().charAt(0);
  const b = (props.apellidos || '').trim().charAt(0);
  return `${a}${b}`.toUpperCase() || '?';
});
</script>