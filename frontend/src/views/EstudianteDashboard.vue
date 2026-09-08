<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-blue-600 text-white p-4 flex justify-between items-center">
      <h1 class="text-xl font-bold">Portal del Estudiante</h1>
      <div class="flex items-center gap-4">
        <span>Hola, {{ authStore.user.nombres }}</span>
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

      <!-- Si ya tiene perfil, mostrar opciones -->
      <div v-else class="space-y-6">
        <div class="bg-white p-6 rounded-lg shadow">
          <h2 class="text-2xl font-bold text-gray-800">Mis Datos Académicos</h2>
          <p class="text-gray-600 mt-2">Código: {{ tramitesStore.perfilEstudiante.codigo_universitario }}</p>
          <p class="text-gray-600">Promedio: {{ tramitesStore.perfilEstudiante.promedio_global }}</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
          <h2 class="text-2xl font-bold text-gray-800 mb-4">Iniciar Modalidad de Titulación</h2>
          <button @click="mostrarFormularioTramite = true" class="bg-green-600 text-white px-6 py-3 rounded hover:bg-green-700 text-lg">
            + Nueva Solicitud de Graduación
          </button>
        </div>
      </div>

      <!-- Modal para Nueva Solicitud -->
      <div v-if="mostrarFormularioTramite" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white p-6 rounded-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
          <h3 class="text-xl font-bold mb-4">Seleccionar Modalidad y Subir Documentos</h3>
          <form @submit.prevent="enviarSolicitud" class="space-y-4">
            <select v-model="nuevoTramite.id_modalidad" class="w-full border p-2 rounded" required>
              <option value="" disabled>Seleccione una modalidad</option>
              <option v-for="mod in tramitesStore.modalidades" :key="mod.id_modalidad" :value="mod.id_modalidad">
                {{ mod.nombre }}
              </option>
            </select>

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
              <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Enviar Solicitud</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useTramitesStore } from '../stores/tramites';

const authStore = useAuthStore();
const tramitesStore = useTramitesStore();

const perfil = ref({ codigo_universitario: '', plan_estudios: '', fecha_conclusion_plan: '', promedio_global: '' });
const mostrarFormularioTramite = ref(false);
const nuevoTramite = ref({ id_modalidad: '', documentos: [] });

onMounted(async () => {
  await tramitesStore.cargarPerfil();
  await tramitesStore.cargarModalidades();
});

const guardarPerfil = async () => {
  await tramitesStore.guardarPerfil(perfil.value);
};

const handleFileUpload = (event, tipo) => {
  const file = event.target.files[0];
  if (file) {
    // Buscar si ya existe para reemplazar, o agregar
    const index = nuevoTramite.value.documentos.findIndex(d => d.tipo === tipo);
    if (index !== -1) {
      nuevoTramite.value.documentos[index].archivo = file;
    } else {
      nuevoTramite.value.documentos.push({ tipo, archivo: file });
    }
  }
};

const enviarSolicitud = async () => {
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
  }
};

const logout = () => {
  authStore.logout();
  window.location.href = '/login';
};
</script>