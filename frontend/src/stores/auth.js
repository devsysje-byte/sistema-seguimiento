import { defineStore } from 'pinia';
import api from '../services/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('token') || null,
    }),
    getters: {
        isAdmin: (state) => state.user?.rol === 'admin',
        isAuthenticated: (state) => !!state.token,
    },
    actions: {
        async login(email, password) {
            try {
                const { data } = await api.post('/login', { email, password });
                this.token = data.access_token;
                this.user = data.user;
                localStorage.setItem('token', this.token);
                localStorage.setItem('user', JSON.stringify(this.user));
                return true;
            } catch (error) {
                return error.response?.data?.message || 'Error al iniciar sesión';
            }
        },
        async logout() {
            try { await api.post('/logout'); } catch(e){}
            this.token = null; this.user = null;
            localStorage.removeItem('token');
            localStorage.removeItem('user');
        }
    }
});