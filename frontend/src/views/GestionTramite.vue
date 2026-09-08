<template>
  <div class="min-h-screen bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Gestión de Trámite #{{ tramite?.id_tramite }}</h1>
        <button @click="volver" class="text-blue-600 hover:underline text-sm">← Volver a la lista</button>
      </div>

      <div v-if="cargando" class="text-gray-500 text-center py-8">Cargando trámite...</div>

      <div v-else-if="tramite" class="grid grid-cols-2 gap-6">
        <!-- Datos del Estudiante -->
        <div class="col-span-2 bg-gray-50 p-4 rounded">
          <h3 class="font-bold text-lg">Datos del Postulante</h3>
          <p>{{ tramite.estudiante.user.nombres }} {{ tramite.estudiante.user.apellidos }}</p>
          <p class="text-sm text-gray-600">Código: {{ tramite.estudiante.codigo_universitario }} | Promedio: {{ tramite.estudiante.promedio_global }}</p>
          <p class="text-sm text-gray-600 mt-1">Modalidad: <strong>{{ tramite.modalidad.nombre }}</strong></p>
          <p class="text-sm text-gray-700 mt-1 flex items-center gap-2">
            Estado actual:
            <span class="px-2 py-0.5 rounded-full text-xs font-bold text-white" :class="estadoChipClass(tramite.estado_actual)">
              {{ formatoEstado(tramite.estado_actual) }}
            </span>
          </p>
        </div>

        <!-- Documentos subidos -->
        <div class="col-span-2">
          <h3 class="font-bold text-lg mb-2">Documentos</h3>
          <div class="flex flex-wrap gap-3">
            <a v-for="doc in tramite.documentos" :key="doc.id_documento"
               :href="`${baseStorageUrl}/${doc.ruta_archivo}`" target="_blank"
               class="bg-blue-50 border border-blue-200 text-blue-700 px-3 py-2 rounded text-sm hover:bg-blue-100">
               📄 {{ doc.tipo_documento }}
            </a>
          </div>
        </div>

        <!-- Aprobar/Rechazar documentación inicial -->
        <div v-if="tramite.estado_actual === 'solicitud_presentada'" class="col-span-2 bg-yellow-50 border border-yellow-200 p-4 rounded">
          <h3 class="font-bold text-lg mb-2">Revisión de Documentación Inicial</h3>
          <p class="text-sm text-gray-600 mb-3">Los documentos aún no fueron revisados. Aprobar la documentación inicia el flujo de estados según la modalidad.</p>
          <div class="flex gap-4 items-center">
            <input v-model="observacionesRev" placeholder="Observaciones (obligatorio para rechazar)" class="border p-2 rounded flex-1">
            <button @click="revisar('aprobar')" class="bg-green-600 text-white px-5 py-2 rounded hover:bg-green-700">Aprobar</button>
            <button @click="revisar('rechazar')" class="bg-red-500 text-white px-5 py-2 rounded hover:bg-red-600">Rechazar</button>
          </div>
        </div>

        <!-- Historial de Estados (línea de tiempo) -->
        <div class="col-span-2">
          <h3 class="font-bold text-lg mb-2">Historial de Seguimiento</h3>
          <ol v-if="tramite.estados.length" class="relative border-l-2 border-gray-200 ml-4 space-y-4">
            <li v-for="estado in tramite.estados.slice().reverse()" :key="estado.id_estado" class="relative pl-6">
              <span class="absolute -left-[9px] top-1 w-4 h-4 rounded-full"
                    :class="estado.nombre_estado === tramite.estado_actual ? 'bg-blue-600' : 'bg-green-500'"></span>
              <div class="text-sm">
                <p class="font-semibold text-gray-800">{{ formatoEstado(estado.nombre_estado) }}</p>
                <p class="text-gray-600">{{ estado.observaciones || 'Sin observaciones.' }}</p>
                <p class="text-gray-400 text-xs mt-0.5">
                  {{ new Date(estado.created_at).toLocaleString('es-BO') }}
                  · {{ estado.responsable?.nombres }} {{ estado.responsable?.apellidos }} ({{ estado.responsable?.rol }})
                </p>
              </div>
            </li>
          </ol>
          <p v-else class="text-gray-500 text-sm">Sin historial registrado.</p>
        </div>

        <!-- Panel de Transición -->
        <div class="col-span-2 border-t pt-4">
          <h3 class="font-bold text-lg mb-2">Avanzar Trámite</h3>
          <div v-if="siguientesEstados.length" class="flex gap-4">
            <select v-model="nuevoEstado" class="border p-2 rounded flex-1">
              <option value="" disabled>Seleccione el siguiente estado...</option>
              <option v-for="estado in siguientesEstados" :key="estado" :value="estado">
                {{ formatoEstado(estado).toUpperCase() }}
              </option>
            </select>
            <input v-model="observaciones" placeholder="Observaciones" class="border p-2 rounded flex-1 w-full">
            <button @click="ejecutarTransicion" :disabled="ejecutando" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:opacity-50">
              {{ ejecutando ? 'Ejecutando...' : 'Ejecutar' }}
            </button>
          </div>
          <p v-else class="text-sm text-gray-500">No hay más transiciones permitidas desde este estado.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';

const route = useRoute();
const router = useRouter();
const tramite = ref(null);
const cargando = ref(false);
const ejecutando = ref(false);
const siguientesEstados = ref([]);
const nuevoEstado = ref('');
const observaciones = ref('');
const observacionesRev = ref('');

const baseStorageUrl = import.meta.env.VITE_STORAGE_URL || 'http://localhost:8000/storage';

const cargarTramite = async () => {
  cargando.value = true;
  try {
    const { data } = await api.get(`/tramites/${route.params.id}`);
    tramite.value = data;
    siguientesEstados.value = data.siguientes_estados || [];
  } catch (error) {
    alert('Error al cargar el trámite: ' + (error.response?.data?.message || 'Verifique el acceso'));
    router.push('/kardex');
  } finally {
    cargando.value = false;
  }
};

onMounted(cargarTramite);

const ejecutarTransicion = async () => {
  if (!nuevoEstado.value) return alert('Seleccione un estado');
  ejecutando.value = true;
  try {
    const { data } = await api.post(`/tramites/${tramite.value.id_tramite}/transicionar`, {
      nuevo_estado: nuevoEstado.value,
      observaciones: observaciones.value
    });
    tramite.value = data;
    siguientesEstados.value = data.siguientes_estados || [];
    nuevoEstado.value = '';
    observaciones.value = '';
    alert('Estado actualizado correctamente.');
  } catch (error) {
    alert('Error: ' + (error.response?.data?.message || 'Transición no permitida'));
  } finally {
    ejecutando.value = false;
  }
};

const revisar = async (accion) => {
  if (accion === 'rechazar' && !observacionesRev.value) {
    return alert('Debe ingresar un motivo para rechazar.');
  }
  try {
    const { data } = await api.post(`/tramites/${tramite.value.id_tramite}/revisar`, {
      accion,
      observaciones: observacionesRev.value || (accion === 'aprobar' ? 'Documentación inicial aprobada.' : 'Solicitud rechazada.')
    });
    tramite.value = data;
    siguientesEstados.value = data.siguientes_estados || [];
    observacionesRev.value = '';
    alert(accion === 'aprobar' ? 'Documentación aprobada. El trámite avanza en la línea de tiempo.' : 'Solicitud rechazada.');
  } catch (error) {
    alert('Error: ' + (error.response?.data?.message || 'No se pudo completar la acción'));
  }
};

const volver = () => router.push('/kardex');

const formatoEstado = (nombre) => String(nombre || '').replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());

const estadoChipClass = (estado) => {
  return ['aprobado', 'documentacion_ok', 'perfil_aprobado'].includes(estado)
    ? 'bg-green-500'
    : ['rechazado', 'reprobado', 'reprobado_ausencia'].includes(estado)
      ? 'bg-red-500'
      : 'bg-blue-500';
};
</script>