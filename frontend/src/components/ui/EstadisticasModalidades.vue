<template>
  <div v-if="visible" class="card p-5 sm:p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-md shrink-0">
          <AppIcon name="chart" :size="22" />
        </div>
        <div>
          <h2 class="text-lg font-bold text-slate-900">Estadísticas de Graduación</h2>
          <p class="text-sm text-slate-500">Trámites aprobados y reprobados por modalidad</p>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-50 ring-1 ring-emerald-200">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
          <span class="text-sm font-bold text-emerald-700">{{ totales.aprobados }} Aprobados</span>
        </div>
        <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-rose-50 ring-1 ring-rose-200">
          <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
          <span class="text-sm font-bold text-rose-700">{{ totales.reprobados }} Reprobados</span>
        </div>
      </div>
    </div>

    <div v-if="cargando" class="flex items-center justify-center gap-2 py-10 text-slate-400">
      <AppIcon name="loader" :size="20" class="animate-spin" />
      Cargando estadísticas...
    </div>

    <div v-else-if="porModalidad.length === 0" class="py-10">
      <EmptyState icon="chart" title="Sin datos disponibles" message="Aún no hay trámites registrados para mostrar estadísticas." />
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="m in porModalidad"
        :key="m.id_modalidad"
        class="relative"
      >
        <div class="flex items-center justify-between mb-1.5 text-sm">
          <span class="font-semibold text-slate-700">{{ m.nombre }}</span>
          <span class="text-xs font-medium text-slate-400">{{ m.total }} trámite{{ m.total !== 1 ? 's' : '' }}</span>
        </div>

        <div class="flex items-center gap-3">
          <div class="flex-1 h-4 rounded-full bg-slate-100 overflow-hidden flex">
            <div
              v-if="m.aprobados > 0"
              class="h-full bg-emerald-500 transition-all duration-500"
              :style="{ width: porcentaje(m.aprobados, m.total) + '%' }"
            ></div>
            <div
              v-if="m.reprobados > 0"
              class="h-full bg-rose-500 transition-all duration-500"
              :style="{ width: porcentaje(m.reprobados, m.total) + '%' }"
            ></div>
          </div>
          <div class="flex items-center gap-3 text-xs font-bold shrink-0">
            <span class="inline-flex items-center gap-1.5 text-emerald-700">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              {{ m.aprobados }}
            </span>
            <span class="inline-flex items-center gap-1.5 text-rose-700">
              <span class="w-2 h-2 rounded-full bg-rose-500"></span>
              {{ m.reprobados }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useTramitesStore } from '../../stores/tramites';
import AppIcon from './AppIcon.vue';
import EmptyState from './EmptyState.vue';

const authStore = useAuthStore();
const tramitesStore = useTramitesStore();

const cargando = ref(false);

const visible = computed(() => ['admin', 'kardex', 'secretaria', 'direccion'].includes(authStore.user?.rol));
const totales = computed(() => tramitesStore.estadisticas?.totales || { aprobados: 0, reprobados: 0, total: 0 });
const porModalidad = computed(() => tramitesStore.estadisticas?.porModalidad || []);

const porcentaje = (valor, total) => (total > 0 ? Math.round((valor / total) * 100) : 0);

onMounted(async () => {
  if (!visible.value) return;
  cargando.value = true;
  await tramitesStore.cargarEstadisticas();
  cargando.value = false;
});
</script>