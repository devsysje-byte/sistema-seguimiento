<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-blue-600 text-white p-4 flex justify-between items-center">
      <h1 class="text-xl font-bold">Portal del Estudiante</h1>
      <div class="flex items-center gap-4">
        <span>Hola, {{ authStore.user?.nombres }}</span>
        <button @click="logout" class="bg-red-500 px-4 py-2 rounded hover:bg-red-600">Cerrar Sesión</button>
      </div>
    </nav>

    <div class="p-8 max-w-4xl mx-auto">
      <!-- Si no tiene perfil, mostrar formulario de registro -->
      <div v-if="!tramitesStore.perfilEstudiante" class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Complete su Perfil Académico</h2>
        <form @submit.prevent="guardarPerfil" class="space-y-4">
          <input v-model="perfil.codigo_universitario" placeholder="Código Universitario" class="w-full border p-2 rounded" required>
          <input v-model="perfil.plan_estudios" placeholder="Plan de Estudios (Ej: 2007)" class="w-full border p-2 rounded" required>
          <label class="block text-sm text-gray-700">Fecha de Conclusión del Plan</label>
          <input v-model="perfil.fecha_conclusion_plan" type="date" class="w-full border p-2 rounded" required>
          <input v-model="perfil.promedio_global" placeholder="Promedio Global (0-100)" type="number" step="0.01" class="w-full border p-2 rounded" required>
          <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Guardar Perfil</button>
        </form>
      </div>

      <div v-else class="space-y-6">
        <!-- Línea de tiempo del trámite activo -->
        <div v-if="tramitesStore.tramiteActivo" class="bg-white p-6 rounded-lg shadow">
          <TimelineTramite :tramite="tramitesStore.tramiteActivo" />
        </div>

        <!-- Sin trámite (o trámite terminado): opciones -->
        <div v-if="mostrarInicioSolicitud" class="space-y-6">
          <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-2xl font-bold text-gray-800">Mis Datos Académicos</h2>
            <p class="text-gray-600 mt-2">Código: {{ tramitesStore.perfilEstudiante.codigo_universitario }}</p>
            <p class="text-gray-600">Plan: {{ tramitesStore.perfilEstudiante.plan_estudios }}</p>
            <p class="text-gray-600">Promedio: {{ tramitesStore.perfilEstudiante.promedio_global }}</p>
          </div>

          <div v-if="tramiteTerminado" class="bg-green-50 border border-green-200 p-4 rounded">
            <p class="text-green-800 font-semibold">Tu último trámite finalizó ({{ formatoEstado(tramitesStore.tramiteActivo.estado_actual) }}). Puedes iniciar una nueva solicitud cuando lo necesites.</p>
          </div>

          <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-2xl font-bold text-gray-800 mb-1">Iniciar Modalidad de Titulación</h2>
            <p class="text-gray-600 mb-4">Selecciona la modalidad y sube los documentos requeridos en formato PDF.</p>
            <button @click="mostrarFormularioTramite = true" class="bg-green-600 text-white px-6 py-3 rounded hover:bg-green-700 text-lg">
              + Nueva Solicitud de Graduación
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para Nueva Solicitud -->
    <div v-if="mostrarFormularioTramite" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white p-6 rounded-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold mb-4">Seleccionar Modalidad y Subir Documentos</h3>
        <form @submit.prevent="enviarSolicitud" class="space-y-4">
          <select v-model="nuevoTramite.id_modalidad" class="w-full border p-2 rounded" required>
            <option value="" disabled>Seleccione una modalidad</option>
            <option v-for="mod in tramitesStore.modalidades" :key="mod.id_modalidad" :value="mod.id_modalidad">
              {{ mod.nombre }}
            </option>
          </select>

          <div v-if="modalidadSeleccionada" class="bg-yellow-50 border border-yellow-200 p-3 rounded text-sm text-gray-700">
            <p class="font-bold mb-1">Requisitos:</p>
            <p>{{ modalidadSeleccionada.requisitos_minimos || 'Sin requisitos registrados.' }}</p>
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-bold text-gray-700">Certificado de Notas (PDF)</label>
            <input type="file" @change="handleFileUpload($event, 'certificado_notas')" accept=".pdf" class="w-full border p-2 rounded" required>
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-bold text-gray-700">Carta de Solicitud (PDF)</label>
            <input type="file" @change="handleFileUpload($event, 'carta_solicitud')" accept=".pdf" class="w-full border p-2 rounded" required>
          </div>

          <div class="flex justify-end gap-2 mt-4">
            <button type="button" @click="mostrarFormularioTramite = false" class="bg-gray-300 px-4 py-2 rounded">Cancelar</button>
            <button type="submit" :disabled="enviando" class="bg-blue-600 text-white px-4 py-2 rounded disabled:opacity-50">
              {{ enviando ? 'Enviando...' : 'Enviar Solicitud' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import TimelineTramite from '../components/TimelineTramite.vue';
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useTramitesStore } from '../stores/tramites';

const authStore = useAuthStore();
const tramitesStore = useTramitesStore();

const perfil = ref({ codigo_universitario: '', plan_estudios: '', fecha_conclusion_plan: '', promedio_global: '' });
const mostrarFormularioTramite = ref(false);
const nuevoTramite = ref({ id_modalidad: '', documentos: [] });
const enviando = ref(false);

const modalidadSeleccionada = computed(() => {
    return tramitesStore.modalidades.find((m) => m.id_modalidad == nuevoTramite.value.id_modalidad) || null;
});

const ESTADOS_TERMINALES = ['aprobado', 'reprobado', 'rechazado', 'reprobado_ausencia'];

const tramiteTerminado = computed(() => {
    const actual = tramitesStore.tramiteActivo?.estado_actual;
    return ESTADOS_TERMINALES.includes(actual);
});

const mostrarInicioSolicitud = computed(() => {
    return !tramitesStore.tramiteActivo || tramiteTerminado.value;
});

const formatoEstado = (nombre) => String(nombre || '').replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());

onMounted(async () => {
    await tramitesStore.cargarPerfil();
    await tramitesStore.cargarModalidades();
    await tramitesStore.cargarTramiteActivo();
});

const guardarPerfil = async () => {
    try {
        await tramitesStore.guardarPerfil(perfil.value);
        alert('Perfil guardado correctamente.');
    } catch (error) {
        alert('Error al guardar: ' + (error.response?.data?.message || 'Verifique los datos'));
    }
};

const handleFileUpload = (event, tipo) => {
    const file = event.target.files[0];
    if (!file) return;
    const index = nuevoTramite.value.documentos.findIndex((d) => d.tipo === tipo);
    if (index !== -1) {
        nuevoTramite.value.documentos[index].archivo = file;
    } else {
        nuevoTramite.value.documentos.push({ tipo, archivo: file });
    }
};

const enviarSolicitud = async () => {
    enviando.value = true;
    const formData = new FormData();
    formData.append('id_modalidad', nuevoTramite.value.id_modalidad);

    nuevoTramite.value.documentos.forEach((doc, index) => {
        formData.append(`documentos[${index}][archivo]`, doc.archivo);
        formData.append(`documentos[${index}][tipo]`, doc.tipo);
    });

    try {
        await tramitesStore.iniciarTramite(formData);
        alert('Solicitud enviada correctamente. Espere la revisión de Kardex.');
        mostrarFormularioTramite.value = false;
        nuevoTramite.value = { id_modalidad: '', documentos: [] };
    } catch (error) {
        alert('Error al enviar: ' + (error.response?.data?.message || 'Verifique los datos'));
    } finally {
        enviando.value = false;
    }
};

const logout = () => {
    authStore.logout();
    window.location.href = '/login';
};
</script>