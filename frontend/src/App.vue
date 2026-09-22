<script setup>
// Componente raíz de la aplicación.
// Renderiza la vista correspondiente a la ruta activa dentro de una transición
// de entrada/salida y monta el contenedor global de toasts (notificaciones).
import ToastContainer from '@/ui/ToastContainer.vue';
</script>

<template>
  <router-view v-slot="{ Component }">
    <transition name="page" mode="out-in">
      <component :is="Component" />
    </transition>
  </router-view>
  <ToastContainer />
</template>