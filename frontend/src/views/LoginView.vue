<template>
  <div class="min-h-screen grid lg:grid-cols-2 bg-slate-50">
    <div class="hidden lg:flex relative flex-col justify-between overflow-hidden bg-slate-950 text-white p-12">
      <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-indigo-600/30 blur-3xl"></div>
      <div class="absolute top-1/3 -right-32 w-96 h-96 rounded-full bg-violet-600/25 blur-3xl"></div>
      <div class="absolute -bottom-24 left-1/4 w-80 h-80 rounded-full bg-sky-600/20 blur-3xl"></div>

      <div class="relative flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center shadow-lg">
          <AppIcon name="graduation" :size="24" />
        </div>
        <div>
          <p class="font-bold leading-tight">Titulación UPEA</p>
          <p class="text-xs text-slate-400">Seguimiento de Modalidades</p>
        </div>
      </div>

      <div class="relative max-w-md">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 ring-1 ring-white/20 text-xs font-semibold text-indigo-200 mb-6">
          <AppIcon name="sparkle" :size="13" />
          Plataforma académica centralizada
        </div>
        <h1 class="text-4xl font-extrabold leading-tight">
          Tu titulación,<br />
          <span class="bg-gradient-to-r from-indigo-400 to-violet-400 bg-clip-text text-transparent">paso a paso.</span>
        </h1>
        <p class="mt-4 text-slate-300">
          Presenta tu modalidad, sube tus documentos y sigue en tiempo real cada etapa de tu trámite, con tu docente tutor junto a vos.
        </p>

        <div class="mt-8 space-y-3">
          <div v-for="item in features" :key="item.title" class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-white/10 ring-1 ring-white/15 flex items-center justify-center text-indigo-300">
              <AppIcon :name="item.icon" :size="16" />
            </div>
            <p class="text-sm text-slate-200">{{ item.title }}</p>
          </div>
        </div>
      </div>

      <p class="relative text-xs text-slate-500">
        © {{ new Date().getFullYear() }} · Sistema de Seguimiento de Titulación
      </p>
    </div>

    <div class="flex items-center justify-center p-6">
      <div class="w-full max-w-md">
        <div class="flex lg:hidden items-center gap-3 mb-8 justify-center">
          <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white shadow-lg">
            <AppIcon name="graduation" :size="24" />
          </div>
          <div>
            <p class="font-bold text-slate-900 leading-tight">Titulación UPEA</p>
            <p class="text-xs text-slate-500">Seguimiento de Modalidades</p>
          </div>
        </div>

        <div class="card p-8 shadow-xl shadow-indigo-200/40">
          <h2 class="text-2xl font-extrabold text-slate-900">Bienvenido de vuelta</h2>
          <p class="mt-1 text-sm text-slate-500">Ingresa con tus credenciales para continuar.</p>

          <form @submit.prevent="handleLogin" class="mt-6 space-y-4">
            <div>
              <label class="label" for="email">Email</label>
              <input id="email" v-model="email" type="email" class="input" placeholder="tucorreo@upea.bo" required>
            </div>

            <div>
              <label class="label" for="password">Contraseña</label>
              <div class="relative">
                <input id="password" v-model="password" :type="showPassword ? 'text' : 'password'" class="input pr-11" placeholder="••••••••" required>
                <button type="button" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600" @click="showPassword = !showPassword">
                  <AppIcon :name="showPassword ? 'eye-off' : 'eye'" :size="19" />
                </button>
              </div>
            </div>

            <div v-if="error" class="flex items-start gap-2 px-3.5 py-3 rounded-xl bg-rose-50 ring-1 ring-rose-200 text-sm text-rose-700">
              <AppIcon name="x-circle" :size="17" class="mt-0.5 shrink-0" />
              {{ error }}
            </div>

            <button type="submit" :disabled="ingresando" class="btn-primary w-full py-3">
              <AppIcon v-if="ingresando" name="loader" :size="17" class="animate-spin" />
              <AppIcon v-else name="send" :size="16" />
              {{ ingresando ? 'Ingresando...' : 'Ingresar' }}
            </button>
          </form>
        </div>

        <p class="mt-6 text-center text-xs text-slate-400">
          ¿Problemas para acceder? Contacta al Administrador de la facultad.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import AppIcon from '../components/ui/AppIcon.vue';

const router = useRouter();
const authStore = useAuthStore();
const email = ref('');
const password = ref('');
const error = ref('');
const showPassword = ref(false);
const ingresando = ref(false);

const features = [
  { title: 'Solicita tu modalidad de titulación en línea', icon: 'file-text' },
  { title: 'Seguimiento transparente en línea de tiempo', icon: 'trending-up' },
  { title: 'Tutoría directa con docentes asignados', icon: 'user-check' },
  { title: 'Historial completo de aprobaciones', icon: 'clipboard' },
];

const handleLogin = async () => {
  error.value = '';
  ingresando.value = true;
  const result = await authStore.login(email.value, password.value);
  ingresando.value = false;
  if (result === true) {
    const byRol = {
      admin: '/admin',
      estudiante: '/estudiante',
      kardex: '/kardex',
      secretaria: '/kardex',
      direccion: '/kardex',
      concejo: '/kardex',
      docente: '/docente',
    };
    router.push(byRol[authStore.user?.rol] || '/estudiante');
  } else {
    error.value = result;
  }
};
</script>