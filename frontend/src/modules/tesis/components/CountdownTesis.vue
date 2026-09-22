<template>
  <div
    class="rounded-2xl p-5 ring-1 overflow-hidden"
    :class="vencido ? 'bg-rose-50/70 ring-rose-200' : softClass"
  >
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div class="flex items-start gap-3">
        <span
          class="shrink-0 w-11 h-11 rounded-xl flex items-center justify-center ring-1"
          :class="vencido ? 'bg-rose-100 text-rose-600 ring-rose-200' : iconClass"
        >
          <AppIcon :name="vencido ? 'alert-triangle' : 'clock'" :size="22" />
        </span>
        <div class="min-w-0">
          <h4 class="font-bold text-stone-900">{{ titulo }}</h4>
          <p v-if="descripcion" class="text-sm text-stone-500 mt-0.5">{{ descripcion }}</p>
        </div>
      </div>

      <div class="text-right shrink-0">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-stone-400">Tiempo restante</p>
        <p class="text-3xl font-extrabold leading-tight" :class="vencido ? 'text-rose-600' : 'text-stone-900'">
          <template v-if="vencido">Plazo vencido</template>
          <template v-else>{{ partes.dias }} <span class="text-base font-bold">días</span>
            <span class="text-sm font-semibold text-stone-400">· {{ partes.horas }} h</span>
          </template>
        </p>
      </div>
    </div>

    <div v-if="!vencido" class="mt-4">
      <div class="flex justify-between text-xs font-semibold text-stone-500 mb-1.5">
        <span>Inicio {{ formatoFecha(fechaInicio) }}</span>
        <span :class="restanteClass">{{ porcentajeRestante }}% restante</span>
        <span>Vence {{ formatoFecha(fechaLimite) }}</span>
      </div>
      <div class="h-2.5 rounded-full bg-stone-200 overflow-hidden">
        <div
          class="h-full rounded-full transition-all duration-700"
          :class="barClass"
          :style="{ width: Math.max(porcentajeRestante, 2) + '%' }"
        ></div>
      </div>
      <p class="mt-2 text-xs text-stone-500">
        Ventana configurada de presentación del documento final.
      </p>
    </div>

    <p v-else class="mt-3 text-sm font-medium text-rose-700">
      Se superó el plazo establecido. Contacta a tu tutor o a la Dirección de Carrera.
    </p>
  </div>
</template>

<script setup>
// Componente de cuenta regresiva (countdown) del módulo de Tesis de Grado.
// Muestra el tiempo restante del periodo de presentación del documento final
// (configurable entre 3 y 12 meses vía .env) o del plazo de correcciones,
// con una barra de avance del periodo transcurrido.
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AppIcon from '@/ui/AppIcon.vue';

const props = defineProps({
  fechaLimite: { type: String, required: true },
  fechaInicio: { type: String, default: null },
  titulo: { type: String, default: 'Tiempo límite' },
  descripcion: { type: String, default: '' },
});

const ahora = ref(Date.now());
let temporizador = null;

onMounted(() => {
  temporizador = setInterval(() => { ahora.value = Date.now(); }, 30000);
});
onUnmounted(() => clearInterval(temporizador));

const fechaLimiteDate = computed(() => new Date(props.fechaLimite + 'T23:59:59'));
const fechaInicioDate = computed(() => {
  if (!props.fechaInicio) return new Date();
  return new Date(props.fechaInicio + 'T00:00:00');
});

const vencido = computed(() => fechaLimiteDate.value.getTime() <= ahora.value);

const totalMs = computed(() => Math.max(fechaLimiteDate.value.getTime() - fechaInicioDate.value.getTime(), 1));
const restanteMs = computed(() => Math.max(fechaLimiteDate.value.getTime() - ahora.value, 0));

const porcentajeRestante = computed(() => Math.min(Math.round((restanteMs.value / totalMs.value) * 100), 100));

const partes = computed(() => {
  const ms = restanteMs.value;
  const dias = Math.floor(ms / 86400000);
  const horas = Math.floor((ms % 86400000) / 3600000);
  return { dias, horas };
});

// Estado visual según el porcentaje restante.
const estadoVital = computed(() => {
  if (porcentajeRestante.value >= 50) return 'ok';
  if (porcentajeRestante.value >= 25) return 'medio';
  return 'critico';
});

const softClass = computed(() => ({
  ok: 'bg-emerald-50/70 border-emerald-200',
  medio: 'bg-amber-50/70 border-amber-200',
  critico: 'bg-rose-50/70 border-rose-200',
}[estadoVital.value]));

const iconClass = computed(() => ({
  ok: 'bg-emerald-100 text-emerald-600 ring-emerald-200',
  medio: 'bg-amber-100 text-amber-600 ring-amber-200',
  critico: 'bg-rose-100 text-rose-600 ring-rose-200',
}[estadoVital.value]));

const barClass = computed(() => ({
  ok: 'bg-gradient-to-r from-emerald-500 to-teal-500',
  medio: 'bg-gradient-to-r from-amber-400 to-orange-500',
  critico: 'bg-gradient-to-r from-rose-500 to-red-500',
}[estadoVital.value]));

const restanteClass = computed(() => ({
  ok: 'text-emerald-600',
  medio: 'text-amber-600',
  critico: 'text-rose-600',
}[estadoVital.value]));

/** Formatea una fecha (YYYY-MM-DD) en formato corto local. */
function formatoFecha(iso) {
  if (!iso) return '—';
  return new Date(iso + 'T00:00:00').toLocaleDateString('es-BO', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>