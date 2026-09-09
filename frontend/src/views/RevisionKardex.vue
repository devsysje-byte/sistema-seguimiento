<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-purple-800 text-white p-4 flex justify-between items-center">
      <h1 class="text-xl font-bold">Panel de Revisión (Kardex/Dirección)</h1>
      <div class="flex items-center gap-4">
        <span>Hola, {{ authStore.user.nombres }} ({{ authStore.user.rol }})</span>
        <button @click="logout" class="bg-red-500 px-4 py-2 rounded hover:bg-red-600">Cerrar Sesión</button>
      </div>
    </nav>

    <div class="p-8 max-w-6xl mx-auto">
      <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Trámites de Titulación en Proceso</h2>

        <div v-if="cargando" class="text-gray-500 text-center py-4">Cargando...</div>
        <div v-else-if="tramitesStore.tramitesPendientes.length === 0" class="text-gray-500 text-center py-4">
          No hay solicitudes pendientes de revisión.
        </div>

        <div v-for="tramite in tramitesStore.tramitesPendientes" :key="tramite.id_tramite" class="border-b py-4">
          <div class="flex justify-between items-start">
            <div>
              <h3 class="text-lg font-bold text-blue-600">{{ tramite.modalidad.nombre }}</h3>
              <p class="text-gray-700">Estudiante: {{ tramite.estudiante.user.nombres }} {{ tramite.estudiante.user.apellidos }}</p>
              <p class="text-sm text-gray-500">Código: {{ tramite.estudiante.codigo_universitario }} | Promedio: {{ tramite.estudiante.promedio_global }}</p>
              <p class="text-sm mt-1">
                <span class="font-semibold text-gray-600">Estado:</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold text-white" :class="estadoChipClass(tramite.estado_actual)">
                  {{ formatoEstado(tramite.estado_actual) }}
                </span>
              </p>
              <p class="text-sm mt-1">
                <span class="font-semibold text-gray-600">Tutor:</span>
                <span v-if="tramite.tutor">{{ tramite.tutor.nombres }} {{ tramite.tutor.apellidos }}</span>
                <span v-else class="text-gray-400">No asignado (asignar en Gestionar)</span>
              </p>
            </div>
            <div class="flex gap-2">
              <button
                v-if="tramite.estado_actual === 'solicitud_presentada'"
                @click="aprobar(tramite)"
                class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
              >Aprobar Documentación</button>
              <button
                v-if="tramite.estado_actual === 'solicitud_presentada'"
                @click="rechazar(tramite)"
                class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
              >Rechazar</button>
              <button
                @click="gestionar(tramite)"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
              >Ver / Gestionar</button>
            </div>
          </div>

          <!-- Lista de documentos subidos -->
          <div class="mt-3 flex gap-2">
            <a v-for="doc in tramite.documentos" :key="doc.id_documento"
               :href="`${baseStorageUrl}/${doc.ruta_archivo}`"
               target="_blank"
               class="text-blue-500 underline text-sm">
               📄 {{ doc.tipo_documento }}
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useTramitesStore } from '../stores/tramites';

const router = useRouter();
const authStore = useAuthStore();
const tramitesStore = useTramitesStore();
const cargando = ref(false);

const baseStorageUrl = import.meta.env.VITE_STORAGE_URL || 'http://localhost:8000/storage';

onMounted(async () => {
  cargando.value = true;
  await tramitesStore.cargarPendientes();
  cargando.value = false;
});

const gestionar = (tramite) => {
  router.push({ name: 'GestionTramite', params: { id: tramite.id_tramite } });
};

const aprobar = async (tramite) => {
  const obs = prompt('Observaciones (opcional):');
  if (obs === null) return;
  try {
    await tramitesStore.revisarTramite(tramite.id_tramite, 'aprobar', obs || 'Documentación inicial aprobada.');
    alert('Solicitud aprobada. El estudiante puede continuar con el trámite.');
  } catch (error) {
    alert('Error: ' + (error.response?.data?.message || 'No se pudo aprobar.'));
  }
};

const rechazar = async (tramite) => {
  const obs = prompt('Motivo del rechazo (obligatorio):');
  if (!obs) {
    alert('Debe ingresar un motivo para rechazar.');
    return;
  }
  try {
    await tramitesStore.revisarTramite(tramite.id_tramite, 'rechazar', obs);
    alert('Solicitud rechazada. Se notificará al estudiante.');
  } catch (error) {
    alert('Error: ' + (error.response?.data?.message || 'No se pudo rechazar.'));
  }
};

const logout = () => {
  authStore.logout();
  window.location.href = '/login';
};

const formatoEstado = (nombre) => {
  return String(nombre || '').replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};

const estadoChipClass = (estado) => {
  if (['aprobado', 'documentacion_ok', 'perfil_aprobado'].includes(estado)) return 'bg-green-500';
  if (['rechazado', 'reprobado', 'reprobado_ausencia'].includes(estado)) return 'bg-red-500';
  return 'bg-blue-500';
};
</script>