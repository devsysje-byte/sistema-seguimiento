<template>
  <AppShell title="Portal del Estudiante" subtitle="Seguimiento de tu modalidad de titulación">
    <template v-if="sinPerfil">
      <div class="max-w-2xl mx-auto">
        <div class="card overflow-hidden">
          <div class="h-2 bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500"></div>
          <div class="p-8 text-center">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-amber-50 ring-1 ring-amber-200 flex items-center justify-center text-amber-600">
              <AppIcon name="user" :size="26" />
            </div>
            <h1 class="mt-4 text-xl font-extrabold text-stone-900">Tu perfil aún no está registrado</h1>
            <p class="mt-2 text-sm text-stone-500 leading-relaxed max-w-md mx-auto">
              El administrador debe crear tu perfil de estudiante (con tu CI,
              registro universitario y fecha de nacimiento) antes de que puedas
              iniciar un trámite de titulación. Una vez dado de alta, recibirás
              tu usuario y contraseña de acceso.
            </p>
            <p class="mt-4 text-xs font-semibold text-stone-400">Contacta a la Dirección de Carrera.</p>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <StatCard label="Registro Universitario" :value="perfilEstudiante.registro_universitario" icon="file-text" tone="amber" />
        <StatCard label="Plan de Estudios" :value="perfilEstudiante.plan_estudios || '—'" icon="book" tone="rose" />
        <StatCard label="Promedio Global" :value="perfilEstudiante.promedio_global ?? '—'" icon="chart" tone="orange" />
        <StatCard
          label="Estado del Trámite"
          :value="tramitesStore.tramiteActivo ? formatoEstado(tramitesStore.tramiteActivo.estado_actual) : '—'"
          :icon="tramitesStore.tramiteActivo ? 'trending-up' : 'inbox'"
          :tone="tramitesStore.tramiteActivo ? 'amber' : 'rose'"
          :sublabel="tramitesStore.tramiteActivo ? tramitesStore.tramiteActivo.modalidad.nombre : 'Sin trámite activo'"
        />
      </div>

      <div class="grid lg:grid-cols-2 gap-4 mb-6">
        <div class="card overflow-hidden">
          <div class="h-2 bg-gradient-to-r from-stone-400 to-stone-600"></div>
          <div class="p-6">
            <h2 class="text-base font-extrabold text-stone-900 flex items-center gap-2">
              <AppIcon name="user" :size="18" class="text-stone-500" />
              Mis Datos
            </h2>
            <p class="mt-1 text-xs text-stone-400">Registrados por el administrador · solo lectura</p>
            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
              <div>
                <dt class="text-xs uppercase tracking-wide text-stone-400 font-semibold">CI</dt>
                <dd class="mt-0.5 font-semibold text-stone-800">{{ perfilEstudiante.ci }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-wide text-stone-400 font-semibold">Registro Universitario</dt>
                <dd class="mt-0.5 font-semibold text-stone-800">{{ perfilEstudiante.registro_universitario }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-wide text-stone-400 font-semibold">Nombres</dt>
                <dd class="mt-0.5 font-semibold text-stone-800">{{ displayNombres }}</dd>
              </div>
              <div>
                <dt class="text-xs uppercase tracking-wide text-stone-400 font-semibold">Fecha de Nacimiento</dt>
                <dd class="mt-0.5 font-semibold text-stone-800">{{ formatoFechaLarga(perfilEstudiante.fecha_nacimiento) }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <div class="card overflow-hidden">
          <div class="h-2 bg-gradient-to-r from-amber-500 to-orange-600"></div>
          <div class="p-6">
            <h2 class="text-base font-extrabold text-stone-900 flex items-center gap-2">
              <AppIcon name="book" :size="18" class="text-amber-600" />
              Actualiza tu Perfil Académico
            </h2>
            <p class="mt-1 text-xs text-stone-400">Estos datos los administras tú mismo.</p>

            <form @submit.prevent="guardarPerfil" class="mt-4 space-y-4">
              <div>
                <label class="label">Plan de Estudios</label>
                <input v-model="perfil.plan_estudios" class="input" placeholder="Ej: 2007" required>
              </div>
              <div>
                <label class="label">Fecha de Conclusión del Plan</label>
                <input v-model="perfil.fecha_conclusion_plan" type="date" class="input" required>
              </div>
              <div>
                <label class="label">Promedio Global (0-100)</label>
                <input v-model="perfil.promedio_global" type="number" step="0.01" min="0" max="100" class="input" required>
              </div>
              <button type="submit" :disabled="guardando" class="btn-primary w-full sm:w-auto px-8 py-3">
                <AppIcon v-if="guardando" name="loader" :size="16" class="animate-spin" />
                <AppIcon v-else name="check" :size="16" />
                Guardar Perfil
              </button>
            </form>
          </div>
        </div>
      </div>

      <div v-if="cargandoTramite" class="card flex items-center justify-center gap-2 py-16 text-stone-400">
        <AppIcon name="loader" :size="20" class="animate-spin" />
        Cargando tu trámite...
      </div>

      <div v-else class="space-y-6">
        <div v-if="tramitesStore.tramiteActivo && !esTesisActivo" class="card overflow-hidden">
          <div class="h-2 bg-gradient-to-r from-amber-500 to-orange-600"></div>
          <div class="p-6 sm:p-8">
            <TimelineTramite :tramite="tramitesStore.tramiteActivo" title="Seguimiento de mi Titulación" />
          </div>
        </div>

        <router-link v-else-if="tramitesStore.tramiteActivo && esTesisActivo" to="/estudiante/tesis" class="card overflow-hidden relative block group">
          <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-amber-500 to-rose-500"></div>
          <div class="p-6 sm:p-8 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 rounded-2xl bg-amber-50 ring-1 ring-amber-200 flex items-center justify-center text-amber-600 group-hover:scale-105 transition">
                <AppIcon name="graduation" :size="28" />
              </div>
              <div>
                <h2 class="text-lg font-extrabold text-stone-900">Tesis de Grado · En Curso</h2>
                <p class="text-sm text-stone-500">
                  Estado:
                  <span class="font-semibold text-amber-700">{{ formatoEstado(tramitesStore.tramiteActivo.estado_actual) }}</span>
                </p>
              </div>
            </div>
            <span class="btn-warm px-5 py-2.5">
              <AppIcon name="arrow-left" :size="15" class="rotate-180" />
              Continuar con el seguimiento
            </span>
          </div>
        </router-link>

        <template v-if="mostrarInicioSolicitud">
          <div v-if="terminalAprobada" class="rounded-2xl bg-emerald-50 ring-1 ring-emerald-200 p-5 flex items-start gap-3 text-emerald-800">
            <AppIcon name="award" :size="22" class="mt-0.5 shrink-0" />
            <p class="text-sm font-medium">
              <strong>¡FELICIDADES APROBADO!</strong> Tu tesis de grado fue aprobada
              y ya no puedes realizar más solicitudes de trámite.
            </p>
          </div>

          <div v-else-if="terminalReprobada" class="rounded-xl bg-rose-50 ring-1 ring-rose-200 p-4 flex items-start gap-3 text-rose-800">
            <AppIcon name="x-circle" :size="20" class="mt-0.5 shrink-0" />
            <p class="text-sm font-medium">
              Tu tesis de grado no fue aprobada.
              <template v-if="reoptar.puede">
                Ya puedes volver a presentar tu solicitud para optar por una modalidad.
              </template>
              <template v-else>
                Podrás volver a presentar tu solicitud a partir del
                <strong>{{ formatoFechaLarga(reoptar.fechaHabilitacion) }}</strong>.
              </template>
            </p>
          </div>

          <div v-else-if="tramiteTerminado" class="rounded-xl bg-orange-50 ring-1 ring-orange-200 p-4 flex items-start gap-3 text-orange-800">
            <AppIcon name="check-circle" :size="20" class="mt-0.5 shrink-0" />
            <p class="text-sm font-medium">
              Tu último trámite finalizó con estado
              <strong>{{ formatoEstado(tramitesStore.tramiteActivo.estado_actual) }}</strong>.
              Puedes iniciar una nueva tesis de grado cuando lo necesites.
            </p>
          </div>

          <router-link to="/estudiante/tesis" class="card overflow-hidden relative block group">
            <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-amber-500 to-rose-500"></div>
            <div class="p-6 sm:p-8 flex flex-wrap items-center justify-between gap-4">
              <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 ring-1 ring-amber-200 flex items-center justify-center text-amber-600 group-hover:scale-105 transition">
                  <AppIcon name="graduation" :size="28" />
                </div>
                <div>
                  <h2 class="text-lg font-extrabold text-stone-900">Tesis de Grado</h2>
                  <p class="text-sm text-stone-500">Aqui puedes solcitar la modalidad de graduación de Tesis de Grado y realizar el seguimiento correspondiente.</p>
                </div>
              </div>
              <span class="btn-warm px-5 py-2.5">
                <AppIcon name="arrow-left" :size="15" class="rotate-180" />
                Solicitar Modalidad
              </span>
            </div>
          </router-link>
        </template>
      </div>
    </template>
  </AppShell>
</template>

<script setup>
// Vista del portal del estudiante.
// El perfil aislado lo registra el administrador; aquí el estudiante consulta
// sus datos (solo lectura) y AUTO-GESTIONA sus campos académicos (plan de
// estudios, fecha de conclusión y promedio global). Además muestra la línea de
// tiempo de su trámite activo (o el acceso al módulo dedicado de Tesis).
import { TimelineTramite, useTramitesStore, formatoEstado, ESTADOS_TERMINALES } from '@/modules/tramites';
import { useTesisStore, reoptarInfo } from '@/modules/tesis';
import { ref, computed, watch, onMounted } from 'vue';
import { useEstudianteStore } from '../stores/estudiante';
import { useToastStore } from '@/core/stores/toast';
import { AppShell } from '@/modules/layout';
import AppIcon from '@/ui/AppIcon.vue';
import StatCard from '@/ui/StatCard.vue';

const tramitesStore = useTramitesStore();
const estudianteStore = useEstudianteStore();
const tesisStore = useTesisStore();
const toastStore = useToastStore();

// Es null cuando el administrador aún no registró el perfil del estudiante.
const perfilEstudiante = computed(() => estudianteStore.perfilEstudiante);

// En el login se guardó el perfil (relación estudiante) si el usuario es
// estudiante; si no, la carga del perfil se hace al montar el portal.
const sinPerfil = ref(false);
const cargandoTramite = computed(() => tramitesStore.cargandoTramite);
const guardando = ref(false);

// Formulario de auto-gestión: solo los campos académicos.
const perfil = ref({ plan_estudios: '', fecha_conclusion_plan: '', promedio_global: '' });

// Sincroniza el formulario cada vez que cambia el perfil cargado.
watch(
  perfilEstudiante,
  (p) => {
    if (p) {
      perfil.value = {
        plan_estudios: p.plan_estudios || '',
        fecha_conclusion_plan: p.fecha_conclusion_plan || '',
        promedio_global: p.promedio_global ?? '',
      };
    }
  },
  { immediate: true },
);

// Nombre completo mostrado en la ficha de datos personales.
const displayNombres = computed(() =>
  [perfilEstudiante.value?.nombres, perfilEstudiante.value?.apellidos].filter(Boolean).join(' ') || '—',
);

// true si el trámite activo ya alcanzó un estado terminal.
const tramiteTerminado = computed(() => ESTADOS_TERMINALES.includes(tramitesStore.tramiteActivo?.estado_actual));

// Resultados terminales de la tesis: aprobada bloquea nuevos trámites; reprobada
// habilita la re-opción de modalidad según los plazos reglamentarios.
const terminalAprobada = computed(() =>
  tramitesStore.tramiteActivo?.modalidad?.nombre === 'Tesis de Grado'
  && tramitesStore.tramiteActivo?.estado_actual === 'aprobado'
);
const terminalReprobada = computed(() =>
  tramitesStore.tramiteActivo?.modalidad?.nombre === 'Tesis de Grado'
  && ['reprobado', 'reprobado_ausencia'].includes(tramitesStore.tramiteActivo?.estado_actual)
);
const reoptar = computed(() => reoptarInfo(tramitesStore.tramiteActivo, {
  diasCorreccion: tesisStore.config.dias_correccion,
  diasRemodalidad: tesisStore.config.dias_remodalidad,
}));

// true si el trámite activo es de la modalidad Tesis de Grado: en ese caso el
// portal no duplica la línea de tiempo (la muestra el módulo dedicado), solo
// el resumen/estado con acceso al módulo.
const esTesisActivo = computed(() => tramitesStore.tramiteActivo?.modalidad?.nombre === 'Tesis de Grado');

// Permite iniciar una nueva solicitud solo si no hay trámite activo o ya terminó.
const mostrarInicioSolicitud = computed(() => !tramitesStore.tramiteActivo || tramiteTerminado.value);

// Carga inicial: perfil y trámite activo.
onMounted(async () => {
    await estudianteStore.cargarPerfil(true);
    sinPerfil.value = !estudianteStore.perfilEstudiante;
    await tramitesStore.cargarTramiteActivo();
});

// El perfil pudo llegar ya cargado desde el login; en ese caso no es "sin perfil".
watch(perfilEstudiante, (p) => { sinPerfil.value = !p; }, { immediate: true });

/** Guarda los campos académicos vía PUT /api/estudiante/perfil y avisa el resultado. */
const guardarPerfil = async () => {
    guardando.value = true;
    try {
        await estudianteStore.guardarPerfil(perfil.value);
        toastStore.success('Perfil académico actualizado correctamente.');
    } catch (error) {
        toastStore.error('Error al guardar: ' + (error.response?.data?.message || 'Verifique los datos'));
    } finally {
        guardando.value = false;
    }
};

/** Formatea una fecha (YYYY-MM-DD) en formato largo en español. */
function formatoFechaLarga(iso) {
    if (!iso) return '—';
    return new Date(iso + 'T00:00:00').toLocaleDateString('es-BO', {
        day: 'numeric', month: 'long', year: 'numeric',
    });
}
</script>