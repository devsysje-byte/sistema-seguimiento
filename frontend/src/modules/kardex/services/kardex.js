import http from '@/core/http/client';

/**
 * Servicio del Dashboard de KARDEX.
 *
 * Encapsula los endpoints del módulo backend Kardex: búsqueda de postulantes
 * por CI o registro universitario, asignación de modalidad con generación
 * automática de credenciales y consulta del flujo del postulante. Las vistas y
 * el store del módulo lo usan en lugar de llamar a la API directamente.
 */
export const kardexService = {
    /** Lista las modalidades de titulación activas (catálogo compartido). */
    modalidades() {
        return http.get('/modalidades');
    },
    /** Busca un postulante por CI o registro universitario. */
    buscarPostulante(identificador) {
        return http.post('/kardex/postulantes/buscar', { identificador });
    },
    /** Asigna la modalidad al postulante y genera sus credenciales (una vez). */
    asignarModalidad(identificador, idModalidad) {
        return http.post('/kardex/postulantes/asignar-modalidad', {
            identificador,
            id_modalidad: idModalidad,
        });
    },
    /** Consulta el flujo actual del postulante (trámite más reciente). */
    consultar(identificador) {
        return http.get('/kardex/postulantes/consultar', { params: { identificador } });
    },
    /** Guarda un módulo del flujo de titulación y avanza el estado del trámite. */
    guardarModuloFlujo(id, modulo, datos) {
        return http.post(`/tramites/${id}/flujo/${modulo}`, { datos });
    },
    /** Resumen estadístico del dashboard (modalidades, estudiantes y tutores). */
    resumen() {
        return http.get('/kardex/resumen');
    },
};