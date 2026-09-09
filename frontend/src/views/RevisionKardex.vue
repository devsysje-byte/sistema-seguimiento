<template>
  <AppShell title="Trámites de Titulación" subtitle="Revisión y gestión de solicitudes en proceso">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatCard label="Trámites en Proceso" :value="tramitesStore.tramitesPendientes.length" icon="folder" tone="indigo" />
      <StatCard label="Documentos por Revisar" :value="porRevisar.length" icon="inbox" tone="amber" sublabel="Estado: solicitud presentada" />
      <StatCard label="Con Tutor Asignado" :value="conTutor.length" icon="user-check" tone="emerald" />
      <StatCard label="Sin Tutor" :value="sinTutor.length" icon="info" tone="rose" />
    </div>

    <div class="card overflow-hidden mb-6">
      <div class="flex flex-wrap items-center gap-3 p-5 border-b border-slate-100">
        <div>
          <h2 class="text-lg font-bold text-slate-900">Solicitudes en Proceso</h2>
          <p class="text-sm text-slate-500">Busca por estudiante, código, modalidad o estado.</p>
        </div>
        <div class="ml-auto w-full md:w-auto">
          <div class="relative md:w-64">
            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
              <AppIcon name="search" :size="17" />
            </span>
            <input v-model="buscar" class="input pl-9 md:w-64" placeholder="Buscar trámite..." />
          </div>
        </div>
      </div>
      <div v-if="cargando" class="flex items-center justify-center gap-2 py-16 text-slate-400">
        <AppIcon name="loader" :size="20" class="animate-spin" />
        Cargando trámites...
      </div>
      <div v-else-if="filtrados.length === 0" class="p-6">
        <EmptyState
          icon="folder"
          title="Sin trámites para mostrar"
          :message="tramitesStore.tramitesPendientes.length === 0 ? 'No hay solicitudes pendientes de revisión.' : 'Ningún trámite coincide con la búsqueda.'"
        >
          <button v-if="tramitesStore.tramitesPendientes.length" class="btn-ghost" @click="buscar = ''">Limpiar búsqueda</button>
        </EmptyState>
      </div>
    </div>

    <div v-if="!cargando && filtrados.length" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div v-for="tramite in filtrados" :key="tramite.id_tramite" class="card overflow-hidden flex flex-col hover:shadow-lg hover:-translate-y-0.5 transition duration-200">
        <div class="relative h-2 bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500"></div>
        <div class="p-5 flex-1 flex flex-col">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <h3 class="text-lg font-bold text-indigo-700 truncate">{{ tramite.modalidad.nombre }}</h3>
                <span class="text-xs text-slate-400">#{{ tramite.id_tramite }}</span>
              </div>
              <div class="flex items-center gap-2.5 mt-2">
                <Avatar :nombres="tramite.estudiante.user.nombres" :apellidos="tramite.estudiante.user.apellidos" size="10" />
                <div>
                  <p class="font-semibold text-slate-800 text-sm leading-tight">
                    {{ tramite.estudiante.user.nombres }} {{ tramite.estudiante.user.apellidos }}
                  </p>
                  <p class="text-xs text-slate-400">
                    Cod: {{ tramite.estudiante.codigo_universitario }} · Promedio: {{ tramite.estudiante.promedio_global }}
                  </p>
                </div>
              </div>
            </div>
            <EstadoBadge :estado="tramite.estado_actual" />
          </div>

          <div class="mt-4 space-y-1.5 text-sm">
            <div class="flex items-center gap-2 text-slate-600">
              <AppIcon name="user-check" :size="14" class="text-slate-400 shrink-0" />
              <span class="text-slate-400 font-medium">Tutor:</span>
              <span v-if="tramite.tutor">{{ tramite.tutor.nombres }} {{ tramite.tutor.apellidos }}</span>
              <span v-else class="text-amber-600 font-semibold">No asignado</span>
            </div>
            <div class="flex items-center gap-2 text-slate-600">
              <AppIcon name="trending-up" :size="14" class="text-slate-400 shrink-0" />
              <span class="text-slate-400 font-medium">Progreso:</span>
              <span class="font-bold text-indigo-600">{{ progresoEstado(tramite) }}%</span>
            </div>
          </div>

          <div class="mt-3 flex-1">
            <ProgressBar :value="progresoEstado(tramite)" />
          </div>

          <div class="mt-4 flex flex-wrap gap-1.5">
            <a v-for="doc in tramite.documentos" :key="doc.id_documento"
               :href="`${baseStorageUrl}/${doc.ruta_archivo}`" target="_blank"
               class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-indigo-700 bg-indigo-50 ring-1 ring-indigo-200 hover:bg-indigo-100 transition">
              <AppIcon name="link" :size="13" />
              {{ doc.tipo_documento }}
            </a>
          </div>

          <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap gap-2">
            <button v-if="esPersonal && tramite.estado_actual === 'solicitud_presentada'"
                    class="btn-emerald flex-1 py-2.5" @click="aprobar(tramite)">
              <AppIcon name="check" :size="15" />
              Aprobar
            </button>
            <button v-if="esPersonal && tramite.estado_actual === 'solicitud_presentada'"
                    class="btn-rose flex-1 py-2.5" @click="abrirRechazo(tramite)">
              <AppIcon name="x" :size="15" />
              Rechazar
            </button>
            <button class="btn-primary flex-1 py-2.5" @click="gestionar(tramite)">
              <AppIcon name="chevron-right" :size="15" />
              Ver / Gestionar
            </button>
          </div>
        </div>
      </div>
    </div>

    <UiModal v-model="showRechazo" :title="`Rechazar solicitud #${tramiteRechazo?.id_tramite || ''}`" max-width="460px">
      <div class="bg-amber-50 ring-1 ring-amber-200 rounded-xl p-3.5 text-sm text-amber-800 mb-4">
        <p class="font-bold mb-1">Estudiante:</p>
        <p>{{ tramiteRechazo?.estudiante?.user?.nombres }} {{ tramiteRechazo?.estudiante?.user?.apellidos }}</p>
        <p class="text-xs mt-1">{{ tramiteRechazo?.modalidad?.nombre }}</p>
      </div>
      <label class="label">Motivo del rechazo (obligatorio)</label>
      <textarea v-model="motivoRechazo" rows="4" class="input resize-none" placeholder="Explique el motivo para que el estudiante pueda corregirlo..." required></textarea>
      <div class="flex justify-end gap-2 mt-5">
        <button class="btn-ghost px-4 py-2.5" @click="showRechazo = false">Cancelar</button>
        <button class="btn-rose px-4 py-2.5" :disabled="!motivoRechazo" @click="rechazar(tramiteRechazo)">
          <AppIcon name="x" :size="15" />
          Confirmar Rechazo
        </button>
      </div>
    </UiModal>
  </AppShell>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useTramitesStore } from '../stores/tramites';
import { progresoEstado } from '../utils/estados';
import AppShell from '../components/ui/AppShell.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import Avatar from '../components/ui/Avatar.vue';
import EstadoBadge from '../components/ui/EstadoBadge.vue';
import StatCard from '../components/ui/StatCard.vue';
import ProgressBar from '../components/ui/ProgressBar.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import UiModal from '../components/ui/UiModal.vue';

const router = useRouter();
const authStore = useAuthStore();
const tramitesStore = useTramitesStore();
const cargando = ref(false);
const buscar = ref('');
const showRechazo = ref(false);
const tramiteRechazo = ref(null);
const motivoRechazo = ref('');

const baseStorageUrl = import.meta.env.VITE_STORAGE_URL || 'http://localhost:8000/storage';

const esPersonal = computed(() => !['docente', 'estudiante'].includes(authStore.user?.rol));

const porRevisar = computed(() => tramitesStore.tramitesPendientes.filter((t) => t.estado_actual === 'solicitud_presentada'));
const conTutor = computed(() => tramitesStore.tramitesPendientes.filter((t) => t.tutor));
const sinTutor = computed(() => tramitesStore.tramitesPendientes.filter((t) => !t.tutor));

const filtrados = computed(() => {
  const q = buscar.value.toLowerCase().trim();
  if (!q) return tramitesStore.tramitesPendientes;
  return tramitesStore.tramitesPendientes.filter((t) => {
    const estudiante = `${t.estudiante.user.nombres} ${t.estudiante.user.apellidos}`.toLowerCase();
    return estudiante.includes(q)
      || t.estudiante.codigo_universitario.toLowerCase().includes(q)
      || t.modalidad.nombre.toLowerCase().includes(q)
      || t.estado_actual.toLowerCase().includes(q);
  });
});

onMounted(async () => {
  cargando.value = true;
  await tramitesStore.cargarPendientes();
  cargando.value = false;
});

const gestionar = (tramite) => {
  router.push({ name: 'GestionTramite', params: { id: tramite.id_tramite } });
};

const aprobar = async (tramite) => {
  if (!confirm(`¿Aprobar la documentación inicial de ${tramite.estudiante.user.nombres} ${tramite.estudiante.user.apellidos}?`)) return;
  try {
    await tramitesStore.revisarTramite(tramite.id_tramite, 'aprobar', 'Documentación inicial aprobada.');
  } catch (error) {
    alert('Error: ' + (error.response?.data?.message || 'No se pudo aprobar.'));
  }
};

const abrirRechazo = (tramite) => {
  tramiteRechazo.value = tramite;
  motivoRechazo.value = '';
  showRechazo.value = true;
};

const rechazar = async (tramite) => {
  try {
    await tramitesStore.revisarTramite(tramite.id_tramite, 'rechazar', motivoRechazo.value);
    showRechazo.value = false;
    tramiteRechazo.value = null;
  } catch (error) {
    alert('Error: ' + (error.response?.data?.message || 'No se pudo rechazar.'));
  }
};
</script>