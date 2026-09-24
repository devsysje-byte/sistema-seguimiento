<template>
  <div class="min-h-screen grid lg:grid-cols-2 bg-stone-50">
    <div class="hidden lg:flex relative flex-col justify-between overflow-hidden bg-stone-950 text-white p-12">
      <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-amber-600/30 blur-3xl"></div>
      <div class="absolute top-1/3 -right-32 w-96 h-96 rounded-full bg-orange-600/25 blur-3xl"></div>
      <div class="absolute -bottom-24 left-1/4 w-80 h-80 rounded-full bg-rose-600/20 blur-3xl"></div>

      <div class="relative flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg">
          <AppIcon name="graduation" :size="24" />
        </div>
        <div>
          <p class="font-bold leading-tight">Titulación UPEA</p>
          <p class="text-xs text-stone-400">Seguimiento de Modalidades de Graduación</p>
        </div>
      </div>

      <div class="relative max-w-md">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 ring-1 ring-white/20 text-xs font-semibold text-amber-200 mb-6">

          Plataforma académica centralizada
        </div>
        <h1 class="text-4xl font-extrabold leading-tight">
          Tu titulación,<br />
          <span class="bg-gradient-to-r from-amber-400 to-orange-400 bg-clip-text text-transparent">paso a paso.</span>
        </h1>
        <p class="mt-4 text-stone-300">
          Regístrate como estudiante, presenta tu modalidad, sube tus documentos y sigue en tiempo real cada etapa de tu trámite, con tu docente tutor junto a ti.
        </p>

        <div class="mt-8 space-y-3">
          <div
            v-for="item in features"
            :key="item.title"
            class="group flex items-center gap-4 p-3 rounded-xl bg-white/[0.06] hover:bg-white/[0.1] ring-1 ring-white/[0.08] hover:ring-amber-400/20 transition-all duration-300"
          >
            <div
              class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-400/20 to-orange-500/20 ring-1 ring-amber-400/25 flex items-center justify-center text-amber-300 group-hover:text-amber-200 group-hover:scale-105 transition-all duration-300 shrink-0"
            >
              <AppIcon :name="item.icon" :size="18" />
            </div>
            <p class="text-sm text-stone-300 group-hover:text-stone-200 transition-colors duration-300 leading-snug">
              {{ item.title }}
            </p>
          </div>
        </div>
      </div>

      <p class="relative text-xs text-stone-500">
        © {{ new Date().getFullYear() }} · Sistema de Seguimiento de Titulación
      </p>
    </div>

    <div class="flex items-center justify-center p-6">
      <div class="w-full max-w-md">
        <div class="flex lg:hidden items-center gap-3 mb-8 justify-center">
          <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white shadow-lg">
            <AppIcon name="graduation" :size="24" />
          </div>
          <div>
            <p class="font-bold text-stone-900 leading-tight">Titulación UPEA</p>
            <p class="text-xs text-stone-500">Seguimiento de Modalidades</p>
          </div>
        </div>

        <div class="card p-8 shadow-xl shadow-amber-200/40">
          <div class="flex bg-stone-100 p-1 rounded-xl mb-6">
            <button
              type="button"
              class="flex-1 py-2 rounded-lg text-sm font-semibold transition"
              :class="modo === 'login' ? 'bg-white shadow text-stone-900' : 'text-stone-500 hover:text-stone-700'"
              @click="modo = 'login'"
            >
              Ingresar
            </button>
            <button
              type="button"
              class="flex-1 py-2 rounded-lg text-sm font-semibold transition"
              :class="modo === 'registro' ? 'bg-white shadow text-stone-900' : 'text-stone-500 hover:text-stone-700'"
              @click="modo = 'registro'"
            >
              Registrarse
            </button>
          </div>

          <template v-if="registrado">
            <div class="text-center py-4">
              <div class="mx-auto w-14 h-14 rounded-2xl bg-emerald-50 ring-1 ring-emerald-200 flex items-center justify-center text-emerald-600">
                <AppIcon name="check-circle" :size="28" />
              </div>
              <h2 class="mt-4 text-xl font-extrabold text-stone-900">¡Registro exitoso!</h2>
              <p class="mt-2 text-sm text-stone-500 leading-relaxed">
                Tu perfil de estudiante fue registrado. La Dirección de Carrera / Kardex
                generará tu <strong>usuario y contraseña</strong> de acceso para que puedas
                presentar tu modalidad de graduación.
              </p>
            </div>
          </template>

          <template v-else-if="modo === 'login'">
            <h2 class="text-xl font-extrabold text-stone-900">Bienvenido de vuelta</h2>
            <p class="mt-1 text-sm text-stone-500">Ingresa con tus credenciales para continuar.</p>

            <form @submit.prevent="handleLogin" class="mt-6 space-y-4">
              <div>
                <label class="label" for="username">Usuario</label>
                <input id="username" v-model="username" type="text" class="input" placeholder="p. ej. anabel_2273515" autocomplete="username" required>
              </div>

              <div>
                <label class="label" for="password">Contraseña</label>
                <div class="relative">
                  <input id="password" v-model="password" :type="showPassword ? 'text' : 'password'" class="input pr-11" placeholder="••••••••" required>
                  <button type="button" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-stone-400 hover:text-stone-600" @click="showPassword = !showPassword">
                    <AppIcon :name="showPassword ? 'eye-off' : 'eye'" :size="19" />
                  </button>
                </div>
              </div>

              <button type="submit" :disabled="ingresando" class="btn-primary w-full py-3">
                <AppIcon v-if="ingresando" name="loader" :size="17" class="animate-spin" />
                <AppIcon v-else name="send" :size="16" />
                {{ ingresando ? 'Ingresando...' : 'Ingresar' }}
              </button>
            </form>
          </template>

          <template v-else>
            <h2 class="text-xl font-extrabold text-stone-900">Crea tu cuenta de estudiante</h2>
            <p class="mt-1 text-sm text-stone-500">Regístrate y la Dirección de Carrera generará tu usuario y contraseña.</p>

            <form @submit.prevent="registrarEstudiante" class="mt-6 space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="label">Nombres</label>
                  <input v-model="reg.nombres" class="input" placeholder="Nombre(s)" required>
                </div>
                <div>
                  <label class="label">Apellidos</label>
                  <input v-model="reg.apellidos" class="input" placeholder="Apellido(s)" required>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="label">CI</label>
                  <input v-model="reg.ci" class="input" placeholder="1234567" required>
                </div>
                <div>
                  <label class="label">Registro Universitario</label>
                  <input v-model="reg.registro_universitario" class="input" placeholder="2020-0001" required>
                </div>
              </div>

              <div>
                <label class="label">Fecha de Nacimiento</label>
                <p class="text-xs text-stone-400 -mt-1 mb-1">Se usa para generar la contraseña inicial (dd-mm-aaaa).</p>
                <input v-model="reg.fecha_nacimiento" type="date" class="input" required :max="hoy">
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="label">Email</label>
                  <input v-model="reg.email" type="email" class="input" placeholder="correo@upea.bo">
                </div>
                <div>
                  <label class="label">Teléfono</label>
                  <input v-model="reg.telefono" class="input" placeholder="59170000000">
                </div>
              </div>

              <button type="submit" :disabled="registrando" class="btn-primary w-full py-3">
                <AppIcon v-if="registrando" name="loader" :size="17" class="animate-spin" />
                <AppIcon v-else name="user-plus" :size="16" />
                {{ registrando ? 'Registrando...' : 'Registrarme' }}
              </button>
            </form>
          </template>
        </div>

        <p class="mt-6 text-center text-xs text-stone-400">
          ¿Problemas para acceder? Contacta al Administrador de la carrera.
        </p>
      </div>
    </div>
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showErrorModal"
          class="fixed inset-0 z-50 flex items-center justify-center p-4"
          @keydown.escape="closeErrorModal"
          tabindex="0"
          ref="modalOverlay"
        >
          <div class="absolute inset-0 bg-stone-950/60 backdrop-blur-sm" @click="closeErrorModal"></div>

          <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl shadow-rose-900/20 p-8 text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-rose-100 flex items-center justify-center mb-5">
              <AppIcon name="alert-triangle" :size="28" class="text-rose-500" />
            </div>

            <h3 class="text-lg font-bold text-stone-900">Error</h3>
            <p class="mt-2 text-sm text-stone-500 leading-relaxed">{{ errorMessage }}</p>

            <button
              @click="closeErrorModal"
              class="mt-6 w-full py-2.5 rounded-xl bg-stone-900 text-white text-sm font-semibold hover:bg-stone-800 active:scale-[0.98] transition-all duration-200"
            >
              Intentar de nuevo
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
// Vista de inicio de sesión / auto-registro.
// Muestra dos modos: "Ingresar" (usuario/contraseña) y "Registrarse" (el
// estudiante crea su perfil aislado). Tras el auto-registro el estudiante
// recibe usuario y contraseña generados por la instancia académica (CREAR
// USUARIO) y ya no edita su perfil: solo consulta sus datos y las modalidades.
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { homeForRol } from '../guards';
import { estudianteService } from '@/modules/estudiantes/services/estudiantes';
import AppIcon from '@/ui/AppIcon.vue';

const router = useRouter();
const authStore = useAuthStore();

// Modo activo del panel: 'login' o 'registro'.
const modo = ref('login');

// Campos del formulario y controles de UI (login).
const username = ref('');
const password = ref('');
const showPassword = ref(false);   // Alterna visibilidad de la contraseña.
const ingresando = ref(false);     // true mientras se procesa el login.
const showErrorModal = ref(false); // Controla el modal de error.
const errorMessage = ref('');      // Mensaje de error a mostrar.

// Formulario de auto-registro del estudiante.
const reg = ref({ ci: '', nombres: '', apellidos: '', registro_universitario: '', fecha_nacimiento: '', email: '', telefono: '' });
const registrando = ref(false);
const registrado = ref(false);
const hoy = new Date().toISOString().slice(0, 10);

/** Cierra el modal de error. */
const closeErrorModal = () => {
  showErrorModal.value = false;
};

// Cierra el modal con la tecla Escape.
const onKeydown = (event) => {
  if (event.key === 'Escape' && showErrorModal.value) {
    closeErrorModal();
  }
};

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));

/** Abre el modal de error con el mensaje indicado. */
const openErrorModal = (message) => {
  errorMessage.value = message;
  showErrorModal.value = true;
};

// Características destacadas mostradas en el panel lateral informativo.
const features = [
  { title: 'Regístrate y solicita tu modalidad de titulación en línea', icon: 'layers' },
  { title: 'Seguimiento transparente en línea de tiempo', icon: 'eye' },
  { title: 'Tutoría directa con docentes asignados', icon: 'users' },
  { title: 'Historial completo de aprobaciones', icon: 'check-square' },
];

/**
 * Autentica con POST /api/login. Si el login es exitoso redirige según el rol;
 * en caso de error muestra un modal con el mensaje devuelto por la API.
 */
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
    // Error de red / servidor no disponible.
    openErrorModal('No se pudo conectar con el servidor. Inténtalo de nuevo.');
  } finally {
    ingresando.value = false;
  }
};

/**
 * Registra al estudiante vía POST /api/estudiantes/registro. Al completarse
 * muestra el aviso de éxito; las credenciales las generará la instancia
 * académica.
 */
const registrarEstudiante = async () => {
  registrando.value = true;
  try {
    await estudianteService.registro(reg.value);
    registrado.value = true;
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