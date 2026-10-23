<template>
  <ModuloTarjeta
    numero="1"
    titulo="Verificación del Promedio"
    descripcion="Verifique el promedio general del estudiante. Con el mínimo reglamentario la solicitud avanza; por debajo, queda rechazada."
    icono="clipboard"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <!-- Promedio ya verificado -->
    <div v-if="completado" class="space-y-3">
      <div
        class="flex items-center gap-3 rounded-xl border px-4 py-3"
        :class="aprobado ? 'border-emerald-500/30 bg-emerald-500/10' : 'border-red-500/30 bg-red-900/30'"
      >
        <AppIcon
          :name="aprobado ? 'check-circle' : 'x-circle'"
          :size="18"
          :class="aprobado ? 'text-emerald-300' : 'text-red-400'"
        />
        <p class="text-sm font-semibold" :class="aprobado ? 'text-emerald-200' : 'text-red-200'">
          {{ aprobado ? 'Promedio verificado: el estudiante habilita la modalidad' : 'Promedio por debajo del mínimo: solicitud RECHAZADA' }}
        </p>
      </div>

      <dl class="grid sm:grid-cols-3 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Promedio declarado</dt>
          <dd class="text-2xl font-extrabold text-white mt-0.5">
            {{ declarado !== null ? declarado : '—' }}
            <span class="text-xs font-semibold text-slate-400">/ 100</span>
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Promedio verificado</dt>
          <dd class="text-2xl font-extrabold mt-0.5" :class="aprobado ? 'text-emerald-300' : 'text-red-400'">
            {{ verificado !== null ? verificado : '—' }}
            <span class="text-xs font-semibold text-slate-400">/ 100</span>
          </dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Mínimo exigido</dt>
          <dd class="text-2xl font-extrabold text-white mt-0.5">
            {{ notaMinima }}
            <span class="text-xs font-semibold text-slate-400">/ 100</span>
          </dd>
        </div>
      </dl>

      <p class="dato-ayuda">
        El promedio verificado se consolidó en el perfil del estudiante
        (<span class="font-semibold text-slate-300">promedio global</span>).
      </p>
    </div>

    <!-- Formulario de verificación -->
    <form v-else class="space-y-5" @submit.prevent="guardar">
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="label" for="excelencia-promedio-declarado">Promedio declarado por el estudiante</label>
          <input
            id="excelencia-promedio-declarado"
            :value="declarado ?? ''"
            type="number"
            min="0"
            max="100"
            step="0.01"
            class="input"
            disabled
          />
          <p class="dato-ayuda">Valor registrado en la solicitud; solo informativo.</p>
        </div>
        <div>
          <label class="label" for="excelencia-promedio-verificado">Promedio verificado · editable</label>
          <input
            id="excelencia-promedio-verificado"
            v-model="form.promedio"
            type="number"
            min="0"
            max="100"
            step="0.01"
            class="input"
            placeholder="Ej: 87.50"
          />
          <p class="dato-ayuda">Se habilita con {{ notaMinima }} o más. Este valor queda en el perfil del estudiante.</p>
        </div>
      </div>

      <div>
        <label class="label" for="excelencia-observaciones">Observaciones (opcional)</label>
        <textarea
          id="excelencia-observaciones"
          v-model="form.observaciones"
          rows="2"
          class="input resize-none"
          placeholder="Detalle la verificación realizada..."
        ></textarea>
      </div>

      <ul v-if="errores.length" class="space-y-1">
        <li v-for="(e, i) in errores" :key="i" class="flex items-start gap-2 text-sm text-red-400">
          <AppIcon name="alert-triangle" :size="15" class="shrink-0 mt-0.5" />
          {{ e }}
        </li>
      </ul>

      <ul v-if="advertencias.length" class="space-y-1">
        <li v-for="(a, i) in advertencias" :key="i" class="flex items-start gap-2 text-sm text-amber-300">
          <AppIcon name="info" :size="15" class="shrink-0 mt-0.5" />
          {{ a }}
        </li>
      </ul>

      <!-- Un promedio insuficiente NO es un error: el backend lo registra como
           RECHAZADO. Se pide confirmación explícita para evitar el mistake. -->
      <label
        v-if="rechazara"
        class="flex items-start gap-2.5 rounded-xl border border-amber-500/40 bg-amber-500/10 px-3.5 py-3 cursor-pointer"
      >
        <input
          v-model="confirmoRechazo"
          type="checkbox"
          class="mt-0.5 h-4 w-4 accent-amber-500"
        />
        <span class="text-xs text-amber-200 leading-relaxed">
          Confirmo registrar este promedio: el estudiante queda
          <strong>RECHAZADO</strong>, el flujo de titulación termina y no habilita
          la Excelencia Académica.
        </span>
      </label>

      <div class="flex justify-end">
        <button
          type="submit"
          class="btn-warm px-6"
          :disabled="cargando || (rechazara && !confirmoRechazo)"
        >
          <AppIcon v-if="cargando" name="loader" :size="18" class="animate-spin" />
          <AppIcon v-else name="clipboard" :size="18" />
          <span class="uppercase tracking-wide">Verificar promedio</span>
        </button>
      </div>
    </form>
  </ModuloTarjeta>
</template>

<script setup>
// Módulo 1 del flujo de Excelencia Académica: verificación del promedio general.
//
// El estudiante declara su promedio al presentar la solicitud; KARDEX lo
// verifica aquí y puede corregirlo (es la nota que se consolida en el perfil
// del estudiante). Con el promedio en o por encima del mínimo el trámite
// avanza a `promedio_verificado`; por debajo queda `rechazado` y el flujo
// termina.
import { computed, reactive, ref, watch } from 'vue';
import { NOTA_MINIMA, validarModulo } from '@/modules/excelencia/utils/flujoExcelencia';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from '../flujo/ModuloTarjeta.vue';

const props = defineProps({
    completado: { type: Boolean, default: false },
    enProgreso: { type: Boolean, default: false },
    bloqueado: { type: Boolean, default: false },
    cargando: { type: Boolean, default: false },
    datos: { type: Object, default: null },
    // Mínimo reglamentario: lo define el backend (`config/excelencia.php`).
    notaMinima: { type: Number, default: NOTA_MINIMA },
    promedioDeclarado: { type: [Number, String], default: null },
    promedioVerificado: { type: [Number, String], default: null },
});

const emit = defineEmits(['guardar']);

const notaMinima = computed(() => Number(props.notaMinima) || NOTA_MINIMA);

const declarado = computed(() =>
    props.promedioDeclarado === null || props.promedioDeclarado === undefined
        ? null
        : Number(props.promedioDeclarado),
);

const verificado = computed(() =>
    props.promedioVerificado === null || props.promedioVerificado === undefined
        ? null
        : Number(props.promedioVerificado),
);

const form = reactive({
    promedio: props.promedioVerificado ?? props.promedioDeclarado ?? '',
    observaciones: props.datos?.observaciones || '',
});

watch(
    () => props.promedioVerificado,
    (valor) => {
        if (valor !== null && valor !== undefined) form.promedio = valor;
    },
);

const errores = ref([]);
const advertencias = ref([]);
const confirmoRechazo = ref(false);

/** El promedio verificado habilita la modalidad cuando alcanza el mínimo. */
const aprobado = computed(() => verificado.value !== null && verificado.value >= notaMinima.value);

/** ¿El promedio capturado deja la solicitud en estado RECHAZADO? */
const rechazara = computed(() => {
    const nota = Number(form.promedio);
    return form.promedio !== '' && !Number.isNaN(nota) && nota < notaMinima.value;
});

function guardar() {
    const datos = {
        promedio: form.promedio === '' ? '' : Number(form.promedio),
        observaciones: form.observaciones.trim(),
    };

    const res = validarModulo('verificacion_promedio', datos);

    // El aviso del rechazo usa el mínimo vigente (lo define el backend), no el
    // valor por defecto del espejo frontend.
    advertencias.value = rechazara.value
        ? [
            `Con ${form.promedio} de promedio el estudiante NO alcanza el mínimo de ${notaMinima.value}: `
            + 'el módulo se cerrará como RECHAZADO y el flujo de titulación terminará.',
        ]
        : res.advertencias;

    if (!res.ok) {
        errores.value = res.errores;
        return;
    }

    if (rechazara.value && !confirmoRechazo.value) return;

    errores.value = [];
    emit('guardar', datos);
}
</script>
