import axios from 'axios';
import { API_URL } from '@/core/config/env';

/**
 * Cliente HTTP centralizado (axios) para toda la aplicación.
 *
 * - baseURL: apunta a la API de Laravel (leída desde `core/config/env`).
 * - Envía/recibe JSON y adjunta automáticamente el token Bearer a cada petición.
 * - Detecta sesiones expiradas (401) y redirige a la pantalla de login.
 *
 * Los servicios de cada módulo (`src/modules/<dominio>/services`) usan esta instancia;
 * ningún componente debería importar axios directamente.
 */
const http = axios.create({
    baseURL: API_URL,
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
});

// Interceptor de petición: agrega el header Authorization con el token de
// Sanctum almacenado en localStorage, si existe.
http.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

// Interceptor de respuesta: cuando una petición distinta al login devuelve 401,
// limpia las credenciales locales y redirige a la pantalla de login.
http.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401 && error.config?.url !== '/login') {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            localStorage.removeItem('perfilEstudiante');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default http;