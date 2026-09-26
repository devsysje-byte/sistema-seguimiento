<template>
  <ModuloTarjeta
    numero="1"
    titulo="Perfil de Tesis"
    descripcion="Verifique los 3 documentos obligatorios del postulante para aprobar su perfil."
    icono="file-text"
    :completado="completado"
    :en-progreso="enProgreso"
    :bloqueado="bloqueado"
  >
    <div v-if="completado && datos">
      <div class="space-y-2">
        <div
          v-for="d in DOCUMENTOS_PERFIL"
          :key="d.tipo"
          class="flex items-center justify-between gap-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-3.5 py-2.5"
        >
          <span class="flex items-center gap-2 text-sm font-semibold text-emerald-200">
            <AppIcon name="check-circle" :size="16" class="text-emerald-400" />
            {{ d.etiqueta }}
          </span>
          <span class="text-xs text-emerald-400 truncate max-w-[40%]">
            {{ datos.documentos?.[d.tipo]?.archivo?.nombre || 'Verificado' }}
          </span>
        </div>
      </div>
      <p v-if="datos.observaciones" class="mt-3 text-sm text-slate-300 italic">
        “{{ datos.observaciones }}”
      </p>
    </div>

    <div v-else-if="bloqueado">
      <div class="flex items-center gap-2 rounded-xl bg-white/5 border border-white/10 px-4 py-3 text-sm text-slate-400">
        <AppIcon name="shield" :size="15" />
        Complete los módulos anteriores para habilitar el registro del perfil.
      </div>
    </div>

    <form v-else @submit.prevent="guardar">
      <div class="space-y-3">
        <div
          v-for="d in DOCUMENTOS_PERFIL"
          :key="d.tipo"
          class="rounded-xl bg-white/5 border border-white/10 p-3.5"
        >
          <label class="flex items-center gap-3 cursor-pointer select-none">
            <input
              :id="`perfil-${d.tipo}-check`"
              v-model="form.documentos[d.tipo].marcado"
              type="checkbox"
              class="w-4 h-4 rounded border-slate-500 bg-transparent text-orange-500 focus:ring-orange-400 focus:ring-offset-0"
            />
            <span class="text-sm font-semibold text-white flex-1">
              {{ d.etiqueta }}
              <span class="text-xs font-normal text-slate-400">(verificado)</span>
            </span>
          </label>

          <label
            :class="['mt-3 flex items-center justify-between gap-3 px-3.5 py-2.5 rounded-xl border-2 border-dashed cursor-pointer transition', form.documentos[d.tipo].archivo ? 'border-orange-500/50 bg-orange-500/10' : 'border-white/10 bg-white/5 hover:border-orange-500/40']"
          >
            <span
              class="flex items-center gap-2 text-sm min-w-0"
              :class="form.documentos[d.tipo].archivo ? 'text-orange-200' : 'text-slate-400'"
            >
              <AppIcon v-if="form.documentos[d.tipo].archivo" name="check-circle" :size="16" class="text-emerald-400 shrink-0" />
              <AppIcon v-else name="file" :size="16" class="shrink-0" />
              <span class="truncate">{{ form.documentos[d.tipo].archivo?.name || 'Subir comprobante en PDF...' }}</span>
            </span>
            <input type="file" accept=".pdf" class="hidden" @change="handleArchivo(d.tipo, $event)">
            <span class="btn-ghost !py-1.5 pointer-events-none text-xs">
              <AppIcon name="plus" :size="13" />
              PDF
            </span>
          </label>
        </div>
      </div>

      <div class="mt-4">
        <label class="label" for="perfil-observaciones">Observaciones (opcional)</label>
        <textarea
          id="perfil-observaciones"
          v-model="form.observaciones"
          rows="3"
          class="input resize-none"
          placeholder="Comentarios sobre la revisión de la documentación..."
        ></textarea>
      </div>

      <ul v-if="errores.length" class="mt-4 space-y-1">
        <li v-for="(e, i) in errores" :key="i" class="flex items-start gap-2 text-sm text-red-400">
          <AppIcon name="alert-triangle" :size="15" class="shrink-0 mt-0.5" />
          {{ e }}
        </li>
      </ul>

      <div class="flex justify-end mt-4">
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
import { DOCUMENTOS_PERFIL, validarModulo } from '@/modules/tesis/utils/flujoTitulacion';
import AppIcon from '@/ui/AppIcon.vue';
import ModuloTarjeta from './ModuloTarjeta.vue';

defineProps({
  completado: { type: Boolean, default: false },
  enProgreso: { type: Boolean, default: false },
  bloqueado: { type: Boolean, default: false },
  datos: { type: Object, default: null },
  cargando: { type: Boolean, default: false },
});

const emit = defineEmits(['guardar']);

const form = reactive({
  documentos: Object.fromEntries(
    DOCUMENTOS_PERFIL.map((d) => [d.tipo, { marcado: false, archivo: null }]),
  ),
  observaciones: '',
});

const errores = ref([]);

function handleArchivo(tipo, event) {
  const file = event.target.files[0];
  form.documentos[tipo].archivo = file || null;
}

function obtenerDatos() {
  return {
    documentos: Object.fromEntries(
      Object.entries(form.documentos).map(([tipo, item]) => [
        tipo,
        { marcado: Boolean(item.marcado), archivo: item.archivo ? { nombre: item.archivo.name, tamanio: item.archivo.size } : null },
      ]),
    ),
    observaciones: form.observaciones.trim(),
  };
}

function guardar() {
  const datos = obtenerDatos();
  const res = validarModulo('perfil_tesis', datos);
  if (!res.ok) {
    errores.value = res.errores;
    return;
  }
  errores.value = [];
  emit('guardar', datos);
}
</script>