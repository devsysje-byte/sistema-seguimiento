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
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Solicitudes de Titulación Pendientes</h2>
        
        <div v-if="tramitesStore.tramitesPendientes.length === 0" class="text-gray-500 text-center py-4">
          No hay solicitudes pendientes de revisión.
        </div>

        <div v-for="tramite in tramitesStore.tramitesPendientes" :key="tramite.id_tramite" class="border-b py-4">
          <div class="flex justify-between items-start">
            <div>
              <h3 class="text-lg font-bold text-blue-600">{{ tramite.modalidad.nombre }}</h3>
              <p class="text-gray-700">Estudiante: {{ tramite.estudiante.user.nombres }} {{ tramite.estudiante.user.apellidos }}</p>
              <p class="text-sm text-gray-500">Código: {{ tramite.estudiante.codigo_universitario }} | Promedio: {{ tramite.estudiante.promedio_global }}</p>
            </div>
            <div class="flex gap-2">
              <button @click="aprobar(tramite.id_tramite)" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Aprobar Documentación</button>
              <button @click="rechazar(tramite.id_tramite)" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Rechazar</button>
            </div>
          </div>
          
          <!-- Lista de documentos subidos -->
          <div class="mt-3 flex gap-2">
            <a v-for="doc in tramite.documentos" :key="doc.id_documento" 
               :href="`http://localhost:8000/storage/${doc.ruta_archivo}`" 
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
import { onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useTramitesStore } from '../stores/tramites';

const authStore = useAuthStore();
const tramitesStore = useTramitesStore();

onMounted(() => {
  tramitesStore.cargarPendientes();
});


// En lugar de aprobar directamente:
router.push({ name: 'GestionTramite', params: { id: tramite.id_tramite } });
/*
const aprobar = async (id) => {
  const obs = prompt('Observaciones (opcional):');
  await tramitesStore.revisarTramite(id, 'aprobar', obs || '');
  alert('Solicitud aprobada. El estudiante puede continuar con el pago en Secretaría.');
};
*/
const rechazar = async (id) => {
  const obs = prompt('Motivo del rechazo (obligatorio):');
  if (obs) {
    await tramitesStore.revisarTramite(id, 'rechazar', obs);
    alert('Solicitud rechazada. Se notificó al estudiante.');
  } else {
    alert('Debe ingresar un motivo para rechazar.');
  }
};

const logout = () => {
  authStore.logout();
  window.location.href = '/login';
};
</script>