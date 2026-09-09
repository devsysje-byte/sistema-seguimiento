<template>
  <div class="min-h-screen bg-slate-100">
    <div class="lg:flex">
      <div
        v-if="open"
        class="fixed inset-0 z-40 bg-slate-900/60 lg:hidden"
        @click="open = false"
      ></div>

      <aside
        class="fixed inset-y-0 left-0 z-50 w-72 bg-slate-900 flex flex-col transition-transform duration-300 lg:translate-x-0 lg:static"
        :class="open ? 'translate-x-0' : '-translate-x-full'"
      >
        <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-800">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white shadow-lg">
            <AppIcon name="graduation" :size="22" />
          </div>
          <div>
            <p class="text-white font-bold leading-tight">Titulación UPEA</p>
            <p class="text-xs text-slate-400">Seguimiento de Modalidades</p>
          </div>
        </div>

        <nav class="flex-1 px-3 py-5 space-y-1">
          <p class="px-3 mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">Menú</p>
          <router-link
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            @click="open = false"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition"
            :class="isActive(item.to) ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
          >
            <AppIcon :name="item.icon" :size="19" />
            {{ item.label }}
          </router-link>
        </nav>

        <div class="px-4 py-5 border-t border-slate-800">
          <div class="flex items-center gap-3 px-2 py-3 rounded-xl bg-slate-800/50">
            <Avatar :nombres="authStore.user?.nombres" :apellidos="authStore.user?.apellidos" size="10" />
            <div class="min-w-0 flex-1">
              <p class="text-sm font-semibold text-white truncate">
                {{ authStore.user?.nombres }} {{ authStore.user?.apellidos }}
              </p>
              <p class="text-xs text-slate-400 capitalize">{{ rolLabel(authStore.user?.rol) }}</p>
            </div>
          </div>
          <button class="mt-3 w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-300 hover:text-white hover:bg-rose-600 transition" @click="logout">
            <AppIcon name="logout" :size="17" />
            Cerrar Sesión
          </button>
        </div>
      </aside>

      <div class="flex-1 flex flex-col min-w-0">
        <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-slate-200">
          <div class="flex items-center gap-3 px-4 sm:px-6 lg:px-8 py-4">
            <button class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 lg:hidden" @click="open = true">
              <AppIcon name="menu" :size="22" />
            </button>
            <div class="min-w-0">
              <h1 class="text-lg sm:text-xl font-bold text-slate-900 truncate">{{ title }}</h1>
              <p v-if="subtitle" class="text-xs sm:text-sm text-slate-500 truncate">{{ subtitle }}</p>
            </div>
            <div class="ml-auto flex items-center gap-3">
              <div class="hidden sm:flex items-center gap-2.5 px-3 py-1.5 rounded-full bg-white ring-1 ring-slate-200">
                <Avatar :nombres="authStore.user?.nombres" :apellidos="authStore.user?.apellidos" size="8" />
                <span class="text-sm font-semibold text-slate-700">
                  {{ authStore.user?.nombres }} {{ authStore.user?.apellidos }}
                </span>
              </div>
            </div>
          </div>
        </header>

        <main class="flex-1 p-4 sm:p-6 lg:p-8 w-full max-w-7xl mx-auto">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { rolLabel } from '../../utils/estados';
import AppIcon from './AppIcon.vue';
import Avatar from './Avatar.vue';

defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
});

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const open = ref(false);

watch(open, (value) => {
  document.body.style.overflow = value ? 'hidden' : '';
});

const navItems = computed(() => {
  const rol = authStore.user?.rol;
  const items = [];
  if (rol === 'admin') items.push({ to: '/admin', label: 'Usuarios', icon: 'users' });
  if (['kardex', 'secretaria', 'direccion', 'concejo', 'admin'].includes(rol)) {
    items.push({ to: '/kardex', label: 'Trámites', icon: 'folder' });
  }
  if (rol === 'docente') items.push({ to: '/docente', label: 'Mis Tutorías', icon: 'book' });
  if (rol === 'estudiante') items.push({ to: '/estudiante', label: 'Mi Trámite', icon: 'graduation' });
  return items;
});

const isActive = (to) => {
  if (to === '/kardex') return route.path.startsWith('/kardex') || route.path.startsWith('/tramites/');
  return route.path === to;
};

const logout = () => {
  authStore.logout();
  window.location.href = '/login';
};
</script>