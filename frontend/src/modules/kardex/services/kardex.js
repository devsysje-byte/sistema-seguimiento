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
    /**
     * Guarda un módulo del flujo de Examen de Grado.
     *
     * Los sorteos son de un solo paso (asistencia, tema y fecha); las notas no
     * se registran aquí, sino en el módulo `asignacion_notas`, que cierra los
     * dos exámenes con su nota final.
     *
     * @param {number} id     Identificador del trámite.
     * @param {string} modulo Módulo del flujo (ver `flujoExamen.js`).
     * @param {Object} datos  Datos validados del formulario.
     */
    guardarModuloExamen(id, modulo, datos) {
        return http.post(`/tramites/${id}/examen/${modulo}`, { datos });
    },
    /**
     * Guarda un módulo del flujo de Excelencia Académica.
     *
     * Los tres módulos son gestionados por KARDEX: verificación del promedio
     * (que puede corregir la nota del estudiante), registro de la Resolución
     * del HCC y reporte final, que titula al estudiante.
     *
     * @param {number} id     Identificador del trámite.
     * @param {string} modulo Módulo del flujo (ver `flujoExcelencia.js`).
     * @param {Object} datos  Datos validados del formulario.
     */
    guardarModuloExcelencia(id, modulo, datos) {
        return http.post(`/tramites/${id}/excelencia/${modulo}`, { datos });
    },
    /**
     * Configuración del módulo de Excelencia Académica (promedio mínimo y rango
     * válido de nota).
     *
     * @returns {Promise} Respuesta con `{ modalidad, nota_minima, rango_nota }`.
     */
    configuracionExcelencia() {
        return http.get('/excelencia/config');
    },
    /**
     * Reabre un trámite de Examen de Grado reprobado o con ausencia para que el
     * estudiante vuelva a sortear el examen. Consume un intento.
     *
     * @param {number} id            Identificador del trámite.
     * @param {string} [observaciones] Motivo de la reapertura.
     */
    reabrirExamen(id, observaciones = null) {
        return http.post(`/tramites/${id}/examen/reabrir`, { observaciones });
    },
    /**
     * Verifica la unicidad de un título de tesis de grado.
     *
     * @param {string} titulo          Título a verificar.
     * @param {number} [excluirTramite] Trámite a omitir de la comparación.
     * @param {number} [excluirEstudiante] Estudiante cuyos trámites se omiten.
     */
    verificarTituloTesis(titulo, excluirTramite = null, excluirEstudiante = null) {
        return http.get('/kardex/titulos-tesis', {
            params: {
                titulo,
                excluir_tramite: excluirTramite || undefined,
                excluir_estudiante: excluirEstudiante || undefined,
            },
        });
    },
    /** Resumen estadístico del dashboard (modalidades, estudiantes y tutores). */
    resumen() {
        return http.get('/kardex/resumen');
    },
    /** Reportes estadísticos por modalidad, mes, año o rango de fechas. */
    reportes(filtros = {}) {
        return http.get('/kardex/reportes', { params: filtros });
    },
};