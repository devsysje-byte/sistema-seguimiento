import http from '@/core/http/client';

/**
 * Servicio de trámites de titulación.
 * Encapsula todos los endpoints del dominio de trámites (modalidades, solicitudes,
 * revisión, transiciones, asignación de tutor y tutorías docentes). Los stores y
 * vistas del módulo lo usan en lugar de llamar a la API directamente.
 */
export const tramitesService = {
    /** Lista las modalidades de titulación activas. */
    modalidades() {
        return http.get('/modalidades');
    },
    /** Devuelve el trámite activo del estudiante autenticado (o null). */
    tramiteActivo() {
        return http.get('/estudiante/tramite-activo');
    },
    /** Crea un trámite enviando un FormData multipart (solicitud del estudiante). */
    crear(formData) {
        return http.post('/tramites', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
    },
    /** Lista las solicitudes en proceso (Kardex/Dirección). */
    pendientes() {
        return http.get('/tramites/pendientes');
    },
    /** Estadísticas de aprobados/reprobados por modalidad. */
    estadisticas() {
        return http.get('/tramites/estadisticas');
    },
    /** Detalle completo de un trámite (incluye siguientes_estados). */
    detalle(id) {
        return http.get(`/tramites/${id}`);
    },
    /** Aprueba o rechaza la documentación inicial de un trámite. */
    revisar(id, accion, observaciones) {
        return http.post(`/tramites/${id}/revisar`, { accion, observaciones });
    },
    /** Ejecuta una transición al siguiente estado del flujo. */
    transicionar(id, nuevoEstado, observaciones) {
        return http.post(`/tramites/${id}/transicionar`, { nuevo_estado: nuevoEstado, observaciones });
    },
    /** Asigna o reasigna el tutor de un trámite. */
    asignarTutor(id, idTutor) {
        return http.post(`/tramites/${id}/asignar-tutor`, { id_tutor: idTutor });
    },
    /** Lista los trámites donde el docente autenticado es tutor. */
    tutorias() {
        return http.get('/tutorias');
    },
    /** Lista los docentes activos para el selector de tutor. */
    docentes() {
        return http.get('/usuarios/docentes');
    },
};