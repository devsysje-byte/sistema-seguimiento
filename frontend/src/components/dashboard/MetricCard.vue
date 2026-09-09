<template>
  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-5 relative overflow-hidden hover:shadow-md transition-shadow duration-300">
    <div class="flex items-start justify-between">
      <div
        class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0"
        :class="tones.bg"
      >
        <span :class="tones.icon">
          <AppIcon :name="icon" :size="22" />
        </span>
      </div>

      <span
        class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold"
        :class="trendPositive ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'"
      >
        <template v-if="!trendPositive">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </template>
        <template v-else>
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
        </template>
        {{ change }}
      </span>
    </div>

    <div class="mt-4">
      <p class="text-sm font-semibold text-slate-400">{{ label }}</p>
      <p class="mt-1 text-3xl font-extrabold text-slate-900 tracking-tight">{{ value }}</p>
    </div>

    <div class="mt-4">
      <svg viewBox="0 0 120 36" fill="none" class="w-full h-10" preserveAspectRatio="none">
        <path
          :d="areaPath"
          :fill="tones.fill"
          stroke="none"
          opacity="0.5"
        />
        <path
          :d="linePath"
          :stroke="tones.stroke"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          fill="none"
        />
        <circle
          :cx="lastPoint[0]"
          :cy="lastPoint[1]"
          r="3"
          :fill="tones.stroke"
        />
      </svg>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import AppIcon from '../ui/AppIcon.vue';

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  change: { type: String, required: true },
  icon: { type: String, required: true },
  tone: { type: String, default: 'blue' },
});

const tones = {
  blue: { bg: 'bg-blue-50', icon: 'text-blue-600', stroke: '#1e40af', fill: '#bfdbfe' },
  violet: { bg: 'bg-purple-50', icon: 'text-purple-600', stroke: '#7c3aed', fill: '#ddd6fe' },
  amber: { bg: 'bg-amber-50', icon: 'text-amber-600', stroke: '#b45309', fill: '#fde68a' },
  rose: { bg: 'bg-rose-50', icon: 'text-rose-500', stroke: '#e11d48', fill: '#fecdd3' },
};

const data = computed(() => {
  const base = {
    blue: [28, 32, 24, 34, 30, 38, 33],
    violet: [40, 36, 38, 32, 30, 28, 26],
    amber: [22, 28, 25, 33, 30, 36, 34],
    rose: [30, 26, 32, 28, 34, 31, 37],
  }[props.tone];
  return base;
});

const coords = computed(() => {
  const width = 120;
  const height = 36;
  const max = Math.max(...data.value);
  const min = Math.min(...data.value);
  const range = max - min || 1;
  return data.value.map((v, i) => ({
    x: (i / (data.value.length - 1)) * width,
    y: height - ((v - min) / range) * (height - 6) - 3,
  }));
});

const linePath = computed(() =>
  coords.value.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ')
);

const areaPath = computed(() => {
  const c = coords.value;
  const start = `M ${c[0].x} 36`;
  const points = c.map((p) => `L ${p.x} ${p.y}`).join(' ');
  return `${start} ${points} L ${c[c.length - 1].x} 36 Z`;
});

const lastPoint = computed(() => [coords.value[coords.value.length - 1].x, coords.value[coords.value.length - 1].y]);

const trendPositive = computed(() => parseFloat(props.change) >= 0);
</script>
