<template>
  <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-xl border-b border-slate-100">
    <div class="flex items-center gap-4 h-16 px-4 sm:px-6 lg:px-8">
      <button
        class="p-2 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition lg:hidden"
        @click="$emit('toggle-sidebar')"
      >
        <AppIcon name="menu" :size="22" />
      </button>

      <div class="min-w-0 flex-1">
        <h1 class="text-lg sm:text-xl font-extrabold text-slate-900 tracking-tight">
          Hi, Welcome back <span class="inline-block">👋</span>
        </h1>
        <p class="text-xs text-slate-400 mt-0.5 hidden sm:block">Here's what's happening today.</p>
      </div>

      <div class="flex items-center gap-1.5 sm:gap-2">
        <button
          class="hidden md:flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition text-sm"
        >
          <AppIcon name="search" :size="18" />
          <span class="text-slate-400 text-sm">Search...</span>
          <kbd class="ml-4 text-[10px] font-bold text-slate-300 bg-white px-1.5 py-0.5 rounded-md ring-1 ring-slate-200">⌘K</kbd>
        </button>

        <button class="md:hidden p-2.5 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
          <AppIcon name="search" :size="20" />
        </button>

        <button
          @click="langOpen = !langOpen"
          class="relative p-2.5 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition"
        >
          <AppIcon name="globe" :size="20" />
          <Transition name="drop">
            <div v-if="langOpen" class="absolute right-0 top-full mt-2 w-40 bg-white rounded-xl border border-slate-200 shadow-xl overflow-hidden z-50">
              <button
                v-for="lang in languages"
                :key="lang.code"
                @click="selectedLang = lang; langOpen = false"
                class="w-full flex items-center gap-2.5 px-3.5 py-2.5 text-sm transition"
                :class="selectedLang.code === lang.code ? 'bg-violet-50 text-violet-700 font-semibold' : 'text-slate-600 hover:bg-slate-50'"
              >
                <span class="text-base">{{ lang.flag }}</span>
                {{ lang.name }}
              </button>
            </div>
          </Transition>
        </button>

        <button class="relative p-2.5 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
          <AppIcon name="bell" :size="20" />
          <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 rounded-full bg-red-500 ring-2 ring-white"></span>
        </button>

        <div class="hidden sm:block w-px h-8 bg-slate-200 mx-1"></div>

        <button class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-xl hover:bg-slate-50 transition">
          <Avatar :nombres="userName" :apellidos="userLastName" size="10" />
          <div class="hidden sm:block text-left">
            <p class="text-sm font-semibold text-slate-700 leading-tight">{{ userName }} {{ userLastName }}</p>
            <p class="text-[11px] text-slate-400">Admin</p>
          </div>
          <AppIcon name="chevron-down" :size="14" class="text-slate-400 hidden sm:block" />
        </button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref } from 'vue';
import AppIcon from '../ui/AppIcon.vue';
import Avatar from '../ui/Avatar.vue';
import { useAuthStore } from '../../stores/auth';

defineEmits(['toggle-sidebar']);

const authStore = useAuthStore();
const userName = authStore.user?.nombres || 'Admin';
const userLastName = authStore.user?.apellidos || 'User';

const langOpen = ref(false);
const selectedLang = ref({ code: 'en', name: 'English', flag: '🇺🇸' });

const languages = [
  { code: 'en', name: 'English', flag: '🇺🇸' },
  { code: 'es', name: 'Español', flag: '🇪🇸' },
  { code: 'fr', name: 'Français', flag: '🇫🇷' },
  { code: 'pt', name: 'Português', flag: '🇧🇷' },
];
</script>

<style scoped>
.drop-enter-active {
  transition: all 0.2s ease-out;
}
.drop-leave-active {
  transition: all 0.15s ease-in;
}
.drop-enter-from {
  opacity: 0;
  transform: translateY(-6px) scale(0.97);
}
.drop-leave-to {
  opacity: 0;
  transform: translateY(-4px) scale(0.97);
}
</style>
