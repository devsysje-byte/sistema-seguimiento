<template>
  <ModuloTarjeta
    numero="2"
    titulo="Resolución del HCC"
    descripcion="Registre la Resolución del Consejo de la Universidad que habilita al estudiante para titularse por Excelencia Académica."
    icono="shield"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <!-- Resolución ya registrada -->
    <div v-if="completado" class="space-y-3">
      <dl class="grid sm:grid-cols-2 gap-2.5">
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">N.º de Resolución del HCC</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ registro?.numero_resolucion || '—' }}</dd>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="dato-label">Fecha de la resolución</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">
            {{ fechaLegible(registro?.fecha_resolucion || '') || '—' }}
          </dd>
        </div>
      </dl>
      <p class="dato-ayuda">El estudiante ya puede emitir su acta de titulación.</p>
    </div>

    <div v-else-if="bloqueado" class="aviso-bloqueo">
      <AppIcon name="shield" :size="15" />
      Verifique el promedio del estudiante para poder registrar la Resolución.
    </div>

    <!-- Formulario de la resolución -->
    <form v-else class="space-y-5" @submit.prevent="guardar">
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="label" for="excelencia-resolucion">Número de Resolución del HCC</label>
          <input
            id="excelencia-resolucion"
            v-model="form.numero_resolucion"
            type="text"
            class="input"
            placeholder="Ej: HCC-0412/2026"
          />
        </div>
        <div>
          <label class="label" for="excelencia-fecha-resolucion">Fecha de la Resolución</label>
          <input id="excelencia-fecha-resolucion" v-model="form.fecha_resolucion" type="date" class="input" :max="hoyISO" />
        </div>
      </div>

      <div>
        <label class="label" for="excelencia-observaciones-resolucion">Observaciones (opcional)</label>
        <textarea
          id="excelencia-observaciones-resolucion"
          v-model="form.observaciones"
          rows="2"
          class="input resize-none"
          placeholder="Detalle de la resolución..."
        ></textarea>
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
          <AppIcon v-else name="shield" :size="18" />
          <span class="uppercase tracking-wide">Registrar resolución</span>
        </button>
      </div>
    </form>
  </ModuloTarjeta>
</template>

<script setup>
// Módulo 2 del flujo de Excelencia Académica: registro de la Resolución del
// HCC que habilita al estudiante para titularse por esta modalidad.
import { computed, reactive, ref } from 'vue';
import { validarModulo } from '@/modules/excelencia/utils/flujoExcelencia';
import { fechaLegible } from '@/modules/kardex/utils/formato';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from '../flujo/ModuloTarjeta.vue';

const props = defineProps({
    completado: { type: Boolean, default: false },
    enProgreso: { type: Boolean, default: false },
    bloqueado: { type: Boolean, default: false },
    cargando: { type: Boolean, default: false },
    datos: { type: Object, default: null },
    registro: { type: Object, default: null },
});

const emit = defineEmits(['guardar']);

const form = reactive({
    numero_resolucion: props.datos?.numero_resolucion || '',
    fecha_resolucion: props.datos?.fecha_resolucion || '',
    observaciones: props.datos?.observaciones || '',
});

const errores = ref([]);
const hoyISO = computed(() => new Date().toISOString().slice(0, 10));

// El historial es la fuente de verdad: guarda la resolución registrada, aunque
// el formulario ya se haya limpiado.
const registro = computed(() => props.registro || props.datos);

function guardar() {
    const datos = {
        numero_resolucion: form.numero_resolucion.trim(),
        fecha_resolucion: form.fecha_resolucion,
        observaciones: form.observaciones.trim(),
    };

    const res = validarModulo('resolucion_hcc', datos);

    if (!res.ok) {
        errores.value = res.errores;
        return;
    }

    errores.value = [];
    emit('guardar', datos);
}
</script>
