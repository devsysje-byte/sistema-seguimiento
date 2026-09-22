import { defineStore } from 'pinia';
import { estudianteService } from '../services/estudiantes';

/**
 * Store del perfil académico del estudiante autenticado.
 *
 * Vive en el módulo Estudiantes y pertenece al dominio estudiante (perfil),
 * desacoplado del store de trámites. Mantiene el perfil sincronizado con
 * localStorage y aplica caché temporal (`_ts`) para evitar peticiones repetidas.
 */
export const useEstudianteStore = defineStore('estudiante', {
    state: () => ({
        perfilEstudiante: JSON.parse(localStorage.getItem('perfilEstudiante')) || null,
        _ts: {},                                // Marca de tiempo de la última carga.
    }),
    actions: {
        /**
         * Comprueba si la caché sigue vigente según un TTL en milisegundos.
         *
         * @param {number} ttl Duración máxima de vigencia en ms.
         * @returns {boolean} true si la caché aún es válida.
         */
        _fresco(ttl) {
            return this._ts.perfil && Date.now() - this._ts.perfil < ttl;
        },
        /**
         * Carga el perfil de estudiante del usuario autenticado desde
         * GET /api/estudiante/perfil y lo persiste en localStorage.
         *
         * @param {boolean} [force=false] Si es true ignora la caché.
         * @returns {Promise<Object|null>} Perfil de estudiante o null si no existe.
         */
        async cargarPerfil(force = false) {
            if (!force && this._fresco(30000)) return this.perfilEstudiante;
            try {
                const { data } = await estudianteService.perfil();
                this.perfilEstudiante = data?.id_estudiante ? data : null;
                if (this.perfilEstudiante) {
                    localStorage.setItem('perfilEstudiante', JSON.stringify(this.perfilEstudiante));
                } else {
                    localStorage.removeItem('perfilEstudiante');
                }
            } catch (error) {
                this.perfilEstudiante = null;
                localStorage.removeItem('perfilEstudiante');
            }
            this._ts.perfil = Date.now();
            return this.perfilEstudiante;
        },
        /**
         * Guarda (crea o actualiza) el perfil de estudiante vía POST /api/estudiante/perfil.
         *
         * @param {Object} datos Datos validados del perfil (código, plan, promedio, fecha).
         * @returns {Promise<Object>} Perfil devuelto por la API.
         */
        async guardarPerfil(datos) {
            const { data } = await estudianteService.guardarPerfil(datos);
            this.perfilEstudiante = data;
            localStorage.setItem('perfilEstudiante', JSON.stringify(data));
            this._ts.perfil = Date.now();
        },
    },
});