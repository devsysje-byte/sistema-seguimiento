<template>
  <ModuloTarjeta
    numero="6"
    titulo="Publicación"
    descripcion="Adjunte el certificado de conclusión, la fecha de titulación y el enlace del registro SUNEDU."
    icono="graduation"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <div v-if="completado && datos" class="space-y-3">
      <dl class="grid sm:grid-cols-2 gap-2.5">
        <div
          class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-3.5 py-2.5 flex items-center gap-2"
        >
          <AppIcon name="file-text" :size="15" class="text-emerald-400 shrink-0" />
          <span class="text-sm font-semibold text-emerald-200 truncate">
            {{ datos.archivo?.nombre || 'Certificado adjunto' }}
          </span>
        </div>
        <div class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5">
          <dt class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Fecha de Titulación</dt>
          <dd class="text-sm font-semibold text-white mt-0.5">{{ fechaLegible(datos.fecha_titulacion) }}</dd>
        </div>
      </dl>
      <div v-if="datos.enlace_registro" class="rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5 flex items-center gap-2">
        <AppIcon name="link" :size="15" class="text-slate-400 shrink-0" />
        <a
          :href="datos.enlace_registro"
          target="_blank"
          rel="noopener noreferrer"
          class="text-sm font-semibold text-orange-300 hover:text-orange-200 truncate"
        >
          {{ datos.enlace_registro }}
        </a>
      </div>
    </div>

    <div v-else-if="bloqueado">
      <div class="flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm text-slate-400">
        <AppIcon name="shield" :size="15" />
        Complete el módulo anterior para registrar la publicación.
      </div>
    </div>

    <form v-else @submit.prevent="guardar" class="grid sm:grid-cols-2 gap-4">
      <div class="sm:col-span-2">
        <label class="label">Certificado de Conclusión (PDF)</label>
        <label
          :class="['flex items-center justify-between gap-3 px-3.5 py-3 rounded-xl border-2 border-dashed cursor-pointer transition', form.archivo ? 'border-orange-500/50 bg-orange-500/10' : 'border-white/10 bg-white/5 hover:border-orange-500/40']"
        >
          <span
            class="flex items-center gap-2 text-sm min-w-0"
            :class="form.archivo ? 'text-orange-200' : 'text-slate-400'"
          >
            <AppIcon v-if="form.archivo" name="check-circle" :size="16" class="text-emerald-400 shrink-0" />
            <AppIcon v-else name="file" :size="16" class="shrink-0" />
            <span class="truncate">{{ form.archivo?.name || 'Subir certificado en PDF...' }}</span>
          </span>
          <input type="file" accept=".pdf" class="hidden" @change="handleArchivo">
          <span class="btn-ghost !py-1.5 pointer-events-none text-xs">
            <AppIcon name="plus" :size="13" />
            PDF
          </span>
        </label>
      </div>

      <div>
        <label class="label" for="pub-fecha">Fecha de Titulación</label>
        <input id="pub-fecha" v-model="form.fecha_titulacion" type="date" class="input" :max="hoyISO" />
      </div>
      <div>
        <label class="label" for="pub-enlace">Enlace de registro (opcional)</label>
        <input
          id="pub-enlace"
          v-model="form.enlace_registro"
          type="url"
          class="input"
          placeholder="https://www.renati.sunedu.gob.pe/..."
        />
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
  cargando: { type: Boolean, default: false },
});

const emit = defineEmits(['guardar']);

const form = reactive({
  archivo: null,
  fecha_titulacion: props.datos?.fecha_titulacion || '',
  enlace_registro: props.datos?.enlace_registro || '',
});

const errores = ref([]);
const hoyISO = computed(() => new Date().toISOString().slice(0, 10));

function handleArchivo(event) {
  form.archivo = event.target.files[0] || null;
}

function obtenerDatos() {
  return {
    archivo: form.archivo ? { nombre: form.archivo.name, tamanio: form.archivo.size } : null,
    fecha_titulacion: form.fecha_titulacion,
    enlace_registro: form.enlace_registro.trim(),
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