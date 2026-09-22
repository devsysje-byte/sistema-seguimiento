import http from '@/core/http/client';

/**
 * Servicio de autenticación. Encapsula los endpoints públicos de sesión de la
 * API (login/logout/me) para mantenerlos fuera de los componentes.
 */
export const authService = {
    /** Inicia sesión con email/contraseña y devuelve el token + usuario. */
    login(email, password) {
        return http.post('/login', { email, password });
    },
    /** Invalida el token de la sesión actual. */
    logout() {
        return http.post('/logout');
    },
    /** Devuelve el usuario autenticado. */
    me() {
        return http.get('/me');
    },
};