<template>
  <ModuloTarjeta
    numero="3"
    titulo="Tribunal Revisor"
    descripcion="Registre la Resolución del HCC que designa al tribunal revisor."
    icono="users"
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
      <div class="space-y-2">
        <div
          v-for="miembro in datos.tribunal"
          :key="miembro.rol"
          class="flex items-center justify-between gap-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-3.5 py-2.5"
        >
          <span class="text-[11px] uppercase tracking-wide text-emerald-300 font-bold">{{ miembro.rol }}</span>
          <span class="text-sm font-semibold text-emerald-100">{{ miembro.nombre }}</span>
        </div>
      </div>
    </div>

    <div v-else-if="bloqueado">
      <div class="flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm text-slate-400">
        <AppIcon name="shield" :size="15" />
        Complete el módulo anterior para designar el tribunal revisor.
      </div>
    </div>

    <form v-else @submit.prevent="guardar" class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="label" for="tribunal-resolucion">Número de Resolución del HCC</label>
        <input
          id="tribunal-resolucion"
          v-model="form.numero_resolucion"
          type="text"
          class="input"
          placeholder="Ej: HCC-0158/2026"
        />
      </div>
      <div>
        <label class="label" for="tribunal-fecha">Fecha de Resolución</label>
        <input id="tribunal-fecha" v-model="form.fecha_resolucion" type="date" class="input" :max="hoyISO" />
      </div>

      <div class="sm:col-span-2">
        <label class="label">Nombres del Tribunal Revisor</label>
        <div class="space-y-2.5">
          <div
            v-for="miembro in form.tribunal"
            :key="miembro.rol"
            class="flex items-center gap-3"
          >
            <span class="w-24 shrink-0 text-[11px] uppercase tracking-wider font-bold text-orange-300">
              {{ miembro.rol }}
            </span>
            <input
              v-model="miembro.nombre"
              type="text"
              class="input flex-1"
              :placeholder="`Nombre del ${miembro.rol} del tribunal...`"
            />
          </div>
        </div>
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
import { ROLES_TRIBUNAL, validarModulo } from '@/modules/tesis/utils/flujoTitulacion';
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
  fecha_resolucion: props.datos?.fecha_resolucion || '',
  tribunal: ROLES_TRIBUNAL.map((rol) => ({
    rol,
    nombre: props.datos?.tribunal?.find((t) => t.rol === rol)?.nombre || '',
  })),
});

const errores = ref([]);
const hoyISO = computed(() => new Date().toISOString().slice(0, 10));

function obtenerDatos() {
  return {
    numero_resolucion: form.numero_resolucion.trim(),
    fecha_resolucion: form.fecha_resolucion,
    tribunal: form.tribunal.map((t) => ({ rol: t.rol, nombre: t.nombre.trim() })),
  };
}

function guardar() {
  const datos = obtenerDatos();
  const res = validarModulo('tribunal_revisor', datos);
  if (!res.ok) {
    errores.value = res.errores;
    return;
  }
  errores.value = [];
  emit('guardar', datos);
}
</script>