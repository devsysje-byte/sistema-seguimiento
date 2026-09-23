import http from '@/core/http/client';

/**
 * Servicio de estudiantes. Encapsula los endpoints del dominio estudiante:
 * la auto-gestión del perfil del estudiante autenticado (consulta y
 * actualización de sus campos académicos) y la gestión administrativa del
 * perfil aislado (alta y listados) que alimenta el panel del admin.
 */
export const estudianteService = {
    /** Devuelve el perfil aislado del estudiante autenticado (o null si no existe). */
    perfil() {
        return http.get('/estudiante/perfil');
    },
    /**
     * Actualiza los campos académicos que el estudiante mantiene por su cuenta
     * (plan de estudios, fecha de conclusión y promedio global).
     */
    guardarPerfil(datos) {
        return http.put('/estudiante/perfil', datos);
    },

    /** Listado paginado de estudiantes (solo admin). */
    index(params) {
        return http.get('/estudiantes', { params });
    },
    /** Crea el perfil aislado de un estudiante (solo admin, botón CREAR ESTUDIANTE). */
    crear(datos) {
        return http.post('/estudiantes', datos);
    },
    /** Listado paginado de estudiantes SIN cuenta de acceso (solo admin, CREAR USUARIO). */
    sinUsuario(params) {
        return http.get('/estudiantes/sin-usuario', { params });
    },
};