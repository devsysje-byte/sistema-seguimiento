<template>
  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-100 p-6 hover:shadow-md transition-shadow duration-300">
    <div class="flex items-start justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-800">Website visits</h2>
        <p class="text-xs text-slate-400 mt-0.5">Comparison between teams</p>
      </div>
      <select class="text-xs font-semibold text-slate-400 bg-transparent focus:outline-none cursor-pointer">
        <option>This week</option>
        <option>This month</option>
      </select>
    </div>

    <div class="flex items-center gap-5 mt-2">
      <div class="flex items-center gap-2">
        <span class="w-3 h-3 rounded-full bg-violet-500"></span>
        <span class="text-sm font-semibold text-slate-600">Team A</span>
      </div>
      <div class="flex items-center gap-2">
        <span class="w-3 h-3 rounded-full bg-slate-200"></span>
        <span class="text-sm font-semibold text-slate-600">Team B</span>
      </div>
    </div>

    <div class="mt-6">
      <svg viewBox="0 0 400 210" class="w-full">
        <g v-for="lineY in gridLines" :key="'l'+lineY.y">
          <line
            :x1="0"
            :x2="400"
            :y1="lineY.y"
            :y2="lineY.y"
            class="stroke-slate-100"
            stroke-width="1"
            stroke-dasharray="4 4"
          />
          <text :x="0" :y="lineY.y - 6" class="fill-slate-300 text-[9px] font-medium">{{ lineY.label }}</text>
        </g>

        <g v-for="(b, i) in bars" :key="'a'+i">
          <rect
            :x="b.groupX"
            :y="downY - b.a"
            :width="barWidth"
            :height="b.a"
            rx="4"
            class="fill-violet-500"
          />
        </g>
        <g v-for="(b, i) in bars" :key="'b'+i">
          <rect
            :x="b.groupX + barWidth"
            :y="downY - b.b"
            :width="barWidth"
            :height="b.b"
            rx="4"
            class="fill-slate-200"
          />
        </g>

        <text
          v-for="(b, i) in bars"
          :key="'x'+i"
          :x="b.groupX + barWidth"
          :y="downY + 18"
          text-anchor="middle"
          class="fill-slate-400 text-[10px] font-medium"
        >{{ b.label }}</text>
      </svg>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const barWidth = 16;
const groupGap = 24;
const startX = 40;
const downY = 200;
const barAreaTop = 20;

const groups = [
  { label: 'Mon', a: 90, b: 55 },
  { label: 'Tue', a: 70, b: 35 },
  { label: 'Wed', a: 85, b: 45 },
  { label: 'Thu', a: 60, b: 40 },
  { label: 'Fri', a: 75, b: 30 },
  { label: 'Sat', a: 95, b: 65 },
  { label: 'Sun', a: 80, b: 50 },
];

const bars = computed(() => {
  const max = Math.max(...groups.flatMap((g) => [g.a, g.b]));
  const scale = (v) => ((downY - barAreaTop) * v) / max;
  return groups.map((g, i) => ({
    label: g.label,
    groupX: startX + i * (barWidth * 2 + groupGap),
    a: scale(g.a),
    b: scale(g.b),
  }));
});

const gridLines = [
  { y: 20, label: '100' },
  { y: 65, label: '75' },
  { y: 110, label: '50' },
  { y: 155, label: '25' },
];
</script>
