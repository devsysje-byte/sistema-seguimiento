<template>
  <ModuloTarjeta
    numero="2"
    titulo="Aprobación del Tema"
    descripcion="Registre la Resolución del HCC que aprueba el tema y asigne el tutor."
    icono="book"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <div v-if="completado && datos" class="space-y-3">
      <dl class="grid sm:grid-cols-2 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">N.º Resolución HCC</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ datos.numero_resolucion }}</dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Fecha de Resolución</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ fechaLegible(datos.fecha_resolucion) }}</dd>
        </div>
      </dl>
      <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
        <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Tema de Investigación</dt>
        <dd class="text-sm font-semibold text-white mt-0.5">{{ datos.tema_investigacion }}</dd>
      </div>
      <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-3.5 py-2.5 flex items-center gap-2">
        <AppIcon name="user-check" :size="16" class="text-emerald-400" />
        <span class="text-sm font-semibold text-emerald-200">{{ nombreTutor }}</span>
      </div>
    </div>

    <div v-else-if="bloqueado">
      <div class="flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm text-slate-400">
        <AppIcon name="shield" :size="15" />
        Complete el módulo anterior para registrar la aprobación del tema.
      </div>
    </div>

    <form v-else @submit.prevent="guardar" class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="label" for="tema-resolucion">Número de Resolución del HCC</label>
        <input
          id="tema-resolucion"
          v-model="form.numero_resolucion"
          type="text"
          class="input"
          placeholder="Ej: HCC-0102/2026"
        />
      </div>
      <div>
        <label class="label" for="tema-fecha">Fecha de Resolución</label>
        <input id="tema-fecha" v-model="form.fecha_resolucion" type="date" class="input" :max="hoyISO" />
      </div>

      <div class="sm:col-span-2">
        <label class="label" for="tema-investigacion">Tema de Investigación</label>
        <textarea
          id="tema-investigacion"
          v-model="form.tema_investigacion"
          rows="3"
          class="input resize-none"
          placeholder="Tema de investigación aprobado por el Consejo..."
        ></textarea>
      </div>

      <div class="sm:col-span-2">
        <label class="label">Asignación de Tutor</label>
        <div class="relative">
          <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
            <AppIcon name="search" :size="16" />
          </span>
          <input
            v-model="busqueda"
            type="text"
            class="input pl-10"
            placeholder="Buscar docente por nombre..."
            @focus="abierta = true"
          />
          <ul
            v-if="abierta && busqueda"
            class="absolute z-20 mt-1 w-full rounded-xl border border-white/10 bg-slate-800 max-h-44 overflow-auto shadow-xl"
          >
            <li v-if="!docentesFiltrados.length" class="px-4 py-3 text-sm text-slate-400">
              Sin coincidencias.
            </li>
            <li
              v-for="doc in docentesFiltrados"
              :key="doc.id_usuario"
              class="px-4 py-2.5 text-sm text-slate-200 cursor-pointer hover:bg-orange-500/10 hover:text-orange-200 flex items-center gap-2"
              @click="seleccionar(doc)"
            >
              <AppIcon name="user" :size="14" class="text-slate-500" />
              {{ doc.nombres }} {{ doc.apellidos }}
            </li>
          </ul>
        </div>
        <p v-if="tutorElegido" class="mt-2 text-sm font-semibold text-emerald-300 flex items-center gap-1.5">
          <AppIcon name="user-check" :size="15" />
          Tutor: {{ tutorElegido.nombres }} {{ tutorElegido.apellidos }}
        </p>
      </div>

      <ul v-if="errores.length" class="sm:col-span-2 space-y-1">
        <li v-for="(e, i) in errores" :key="i" class="flex items-start gap-2 text-sm text-red-400">
          <AppIcon name="alert-triangle" :size="15" class="shrink-0 mt-0.5" />
          {{ e }}
        </li>
      </ul>

      <div class="sm:col-span-2 flex justify-end">
        <button type="submit" class="btn-warm px-6" :disabled="cargando">
          <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="check" :size="18" />
          <span class="uppercase tracking-wide">Guardar y actualizar flujo</span>
        </button>
      </div>
    </form>
  </ModuloTarjeta>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { validarModulo } from '@/modules/tesis/utils/flujoTitulacion';
import { fechaLegible } from '@/modules/kardex/utils/formato';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from './ModuloTarjeta.vue';

const props = defineProps({
  completado: { type: Boolean, default: false },
  enProgreso: { type: Boolean, default: false },
  bloqueado: { type: Boolean, default: false },
  datos: { type: Object, default: null },
  docentes: { type: Array, default: () => [] },
  cargando: { type: Boolean, default: false },
});

const emit = defineEmits(['guardar']);

const form = reactive({
  numero_resolucion: props.datos?.numero_resolucion || '',
  fecha_resolucion: props.datos?.fecha_resolucion || '',
  tema_investigacion: props.datos?.tema_investigacion || '',
  tutor_id: props.datos?.tutor_id || '',
});

const busqueda = ref('');
const abierta = ref(false);
const errores = ref([]);

const hoyISO = computed(() => new Date().toISOString().slice(0, 10));
const tutorElegido = computed(() => props.docentes.find((d) => d.id_usuario === Number(form.tutor_id)));

const docentesFiltrados = computed(() => {
  const q = busqueda.value.trim().toLowerCase();
  if (!q) return [];
  return props.docentes
    .filter((d) => `${d.nombres} ${d.apellidos}`.toLowerCase().includes(q))
    .slice(0, 8);
});

const nombreTutor = computed(() => {
  if (!props.datos?.tutor_id) return 'No registrado';
  const doc = props.docentes.find((d) => d.id_usuario === Number(props.datos.tutor_id));
  return doc ? `${doc.nombres} ${doc.apellidos}` : `Docente #${props.datos.tutor_id}`;
});

function seleccionar(doc) {
  form.tutor_id = doc.id_usuario;
  busqueda.value = `${doc.nombres} ${doc.apellidos}`;
  abierta.value = false;
}

function obtenerDatos() {
  return {
    numero_resolucion: form.numero_resolucion.trim(),
    fecha_resolucion: form.fecha_resolucion,
    tema_investigacion: form.tema_investigacion.trim(),
    tutor_id: form.tutor_id,
  };
}

function guardar() {
  const datos = obtenerDatos();
  const res = validarModulo('aprobacion_tema', datos);
  if (!res.ok) {
    errores.value = res.errores;
    return;
  }
  errores.value = [];
  emit('guardar', datos);
}
</script>