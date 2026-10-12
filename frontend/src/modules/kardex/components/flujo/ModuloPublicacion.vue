<template>
  <ModuloTarjeta
    numero="5"
    titulo="Publicación"
    descripcion="Adjunte el Trabajo de Investigación (PDF) — opcional"
    icono="graduation"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <div v-if="completado && datos" class="space-y-3">
      <div v-if="datos.archivo?.nombre" class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-3.5 py-2.5 flex items-center gap-2">
        <AppIcon name="file-text" :size="15" class="text-emerald-400 shrink-0" />
        <span class="text-sm font-semibold text-emerald-200 truncate">
          {{ datos.archivo.nombre }}
        </span>
      </div>
      <div v-else class="flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm text-slate-400">
        <AppIcon name="file" :size="15" />
        Sin archivo adjunto
      </div>
    </div>

    <div v-else-if="bloqueado">
      <div class="flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm text-slate-400">
        <AppIcon name="shield" :size="15" />
        Complete el módulo anterior para registrar la publicación.
      </div>
    </div>

    <form v-else @submit.prevent="guardar" class="grid gap-4">
      <div>
        <label class="label">Trabajo de Investigación (PDF) — opcional</label>
        <label
          :class="['flex items-center justify-between gap-3 px-3.5 py-3 rounded-xl border-2 border-dashed cursor-pointer transition', form.archivo ? 'border-orange-500/50 bg-orange-500/10' : 'border-white/10 bg-white/5 hover:border-orange-500/40']"
        >
          <span
            class="flex items-center gap-2 text-sm min-w-0"
            :class="form.archivo ? 'text-orange-200' : 'text-slate-400'"
          >
            <AppIcon v-if="form.archivo" name="check-circle" :size="16" class="text-emerald-400 shrink-0" />
            <AppIcon v-else name="file" :size="16" class="shrink-0" />
            <span class="truncate">{{ form.archivo?.name || 'Subir trabajo de investigación en PDF...' }}</span>
          </span>
          <input type="file" accept=".pdf" class="hidden" @change="handleArchivo">
          <span class="btn-ghost !py-1.5 pointer-events-none text-xs">
            <AppIcon name="plus" :size="13" />
            PDF
          </span>
        </label>
        <button v-if="form.archivo" type="button" class="text-xs text-red-400 mt-2 hover:text-red-300" @click="eliminarArchivo">Quitar archivo</button>
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
import { reactive, ref } from 'vue';
import { validarModulo } from '@/modules/tesis/utils/flujoTitulacion';
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
  archivo: null,
});

const errores = ref([]);

function handleArchivo(event) {
  form.archivo = event.target.files[0] || null;
}

function eliminarArchivo() {
  form.archivo = null;
}

function obtenerDatos() {
  return {
    archivo: form.archivo ? { nombre: form.archivo.name, tamanio: form.archivo.size } : (props.datos?.archivo || null),
  };
}

function guardar() {
  const datos = obtenerDatos();
  const res = validarModulo('publicacion', datos);
  if (!res.ok) {
    errores.value = res.errores;
    return;
  }
  errores.value = [];
  emit('guardar', datos);
}
</script>