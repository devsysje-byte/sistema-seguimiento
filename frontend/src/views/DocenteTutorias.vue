<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-indigo-800 text-white p-4 flex justify-between items-center">
      <h1 class="text-xl font-bold">Panel del Docente Tutor</h1>
      <div class="flex items-center gap-4">
        <span>Hola, {{ authStore.user?.nombres }} {{ authStore.user?.apellidos }}</span>
        <button @click="logout" class="bg-red-500 px-4 py-2 rounded hover:bg-red-600">Cerrar Sesión</button>
      </div>
    </nav>

    <div class="p-8 max-w-6xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Mis Tutorías</h2>
        <button @click="recargar" :disabled="cargando" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 disabled:opacity-50">
          {{ cargando ? 'Cargando...' : 'Recargar' }}
        </button>
      </div>

      <div v-if="cargando" class="text-gray-500 text-center py-8">Cargando tutorías...</div>

      <template v-else>
        <div v-if="activos.length === 0 && finalizados.length === 0" class="bg-white rounded-lg shadow p-10 text-center">
          <p class="text-gray-500 text-lg">No tienes estudiantes asignados a tu tutoría todavía.</p>
          <p class="text-gray-400 text-sm mt-2">Cuando Kardex/Dirección te asigne como tutor de una modalidad, aparecerán aquí.</p>
        </div>

        <template v-else>
          <!-- Tutorías en proceso -->
          <h3 class="text-xl font-bold text-gray-700 mb-3">En Proceso ({{ activos.length }})</h3>
          <div v-if="activos.length" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <div v-for="tramite in activos" :key="tramite.id_tramite" class="bg-white rounded-lg shadow p-5">
              <div class="flex justify-between items-start">
                <div>
                  <h4 class="text-lg font-bold text-indigo-700">{{ tramite.modalidad.nombre }}</h4>
                  <p class="text-gray-800">{{ tramite.estudiante.user.nombres }} {{ tramite.estudiante.user.apellidos }}</p>
                  <p class="text-sm text-gray-500">Cod: {{ tramite.estudiante.codigo_universitario }} | Promedio: {{ tramite.estudiante.promedio_global }}</p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold text-white" :class="estadoChipClass(tramite.estado_actual)">
                  {{ formatoEstado(tramite.estado_actual) }}
                </span>
              </div>
              <div class="mt-3">
                <div class="flex justify-between text-sm text-gray-600 mb-1">
                  <span>Progreso</span><span>{{ porcentaje(tramite) }}%</span>
                </div>
                <div class="bg-gray-200 h-2 rounded-full overflow-hidden">
                  <div class="bg-indigo-600 h-2 rounded-full" :style="{ width: porcentaje(tramite) + '%' }"></div>
                </div>
              </div>
              <p v-if="tramite.estados.length" class="text-xs text-gray-400 mt-2">
                Último: {{ formatoEstado(tramite.estado_actual) }} · {{ new Date(tramite.estados[tramite.estados.length - 1].created_at).toLocaleString('es-BO') }}
              </p>
              <button @click="gestionar(tramite)" class="mt-3 w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700">
                Ver Seguimiento del Trámite
              </button>
            </div>
          </div>
          <div v-else class="bg-yellow-50 border border-yellow-200 p-4 rounded text-yellow-800 mb-8">
            Sin tutorías activas en este momento.
          </div>

          <!-- Tutorías finalizadas -->
          <h3 v-if="finalizados.length" class="text-xl font-bold text-gray-700 mb-3">Finalizados ({{ finalizados.length }})</h3>
          <div v-if="finalizados.length" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="tramite in finalizados" :key="tramite.id_tramite" class="bg-white rounded-lg shadow p-5 opacity-80">
              <div class="flex justify-between items-start">
                <div>
                  <h4 class="text-lg font-bold text-gray-600">{{ tramite.modalidad.nombre }}</h4>
                  <p>{{ tramite.estudiante.user.nombres }} {{ tramite.estudiante.user.apellidos }}</p>
                  <p class="text-sm text-gray-500">Cod: {{ tramite.estudiante.codigo_universitario }}</p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold text-white" :class="estadoChipClass(tramite.estado_actual)">
                  {{ formatoEstado(tramite.estado_actual) }}
                </span>
              </div>
              <button @click="gestionar(tramite)" class="mt-3 w-full bg-gray-500 text-white py-2 rounded hover:bg-gray-600">
                Ver Detalle
              </button>
            </div>
          </div>
        </template>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useTramitesStore } from '../stores/tramites';

const router = useRouter();
const authStore = useAuthStore();
const tramitesStore = useTramitesStore();
const cargando = ref(false);

const ESTADOS_TERMINALES = ['aprobado', 'reprobado', 'rechazado', 'reprobado_ausencia'];

const activos = computed(() => tramitesStore.tutorias.filter((t) => !ESTADOS_TERMINALES.includes(t.estado_actual)));
const finalizados = computed(() => tramitesStore.tutorias.filter((t) => ESTADOS_TERMINALES.includes(t.estado_actual)));

onMounted(recargar);

async function recargar() {
  cargando.value = true;
  await tramitesStore.cargarTutorias();
  cargando.value = false;
}

const gestionar = (tramite) => router.push({ name: 'GestionTramite', params: { id: tramite.id_tramite } });

const porcentaje = (tramite) => {
  const idx = tramite.secuencia?.indexOf(tramite.estado_actual) ?? -1;
  if (idx === -1 || !tramite.secuencia?.length) return 0;
  return Math.round(((idx + 1) / tramite.secuencia.length) * 100);
};

const formatoEstado = (nombre) => String(nombre || '').replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());

const estadoChipClass = (estado) => {
  if (['aprobado', 'documentacion_ok', 'perfil_aprobado', 'tema_aprobado', 'monografia_aprobada'].includes(estado)) return 'bg-green-500';
  if (['rechazado', 'reprobado', 'reprobado_ausencia', 'perfil_rechazado', 'monografia_rechazada'].includes(estado)) return 'bg-red-500';
  return 'bg-indigo-500';
};

const logout = () => {
  authStore.logout();
  window.location.href = '/login';
};
</script>