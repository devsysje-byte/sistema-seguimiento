<template>
  <AppShell :title="`Trámite #${tramite?.id_tramite || ''}`" :subtitle="tramite ? `${tramite.estudiante.user.nombres} ${tramite.estudiante.user.apellidos} · ${tramite.modalidad.nombre}` : ''">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <p class="text-sm text-stone-500">
        Estado actual:
        <EstadoBadge v-if="tramite" :estado="tramite.estado_actual" class="ml-1" />
      </p>
      <button class="btn-ghost" @click="volver">
        <AppIcon name="arrow-left" :size="16" />
        {{ esDocente ? 'Ir a Mis Tutorías' : 'Volver a Trámites' }}
      </button>
    </div>

    <div v-if="cargando" class="card flex items-center justify-center gap-2 py-16 text-stone-400">
      <AppIcon name="loader" :size="20" class="animate-spin" />
      Cargando trámite...
    </div>

    <div v-else-if="tramite" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="card p-6">
        <h3 class="font-bold text-stone-800 mb-4 inline-flex items-center gap-2">
          <span class="w-8 h-8 rounded-lg bg-amber-50 ring-1 ring-amber-200 flex items-center justify-center text-amber-600">
            <AppIcon name="user" :size="17" />
          </span>
          Datos del Postulante
        </h3>
        <div class="flex items-center gap-4">
          <Avatar :nombres="tramite.estudiante.user.nombres" :apellidos="tramite.estudiante.user.apellidos" size="14" />
          <div>
            <p class="text-lg font-bold text-stone-900">
              {{ tramite.estudiante.user.nombres }} {{ tramite.estudiante.user.apellidos }}
            </p>
            <p class="text-sm text-stone-500 capitalize">{{ rolLabel(tramite.estudiante.user.rol) }}</p>
          </div>
        </div>
        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
          <div class="rounded-xl bg-stone-50 p-3">
            <dt class="text-xs text-stone-400 font-semibold uppercase">Código</dt>
            <dd class="font-semibold text-stone-800">{{ tramite.estudiante.codigo_universitario }}</dd>
          </div>
          <div class="rounded-xl bg-stone-50 p-3">
            <dt class="text-xs text-stone-400 font-semibold uppercase">Promedio</dt>
            <dd class="font-semibold text-stone-800">{{ tramite.estudiante.promedio_global }}</dd>
          </div>
          <div class="rounded-xl bg-stone-50 p-3">
            <dt class="text-xs text-stone-400 font-semibold uppercase">Plan</dt>
            <dd class="font-semibold text-stone-800">{{ tramite.estudiante.plan_estudios }}</dd>
          </div>
          <div class="rounded-xl bg-stone-50 p-3">
            <dt class="text-xs text-stone-400 font-semibold uppercase">Conclusión del plan</dt>
            <dd class="font-semibold text-stone-800">{{ tramite.estudiante.fecha_conclusion_plan }}</dd>
          </div>
        </dl>
      </div>

      <div class="card p-6">
        <h3 class="font-bold text-stone-800 mb-4 inline-flex items-center gap-2">
          <span class="w-8 h-8 rounded-lg bg-orange-50 ring-1 ring-orange-200 flex items-center justify-center text-orange-600">
            <AppIcon name="graduation" :size="17" />
          </span>
          Modalidad
        </h3>
        <p class="text-lg font-bold text-amber-700">{{ tramite.modalidad.nombre }}</p>
        <p class="mt-2 text-sm text-stone-600">{{ tramite.modalidad.descripcion || '' }}</p>
        <div class="mt-4 rounded-xl bg-orange-50 ring-1 ring-orange-200 p-3.5 text-sm text-orange-800">
          <p class="font-bold mb-1 inline-flex items-center gap-1.5">
            <AppIcon name="info" :size="15" />
            Requisitos mínimos
          </p>
          <p class="mt-0.5">{{ tramite.modalidad.requisitos_minimos || 'Sin requisitos registrados.' }}</p>
        </div>
      </div>

      <div class="lg:col-span-2 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="card p-6">
          <h3 class="font-bold text-stone-800 mb-4 inline-flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-orange-50 ring-1 ring-orange-200 flex items-center justify-center text-orange-600">
              <AppIcon name="user-check" :size="17" />
            </span>
            Tutor Asignado
          </h3>
          <div v-if="tramite.tutor" class="flex items-center gap-3">
            <Avatar :nombres="tramite.tutor.nombres" :apellidos="tramite.tutor.apellidos" size="12" />
            <div>
              <p class="font-semibold text-stone-800">{{ tramite.tutor.nombres }} {{ tramite.tutor.apellidos }}</p>
              <p class="text-xs text-stone-500">{{ tramite.tutor.email }}</p>
            </div>
          </div>
          <div v-else class="text-sm text-orange-500 font-medium">No hay tutor asignado todavía.</div>

          <div v-if="esPersonal" class="mt-4 pt-4 border-t border-stone-100">
            <label class="label">Cambiar / Asignar tutor</label>
            <div class="flex flex-col sm:flex-row gap-2">
              <select v-model="tutorSeleccionado" class="input flex-1 min-w-0">
                <option value="" disabled>Seleccione un docente tutor...</option>
                <option v-for="doc in tramitesStore.docentes" :key="doc.id_usuario" :value="doc.id_usuario">
                  {{ doc.nombres }} {{ doc.apellidos }} ({{ doc.email }})
                </option>
              </select>
              <button class="btn-primary w-full sm:w-auto shrink-0" :disabled="asignando || !tutorSeleccionado" @click="asignarTutor">
                <AppIcon v-if="asignando" name="loader" :size="15" class="animate-spin" />
                <AppIcon v-else name="check" :size="15" />
                Asignar
              </button>
            </div>
          </div>
        </div>

        <div class="card p-6">
          <h3 class="font-bold text-stone-800 mb-4 inline-flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-amber-50 ring-1 ring-amber-200 flex items-center justify-center text-amber-600">
              <AppIcon name="file-text" :size="17" />
            </span>
            Documentos Presentados
          </h3>
          <div v-if="tramite.documentos.length" class="space-y-2">
            <a v-for="doc in tramite.documentos" :key="doc.id_documento"
               :href="`${baseStorageUrl}/${doc.ruta_archivo}`" target="_blank"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-amber-700 bg-amber-50 ring-1 ring-amber-200 hover:bg-amber-100 transition">
              <AppIcon name="link" :size="16" />
              {{ doc.tipo_documento }}
              <span class="ml-auto text-xs text-stone-400">{{ new Date(doc.created_at).toLocaleDateString('es-BO') }}</span>
            </a>
          </div>
          <p v-else class="text-sm text-stone-400">Sin documentos registrados.</p>
        </div>
      </div>

      <div class="lg:col-span-2 card overflow-hidden">
        <div class="h-2 bg-gradient-to-r from-amber-500 to-orange-600"></div>
        <div class="p-6 sm:p-8">
          <TimelineTramite :tramite="tramite" :title="esDocente ? 'Seguimiento de la Tutoría' : 'Historial de Seguimiento'" />
        </div>
      </div>

      <div v-if="esPersonal" class="lg:col-span-2 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div v-if="tramite.estado_actual === 'solicitud_presentada'" class="card p-6">
          <h3 class="font-bold text-stone-800 mb-2 inline-flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-orange-50 ring-1 ring-orange-200 flex items-center justify-center text-orange-600">
              <AppIcon name="inbox" :size="17" />
            </span>
            Revisión de Documentación Inicial
          </h3>
          <p class="text-sm text-stone-500 mb-4">Aprobar inicia el flujo de estados según la modalidad. Indica observaciones para registrar en el historial.</p>
          <textarea v-model="observacionesRev" rows="3" class="input resize-none mb-3" placeholder="Observaciones (obligatorio para rechazar)"></textarea>
          <div class="flex gap-2">
            <button class="btn-primary flex-1" @click="revisar('aprobar')">
              <AppIcon name="check" :size="15" />
              Aprobar
            </button>
            <button class="btn-rose flex-1" @click="revisar('rechazar')">
              <AppIcon name="x" :size="15" />
              Rechazar
            </button>
          </div>
        </div>

        <div class="card p-6">
          <h3 class="font-bold text-stone-800 mb-2 inline-flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-amber-50 ring-1 ring-amber-200 flex items-center justify-center text-amber-600">
              <AppIcon name="trending-up" :size="17" />
            </span>
            Avanzar Trámite
          </h3>
          <p class="text-sm text-stone-500 mb-4">Mueve el trámite al siguiente estado del flujo correspondiente.</p>
          <div v-if="siguientesEstados.length" class="space-y-3">
            <select v-model="nuevoEstado" class="input">
              <option value="" disabled>Seleccione el siguiente estado...</option>
              <option v-for="estado in siguientesEstados" :key="estado" :value="estado">
                {{ formatoEstado(estado).toUpperCase() }}
              </option>
            </select>
            <input v-model="observaciones" class="input" placeholder="Observaciones (opcional)">
            <button class="btn-primary w-full" :disabled="ejecutando || !nuevoEstado" @click="ejecutarTransicion">
              <AppIcon v-if="ejecutando" name="loader" :size="15" class="animate-spin" />
              <AppIcon v-else name="send" :size="15" />
              {{ ejecutando ? 'Ejecutando...' : 'Ejecutar Transición' }}
            </button>
          </div>
          <p v-else class="text-sm text-stone-500">No hay más transiciones permitidas desde este estado.</p>
        </div>
      </div>

      <div v-else class="lg:col-span-2 rounded-xl bg-amber-50 ring-1 ring-amber-200 p-4 flex items-start gap-3 text-amber-800 text-sm">
        <AppIcon name="info" :size="18" class="mt-0.5 shrink-0" />
        Estás viendo el seguimiento de la tutoría en modo lectura. Las acciones de revisión las realizan Kardex y Dirección.
      </div>
    </div>
  </AppShell>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../services/api';
import { useAuthStore } from '../stores/auth';
import { useTramitesStore } from '../stores/tramites';
import { formatoEstado, rolLabel } from '../utils/estados';
import AppShell from '../components/ui/AppShell.vue';
import AppIcon from '../components/ui/AppIcon.vue';
import Avatar from '../components/ui/Avatar.vue';
import EstadoBadge from '../components/ui/EstadoBadge.vue';
import TimelineTramite from '../components/TimelineTramite.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const tramitesStore = useTramitesStore();
const tramite = ref(null);
const cargando = ref(false);
const ejecutando = ref(false);
const siguientesEstados = ref([]);
const nuevoEstado = ref('');
const observaciones = ref('');
const observacionesRev = ref('');
const tutorSeleccionado = ref('');
const asignando = ref(false);

const esDocente = computed(() => authStore.user?.rol === 'docente');
const esPersonal = computed(() => !esDocente.value && authStore.user?.rol !== 'estudiante');

const baseStorageUrl = import.meta.env.VITE_STORAGE_URL || 'http://localhost:8000/storage';

const cargarTramite = async () => {
  cargando.value = true;
  try {
    const { data } = await api.get(`/tramites/${route.params.id}`);
    tramite.value = data;
    siguientesEstados.value = data.siguientes_estados || [];
  } catch (error) {
    alert('Error al cargar el trámite: ' + (error.response?.data?.message || 'Verifique el acceso'));
    volver();
  } finally {
    cargando.value = false;
  }
};

onMounted(async () => {
  if (esPersonal.value) {
    await tramitesStore.cargarDocentes();
  }
  await cargarTramite();
});

const volver = () => {
  if (esDocente.value) return router.push('/docente');
  if (authStore.user?.rol === 'admin') return router.push('/admin');
  router.push('/kardex');
};

const asignarTutor = async () => {
  if (!tutorSeleccionado.value) return alert('Seleccione un docente');
  asignando.value = true;
  try {
    const { data } = await tramitesStore.asignarTutor(tramite.value.id_tramite, tutorSeleccionado.value);
    tramite.value = data;
    tutorSeleccionado.value = '';
    alert('Tutor asignado correctamente.');
  } catch (error) {
    alert('Error: ' + (error.response?.data?.message || 'No se pudo asignar el tutor'));
  } finally {
    asignando.value = false;
  }
};

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
</script>
