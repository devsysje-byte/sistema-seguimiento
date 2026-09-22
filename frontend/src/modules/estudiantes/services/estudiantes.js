import http from '@/core/http/client';

/**
 * Servicio de estudiantes. Encapsula los endpoints del perfil académico del
 * estudiante autenticado (consulta y creación/actualización).
 */
export const estudianteService = {
    /** Devuelve el perfil académico del estudiante autenticado (o 404 si no existe). */
    perfil() {
        return http.get('/estudiante/perfil');
    },
    /** Crea o actualiza el perfil académico del estudiante. */
    guardarPerfil(datos) {
        return http.post('/estudiante/perfil', datos);
    },
};