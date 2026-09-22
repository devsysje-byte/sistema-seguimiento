<template>
  <div v-if="open" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="close"></div>
    <div class="relative flex items-center justify-center min-h-full p-4">
      <div class="card w-full max-h-[calc(100vh-2rem)] overflow-y-auto p-5 sm:p-6 shadow-2xl" :style="{ maxWidth: maxWidth }">
        <div class="flex items-center justify-between mb-5">
          <h3 class="text-lg font-bold text-slate-800">{{ title }}</h3>
          <button class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition" @click="close">
            <AppIcon name="x" :size="20" />
          </button>
        </div>
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
import AppIcon from './AppIcon.vue';

const props = defineProps({
  title: { type: String, required: true },
  maxWidth: { type: String, default: '480px' },
});

const open = defineModel({ type: Boolean, default: false });

const close = () => {
  open.value = false;
};
</script>