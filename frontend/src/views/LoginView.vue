<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
      <h2 class="text-2xl font-bold mb-6 text-center text-blue-800">Sistema de Titulación</h2>
      <form @submit.prevent="handleLogin">
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
          <input v-model="email" type="email" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" required>
        </div>
        <div class="mb-6">
          <label class="block text-gray-700 text-sm font-bold mb-2">Contraseña</label>
          <input v-model="password" type="password" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" required>
        </div>
        <p v-if="error" class="text-red-500 text-sm mb-4">{{ error }}</p>
        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">Ingresar</button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();
const email = ref('');
const password = ref('');
const error = ref('');

const handleLogin = async () => {
  error.value = '';
  const result = await authStore.login(email.value, password.value);
  if (result === true) {
    const byRol = {
      admin: '/admin',
      estudiante: '/estudiante',
      kardex: '/kardex',
      secretaria: '/kardex',
      direccion: '/kardex',
      concejo: '/kardex',
      docente: '/kardex'
    };
    router.push(byRol[authStore.user?.rol] || '/estudiante');
  } else {
    error.value = result;
  }
};
</script>