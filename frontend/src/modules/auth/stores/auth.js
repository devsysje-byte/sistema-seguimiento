import { defineStore } from 'pinia';
import { authService } from '../services/auth';

/**
 * Store de autenticación.
 *
 * Gestiona la sesión del usuario (datos y token) manteniéndolos sincronizados
 * con localStorage, e implementa el ingreso y cierre de sesión contra la API.
 */
export const useAuthStore = defineStore('auth', {
    // Estado inicial: restaura la sesión desde localStorage si existía.
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('token') || null,
    }),
    getters: {
        // true si el usuario autenticado tiene rol admin.
        isAdmin: (state) => state.user?.rol === 'admin',
        // true mientras exista token almacenado (sesión considerada activa).
        isAuthenticated: (state) => !!state.token,
    },
    actions: {
        /**
         * Inicia sesión con username y contraseña contra POST /api/login.
         *
         * @param {string} username Usuario de acceso (autogenerado en estudiantes).
         * @param {string} password Contraseña sin cifrar.
         * @returns {Promise<boolean|string>} `true` si el ingreso fue exitoso; en
         *          caso contrario, el mensaje de error devuelto por la API.
         */
        async login(username, password) {
            try {
                const { data } = await authService.login(username, password);
                this.token = data.access_token;
                this.user = data.user;
                localStorage.setItem('token', this.token);
                localStorage.setItem('user', JSON.stringify(this.user));
                return true;
            } catch (error) {
                return error.response?.data?.message || 'Error al iniciar sesión';
            }
        },
        /**
         * Cierra la sesión: invalida el token en la API y limpia el estado y
         * localStorage (incluido el perfil de estudiante en caché).
         *
         * @returns {Promise<void>}
         */
        async logout() {
            try { await authService.logout(); } catch(e){}
            this.token = null; this.user = null;
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            localStorage.removeItem('perfilEstudiante');
        }
    }
});