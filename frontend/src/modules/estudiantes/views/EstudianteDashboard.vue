<template>
  <AppShell title="Portal del Estudiante" subtitle="Seguimiento de tu modalidad de titulación">
    <template v-if="!estudianteStore.perfilEstudiante">
      <div class="max-w-2xl mx-auto">
        <div class="card overflow-hidden">
          <div class="h-2 bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500"></div>
          <div class="p-8">
            <div class="flex items-center gap-3 mb-6">
              <div class="w-12 h-12 rounded-2xl bg-amber-50 ring-1 ring-amber-200 flex items-center justify-center text-amber-600">
                <AppIcon name="user" :size="24" />
              </div>
              <div>
                <h1 class="text-xl font-extrabold text-stone-900">Complete su Perfil Académico</h1>
                <p class="text-sm text-stone-500">Necesitamos estos datos para habilitar tu solicitud de titulación.</p>
              </div>
            </div>

            <form @submit.prevent="guardarPerfil" class="space-y-4">
              <div>
                <label class="label">Código Universitario</label>
                <input v-model="perfil.codigo_universitario" class="input" placeholder="Ej: 2020-0001" required>
              </div>
              <div class="grid sm:grid-cols-2 gap-4">
                <div>
                  <label class="label">Plan de Estudios</label>
                  <input v-model="perfil.plan_estudios" class="input" placeholder="Ej: 2007" required>
                </div>
                <div>
                  <label class="label">Promedio Global (0-100)</label>
                  <input v-model="perfil.promedio_global" type="number" step="0.01" min="0" max="100" class="input" required>
                </div>
              </div>
              <div>
                <label class="label">Fecha de Conclusión del Plan</label>
                <input v-model="perfil.fecha_conclusion_plan" type="date" class="input" required>
              </div>
              <button type="submit" class="btn-primary w-full sm:w-auto px-8 py-3">
                <AppIcon name="check" :size="16" />
                Guardar Perfil
              </button>
            </form>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <StatCard label="Código Universitario" :value="estudianteStore.perfilEstudiante.codigo_universitario" icon="file-text" tone="amber" />
        <StatCard label="Plan de Estudios" :value="estudianteStore.perfilEstudiante.plan_estudios" icon="book" tone="rose" />
        <StatCard label="Promedio Global" :value="estudianteStore.perfilEstudiante.promedio_global" icon="chart" tone="orange" />
        <StatCard
          label="Estado del Trámite"
          :value="tramitesStore.tramiteActivo ? formatoEstado(tramitesStore.tramiteActivo.estado_actual) : '—'"
          :icon="tramitesStore.tramiteActivo ? 'trending-up' : 'inbox'"
          :tone="tramitesStore.tramiteActivo ? 'amber' : 'rose'"
          :sublabel="tramitesStore.tramiteActivo ? tramitesStore.tramiteActivo.modalidad.nombre : 'Sin trámite activo'"
        />
      </div>

      <div v-if="cargandoTramite" class="card flex items-center justify-center gap-2 py-16 text-stone-400">
        <AppIcon name="loader" :size="20" class="animate-spin" />
        Cargando tu trámite...
      </div>

      <div v-else class="space-y-6">
        <div v-if="tramitesStore.tramiteActivo && !esTesisActivo" class="card overflow-hidden">
          <div class="h-2 bg-gradient-to-r from-amber-500 to-orange-600"></div>
          <div class="p-6 sm:p-8">
            <TimelineTramite :tramite="tramitesStore.tramiteActivo" title="Seguimiento de mi Titulación" />
          </div>
        </div>

        <router-link v-else-if="tramitesStore.tramiteActivo && esTesisActivo" to="/estudiante/tesis" class="card overflow-hidden relative block group">
          <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-amber-500 to-rose-500"></div>
          <div class="p-6 sm:p-8 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-2xl bg-amber-50 ring-1 ring-amber-200 flex items-center justify-center text-amber-600 group-hover:scale-105 transition">
                <AppIcon name="graduation" :size="28" />
              </div>
              <div>
                <h2 class="text-lg font-extrabold text-stone-900">Tesis de Grado · En Curso</h2>
                <p class="text-sm text-stone-500">
                  Estado:
                  <span class="font-semibold text-amber-700">{{ formatoEstado(tramitesStore.tramiteActivo.estado_actual) }}</span>
                </p>
              </div>
            </div>
            <span class="btn-warm px-5 py-2.5">
              <AppIcon name="arrow-left" :size="15" class="rotate-180" />
              Continuar en el Módulo
            </span>
          </div>
        </router-link>

        <template v-if="mostrarInicioSolicitud">
          <div v-if="tramiteTerminado" class="rounded-xl bg-orange-50 ring-1 ring-orange-200 p-4 flex items-start gap-3 text-orange-800">
            <AppIcon name="check-circle" :size="20" class="mt-0.5 shrink-0" />
            <p class="text-sm font-medium">
              Tu último trámite finalizó con estado
              <strong>{{ formatoEstado(tramitesStore.tramiteActivo.estado_actual) }}</strong>.
              Puedes iniciar una nueva tesis de grado cuando lo necesites.
            </p>
          </div>

          <router-link to="/estudiante/tesis" class="card overflow-hidden relative block group">
            <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-amber-500 to-rose-500"></div>
            <div class="p-6 sm:p-8 flex flex-wrap items-center justify-between gap-4">
              <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 ring-1 ring-amber-200 flex items-center justify-center text-amber-600 group-hover:scale-105 transition">
                  <AppIcon name="graduation" :size="28" />
                </div>
                <div>
                  <h2 class="text-lg font-extrabold text-stone-900">Tesis de Grado</h2>
                  <p class="text-sm text-stone-500">Módulo dedicado: solicitud con 3 documentos, seguimiento del Consejo, plazo de presentación y defensa.</p>
                </div>
              </div>
              <span class="btn-warm px-5 py-2.5">
                <AppIcon name="arrow-left" :size="15" class="rotate-180" />
                Ingresar al Módulo
              </span>
            </div>
          </router-link>
        </template>
      </div>
    </template>
  </AppShell>
</template>

<script setup>
// Vista del portal del estudiante.
// Si el estudiante no tiene perfil completo muestra el formulario para crearlo;
// en caso contrario despliega las estadísticas de su perfil y la línea de tiempo
// de su trámite activo (o el acceso al módulo dedicado de Tesis de Grado).
import { TimelineTramite, useTramitesStore, formatoEstado, ESTADOS_TERMINALES } from '@/modules/tramites';
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/modules/auth';
import { useEstudianteStore } from '../stores/estudiante';
import { useToastStore } from '@/core/stores/toast';
import { AppShell } from '@/modules/layout';
import AppIcon from '@/ui/AppIcon.vue';
import StatCard from '@/ui/StatCard.vue';

const authStore = useAuthStore();
const tramitesStore = useTramitesStore();
const estudianteStore = useEstudianteStore();
const toastStore = useToastStore();

// Formulario de perfil académico inicial.
const perfil = ref({ codigo_universitario: '', plan_estudios: '', fecha_conclusion_plan: '', promedio_global: '' });
const cargandoTramite = computed(() => tramitesStore.cargandoTramite);

// true si el trámite activo ya alcanzó un estado terminal.
const tramiteTerminado = computed(() => ESTADOS_TERMINALES.includes(tramitesStore.tramiteActivo?.estado_actual));

// true si el trámite activo es de la modalidad Tesis de Grado: en ese caso el
// portal no duplica la línea de tiempo (la muestra el módulo dedicado), solo
// el resumen/estado con acceso al módulo.
const esTesisActivo = computed(() => tramitesStore.tramiteActivo?.modalidad?.nombre === 'Tesis de Grado');

// Permite iniciar una nueva solicitud solo si no hay trámite activo o ya terminó.
const mostrarInicioSolicitud = computed(() => !tramitesStore.tramiteActivo || tramiteTerminado.value);

// Carga inicial: perfil y trámite activo.
onMounted(async () => {
    await estudianteStore.cargarPerfil(true);
    await tramitesStore.cargarTramiteActivo();
});

/** Guarda el perfil académico vía POST /api/estudiante/perfil y avisa el resultado. */
const guardarPerfil = async () => {
    try {
        await estudianteStore.guardarPerfil(perfil.value);
        toastStore.success('Perfil guardado correctamente.');
    } catch (error) {
        toastStore.error('Error al guardar: ' + (error.response?.data?.message || 'Verifique los datos'));
    }
};
</script>
