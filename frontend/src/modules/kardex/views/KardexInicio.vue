<template>
  <div class="space-y-6">
    <!-- Encabezado del panel general -->
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-xl bg-orange-500 text-white flex items-center justify-center shadow-lg shadow-orange-500/30">
        <AppIcon name="dash-grid" :size="24" />
      </div>
      <div>
        <h2 class="text-2xl font-bold text-stone-900 uppercase">Panel General</h2>
        <p class="text-sm text-stone-500 mt-0.5">
          Resumen de estadísticas de modalidades, estudiantes y tutores.
        </p>
      </div>
    </div>

    <!-- Carga del resumen -->
    <div v-if="cargando && !resumen" class="card p-8 flex items-center justify-center gap-2 text-stone-400">
      <AppIcon name="loader" :size="20" class="animate-spin" />
      Cargando estadísticas...
    </div>

    <template v-else-if="resumen">
      <!-- KPIs principales -->
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard label="Estudiantes" :value="resumen.estudiantes.total" sublabel="Postulantes registrados" icon="users" tone="indigo" />
        <StatCard label="Trámites" :value="resumen.totales.tramites" sublabel="Solicitudes de titulación" icon="file-text" tone="sky" />
        <StatCard label="Aprobados" :value="resumen.totales.aprobados" sublabel="Titulaciones concluidas" icon="award" tone="emerald" />
        <StatCard label="Tutores" :value="resumen.tutores.total" sublabel="Docentes disponibles" icon="user-check" tone="orange" />
      </div>

      <div class="grid lg:grid-cols-2 gap-6">
        <!-- Estadísticas de modalidades -->
        <div class="card p-5 sm:p-6">
          <h3 class="font-bold text-stone-900 uppercase flex items-center gap-2 mb-4">
            <AppIcon name="chart" :size="18" class="text-orange-500" />
            Estadísticas de Modalidades
          </h3>

          <div v-if="resumen.modalidades.length === 0">
            <EmptyState icon="folder" title="Sin modalidades" message="Aún no hay modalidades registradas." />
          </div>

          <div v-else class="space-y-4">
            <div v-for="m in resumen.modalidades" :key="m.id_modalidad">
              <div class="flex items-center justify-between mb-1.5 text-sm">
                <span class="font-semibold text-stone-700">{{ m.nombre }}</span>
                <span class="text-xs text-stone-400">{{ m.total }} {{ m.total === 1 ? 'trámite' : 'trámites' }}</span>
              </div>

              <div class="flex items-center gap-3">
                <div class="flex-1 h-4 rounded-full bg-stone-100 overflow-hidden flex">
                  <div
                    v-if="m.en_curso > 0"
                    class="h-full bg-orange-500 transition-all duration-500"
                    :style="{ width: porcentaje(m.en_curso, m.total) + '%' }"
                  ></div>
                  <div
                    v-if="m.aprobados > 0"
                    class="h-full bg-emerald-500 transition-all duration-500"
                    :style="{ width: porcentaje(m.aprobados, m.total) + '%' }"
                  ></div>
                </div>
                <div class="flex items-center gap-3 text-xs font-bold shrink-0">
                  <span class="inline-flex items-center gap-1.5 text-orange-600">
                    <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                    {{ m.en_curso }}
                  </span>
                  <span class="inline-flex items-center gap-1.5 text-emerald-600">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    {{ m.aprobados }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <p class="text-xs text-stone-400 mt-4">
            El segmento naranja indica trámites en curso y el verde los que finalizaron de forma aprobatoria.
          </p>
        </div>

        <!-- Estadísticas de estudiantes y tutores -->
        <div class="space-y-6">
          <div class="card p-5 sm:p-6">
            <h3 class="font-bold text-stone-900 uppercase flex items-center gap-2 mb-4">
              <AppIcon name="users" :size="18" class="text-indigo-500" />
              Estadísticas de Estudiantes
            </h3>

            <div class="grid grid-cols-3 gap-3 text-center">
              <div class="rounded-xl bg-indigo-50 ring-1 ring-indigo-100 px-3 py-4">
                <p class="text-2xl font-bold text-indigo-700">{{ resumen.estudiantes.total }}</p>
                <p class="text-[11px] uppercase tracking-wider text-indigo-400 font-semibold mt-0.5">Registrados</p>
              </div>
              <div class="rounded-xl bg-sky-50 ring-1 ring-sky-100 px-3 py-4">
                <p class="text-2xl font-bold text-sky-700">{{ resumen.estudiantes.con_cuenta }}</p>
                <p class="text-[11px] uppercase tracking-wider text-sky-400 font-semibold mt-0.5">Con cuenta</p>
              </div>
              <div class="rounded-xl bg-emerald-50 ring-1 ring-emerald-100 px-3 py-4">
                <p class="text-2xl font-bold text-emerald-700">{{ resumen.estudiantes.con_tramite }}</p>
                <p class="text-[11px] uppercase tracking-wider text-emerald-400 font-semibold mt-0.5">Con trámite</p>
              </div>
            </div>
          </div>

          <div class="card p-5 sm:p-6">
            <h3 class="font-bold text-stone-900 uppercase flex items-center gap-2 mb-4">
              <AppIcon name="user-check" :size="18" class="text-orange-500" />
              Estadísticas de Tutores
            </h3>

            <div class="grid grid-cols-2 gap-3">
              <div class="rounded-xl bg-orange-50 ring-1 ring-orange-100 px-3 py-4">
                <p class="text-2xl font-bold text-orange-700">{{ resumen.tutores.total }}</p>
                <p class="text-[11px] uppercase tracking-wider text-orange-400 font-semibold mt-0.5">Docentes</p>
              </div>
              <div class="rounded-xl bg-emerald-50 ring-1 ring-emerald-100 px-3 py-4">
                <p class="text-2xl font-bold text-emerald-700">{{ resumen.tutores.activos }}</p>
                <p class="text-[11px] uppercase tracking-wider text-emerald-400 font-semibold mt-0.5">Con trámites</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
// Panel general del Dashboard de KARDEX: estadísticas de modalidades,
// estudiantes y tutores. Los datos provienen de GET /kardex/resumen y se cargan
// al montar el componente; si la API no responde el store usa el simulador.
import { ref, computed, onMounted } from 'vue';
import { useKardexStore } from '@/modules/kardex';
import AppIcon from '@/ui/AppIcon.vue';
import StatCard from '@/ui/StatCard.vue';
import EmptyState from '@/ui/EmptyState.vue';

const kardexStore = useKardexStore();

const cargando = ref(false);
const resumen = computed(() => kardexStore.resumen);

/** Porcentaje redondeado de `valor` respecto al total (evita división por cero). */
const porcentaje = (valor, total) => (total > 0 ? Math.round((valor / total) * 100) : 0);

onMounted(async () => {
  cargando.value = true;
  await kardexStore.cargarResumen();
  cargando.value = false;
});
</script>