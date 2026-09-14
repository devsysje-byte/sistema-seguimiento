<template>
  <div class="relative" ref="contenedor">
    <button
      class="relative p-2 rounded-full bg-white ring-1 ring-stone-200 text-stone-500 hover:text-stone-700 hover:bg-stone-50 transition"
      title="Notificaciones"
      @click="toggle"
    >
      <AppIcon name="bell" :size="20" />
      <span
        v-if="contador !== null"
        class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-600 text-[10px] font-bold text-white flex items-center justify-center ring-2 ring-white"
      >
        {{ contador }}
      </span>
    </button>

    <transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 -translate-y-1 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="abierto"
        class="absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-2xl ring-1 ring-stone-200 shadow-xl z-50 overflow-hidden"
      >
        <div class="flex items-center justify-between px-4 py-3 border-b border-stone-100">
          <p class="text-sm font-bold text-stone-900">
            Notificaciones
            <span v-if="store.noLeidas > 0" class="ml-1 text-xs font-semibold text-stone-400">
              ({{ store.noLeidas }} pendiente{{ store.noLeidas === 1 ? '' : 's' }})
            </span>
          </p>
          <button
            v-if="store.noLeidas > 0"
            class="text-xs font-semibold text-amber-600 hover:text-amber-700"
            @click="marcarTodas"
          >
            Marcar todas leídas
          </button>
        </div>

        <div class="max-h-96 overflow-y-auto">
          <template v-if="store.items.length">
            <button
              v-for="n in store.items"
              :key="n.id_notificacion"
              class="w-full text-left px-4 py-3 flex gap-3 hover:bg-stone-50 transition border-b border-stone-50 last:border-0"
              :class="n.leida ? 'opacity-60' : 'bg-amber-50/40'"
              @click="pulsarNotificacion(n)"
            >
              <span
                class="mt-1.5 shrink-0 w-2 h-2 rounded-full"
                :class="n.leida ? 'bg-stone-200' : 'bg-rose-500'"
              ></span>
              <span class="min-w-0">
                <span class="block text-sm font-semibold text-stone-800 truncate">{{ n.titulo }}</span>
                <span v-if="n.mensaje" class="block text-xs text-stone-500 line-clamp-2">{{ n.mensaje }}</span>
                <span class="block text-[11px] text-stone-400 mt-0.5">{{ tiempoRelativo(n.created_at) }}</span>
              </span>
            </button>
          </template>
          <div v-else class="px-4 py-10 text-center">
            <p class="text-sm font-medium text-stone-500">Sin notificaciones</p>
            <p class="text-xs text-stone-400 mt-1">Aquí verás los avisos de tus trámites.</p>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useNotificacionesStore } from '../../stores/notificaciones';
import AppIcon from './AppIcon.vue';

const abierto = ref(false);
const contenedor = ref(null);
const router = useRouter();
const store = useNotificacionesStore();

const contador = computed(() => {
    if (store.noLeidas === 0) return null;
    return store.noLeidas > 99 ? '99+' : store.noLeidas;
});

const toggle = () => {
    abierto.value = !abierto.value;
    if (abierto.value) store.cargar(true);
};

const pulsarNotificacion = async (n) => {
    if (!n.leida) await store.marcarLeida(n.id_notificacion);
    abierto.value = false;
    if (n.enlace) router.push(n.enlace);
};

const marcarTodas = async () => {
    await store.marcarTodasLeidas();
};

const fuera = (event) => {
    if (contenedor.value && !contenedor.value.contains(event.target)) abierto.value = false;
};

const tiempoRelativo = (fechaIso) => {
    const fecha = new Date(fechaIso);
    if (Number.isNaN(fecha.getTime())) return '';
    const seg = Math.floor((Date.now() - fecha.getTime()) / 1000);
    if (seg < 60) return 'ahora';
    const min = Math.floor(seg / 60);
    if (min < 60) return `hace ${min} min`;
    const hor = Math.floor(min / 60);
    if (hor < 24) return `hace ${hor} h`;
    const dias = Math.floor(hor / 24);
    if (dias === 1) return 'ayer';
    return `hace ${dias} días`;
};

let intervalo = null;
onMounted(async () => {
    await store.cargar();
    intervalo = setInterval(() => store.cargar(true), 60000);
    document.addEventListener('click', fuera);
});
onUnmounted(() => {
    clearInterval(intervalo);
    document.removeEventListener('click', fuera);
});
</script>