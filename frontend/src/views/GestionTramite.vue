<template>
  <div class="min-h-screen bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow">
      <h1 class="text-2xl font-bold mb-6">Gestión de Trámite #{{ tramite?.id_tramite }}</h1>

      <div v-if="tramite" class="grid grid-cols-2 gap-6">
        <!-- Datos del Estudiante -->
        <div class="col-span-2 bg-gray-50 p-4 rounded">
          <h3 class="font-bold text-lg">Datos del Postulante</h3>
          <p>{{ tramite.estudiante.user.nombres }} {{ tramite.estudiante.user.apellidos }}</p>
          <p class="text-sm text-gray-600">Código: {{ tramite.estudiante.codigo_universitario }}</p>
        </div>

        <!-- Historial de Estados -->
        <div class="col-span-2">
          <h3 class="font-bold text-lg mb-2">Historial de Seguimiento</h3>
          <ul class="space-y-2 max-h-60 overflow-y-auto border p-2 rounded">
            <li v-for="estado in tramite.estados" :key="estado.id_estado" class="flex justify-between text-sm border-b pb-1">
              <span><strong>{{ estado.nombre_estado.replace(/_/g, ' ') }}</strong> - {{ estado.observaciones }}</span>
              <span class="text-gray-500">{{ new Date(estado.created_at).toLocaleString() }}</span>
            </li>
          </ul>
        </div>

        <!-- Panel de Transición (Solo para roles autorizados) -->
        <div class="col-span-2 border-t pt-4">
          <h3 class="font-bold text-lg mb-4">Avanzar Trámite</h3>
          <div class="flex gap-4">
            <select v-model="nuevoEstado" class="border p-2 rounded flex-1">
              <option value="" disabled>Seleccione el siguiente estado...</option>
              <option v-for="estado in siguientesEstados" :key="estado" :value="estado">
                {{ estado.replace(/_/g, ' ').toUpperCase() }}
              </option>
            </select>
            <input v-model="observaciones" placeholder="Observaciones (Obligatorio para rechazos)" class="border p-2 rounded flex-2 w-full">
            <button @click="ejecutarTransicion" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
              Ejecutar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';

const route = useRoute();
const router = useRouter();
const tramite = ref(null);
const siguientesEstados = ref([]);
const nuevoEstado = ref('');
const observaciones = ref('');

onMounted(async () => {
  const { data } = await api.get(`/tramites/${route.params.id}`);
  tramite.value = data;
  
  // Calcular siguientes estados permitidos (lógica simple en frontend, validada en backend)
  // En producción, podrías pedir esto al backend: api.get(`/tramites/${id}/siguientes-estados`)
  siguientesEstados.value = getNextStates(data.modalidad.nombre, data.estado_actual);
});

const getNextStates = (modalidad, actual) => {
  const map = {
    'Examen de Grado': { 'solicitud_presentada': ['certificacion_acreditacion'], 'certificacion_acreditacion': ['inscripcion_pagada'] },
    'Tesis de Grado': { 'solicitud_presentada': ['tema_aprobado'], 'perfil_en_evaluacion': ['perfil_aprobado', 'perfil_rechazado'] }
  };
  return map[modalidad]?.[actual] || [];
};

const ejecutarTransicion = async () => {
  if (!nuevoEstado.value) return alert('Seleccione un estado');
  try {
    await api.post(`/tramites/${tramite.value.id_tramite}/transicionar`, {
      nuevo_estado: nuevoEstado.value,
      observaciones: observaciones.value
    });
    alert('Estado actualizado correctamente. Se notificará al estudiante.');
    router.push('/kardex'); // Volver a la lista
  } catch (error) {
    alert('Error: ' + (error.response?.data?.message || 'Transición no permitida'));
  }
};
</script>