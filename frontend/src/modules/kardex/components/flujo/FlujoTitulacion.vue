<template>
  <div class="space-y-5">
    <!-- Resumen del trámite + barra de progreso -->
    <div class="card p-5">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div
            class="w-12 h-12 rounded-full bg-orange-500/15 ring-1 ring-orange-500/40 flex items-center justify-center text-orange-300 font-extrabold"
          >
            {{ iniciales(estudiante) }}
          </div>
          <div>
            <h3 class="font-extrabold text-white">
              {{ estudiante.nombres }} {{ estudiante.apellidos }}
            </h3>
            <p class="text-xs text-slate-400">
              CI {{ estudiante.ci }} · {{ tramite.modalidad?.nombre }}
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2.5">
          <EstadoBadge :estado="estadoBadge" />
        </div>
      </div>

      <div class="mt-6">
        <BarraProgresoTitulacion
          :progreso="progreso"
          :modulos="modulos"
          :estado-actual="estadoBadge"
        />
      </div>
    </div>

    <!-- Hito inicial: Solicitud Presentada -->
    <div class="card p-5 border border-emerald-500/30">
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-300">
            <AppIcon name="inbox" :size="18" />
          </div>
          <div>
            <h4 class="font-bold text-white uppercase">Solicitud Presentada</h4>
            <p class="text-xs text-slate-400">Punto de partida del flujo de titulación</p>
          </div>
        </div>
        <span
          class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide border bg-emerald-500/20 text-emerald-300 border-emerald-500/30"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
          Completado
        </span>
      </div>
      <p v-if="fechaInicio" class="mt-3 text-xs text-slate-400">
        Trámite presentado el <span class="font-semibold text-slate-300">{{ fechaInicio }}</span>
      </p>
    </div>

    <!-- 6 módulos del flujo -->
    <div class="grid lg:grid-cols-2 gap-4">
      <template v-for="(m, i) in modulos" :key="m.id">
        <ModuloPerfilTesis
          v-if="m.id === 'perfil_tesis'"
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="m.bloqueado"
          :datos="m.datos"
          :cargando="i === proximoPendiente && guardando"
          @guardar="(datos) => guardar(m.id, datos)"
        />
        <ModuloAprobacionTema
          v-else-if="m.id === 'aprobacion_tema'"
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="m.bloqueado"
          :datos="m.datos"
          :docentes="docentes"
          :cargando="i === proximoPendiente && guardando"
          @guardar="(datos) => guardar(m.id, datos)"
        />
        <ModuloTribunalRevisor
          v-else-if="m.id === 'tribunal_revisor'"
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="m.bloqueado"
          :datos="m.datos"
          :cargando="i === proximoPendiente && guardando"
          @guardar="(datos) => guardar(m.id, datos)"
        />
        <ModuloDefensa
          v-else-if="m.id === 'defensa'"
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="m.bloqueado"
          :datos="m.datos"
          :cargando="i === proximoPendiente && guardando"
          @guardar="(datos) => guardar(m.id, datos)"
        />
        <ModuloReporte
          v-else-if="m.id === 'reporte'"
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="m.bloqueado"
          :datos="m.datos"
          :tramite="tramite"
          :docentes="docentes"
          :simulado="kardexStore.simulado"
          :cargando="i === proximoPendiente && guardando"
          @guardar="(datos) => guardar(m.id, datos)"
        />
        <ModuloPublicacion
          v-else
          :completado="m.completado"
          :en-progreso="m.enProgreso"
          :bloqueado="m.bloqueado"
          :datos="m.datos"
          :cargando="i === proximoPendiente && guardando"
          @guardar="(datos) => guardar(m.id, datos)"
        />
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useKardexStore } from '../../stores/kardex';
import {
  MODULOS_FLUJO_TITULACION,
  modulosTitulacion,
  proximoModuloPendiente,
  pctTitulacion,
  estadoBadgeTitulacion,
  validarModulo,
} from '@/modules/tesis/utils/flujoTitulacion';
import { fechaLegible, iniciales } from '@/modules/kardex/utils/formato';
import { EstadoBadge } from '@/modules/tramites';
import { useToastStore } from '@/core/stores/toast';
import AppIcon from '@/ui/AppIcon.vue';
import BarraProgresoTitulacion from './BarraProgresoTitulacion.vue';
import ModuloPerfilTesis from './ModuloPerfilTesis.vue';
import ModuloAprobacionTema from './ModuloAprobacionTema.vue';
import ModuloTribunalRevisor from './ModuloTribunalRevisor.vue';
import ModuloDefensa from './ModuloDefensa.vue';
import ModuloReporte from './ModuloReporte.vue';
import ModuloPublicacion from './ModuloPublicacion.vue';

const props = defineProps({
  tramite: { type: Object, default: null },
  docentes: { type: Array, default: () => [] },
});

const emit = defineEmits(['guardado']);

const kardexStore = useKardexStore();
const toastStore = useToastStore();

const guardando = ref(false);

const estudiante = computed(() => props.tramite?.estudiante || {});
const estadoBadge = computed(() => estadoBadgeTitulacion(props.tramite));
const progreso = computed(() => pctTitulacion(props.tramite));
const proximoPendiente = computed(() => proximoModuloPendiente(props.tramite));

const modulos = computed(() => {
  const proximo = proximoPendiente.value;
  return modulosTitulacion(props.tramite).map((m, i) => ({
    ...m,
    enProgreso: !m.completado && i === proximo,
    bloqueado: !m.completado && i !== proximo,
  }));
});

const fechaInicio = computed(() =>
  fechaLegible(props.tramite?.hitos?.solicitud_presentada || props.tramite?.created_at),
);

async function guardar(moduloId, datos) {
  const modulo = MODULOS_FLUJO_TITULACION.find((m) => m.id === moduloId);
  const res = validarModulo(moduloId, datos);
  if (!res.ok) {
    toastStore.error(res.errores.join(' · '));
    return;
  }
  guardando.value = true;
  const actualizado = await kardexStore.guardarModuloFlujo(moduloId, datos);
  guardando.value = false;
  if (actualizado) {
    toastStore.success(`Módulo completado — estado: ${modulo.badge}`);
    emit('guardado', actualizado);
  } else {
    toastStore.error(kardexStore.errorMessage || 'No se pudo guardar el módulo.');
  }
}
</script>