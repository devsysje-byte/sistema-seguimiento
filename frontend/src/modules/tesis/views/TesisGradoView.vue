<template>
  <AppShell title="Módulo de Tesis de Grado" subtitle="Flujo oficial del estudiante: solicitud, seguimiento y defensa">
    <div v-if="cargando" class="card flex items-center justify-center gap-2 py-16 text-stone-400">
      <AppIcon name="loader" :size="20" class="animate-spin" />
      Cargando tu tesis de grado...
    </div>

    <div v-else-if="!estudianteStore.perfilEstudiante?.id_estudiante"
         class="card overflow-hidden">
      <div class="h-2 bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500"></div>
      <div class="p-8 flex flex-col items-center text-center gap-3">
        <div class="w-12 h-12 rounded-2xl bg-amber-50 ring-1 ring-amber-200 flex items-center justify-center text-amber-600">
          <AppIcon name="user" :size="24" />
        </div>
        <h2 class="text-lg font-extrabold text-stone-900">Necesitas completar tu perfil académico</h2>
        <p class="text-sm text-stone-500 max-w-md">
          El módulo de Tesis de Grado requiere tu información académica registrada
          para iniciar la solicitud.
        </p>
        <router-link to="/estudiante" class="btn-primary px-6 py-2.5 mt-1">
          <AppIcon name="arrow-left" :size="15" />
          Ir a mi Portal de Estudiante
        </router-link>
      </div>
    </div>

    <template v-else>
      <!-- ============================================================== -->
      <!-- SIN TESIS ACTIVA: fase de solicitud (3 documentos obligatorios) -->
      <!-- ============================================================== -->
      <div v-if="!tesisActiva" class="space-y-6">
        <div v-if="ultimaTesisTerminada" class="rounded-xl bg-orange-50 ring-1 ring-orange-200 p-4 flex items-start gap-3 text-orange-800">
          <AppIcon name="check-circle" :size="20" class="mt-0.5 shrink-0" />
          <p class="text-sm font-medium">
            Tu último trámite de tesis finalizó con estado
            <strong>{{ formatoEstado(ultimaTesisTerminada.estado_actual) }}</strong>.
            Puedes iniciar una nueva solicitud cuando lo necesites.
          </p>
        </div>

        <div class="card overflow-hidden">
          <div class="h-2 bg-gradient-to-r from-amber-500 to-orange-600"></div>
          <div class="p-6 sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
              <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 ring-1 ring-amber-200 flex items-center justify-center text-amber-600">
                  <AppIcon name="graduation" :size="28" />
                </div>
                <div>
                  <h2 class="text-lg font-extrabold text-stone-900">Solicitud de Tesis de Grado</h2>
                  <p class="text-sm text-stone-500">Presenta tu solicitud con los 3 documentos obligatorios en PDF.</p>
                </div>
              </div>
            </div>

            <!-- Resumen de fases del flujo -->
            <div class="mt-6 grid sm:grid-cols-5 gap-2">
              <div v-for="fase in FASES_TESIS" :key="fase.id"
                   class="rounded-xl px-3 py-3 ring-1 ring-stone-200 bg-stone-50 text-center">
                <AppIcon :name="fase.icon" :size="18" class="mx-auto text-amber-600 mb-1" />
                <p class="text-[11px] font-bold text-stone-700 uppercase tracking-wide">{{ fase.label }}</p>
              </div>
            </div>

            <form @submit.prevent="enviarSolicitud" class="mt-7 grid sm:grid-cols-2 gap-5">
              <div v-for="(ruta, tipo) in tesisStore.config.tipos_documento" :key="tipo">
                <label class="label">{{ ruta }} (PDF) · obligatorio</label>
                <label :class="['flex items-center justify-between gap-3 px-4 py-3 rounded-xl border-2 border-dashed cursor-pointer transition', archivos[tipo] ? 'border-amber-300 bg-amber-50' : 'border-stone-300 bg-stone-50 hover:border-amber-400']">
                  <span class="flex items-center gap-2 text-sm min-w-0" :class="archivos[tipo] ? 'text-amber-700' : 'text-stone-500'">
                    <AppIcon v-if="archivos[tipo]" name="check-circle" :size="18" class="text-emerald-500 shrink-0" />
                    <AppIcon v-else name="file-text" :size="18" class="shrink-0" />
                    <span class="truncate">{{ archivos[tipo]?.name || 'Selecciona el archivo...' }}</span>
                  </span>
                  <input type="file" accept=".pdf" class="hidden" @change="handleArchivo($event, tipo)">
                  <span class="btn-ghost !py-2 pointer-events-none">
                    <AppIcon name="plus" :size="15" />
                    Subir
                  </span>
                </label>
              </div>

              <div class="sm:col-span-2 flex items-center justify-between gap-3 pt-2">
                <p v-if="faltantes.length" class="text-xs text-stone-500 inline-flex items-center gap-1.5">
                  <AppIcon name="info" :size="14" />
                  Faltan subir: {{ faltantes.join(', ') }}
                </p>
                <p v-else class="text-xs font-semibold text-emerald-600 inline-flex items-center gap-1.5">
                  <AppIcon name="check" :size="14" />
                  Documentación completa para enviar.
                </p>
                <button type="submit" class="btn-primary px-6 py-2.5 shrink-0" :disabled="tesisStore.enviando || faltantes.length > 0">
                  <AppIcon v-if="tesisStore.enviando" name="loader" :size="15" class="animate-spin" />
                  <AppIcon v-else name="send" :size="15" />
                  {{ tesisStore.enviando ? 'Enviando...' : 'Enviar Solicitud' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- CON TESIS ACTIVA: seguimiento del flujo -->
      <!-- ============================================================== -->
      <div v-else class="space-y-6">
        <!-- Cabecera: estado y avance por fase -->
        <div class="card p-6">
          <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
              <h2 class="text-lg font-extrabold text-stone-900">Seguimiento de tu Tesis de Grado</h2>
              <p class="text-sm text-stone-500 mt-1">
                Trámite N.º {{ tramite.id_tramite }} · Tutor:
                <template v-if="tramite.tutor">{{ tramite.tutor.nombres }} {{ tramite.tutor.apellidos }}</template>
                <template v-else class="text-orange-500">por asignar</template>
              </p>
            </div>
            <EstadoBadge :estado="tramite.estado_actual" upper />
          </div>

          <p v-if="descripcionEstado" class="mt-3 text-sm text-stone-600 rounded-xl bg-stone-50 ring-1 ring-stone-200 px-4 py-3">
            {{ descripcionEstado }}
          </p>

          <!-- Indicador de fase actual -->
          <div class="mt-5 grid sm:grid-cols-5 gap-2">
            <div v-for="fase in FASES_TESIS" :key="fase.id"
                 class="rounded-xl px-3 py-3 ring-1 transition text-center"
                 :class="estadoFase(fase).contenedor">
              <AppIcon :name="fase.icon" :size="18" class="mx-auto mb-1" :class="estadoFase(fase).icono" />
              <p class="text-[11px] font-bold uppercase tracking-wide" :class="estadoFase(fase).texto">{{ fase.label }}</p>
            </div>
          </div>
        </div>

        <!-- Alerta de perfil rechazado + reenvío -->
        <div v-if="tramite.estado_actual === 'perfil_rechazado'"
             class="rounded-xl bg-rose-50 ring-1 ring-rose-200 p-5 flex items-start gap-3 text-rose-800">
          <AppIcon name="x-circle" :size="20" class="mt-0.5 shrink-0" />
          <div class="flex-1">
            <p class="font-bold text-sm">El Consejo Universitario rechazó tu perfil de tesis.</p>
            <p class="text-sm mt-1">Corrige las observaciones indicadas y reenvía tu perfil para una nueva evaluación.</p>
            <button class="mt-3 btn-warm px-5 py-2.5" @click="mostrarReenvio = true">
              <AppIcon name="pencil" :size="15" />
              Reenviar Perfil Corregido
            </button>
          </div>
        </div>

        <!-- Alerta de documento insuficiente + reenvío a la Comisión Revisora -->
        <div v-if="tramite.estado_actual === 'insuficiente'"
             class="rounded-xl bg-rose-50 ring-1 ring-rose-200 p-5 flex items-start gap-3 text-rose-800">
          <AppIcon name="alert-triangle" :size="20" class="mt-0.5 shrink-0" />
          <div class="flex-1">
            <p class="font-bold text-sm">La Comisión Revisora calificó tu documento como insuficiente.</p>
            <p class="text-sm mt-1">Debes revisar tu trabajo, corregir las observaciones señaladas y volver a presentarlo a la Comisión Revisora.</p>
            <button class="mt-3 btn-warm px-5 py-2.5" @click="mostrarReenvioDocumento = true">
              <AppIcon name="pencil" :size="15" />
              Enviar Documento Corregido
            </button>
          </div>
        </div>

        <!-- Countdown del periodo de presentación / correcciones -->
        <CountdownTesis v-if="countdownCfg" v-bind="countdownCfg" />

        <!-- Solicitud de fecha de defensa (quien la solicita es el estudiante) -->
        <div v-if="tramite.estado_actual === 'solicitud_fecha_defensa'"
             class="rounded-2xl ring-1 p-5"
             :class="fechaDefensaSolicitada ? 'bg-emerald-50/70 ring-emerald-200' : 'bg-amber-50/70 ring-amber-200'">
          <div class="flex items-start gap-3">
            <span class="shrink-0 w-11 h-11 rounded-xl flex items-center justify-center ring-1"
                  :class="fechaDefensaSolicitada ? 'bg-emerald-100 text-emerald-600 ring-emerald-200' : 'bg-amber-100 text-amber-600 ring-amber-200'">
              <AppIcon name="calendar" :size="22" />
            </span>
            <div class="flex-1 min-w-0">
              <h4 class="font-bold text-stone-900">Solicitud de fecha de defensa</h4>
              <template v-if="!fechaDefensaSolicitada">
                <p class="text-sm text-stone-600 mt-1">
                  Tu tesis fue calificada como suficiente por la Comisión Revisora.
                  Solicita una fecha para tu defensa; Kardex la programará.
                </p>
                <form @submit.prevent="enviarSolicitudFecha" class="mt-3 flex flex-wrap items-end gap-2">
                  <div class="w-full sm:w-auto">
                    <label class="label">Fecha sugerida (opcional)</label>
                    <input v-model="fechaSugerida" type="date" :min="hoyISO" class="input w-full sm:w-auto" />
                  </div>
                  <button type="submit" class="btn-primary px-5 py-2.5" :disabled="tesisStore.enviando">
                    <AppIcon v-if="tesisStore.enviando" name="loader" :size="15" class="animate-spin" />
                    <AppIcon v-else name="send" :size="15" />
                    {{ tesisStore.enviando ? 'Solicitando...' : 'Solicitar Fecha de Defensa' }}
                  </button>
                </form>
              </template>
              <template v-else>
                <p class="text-sm text-stone-600 mt-1">
                  Tu solicitud fue enviada a Kardex,
                  <span class="font-semibold text-emerald-700">
                    {{ tramite.hitos?.fecha_defensa_sugerida ? 'con fecha sugerida: ' + formatoFechaLarga(tramite.hitos.fecha_defensa_sugerida) + '.' : 'quien te programará una fecha de defensa.' }}
                  </span>
                </p>
                <p class="text-xs text-stone-500 mt-1.5">Solicitada el {{ formatoFechaLarga(tramite.hitos.fecha_defensa_solicitada) }}. Aguarda la programación de tu defensa.</p>
              </template>
            </div>
          </div>
        </div>

        <!-- Fecha de defensa programada -->
        <div v-if="tramite.estado_actual === 'defensa_programada' && tramite.hitos?.fecha_defensa"
             class="rounded-2xl bg-indigo-50/70 ring-1 ring-indigo-200 p-5">
          <div class="flex items-center gap-3">
            <span class="w-11 h-11 rounded-xl bg-indigo-100 text-indigo-600 ring-1 ring-indigo-200 flex items-center justify-center">
              <AppIcon name="calendar" :size="22" />
            </span>
            <div>
              <h4 class="font-bold text-indigo-900">Fecha de defensa programada</h4>
              <p class="text-sm text-indigo-700 font-semibold">{{ formatoFechaLarga(tramite.hitos.fecha_defensa) }}</p>
            </div>
          </div>
        </div>

        <!-- Documentos presentados -->
        <div class="card overflow-hidden">
          <div class="p-6">
            <h3 class="font-bold text-stone-800 mb-4 inline-flex items-center gap-2">
              <span class="w-8 h-8 rounded-lg bg-amber-50 ring-1 ring-amber-200 flex items-center justify-center text-amber-600">
                <AppIcon name="file-text" :size="17" />
              </span>
              Documentos Presentados
            </h3>
            <div v-if="tramite.documentos.length" class="grid sm:grid-cols-2 gap-2.5">
              <a v-for="doc in tramite.documentos" :key="doc.id_documento"
                 :href="assetUrl(doc.ruta_archivo)" target="_blank"
                 class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold text-amber-700 bg-amber-50 ring-1 ring-amber-200 hover:bg-amber-100 transition">
                <AppIcon name="link" :size="16" />
                {{ etiquetaDocumento(doc.tipo_documento) }}
                <span class="ml-auto text-xs text-stone-400">{{ new Date(doc.created_at).toLocaleDateString('es-BO') }}</span>
              </a>
            </div>
            <p v-else class="text-sm text-stone-400">Sin documentos registrados.</p>
          </div>
        </div>

        </div>
    </template>

    <!-- Modal de reenvío del perfil -->
    <UiModal v-model="mostrarReenvio" title="Reenviar Perfil Corregido" max-width="520px">
      <form @submit.prevent="enviarReenvio" class="space-y-5">
        <p class="text-sm text-stone-500">
          Adjunta la versión corregida del perfil de tesis (PDF opcional). Si solo
          vas a reenviarlo sin cambios, puedes dejarlo vacío.
        </p>
        <div>
          <label class="label">Perfil de Tesis corregido (PDF)</label>
          <label :class="['flex items-center justify-between gap-3 px-4 py-3 rounded-xl border-2 border-dashed cursor-pointer transition', archivoReenvio ? 'border-amber-300 bg-amber-50' : 'border-stone-300 bg-stone-50 hover:border-amber-400']">
            <span class="flex items-center gap-2 text-sm min-w-0" :class="archivoReenvio ? 'text-amber-700' : 'text-stone-500'">
              <AppIcon v-if="archivoReenvio" name="check-circle" :size="18" class="text-emerald-500 shrink-0" />
              <AppIcon v-else name="file-text" :size="18" class="shrink-0" />
              <span class="truncate">{{ archivoReenvio?.name || 'Selecciona el archivo...' }}</span>
            </span>
            <input type="file" accept=".pdf" class="hidden" @change="archivoReenvio = $event.target.files[0] || null">
            <span class="btn-ghost !py-2 pointer-events-none">
              <AppIcon name="plus" :size="15" />
              Subir
            </span>
          </label>
        </div>
        <div>
          <label class="label">Observaciones (opcional)</label>
          <textarea v-model="observacionesReenvio" rows="3" class="input resize-none" placeholder="Indica las correcciones aplicadas..."></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-1">
          <button type="button" class="btn-ghost px-4 py-2.5" @click="mostrarReenvio = false">Cancelar</button>
          <button type="submit" class="btn-primary px-5 py-2.5" :disabled="tesisStore.enviando">
            <AppIcon v-if="tesisStore.enviando" name="loader" :size="15" class="animate-spin" />
            <AppIcon v-else name="send" :size="15" />
            {{ tesisStore.enviando ? 'Enviando...' : 'Reenviar Perfil' }}
          </button>
        </div>
      </form>
    </UiModal>

    <!-- Modal de reenvío del documento final tras calificación insuficiente -->
    <UiModal v-model="mostrarReenvioDocumento" title="Enviar Documento Corregido" max-width="520px">
      <form @submit.prevent="enviarReenvioDocumento" class="space-y-5">
        <p class="text-sm text-stone-500">
          Adjunta la versión corregida de tu documento final (PDF obligatorio). La
          Comisión Revisora lo evaluará nuevamente.
        </p>
        <div>
          <label class="label">Documento final corregido (PDF)</label>
          <label :class="['flex items-center justify-between gap-3 px-4 py-3 rounded-xl border-2 border-dashed cursor-pointer transition', archivoReenvioDocumento ? 'border-amber-300 bg-amber-50' : 'border-stone-300 bg-stone-50 hover:border-amber-400']">
            <span class="flex items-center gap-2 text-sm min-w-0" :class="archivoReenvioDocumento ? 'text-amber-700' : 'text-stone-500'">
              <AppIcon v-if="archivoReenvioDocumento" name="check-circle" :size="18" class="text-emerald-500 shrink-0" />
              <AppIcon v-else name="file-text" :size="18" class="shrink-0" />
              <span class="truncate">{{ archivoReenvioDocumento?.name || 'Selecciona el archivo...' }}</span>
            </span>
            <input type="file" accept=".pdf" class="hidden" @change="archivoReenvioDocumento = $event.target.files[0] || null">
            <span class="btn-ghost !py-2 pointer-events-none">
              <AppIcon name="plus" :size="15" />
              Subir
            </span>
          </label>
        </div>
        <div>
          <label class="label">Observaciones (opcional)</label>
          <textarea v-model="observacionesReenvioDocumento" rows="3" class="input resize-none" placeholder="Indica las correcciones aplicadas..."></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-1">
          <button type="button" class="btn-ghost px-4 py-2.5" @click="mostrarReenvioDocumento = false">Cancelar</button>
          <button type="submit" class="btn-primary px-5 py-2.5" :disabled="tesisStore.enviando || !archivoReenvioDocumento">
            <AppIcon v-if="tesisStore.enviando" name="loader" :size="15" class="animate-spin" />
            <AppIcon v-else name="send" :size="15" />
            {{ tesisStore.enviando ? 'Enviando...' : 'Reenviar Documento' }}
          </button>
        </div>
      </form>
    </UiModal>
  </AppShell>
</template>

<script setup>
// Vista del Módulo de Tesis de Grado (lado del estudiante).
// Dos modos:
//   - Sin tesis activa: formulario de solicitud con los 3 documentos
//     obligatorios (Nota de Solicitud, Certificado de Notas, Perfil de Tesis).
//   - Con tesis activa: seguimiento del flujo oficial (estado, fase, hitos,
//     cuenta regresiva del plazo, fecha de defensa y documentos) y la opción de
//     reenviar el perfil si el Consejo lo rechazó. La línea de tiempo (historial)
//     ya no se muestra aquí: la gestiona el personal (Kardex) en sus paneles.
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { EstadoBadge, useTramitesStore, formatoEstado, ESTADOS_TERMINALES } from '@/modules/tramites';
import { useEstudianteStore } from '@/modules/estudiantes';
import { useAuthStore } from '@/modules/auth';
import { useToastStore } from '@/core/stores/toast';
import { assetUrl } from '@/core/http/storage';
import { AppShell } from '@/modules/layout';
import AppIcon from '@/ui/AppIcon.vue';
import UiModal from '@/ui/UiModal.vue';
import CountdownTesis from '../components/CountdownTesis.vue';
import { useTesisStore } from '../stores/tesis';
import { FASES_TESIS, DESCRIPCION_ESTADO, faseDe, countdownDe } from '../utils/flujo';

const router = useRouter();
const authStore = useAuthStore();
const tramitesStore = useTramitesStore();
const estudianteStore = useEstudianteStore();
const tesisStore = useTesisStore();
const toastStore = useToastStore();

// Estado del formulario de solicitud.
const archivos = ref({ nota_solicitud: null, certificado_notas: null, perfil_tesis: null });
// Modal de reenvío del perfil rechazado.
const mostrarReenvio = ref(false);
const archivoReenvio = ref(null);
const observacionesReenvio = ref('');
// Modal de reenvío del documento final calificado como insuficiente.
const mostrarReenvioDocumento = ref(false);
const archivoReenvioDocumento = ref(null);
const observacionesReenvioDocumento = ref('');
// Solicitud de fecha de defensa.
const fechaSugerida = ref('');
const cargando = ref(true);

const tramite = computed(() => tramitesStore.tramiteActivo);
const esTesis = computed(() => tramite.value?.modalidad?.nombre === 'Tesis de Grado');
const tesisTerminada = computed(() => esTesis.value && ESTADOS_TERMINALES.includes(tramite.value.estado_actual));

// Si la tesis está terminal (o no es tesis), se muestra la solicitud de nuevo.
const tesisActiva = computed(() => esTesis.value && !tesisTerminada.value);

// Último trámite de tesis que terminó (para mostrar su resultado al iniciar otra).
const ultimaTesisTerminada = computed(() => (tesisTerminada.value ? tramite.value : null));

const descripcionEstado = computed(() => DESCRIPCION_ESTADO[tramite.value?.estado_actual]);
const countdownCfg = computed(() => countdownDe(tramite.value));

// true cuando el estudiante ya envió su solicitud de fecha de defensa a Kardex.
const fechaDefensaSolicitada = computed(() => Boolean(tramite.value?.hitos?.fecha_defensa_solicitada));

// Fecha mínima seleccionable para la fecha sugerida (hoy).
const hoyISO = computed(() => new Date().toISOString().slice(0, 10));

// Documentos obligatorios que aún no se subieron en el formulario.
const faltantes = computed(() =>
  Object.entries(tesisStore.config.tipos_documento)
    .filter(([tipo]) => !archivos.value[tipo])
    .map(([, etiqueta]) => etiqueta)
);

onMounted(async () => {
  cargando.value = true;
  try {
    await Promise.all([
      estudianteStore.cargarPerfil(false),
      tesisStore.cargarConfig(),
      tramitesStore.cargarTramiteActivo(),
    ]);
  } finally {
    cargando.value = false;
  }
});

/** Asocia un archivo seleccionado a su tipo de documento obligatorio. */
function handleArchivo(event, tipo) {
  const file = event.target.files[0];
  archivos.value[tipo] = file || null;
}

/**
 * Envía la solicitud de tesis con los 3 documentos como FormData multipart.
 */
async function enviarSolicitud() {
  if (faltantes.value.length) {
    toastStore.warning('Debe adjuntar los 3 documentos obligatorios.');
    return;
  }

  const formData = new FormData();
  const tipos = Object.keys(tesisStore.config.tipos_documento);

  tipos.forEach((tipo, index) => {
    formData.append(`documentos[${index}][archivo]`, archivos.value[tipo]);
    formData.append(`documentos[${index}][tipo]`, tipo);
  });

  try {
    await tesisStore.crearSolicitud(formData);
    toastStore.success('Solicitud de tesis enviada. Queda pendiente del Consejo Universitario.');
    archivos.value = { nota_solicitud: null, certificado_notas: null, perfil_tesis: null };
    await tramitesStore.cargarTramiteActivo();
  } catch (error) {
    toastStore.error('Error al enviar: ' + (error.response?.data?.message || 'Verifique los datos'));
  }
}

/**
 * Reenvía el perfil corregido tras su rechazo por el Consejo.
 */
async function enviarReenvio() {
  const formData = new FormData();
  if (archivoReenvio.value) formData.append('perfil', archivoReenvio.value);
  formData.append('observaciones', observacionesReenvio.value || '');

  try {
    const { data } = await tesisStore.reenviarPerfil(tramite.value.id_tramite, formData);
    tramitesStore.tramiteActivo = data;
    mostrarReenvio.value = false;
    archivoReenvio.value = null;
    observacionesReenvio.value = '';
    toastStore.success('Perfil reenviado. Queda pendiente del Consejo Universitario.');
  } catch (error) {
    toastStore.error('Error al reenviar: ' + (error.response?.data?.message || 'Verifique los datos'));
  }
}

/**
 * Reenvía el documento final corregido tras su calificación insuficiente por la
 * Comisión Revisora.
 */
async function enviarReenvioDocumento() {
  if (!archivoReenvioDocumento.value) {
    return toastStore.warning('Debe adjuntar el documento final corregido.');
  }

  const formData = new FormData();
  formData.append('documento_final', archivoReenvioDocumento.value);
  formData.append('observaciones', observacionesReenvioDocumento.value || '');

  try {
    const { data } = await tesisStore.reenviarDocumento(tramite.value.id_tramite, formData);
    tramitesStore.tramiteActivo = data;
    mostrarReenvioDocumento.value = false;
    archivoReenvioDocumento.value = null;
    observacionesReenvioDocumento.value = '';
    toastStore.success('Documento corregido enviado. Queda en evaluación de la Comisión Revisora.');
  } catch (error) {
    toastStore.error('Error al enviar: ' + (error.response?.data?.message || 'Verifique los datos'));
  }
}

/**
 * Envía la solicitud de fecha de defensa a Kardex para su programación.
 */
async function enviarSolicitudFecha() {
  try {
    const { data } = await tesisStore.solicitarFechaDefensa(
      tramite.value.id_tramite,
      fechaSugerida.value || null
    );
    tramitesStore.tramiteActivo = data;
    fechaSugerida.value = '';
    toastStore.success('Solicitud de fecha de defensa enviada. Kardex la programará.');
  } catch (error) {
    toastStore.error('Error al solicitar: ' + (error.response?.data?.message || 'Verifique los datos'));
  }
}

/**
 * Estado visual de una fase del flujo según el estado actual de la tesis.
 *
 * @returns {{contenedor:string, icono:string, texto:string}}
 */
function estadoFase(fase) {
  const actual = faseDe(tramite.value?.estado_actual);

  if (actual.id === fase.id) {
    return {
      contenedor: 'bg-gradient-to-br from-amber-500 to-orange-500 ring-amber-500 shadow-lg',
      icono: 'text-white',
      texto: 'text-white',
    };
  }
  if (actual.index > FASES_TESIS.indexOf(fase)) {
    return {
      contenedor: 'bg-emerald-50 ring-emerald-200',
      icono: 'text-emerald-500',
      texto: 'text-emerald-700',
    };
  }
  return {
    contenedor: 'bg-stone-50 ring-stone-200',
    icono: 'text-stone-400',
    texto: 'text-stone-500',
  };
}

/** Etiqueta legible de un tipo de documento subido. */
function etiquetaDocumento(tipo) {
  return tesisStore.config.tipos_documento?.[tipo] || tipo;
}

/** Formatea una fecha (YYYY-MM-DD) en formato largo en español. */
function formatoFechaLarga(iso) {
  if (!iso) return '—';
  return new Date(iso + 'T00:00:00').toLocaleDateString('es-BO', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
  });
}
</script>