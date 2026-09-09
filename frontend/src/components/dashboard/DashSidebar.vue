<template>
  <div>
    <div
      v-if="open"
      class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm lg:hidden"
      @click="$emit('update:modelValue', false)"
    ></div>

    <aside
      class="fixed inset-y-0 left-0 z-50 flex flex-col bg-white border-r border-slate-200/80 transition-all duration-300"
      :class="[
        collapsed ? 'w-[72px]' : 'w-[260px]',
        { 'translate-x-0': open, '-translate-x-full': !open },
        'lg:translate-x-0'
      ]"
    >
      <div
        class="flex items-center gap-3 px-4 h-16 border-b border-slate-100 shrink-0"
        :class="collapsed ? 'justify-center px-2' : ''"
      >
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-white shadow-md shrink-0">
          <AppIcon name="zap" :size="18" />
        </div>
        <Transition name="fade">
          <div v-if="!collapsed" class="overflow-hidden">
            <p class="text-sm font-bold text-slate-800 leading-tight truncate">Acme Inc.</p>
            <p class="text-[11px] text-slate-400 truncate">Dashboard</p>
          </div>
        </Transition>
      </div>

      <button
        class="hidden lg:flex absolute -right-3 top-[52px] z-10 w-6 h-6 rounded-full bg-white border border-slate-200 shadow-sm items-center justify-center text-slate-400 hover:text-slate-600 hover:border-slate-300 transition"
        @click="$emit('toggle-collapse')"
      >
        <AppIcon :name="collapsed ? 'chevron-right' : 'chevron-left'" :size="14" />
      </button>

      <div class="px-3 py-4" :class="collapsed ? 'px-2' : ''">
        <div
          v-if="!collapsed"
          class="relative mb-4"
        >
          <button
            @click="teamOpen = !teamOpen"
            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 transition text-sm font-medium text-slate-700"
          >
            <span class="w-7 h-7 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-[11px] font-bold shrink-0">T1</span>
            <span class="flex-1 text-left truncate">Team 1</span>
            <AppIcon :name="teamOpen ? 'chevron-down' : 'chevron-right'" :size="14" class="text-slate-400 shrink-0" />
          </button>
          <Transition name="drop">
            <div v-if="teamOpen" class="absolute z-10 left-0 right-0 mt-1 bg-white rounded-xl border border-slate-200 shadow-lg overflow-hidden">
              <button
                v-for="team in teams"
                :key="team.id"
                @click="selectedTeam = team; teamOpen = false"
                class="w-full flex items-center gap-2.5 px-3 py-2.5 text-sm transition"
                :class="selectedTeam.id === team.id ? 'bg-violet-50 text-violet-700 font-semibold' : 'text-slate-600 hover:bg-slate-50'"
              >
                <span
                  class="w-6 h-6 rounded-md flex items-center justify-center text-white text-[10px] font-bold shrink-0"
                  :class="team.color"
                >{{ team.abbr }}</span>
                {{ team.name }}
              </button>
            </div>
          </Transition>
        </div>

        <div v-if="collapsed" class="mb-4 flex justify-center">
          <button
            @click="$emit('toggle-collapse')"
            class="w-10 h-10 rounded-xl bg-slate-50 hover:bg-slate-100 flex items-center justify-center transition"
          >
            <span class="w-7 h-7 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-[11px] font-bold">T1</span>
          </button>
        </div>

        <p
          v-if="!collapsed"
          class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400"
        >Menu</p>

        <nav class="space-y-1">
          <button
            v-for="item in navItems"
            :key="item.id"
            @click="activeItem = item.id"
            class="relative w-full flex items-center gap-2.5 transition rounded-xl text-sm font-medium"
            :class="[
              collapsed ? 'justify-center px-0 py-2.5' : 'px-3 py-2.5',
              activeItem === item.id
                ? 'bg-violet-50 text-violet-700'
                : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700'
            ]"
            :title="collapsed ? item.label : ''"
          >
            <AppIcon :name="item.icon" :size="20" class="shrink-0" />
            <Transition name="fade">
              <span v-if="!collapsed" class="flex-1 text-left">{{ item.label }}</span>
            </Transition>
            <span
              v-if="item.badge && !collapsed"
              class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-violet-100 text-violet-700 text-[11px] font-bold"
            >{{ item.badge }}</span>
            <span
              v-if="item.badge && collapsed"
              class="absolute top-1 right-1 w-2 h-2 rounded-full bg-violet-500"
            ></span>
          </button>
        </nav>
      </div>

      <div class="mt-auto px-3 pb-4" :class="collapsed ? 'px-2' : ''">
        <div
          v-if="!collapsed"
          class="rounded-2xl p-4 bg-gradient-to-br from-violet-500 via-indigo-500 to-blue-500 text-white relative overflow-hidden"
        >
          <div class="absolute -top-6 -right-6 w-20 h-20 bg-white/10 rounded-full blur-xl"></div>
          <div class="absolute -bottom-4 -left-4 w-16 h-16 bg-white/10 rounded-full blur-lg"></div>
          <div class="relative">
            <p class="text-sm font-bold mb-1">More features?</p>
            <p class="text-xs text-white/70 mb-3 leading-relaxed">Upgrade to Pro for unlimited access and premium tools.</p>
            <button class="w-full py-2 rounded-xl bg-white text-violet-700 text-xs font-bold hover:bg-violet-50 transition shadow-sm">
              Upgrade to Pro
            </button>
          </div>
        </div>
        <button
          v-else
          class="w-full flex justify-center py-2.5 rounded-xl bg-violet-50 text-violet-600 hover:bg-violet-100 transition"
          title="Upgrade to Pro"
        >
          <AppIcon name="sparkle" :size="20" />
        </button>
      </div>
    </aside>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import AppIcon from '../ui/AppIcon.vue';

defineProps({
  collapsed: { type: Boolean, default: false },
  modelValue: { type: Boolean, default: false },
});

defineEmits(['toggle-collapse', 'update:modelValue']);

const teamOpen = ref(false);
const activeItem = ref('dashboard');
const selectedTeam = ref({ id: 1, name: 'Team 1', abbr: 'T1', color: 'bg-gradient-to-br from-blue-400 to-blue-600' });

const teams = [
  { id: 1, name: 'Team 1', abbr: 'T1', color: 'bg-gradient-to-br from-blue-400 to-blue-600' },
  { id: 2, name: 'Team 2', abbr: 'T2', color: 'bg-gradient-to-br from-emerald-400 to-emerald-600' },
  { id: 3, name: 'Team 3', abbr: 'T3', color: 'bg-gradient-to-br from-amber-400 to-orange-500' },
];

const navItems = [
  { id: 'dashboard', label: 'Dashboard', icon: 'dash-grid', badge: null },
  { id: 'user', label: 'User', icon: 'user', badge: null },
  { id: 'product', label: 'Product', icon: 'inbox', badge: '+3' },
  { id: 'blog', label: 'Blog', icon: 'file-text', badge: null },
];
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
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
