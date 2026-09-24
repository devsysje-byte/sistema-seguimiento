<template>
  <!-- Fondo de pantalla completa con gradiente moderno (Azul predominante + toques cálidos) -->
  <div class="min-h-screen flex flex-col items-center justify-center p-4 bg-slate-900 relative overflow-hidden">
    
    <!-- Resplandor cálido de fondo (Naranja/Rojo) -->
    <div class="absolute top-[-20%] right-[-10%] w-[500px] h-[500px] bg-orange-600 rounded-full blur-[120px] opacity-30 pointer-events-none"></div>
    <div class="absolute bottom-[-20%] left-[-10%] w-[500px] h-[500px] bg-red-600 rounded-full blur-[120px] opacity-20 pointer-events-none"></div>
    
    <!-- Gradiente base azul oscuro -->
    <div class="absolute inset-0 bg-gradient-to-br from-primary via-primary to-primary pointer-events-none"></div>

    <!-- Título del Sistema (Parte Superior) -->
    <div class="absolute top-8 left-0 right-0 text-center z-10">
      <h1 class="text-2xl md:text-3xl font-extrabold text-white drop-shadow-md tracking-tight">
        Sistema de Seguimiento
      </h1>
      <p class="text-sm md:text-base text-orange-200 font-medium mt-1">
        Modalidad de Titulación
      </p>
    </div>

    <!-- Tarjeta principal del Login -->
    <div class="w-full max-w-sm relative z-10 mt-16">
      
      <!-- Icono superior de Usuario con líneas decorativas -->
      <div class="flex items-center justify-center mb-10 relative">
        <!-- Línea izquierda -->
        <div class="h-[1px] bg-white/20 flex-1 mr-4"></div>
        
        <!-- Círculo con icono (Usando el gradiente cálido) -->
        <div class="w-24 h-24 rounded-full flex items-center justify-center bg-orange shadow-lg shadow-orange-900/40 shrink-0 border border-white/10">
          <AppIcon name="user" :size="40" class="text-white" />
        </div>
        
        <!-- Línea derecha -->
        <div class="h-[1px] bg-white/20 flex-1 ml-4"></div>
      </div>

      <!-- ================= VISTA DE LOGIN ================= -->
      <template v-if="modo === 'login'">
        <form @submit.prevent="handleLogin" class="space-y-5">
          
          <!-- Input Usuario -->
          <div class="flex items-stretch rounded-lg overflow-hidden shadow-md group">
            <div class="bg-white/10 border border-white/10 border-r-0 flex items-center justify-center w-12 shrink-0 group-focus-within:bg-orange-500/20 transition-colors">
              <AppIcon name="user" :size="20" class="text-orange-200" />
            </div>
            <input 
              id="username" 
              v-model="username" 
              type="text" 
              class="w-full bg-slate-800/80 text-white placeholder-slate-400 px-4 py-3.5 focus:outline-none focus:bg-slate-800 focus:ring-1 focus:ring-orange-500 transition-all" 
              placeholder="USUARIO" 
              autocomplete="username" 
              required
            >
          </div>

          <!-- Input Contraseña (con icono de candado) -->

          <div class="flex items-stretch rounded-lg overflow-hidden shadow-md group">
            <div class="bg-white/10 border border-white/10 border-r-0 flex items-center justify-center w-12 shrink-0 group-focus-within:bg-orange-500/20 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-orange-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
            <input 
              id="password" 
              v-model="password" 
              type="password" 
              class="w-full bg-slate-800/80 text-white placeholder-slate-400 px-4 py-3.5 focus:outline-none focus:bg-slate-800 focus:ring-1 focus:ring-orange-500 transition-all" 
              placeholder="********" 
              required
            >
          </div>

          <!-- Botón Login (Degradado Naranja/Rojo para máximo contraste) -->
          <button type="submit" :disabled="ingresando" class="w-full bg-orange text-white font-bold py-3.5 rounded-lg shadow-lg shadow-orange-900/30 active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-2 mt-4">
            <AppIcon v-if="ingresando" name="loader" :size="17" class="animate-spin" />
            <span v-else>LOGIN</span>
          </button>
        </form>

        <!-- Enlaces inferiores (Remember / Forgot) -->
        <div class="flex items-center justify-between text-xs text-slate-300 mt-6 px-1">
          <label class="flex items-center gap-2 cursor-pointer hover:text-white transition-colors">
            <input type="checkbox" class="w-4 h-4 rounded border-slate-500 bg-transparent text-orange-500 focus:ring-orange-400 focus:ring-offset-0">
            <span>Remember me</span>
          </label>
         <!-- <a href="#" class="italic hover:text-orange-300 transition-colors">Forgot your password?</a> -->
        </div>

        <!-- Botón para ir a Registro -->
        <div class="mt-8 text-center border-t border-white/10 pt-6">
          <p class="text-xs text-slate-400 mb-2">¿No tienes cuenta?</p>
          <button 
            type="button" 
            @click="modo = 'registro'" 
            class="text-sm font-semibold text-orange-400 hover:text-orange-300 transition-colors underline decoration-orange-500/30 underline-offset-4"
          >
            REGISTRARSE
          </button>
        </div>
      </template>

      <!-- ================= VISTA DE REGISTRO ================= -->
      <template v-else>
        <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-6 shadow-2xl">
          <h2 class="text-xl font-bold text-white mb-4 text-center">Crea tu cuenta</h2>
          
          <form @submit.prevent="registrarEstudiante" class="space-y-3">
            <input v-model="reg.nombres" class="w-full bg-slate-800/80 text-white placeholder-slate-400 rounded-lg px-4 py-3 focus:outline-none focus:ring-1 focus:ring-orange-500 text-sm border border-white/5" placeholder="Nombres" required>
            <input v-model="reg.apellidos" class="w-full bg-slate-800/80 text-white placeholder-slate-400 rounded-lg px-4 py-3 focus:outline-none focus:ring-1 focus:ring-orange-500 text-sm border border-white/5" placeholder="Apellidos" required>
            
            <div class="grid grid-cols-2 gap-3">
              <input v-model="reg.ci" class="w-full bg-slate-800/80 text-white placeholder-slate-400 rounded-lg px-4 py-3 focus:outline-none focus:ring-1 focus:ring-orange-500 text-sm border border-white/5" placeholder="CI" required>
              <input v-model="reg.registro_universitario" class="w-full bg-slate-800/80 text-white placeholder-slate-400 rounded-lg px-4 py-3 focus:outline-none focus:ring-1 focus:ring-orange-500 text-sm border border-white/5" placeholder="Reg. Univ." required>
            </div>

            <div>
              <label class="block text-xs text-slate-400 mb-1 ml-1">Fecha de Nacimiento</label>
              <input v-model="reg.fecha_nacimiento" type="date" class="w-full bg-slate-800/80 text-white rounded-lg px-4 py-3 focus:outline-none focus:ring-1 focus:ring-orange-500 text-sm border border-white/5 [color-scheme:dark]" required :max="hoy">
            </div>

            <input v-model="reg.email" type="email" class="w-full bg-slate-800/80 text-white placeholder-slate-400 rounded-lg px-4 py-3 focus:outline-none focus:ring-1 focus:ring-orange-500 text-sm border border-white/5" placeholder="Email">
            <input v-model="reg.telefono" class="w-full bg-slate-800/80 text-white placeholder-slate-400 rounded-lg px-4 py-3 focus:outline-none focus:ring-1 focus:ring-orange-500 text-sm border border-white/5" placeholder="Teléfono">

            <button type="submit" :disabled="registrando" class="w-full bg-orange text-white font-bold py-3.5 rounded-lg shadow-lg shadow-orange-900/30 active:scale-[0.98] transition-all duration-200 mt-2 flex items-center justify-center gap-2">
              <AppIcon v-if="registrando" name="loader" :size="17" class="animate-spin" />
              <span v-else>REGISTRARSE</span>
            </button>
          </form>

          <!-- Botón para volver al Login -->
          <div class="mt-6 text-center border-t border-white/10 pt-4">
            <button 
              type="button" 
              @click="modo = 'login'" 
              class="text-sm text-slate-400 hover:text-white transition-colors"
            >
              Volver al Login
            </button>
          </div>
        </div>
      </template>

    </div>

    <!-- Copyright con el año en curso (Parte Inferior) -->
    <div class="absolute bottom-6 left-0 right-0 text-center z-10">
      <p class="text-xs text-slate-500">
        © {{ new Date().getFullYear() }} Sistema de Seguimiento de Titulación. Todos los derechos reservados.
      </p>
    </div>

    <!-- Modal de Error (Mantenido igual) -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showErrorModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4"
          @keydown.escape="closeErrorModal"
          tabindex="0"
          ref="modalOverlay"
        >
          <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="closeErrorModal"></div>
          <div class="relative w-full max-w-sm bg-slate-800 border border-white/10 rounded-2xl shadow-2xl p-8 text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-red-900/50 flex items-center justify-center mb-5 border border-red-500/30">
              <AppIcon name="alert-triangle" :size="28" class="text-red-400" />
            </div>
            <h3 class="text-lg font-bold text-white">Error</h3>
            <p class="mt-2 text-sm text-slate-300 leading-relaxed">{{ errorMessage }}</p>
            <button @click="closeErrorModal" class="mt-6 w-full py-2.5 rounded-xl bg-white text-slate-900 text-sm font-semibold hover:bg-slate-100 active:scale-[0.98] transition-all duration-200">
              Intentar de nuevo
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { homeForRol } from '../guards';
import { estudianteService } from '@/modules/estudiantes/services/estudiantes';
import AppIcon from '@/ui/AppIcon.vue';

const router = useRouter();
const authStore = useAuthStore();

const modo = ref('login'); // 'login' o 'registro'
const username = ref('');
const password = ref('');
const ingresando = ref(false);
const showErrorModal = ref(false);
const errorMessage = ref('');

const reg = ref({ ci: '', nombres: '', apellidos: '', registro_universitario: '', fecha_nacimiento: '', email: '', telefono: '' });
const registrando = ref(false);
const hoy = new Date().toISOString().slice(0, 10);

const closeErrorModal = () => {
  showErrorModal.value = false;
};

const onKeydown = (event) => {
  if (event.key === 'Escape' && showErrorModal.value) {
    closeErrorModal();
  }
};

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));

const openErrorModal = (message) => {
  errorMessage.value = message;
  showErrorModal.value = true;
};

const handleLogin = async () => {
  ingresando.value = true;
  try {
    const result = await authStore.login(username.value, password.value);
    if (result === true) {
      router.push(homeForRol(authStore.user?.rol));
    } else {
      openErrorModal(result || 'Credenciales incorrectas.');
    }
  } catch (err) {
    openErrorModal('No se pudo conectar con el servidor. Inténtalo de nuevo.');
  } finally {
    ingresando.value = false;
  }
};

const registrarEstudiante = async () => {
  registrando.value = true;
  try {
    await estudianteService.registro(reg.value);
    // Al registrarse exitosamente, volvemos al login
    alert('¡Registro exitoso! La Dirección de Carrera generará tus credenciales. Por favor, inicia sesión.');
    modo.value = 'login';
    // Limpiar el formulario de registro
    reg.value = { ci: '', nombres: '', apellidos: '', registro_universitario: '', fecha_nacimiento: '', email: '', telefono: '' };
  } catch (err) {
    const errData = err.response?.data;
    let mensaje = errData?.message;
    if (!mensaje && errData?.errors) {
      const primerError = Object.values(errData.errors).flat()[0];
      mensaje = primerError || null;
    }
    openErrorModal(mensaje || 'No se pudo completar el registro. Verifica tus datos.');
  } finally {
    registrando.value = false;
  }
};
</script>