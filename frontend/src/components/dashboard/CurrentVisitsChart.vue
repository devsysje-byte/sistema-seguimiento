<template>
  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-6 hover:shadow-md transition-shadow duration-300">
    <div class="flex items-start justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-800">Current visit</h2>
        <p class="text-xs text-slate-400 mt-0.5">Total visitors overview</p>
      </div>
      <select class="text-xs font-semibold text-slate-400 bg-transparent focus:outline-none cursor-pointer">
        <option>New</option>
        <option>Returning</option>
      </select>
    </div>

    <div class="flex flex-col items-center mt-6 gap-6">
      <div class="relative w-48 h-48">
        <svg viewBox="0 0 200 200" class="w-full h-full -rotate-90">
          <circle
            v-for="seg in renderSegments"
            :key="seg.id"
            cx="100"
            cy="100"
            r="80"
            fill="none"
            :stroke="seg.color"
            :stroke-width="22"
            :stroke-dasharray="`${seg.gap} ${circumference - seg.gap}`"
            :stroke-dashoffset="seg.offset"
            stroke-linecap="round"
          />
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center">
          <p class="text-3xl font-extrabold text-slate-900">88k</p>
          <p class="text-xs text-slate-400 font-medium">Total</p>
        </div>
      </div>

      <div class="w-full grid grid-cols-2 gap-3">
        <div v-for="item in renderSegments" :key="item.id" class="flex items-center gap-2.5">
          <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: item.color }"></span>
          <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-700">{{ item.label }}</p>
            <p class="text-xs text-slate-400">{{ item.value }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const circumference = 2 * Math.PI * 80;

const segments = [
  { id: 1, label: 'Direct', value: '25%', color: '#6366f1', portion: 0.25 },
  { id: 2, label: 'Social', value: '40%', color: '#f59e0b', portion: 0.4 },
  { id: 3, label: 'Referral', value: '20%', color: '#10b981', portion: 0.2 },
  { id: 4, label: 'Search', value: '15%', color: '#ec4899', portion: 0.15 },
];

const renderSegments = computed(() => {
  let acc = 0;
  return segments.map((s) => {
    const gap = s.portion * circumference;
    const offset = -acc * circumference;
    acc += s.portion;
    return { ...s, gap, offset };
  });
});
</script>
