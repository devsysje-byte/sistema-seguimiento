<template>
  <ModuloTarjeta
    numero="4"
    titulo="Defensa"
    descripcion="Registre la defensa en dos pasos: Resolución y fecha, y luego la nota final."
    icono="award"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <div v-if="completado && datos" class="space-y-3">
      <dl class="grid sm:grid-cols-3 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">N.º Resolución Final</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ datos.numero_resolucion }}</dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Fecha de Defensa</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ fechaLegible(datos.fecha_defensa) }}</dd>
        </div>
        <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-3.5 py-2.5">
          <dt class="text-[11px] uppercase tracking-wider text-emerald-300 font-semibold">Nota Final</dt>
          <dd class="text-2xl font-extrabold text-emerald-300 mt-0.5">
            {{ datos.nota_final }}
            <span class="text-xs font-semibold text-emerald-500">/ 100</span>
          </dd>
        </div>
      </dl>
    </div>

    <div v-else-if="bloqueado">
      <div class="flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm text-slate-400">
        <AppIcon name="shield" :size="15" />
        Complete el módulo anterior para registrar la defensa.
      </div>
    </div>

    <!-- La defensa se registra en DOS pasos consecutivos. -->
    <form v-else @submit.prevent="guardarPasoActual" class="space-y-5">
      <div class="grid sm:grid-cols-2 gap-3">
        <button
          type="button"
          class="flex items-center gap-3 rounded-xl border px-4 py-3 text-left transition text-sm"
          :class="chipPaso1"
          @click="irPaso(1)"
        >
          <span
            class="w-7 h-7 shrink-0 rounded-full flex items-center justify-center text-xs font-extrabold"
            :class="dotPaso1"
          >
            <AppIcon v-if="paso1Completado" name="check" :size="14" />
            <template v-else>1</template>
          </span>
          <span class="min-w-0">
            <span class="block font-bold uppercase tracking-wide">Resolución y fecha de defensa</span>
            <span class="block text-xs" :class="paso1Completado ? 'text-emerald-300/80' : 'text-slate-400'">
              {{ paso1Completado ? 'Completado' : 'Registre la Resolución de aprobación final y la fecha' }}
            </span>
          </span>
        </button>

        <button
          type="button"
          class="flex items-center gap-3 rounded-xl border px-4 py-3 text-left transition text-sm"
          :class="chipPaso2"
          @click="irPaso(2)"
          :disabled="!paso1Completado"
        >
          <span class="w-7 h-7 shrink-0 rounded-full flex items-center justify-center text-xs font-extrabold" :class="dotPaso2">
            2
          </span>
          <span class="min-w-0">
            <span class="block font-bold uppercase tracking-wide">Nota final de la defensa</span>
            <span class="block text-xs text-slate-400">
              {{ paso1Completado ? 'Registre la nota de la defensa' : 'Se habilita tras el paso 1' }}
            </span>
          </span>
        </button>
      </div>

      <!-- Paso 1: Resolución de Aprobación Final + fecha de la defensa -->
      <div v-if="!paso1Completado" class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="label" for="defensa-resolucion">Número de Resolución de Aprobación Final</label>
          <input
            id="defensa-resolucion"
            v-model="form.numero_resolucion"
            type="text"
            class="input"
            placeholder="Ej: HCC-0315/2026"
          />
        </div>
        <div>
          <label class="label" for="defensa-fecha">Fecha de Defensa</label>
          <input id="defensa-fecha" v-model="form.fecha_defensa" type="date" class="input" :max="hoyISO" />
        </div>

        <ul v-if="errores.length" class="col-span-2 space-y-1">
          <li v-for="(e, i) in errores" :key="i" class="flex items-start gap-2 text-sm text-red-400">
            <AppIcon name="alert-triangle" :size="15" class="shrink-0 mt-0.5" />
            {{ e }}
          </li>
        </ul>

        <div class="col-span-2 flex justify-end">
          <button type="submit" class="btn-warm px-6" :disabled="cargando">
            <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
            <AppIcon v-else name="calendar" :size="18" />
            <span class="uppercase tracking-wide">Guardar resolución y fecha</span>
          </button>
        </div>
      </div>

      <!-- Paso 2: nota final de la defensa -->
      <div v-else class="space-y-4">
        <dl class="grid sm:grid-cols-2 gap-2.5">
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">N.º Resolución Final</dt>
            <dd class="text-sm font-semibold text-white mt-0.5">{{ datos.numero_resolucion }}</dd>
          </div>
          <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
            <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Fecha de Defensa</dt>
            <dd class="text-sm font-semibold text-white mt-0.5">{{ fechaLegible(datos.fecha_defensa) }}</dd>
          </div>
        </dl>

        <div>
          <label class="label" for="defensa-nota">Nota Final de Defensa</label>
          <input
            id="defensa-nota"
            v-model="form.nota_final"
            type="number"
            min="0"
            max="100"
            step="0.01"
            class="input"
            placeholder="0 - 100"
          />
        </div>

        <ul v-if="errores.length" class="space-y-1">
          <li v-for="(e, i) in errores" :key="i" class="flex items-start gap-2 text-sm text-red-400">
            <AppIcon name="alert-triangle" :size="15" class="shrink-0 mt-0.5" />
            {{ e }}
          </li>
        </ul>

        <div class="flex justify-end">
          <button type="submit" class="btn-warm px-6" :disabled="cargando">
            <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
            <AppIcon v-else name="award" :size="18" />
            <span class="uppercase tracking-wide">Registrar nota final</span>
          </button>
        </div>
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
  cargando: { type: Boolean, default: false },
});

const emit = defineEmits(['guardar']);

const form = reactive({
  numero_resolucion: props.datos?.numero_resolucion || '',
  fecha_defensa: props.datos?.fecha_defensa || '',
  nota_final: props.datos?.nota_final ?? '',
});

const paso = ref('1');
const errores = ref([]);
const hoyISO = computed(() => new Date().toISOString().slice(0, 10));

// El paso 1 (resolución + fecha) ya fue guardado: se dispone del dato en `hitos`.
const paso1Completado = computed(
  () => Boolean(props.datos?.numero_resolucion) && Boolean(props.datos?.fecha_defensa),
);

const DOT_ACTIVO = 'bg-orange text-white';
const DOT_PENDIENTE = 'bg-white/10 text-slate-400';

const chipPaso1 = computed(() =>
  paso1Completado
    ? 'border-emerald-500/30 bg-emerald-500/10'
    : paso.value === '1'
      ? 'border-orange-500 bg-orange-500/10'
      : 'border-white/10 bg-white/5',
);

const dotPaso1 = computed(() => {
  if (paso1Completado) return 'bg-emerald-500 text-white';
  return paso.value === '1' ? DOT_ACTIVO : DOT_PENDIENTE;
});

const chipPaso2 = computed(() => {
  if (!paso1Completado) return 'border-white/10 bg-white/5 opacity-50';
  return paso.value === '2' ? 'border-orange-500 bg-orange-500/10' : 'border-white/10 bg-white/5';
});

const dotPaso2 = computed(() => (paso.value === '2' ? DOT_ACTIVO : DOT_PENDIENTE));

/** Cambia entre el paso 1 y el paso 2 (solo si el paso 1 ya se completó). */
function irPaso(n) {
  if (n === 2 && !paso1Completado.value) return;
  paso.value = String(n);
  errores.value = [];
}

function obtenerDatos() {
  if (!paso1Completado.value) {
    return {
      numero_resolucion: form.numero_resolucion.trim(),
      fecha_defensa: form.fecha_defensa,
      paso: 1,
    };
  }
  return {
    numero_resolucion: props.datos.numero_resolucion,
    fecha_defensa: props.datos.fecha_defensa,
    nota_final: form.nota_final === '' ? '' : Number(form.nota_final),
    paso: 2,
  };
}

function guardarPasoActual() {
  const datos = obtenerDatos();
  const res = validarModulo('defensa', datos);
  if (!res.ok) {
    errores.value = res.errores;
    return;
  }
  errores.value = [];
  emit('guardar', datos);
}
</script>