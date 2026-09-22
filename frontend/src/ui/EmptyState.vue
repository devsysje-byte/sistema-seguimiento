<template>
  <div class="mx-auto text-center py-10">
    <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center text-slate-400 bg-slate-100 ring-1 ring-slate-200">
      <AppIcon :name="icon" :size="30" />
    </div>
    <h3 class="mt-4 text-lg font-bold text-slate-700">{{ title }}</h3>
    <p v-if="message" class="mt-1 text-sm text-slate-400 max-w-sm mx-auto">{{ message }}</p>
    <div v-if="$slots.default" class="mt-5">
      <slot />
    </div>
  </div>
</template>

<script setup>
import AppIcon from './AppIcon.vue';

defineProps({
  icon: { type: String, default: 'inbox' },
  title: { type: String, required: true },
  message: { type: String, default: '' },
});
</script>