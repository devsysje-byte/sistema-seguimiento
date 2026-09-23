import http from '@/core/http/client';

/**
 * Servicio de gestión de usuarios (Panel de Administración).
 * Encapsula el CRUD de la matriz de actores: listado, alta, actualización y
 * baja lógica, además del listado de docentes activos para asignación de tutor.
 */
export const usuariosService = {
    /** Lista los usuarios activos de la plataforma (respuesta paginada { data, meta }). */
    index(params = {}) {
        return http.get('/usuarios', { params: { page: 1, per_page: 20, ...params } });
    },
    /** Crea un usuario (y su perfil de estudiante si el rol lo requiere). */
    crear(datos) {
        return http.post('/usuarios', datos);
    },
    /** Actualiza un usuario y sus datos opcionales (perfil de estudiante, contraseña). */
    actualizar(id, datos) {
        return http.put(`/usuarios/${id}`, datos);
    },
    /** Da de baja lógica a un usuario. */
    eliminar(id) {
        return http.delete(`/usuarios/${id}`);
    },
    /** Lista los usuarios con rol docente (para el selector de tutor). */
    docentes() {
        return http.get('/usuarios/docentes');
    },
};