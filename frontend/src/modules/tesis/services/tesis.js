import http from '@/core/http/client';

/**
 * Servicio del Módulo de Tesis de Grado (lado del estudiante).
 *
 * Encapsula los endpoints del flujo oficial de la tesis: presentación de la
 * solicitud con los 3 documentos obligatorios, reenvío del perfil tras su
 * rechazo y consulta de la configuración del módulo. El trámite activo se lee
 * con el servicio genérico de trámites (el tesis pepper la misma cola).
 */
export const tesisService = {
    /** Presenta la solicitud de tesis con FormData (3 PDF obligatorios). */
    crearSolicitud(formData) {
        return http.post('/tesis/solicitud', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
    },
    /** Reenvía el perfil corregido tras su rechazo (FormData con `perfil`). */
    reenviarPerfil(id, formData) {
        return http.post(`/tesis/${id}/reenviar`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
    },
    /** Reenvía el documento final corregido tras ser insuficiente (FormData con `documento_final`). */
    reenviarDocumento(id, formData) {
        return http.post(`/tesis/${id}/reenviar-documento`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
    },
    /** Solicita una fecha para la defensa de la tesis (la programa Kardex). */
    solicitarFechaDefensa(id, fechaSugerida) {
        return http.post(`/tesis/${id}/solicitar-fecha-defensa`, { fecha_sugerida: fechaSugerida || null });
    },
    /** Configuración del módulo (plazo de presentación, días de corrección). */
    config() {
        return http.get('/tesis/config');
    },
};