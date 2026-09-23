/**
 * Guardias de navegación del dominio de autenticación.
 *
 * Viven en el módulo auth porque son su política: exigen sesión y restringen el
 * acceso según rol. El router central (composición raíz) solo las instala.
 */
import { useAuthStore } from '../stores/auth';

/**
 * Ruta inicial por rol tras iniciar sesión (o al redirigir por permisos).
 * Única fuente de verdad: LoginView y las guardias la comparten.
 */
export const HOME_BY_ROL = {
  admin: '/admin',
  estudiante: '/estudiante',
  kardex: '/kardex',
  secretaria: '/kardex',
  direccion: '/kardex',
  docente: '/docente',
};

/**
 * Devuelve la ruta inicial del rol indicado (por defecto el portal del
 * estudiante).
 *
 * @param {string} rol Rol del usuario autenticado.
 * @returns {string} Ruta de destino.
 */
export function homeForRol(rol) {
  return HOME_BY_ROL[rol] || '/estudiante';
}

/**
 * Instala las guardias globales de navegación en el router de la aplicación.
 *
 * - Si la ruta requiere auth y no hay sesión → /login.
 * - Si la ruta es exclusiva de administración y no es admin → redirige.
 * - Si la ruta restringe roles y el rol no está incluido → redirige a la ruta
 *   inicial del propio rol.
 *
 * @param {import('vue-router').Router} router Router de la aplicación.
 */
export function registerAuthGuards(router) {
  router.beforeEach((to) => {
    const authStore = useAuthStore();

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
      return { path: '/login' };
    }

    if (to.meta.requiresAdmin && !authStore.isAdmin) {
      return { path: homeForRol(authStore.user?.rol) };
    }

    if (to.meta.roles && !to.meta.roles.includes(authStore.user?.rol)) {
      return { path: homeForRol(authStore.user?.rol) };
    }

    return true;
  });
}