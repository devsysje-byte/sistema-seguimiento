<template>
  <AppShell title="Portal del Estudiante" subtitle="Seguimiento de tu modalidad de titulación">
    <template v-if="!tramitesStore.perfilEstudiante">
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
        <StatCard label="Código Universitario" :value="tramitesStore.perfilEstudiante.codigo_universitario" icon="file-text" tone="amber" />
        <StatCard label="Plan de Estudios" :value="tramitesStore.perfilEstudiante.plan_estudios" icon="book" tone="rose" />
        <StatCard label="Promedio Global" :value="tramitesStore.perfilEstudiante.promedio_global" icon="chart" tone="orange" />
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
        <div v-if="tramitesStore.tramiteActivo" class="card overflow-hidden">
          <div class="h-2 bg-gradient-to-r from-amber-500 to-orange-600"></div>
          <div class="p-6 sm:p-8">
            <TimelineTramite :tramite="tramitesStore.tramiteActivo" title="Seguimiento de mi Titulación" />
          </div>
        </div>

        <template v-if="mostrarInicioSolicitud">
          <div v-if="tramiteTerminado" class="rounded-xl bg-orange-50 ring-1 ring-orange-200 p-4 flex items-start gap-3 text-orange-800">
            <AppIcon name="check-circle" :size="20" class="mt-0.5 shrink-0" />
            <p class="text-sm font-medium">
              Tu último trámite finalizó con estado
              <strong>{{ formatoEstado(tramitesStore.tramiteActivo.estado_actual) }}</strong>.
              Puedes iniciar una nueva solicitud de titulación cuando lo necesites.
            </p>
          </div>

          <div class="card overflow-hidden relative">
            <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-orange-400 to-amber-400"></div>
            <div class="p-6 sm:p-8 flex flex-wrap items-center justify-between gap-4">
              <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 ring-1 ring-amber-200 flex items-center justify-center text-amber-600">
                  <AppIcon name="graduation" :size="28" />
                </div>
                <div>
                  <h2 class="text-lg font-extrabold text-stone-900">Iniciar Modalidad de Titulación</h2>
                  <p class="text-sm text-stone-500">Selecciona la modalidad y sube los documentos en PDF.</p>
                </div>
              </div>
              <button class="btn-warm px-6 py-3" @click="mostrarFormularioTramite = true">
                <AppIcon name="plus" :size="17" />
                Nueva Solicitud de Graduación
              </button>
            </div>
          </div>
        </template>
      </div>
    </template>

    <UiModal v-model="mostrarFormularioTramite" title="Nueva Solicitud de Titulación" max-width="560px">
      <form @submit.prevent="enviarSolicitud" class="space-y-5">
        <div>
          <label class="label">Modalidad</label>
          <select v-model="nuevoTramite.id_modalidad" class="input" required>
            <option value="" disabled>Seleccione una modalidad</option>
            <option v-for="mod in tramitesStore.modalidades" :key="mod.id_modalidad" :value="mod.id_modalidad">
              {{ mod.nombre }}
            </option>
          </select>
          <div v-if="modalidadSeleccionada" class="mt-3 rounded-xl bg-orange-50 ring-1 ring-orange-200 p-3.5 text-sm text-orange-800">
            <p class="font-bold mb-1 inline-flex items-center gap-1.5">
              <AppIcon name="info" :size="15" />
              Requisitos
            </p>
            <p class="mt-0.5">{{ modalidadSeleccionada.requisitos_minimos || 'Sin requisitos registrados.' }}</p>
          </div>
        </div>

        <div>
          <label class="label">Certificado de Notas (PDF)</label>
          <label :class="['flex items-center justify-between gap-3 px-4 py-3 rounded-xl border-2 border-dashed cursor-pointer transition', archivoCertificado ? 'border-amber-300 bg-amber-50' : 'border-stone-300 bg-stone-50 hover:border-amber-400']">
            <span class="flex items-center gap-2 text-sm" :class="archivoCertificado ? 'text-amber-700' : 'text-stone-500'">
              <AppIcon name="file-text" :size="18" />
              <span class="truncate max-w-[260px]">{{ archivoCertificado?.name || 'Selecciona el archivo...' }}</span>
            </span>
            <input type="file" accept=".pdf" class="hidden" @change="handleFileUpload($event, 'certificado_notas')" required>
            <span class="btn-ghost !py-2 pointer-events-none">
              <AppIcon name="plus" :size="15" />
              Subir
            </span>
          </label>
        </div>

        <div>
          <label class="label">Carta de Solicitud (PDF)</label>
          <label :class="['flex items-center justify-between gap-3 px-4 py-3 rounded-xl border-2 border-dashed cursor-pointer transition', archivoCarta ? 'border-amber-300 bg-amber-50' : 'border-stone-300 bg-stone-50 hover:border-amber-400']">
            <span class="flex items-center gap-2 text-sm" :class="archivoCarta ? 'text-amber-700' : 'text-stone-500'">
              <AppIcon name="file-text" :size="18" />
              <span class="truncate max-w-[260px]">{{ archivoCarta?.name || 'Selecciona el archivo...' }}</span>
            </span>
            <input type="file" accept=".pdf" class="hidden" @change="handleFileUpload($event, 'carta_solicitud')" required>
            <span class="btn-ghost !py-2 pointer-events-none">
              <AppIcon name="plus" :size="15" />
              Subir
            </span>
          </label>
        </div>

        <div class="flex justify-end gap-2 pt-1">
          <button type="button" class="btn-ghost px-4 py-2.5" @click="mostrarFormularioTramite = false">Cancelar</button>
          <button type="submit" class="btn-primary px-5 py-2.5" :disabled="enviando">
            <AppIcon v-if="enviando" name="loader" :size="15" class="animate-spin" />
            <AppIcon v-else name="send" :size="15" />
            {{ enviando ? 'Enviando...' : 'Enviar Solicitud' }}
          </button>
        </div>
      </form>
    </UiModal>
  </AppShell>
</template>

<script setup>
import TimelineTramite from '../components/TimelineTramite.vue';
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useTramitesStore } from '../stores/tramites';
import { formatoEstado, ESTADOS_TERMINALES } from '../utils/estados';
import AppShell from '../components/ui/AppShell.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import StatCard from '../components/ui/StatCard.vue';
import UiModal from '../components/ui/UiModal.vue';

const authStore = useAuthStore();
const tramitesStore = useTramitesStore();

const perfil = ref({ codigo_universitario: '', plan_estudios: '', fecha_conclusion_plan: '', promedio_global: '' });
const mostrarFormularioTramite = ref(false);
const nuevoTramite = ref({ id_modalidad: '', documentos: [] });
const enviando = ref(false);
const archivoCertificado = ref(null);
const archivoCarta = ref(null);
const cargandoTramite = computed(() => tramitesStore.cargandoTramite);

const modalidadSeleccionada = computed(() => {
    return tramitesStore.modalidades.find((m) => m.id_modalidad == nuevoTramite.value.id_modalidad) || null;
});

const tramiteTerminado = computed(() => ESTADOS_TERMINALES.includes(tramitesStore.tramiteActivo?.estado_actual));

const mostrarInicioSolicitud = computed(() => !tramitesStore.tramiteActivo || tramiteTerminado.value);

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
    if (tipo === 'certificado_notas') archivoCertificado.value = file;
    if (tipo === 'carta_solicitud') archivoCarta.value = file;
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
        archivoCertificado.value = null;
        archivoCarta.value = null;
    } catch (error) {
        alert('Error al enviar: ' + (error.response?.data?.message || 'Verifique los datos'));
    } finally {
        enviando.value = false;
    }
};
</script>
