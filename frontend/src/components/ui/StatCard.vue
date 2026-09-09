<template>
  <div class="card p-5 relative overflow-hidden">
    <div class="absolute -top-10 -right-10 w-32 h-32 rounded-full blur-2xl opacity-20" :class="blobTone"></div>
    <div class="relative flex items-start justify-between gap-4">
      <div>
        <p class="text-sm font-semibold text-slate-500">{{ label }}</p>
        <p class="mt-1 text-3xl font-bold text-slate-900 leading-none">{{ value }}</p>
        <p v-if="sublabel" class="mt-2 text-xs text-slate-400">{{ sublabel }}</p>
      </div>
      <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white shadow-lg shrink-0 bg-gradient-to-br" :class="iconTone">
        <AppIcon :name="icon" :size="24" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  sublabel: { type: String, default: '' },
  icon: { type: String, default: 'chart' },
  tone: { type: String, default: 'indigo' },
});

const tones = {
  indigo: { icon: 'from-indigo-500 to-indigo-600', blob: 'bg-indigo-400' },
  violet: { icon: 'from-violet-500 to-violet-600', blob: 'bg-violet-400' },
  sky: { icon: 'from-sky-500 to-sky-600', blob: 'bg-sky-400' },
  emerald: { icon: 'from-emerald-500 to-emerald-600', blob: 'bg-emerald-400' },
  amber: { icon: 'from-amber-500 to-amber-600', blob: 'bg-amber-400' },
  rose: { icon: 'from-rose-500 to-rose-600', blob: 'bg-rose-400' },
};

const iconTone = computed(() => tones[props.tone]?.icon || tones.indigo.icon);
const blobTone = computed(() => tones[props.tone]?.blob || tones.indigo.blob);
</script>