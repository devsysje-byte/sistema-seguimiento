<template>
  <div class="fixed top-5 inset-x-0 z-[100] flex flex-col items-center gap-3 pointer-events-none px-4">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toastStore.toasts"
        :key="toast.id"
        role="alert"
        class="pointer-events-auto relative overflow-hidden w-full max-w-md rounded-2xl ring-1 shadow-xl backdrop-blur-sm flex items-start gap-3 px-4 py-3.5"
        :class="toastClasses[toast.type].card"
      >
        <span
          class="shrink-0 w-9 h-9 rounded-xl flex items-center justify-center"
          :class="toastClasses[toast.type].icon"
        >
          <AppIcon :name="iconFor(toast.type)" :size="19" />
        </span>
        <p class="text-sm font-semibold leading-snug flex-1 :text-stone-800">{{ toast.message }}</p>
        <button
          class="shrink-0 p-1 rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-600 transition"
          @click="toastStore.remove(toast.id)"
        >
          <AppIcon name="x" :size="15" />
        </button>
        <span class="absolute bottom-0 left-0 h-1 toast-progress" :class="toastClasses[toast.type].progress" :style="{ animationDuration: toast.duration + 'ms' }"></span>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { useToastStore } from '../../stores/toast';
import AppIcon from './AppIcon.vue';

const toastStore = useToastStore();

const toastClasses = {
  success: {
    card: 'bg-emerald-50 ring-emerald-200',
    icon: 'bg-emerald-100 text-emerald-600',
    progress: 'bg-emerald-400',
  },
  error: {
    card: 'bg-rose-50 ring-rose-200',
    icon: 'bg-rose-100 text-rose-600',
    progress: 'bg-rose-400',
  },
  warning: {
    card: 'bg-amber-50 ring-amber-200',
    icon: 'bg-amber-100 text-amber-600',
    progress: 'bg-amber-400',
  },
  info: {
    card: 'bg-sky-50 ring-sky-200',
    icon: 'bg-sky-100 text-sky-600',
    progress: 'bg-sky-400',
  },
};

const iconFor = (type) => ({
  success: 'check-circle',
  error: 'x-circle',
  warning: 'alert-triangle',
  info: 'info',
}[type] || 'info');
</script>

<style scoped>
.toast-move,
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateY(-16px) scale(0.95);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(28px);
}

.toast-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  transform-origin: left;
  animation-name: toast-progress;
  animation-timing-function: linear;
  animation-fill-mode: forwards;
  border-bottom-left-radius: 1rem;
  border-bottom-right-radius: 1rem;
}

@keyframes toast-progress {
  from {
    transform: scaleX(1);
  }
  to {
    transform: scaleX(0);
  }
}
</style>