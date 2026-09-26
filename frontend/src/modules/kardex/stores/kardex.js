import { defineStore } from 'pinia';
import { kardexService } from '../services/kardex';
import { tramitesService } from '@/modules/tramites/services/tramites';
import { MODALIDADES_MOCK, buscarMock, consultarMock, asignarMock, transicionarMock, asignarTutorMock, resumenMock, aplicarModuloMock } from '../mock/data';

// Modalidades que el KARDEX puede asignar a un postulante.
export const MODALIDADES_ASIGNABLES = ['Tesis de Grado', 'Trabajo Dirigido', 'Examen de Grado'];

/**
 * Store del Dashboard de KARDEX.
 *
 * Centraliza la búsqueda de postulantes por CI/RU, la asignación de modalidad
 * con generación de credenciales y la consulta del flujo. Cuando la API no
 * responde (backend apagado o error de servidor) conmuta a "modo simulado"
 * usando datos de demostración para que el dashboard sea demostrable.
 */
export const useKardexStore = defineStore('kardex', {
    state: () => ({
        modalidades: [],            // Modalidades activas disponibles.
        postulante: null,           // Postulante encontrado (búsqueda/asignación).
        credenciales: null,         // { generadas, ya_existia, username, password }.
        tramite: null,              // Trámite actual del postulante (consulta/asignación).
        resumen: null,              // Resumen estadístico del dashboard (modalidades/estudiantes/tutores).
        simulado: false,            // true cuando los datos provienen del respaldo demo.
        cargando: false,            // true mientras se ejecuta una petición.
        errorMessage: null,         // Mensaje del último error mostrado en la vista.
        _ts: {},                    // Marcas de tiempo de caché.
    }),
    actions: {
        /**
         * Comprueba si una clave de caché sigue vigente.
         *
         * @returns {boolean} true si la caché aún es válida.
         */
        _fresco(clave, ttl) {
            return this._ts[clave] && Date.now() - this._ts[clave] < ttl;
        },
        /**
         * Normaliza el error de una petición: mensaje legible o activación del
         * modo simulado cuando la API no está disponible.
         *
         * @param {Error} error Error de axios.
         * @returns {null} Siempre null (convención de las acciones).
         */
        _manejarError(error) {
            const redInasible = !error?.response || (error?.response?.status ?? 0) >= 500;
            if (redInasible) {
                this.simulado = true;
                return null;
            }
            this.errorMessage =
                error?.response?.data?.message ||
                error?.response?.data?.errors?.identificador?.[0] ||
                'No se pudo completar la operación.';
            return null;
        },
        /** Carga la lista de modalidades activas (con caché breve). */
        async cargarModalidades(force = false) {
            if (!force && this._fresco('modalidades', 300000)) return this.modalidades;
            try {
                const { data } = await kardexService.modalidades();
                this.modalidades = Array.isArray(data) ? data : [];
            } catch (error) {
                if (this._manejarError(error) === null && !this.errorMessage) {
                    this.modalidades = [...MODALIDADES_MOCK];
                }
            }
            this._ts.modalidades = Date.now();
        },
        /**
         * Carga el resumen estadístico del dashboard desde GET /kardex/resumen.
         * En modo simulado (API no disponible) usa el respaldo de demostración.
         */
        async cargarResumen(force = false) {
            if (!force && this._fresco('resumen', 30000)) return this.resumen;
            try {
                const { data } = await kardexService.resumen();
                this.simulado = false;
                this.resumen = data.resumen;
            } catch (error) {
                if (this._manejarError(error) === null && !this.errorMessage) {
                    this.resumen = resumenMock();
                }
            }
            this._ts.resumen = Date.now();
            return this.resumen;
        },
        /**
         * Busca un postulante por CI o registro universitario.
         *
         * @param {string} identificador CI o R.U.
         * @returns {Promise<Object|null>} Postulante o null (con `errorMessage`).
         */
        async buscarPostulante(identificador) {
            this.cargando = true;
            this.errorMessage = null;
            try {
                const { data } = await kardexService.buscarPostulante(identificador);
                this.simulado = false;
                this.postulante = data.postulante;
                this.credenciales = null;
                this.tramite = null;
                return this.postulante;
            } catch (error) {
                if (this._manejarError(error) === null && this.simulado) {
                    const resultado = buscarMock(identificador);
                    if (resultado) {
                        this.postulante = resultado.postulante;
                        this.credenciales = null;
                        this.tramite = null;
                        return this.postulante;
                    }
                    this.postulante = null;
                    this.errorMessage = 'No se encontró un postulante con ese CI o registro universitario.';
                    return null;
                }
                this.postulante = null;
                return null;
            } finally {
                this.cargando = false;
            }
        },
        /**
         * Asigna la modalidad de titulación al postulante y genera (o recupera)
         * sus credenciales de acceso.
         *
         * @returns {Promise<Object|null>} Respuesta `{ postulante, credenciales, tramite }`.
         */
        async asignarModalidad(identificador, idModalidad) {
            this.cargando = true;
            this.errorMessage = null;
            try {
                const { data } = await kardexService.asignarModalidad(identificador, idModalidad);
                this.simulado = false;
                this.postulante = data.postulante;
                this.credenciales = data.credenciales;
                this.tramite = data.tramite;
                return data;
            } catch (error) {
                if (this._manejarError(error) === null && this.simulado) {
                    const resultado = asignarMock(identificador, idModalidad);
                    if (resultado) {
                        this.postulante = resultado.postulante;
                        this.credenciales = resultado.credenciales;
                        this.tramite = resultado.tramite;
                        return resultado;
                    }
                    this.errorMessage = 'No se encontró un postulante con ese CI o registro universitario.';
                    return null;
                }
                return null;
            } finally {
                this.cargando = false;
            }
        },
        /**
         * Consulta el flujo actual del postulante (trámite más reciente).
         *
         * @param {string} identificador CI o R.U.
         * @returns {Promise<Object|null>} Respuesta `{ postulante, tramite }`.
         */
        async consultar(identificador) {
            this.cargando = true;
            this.errorMessage = null;
            try {
                const { data } = await kardexService.consultar(identificador);
                this.simulado = false;
                this.postulante = data.postulante;
                this.tramite = data.tramite;
                return data;
            } catch (error) {
                if (this._manejarError(error) === null && this.simulado) {
                    const resultado = consultarMock(identificador);
                    if (resultado) {
                        this.postulante = resultado.postulante;
                        this.tramite = resultado.tramite;
                        return resultado;
                    }
                    this.postulante = null;
                    this.tramite = null;
                    this.errorMessage = 'No se encontró un postulante con ese CI o registro universitario.';
                    return null;
                }
                this.postulante = null;
                return null;
            } finally {
                this.cargando = false;
            }
        },
        /**
         * Actualiza el estado del trámite (avanza por el flujo). En modo
         * simulado muta el trámite local de demostración.
         *
         * @returns {Promise<Object|null>} Trámite actualizado.
         */
        async actualizarEstado(idTramite, nuevoEstado, observaciones) {
            this.cargando = true;
            this.errorMessage = null;
            try {
                if (this.simulado && this.tramite) {
                    // Espejo de la regla del TramiteService: Investigación en
                    // Desarrollo no comienza sin un tutor asignado.
                    if (nuevoEstado === 'investigacion_en_desarrollo' && !this.tramite.id_tutor) {
                        this.errorMessage = 'Debe asignar un tutor antes de pasar a Investigación en Desarrollo.';
                        return null;
                    }
                    const actualizado = transicionarMock(this.tramite, nuevoEstado, observaciones);
                    this.tramite = actualizado;
                    return this.tramite;
                }
                const { data } = await tramitesService.transicionar(idTramite, nuevoEstado, observaciones);
                this.tramite = data;
                return this.tramite;
            } catch (error) {
                this.errorMessage =
                    error?.response?.data?.message ||
                    error?.response?.data?.errors?.nuevo_estado?.[0] ||
                    'No se pudo actualizar el estado del trámite.';
                return null;
            } finally {
                this.cargando = false;
            }
        },
        /**
         * Asigna (o reasigna) el tutor de un trámite vía
         * POST /api/tramites/{id}/asignar-tutor. En modo simulado actualiza el
         * trámite local de demostración.
         *
         * @param {number|string} id      Identificador del trámite.
         * @param {number|string} idTutor Identificador del usuario docente.
         * @returns {Promise<Object|null>} Trámite actualizado.
         */
        async asignarTutor(idTramite, idTutor) {
            this.cargando = true;
            this.errorMessage = null;
            try {
                if (this.simulado && this.tramite) {
                    const actualizado = asignarTutorMock(this.tramite, idTutor);
                    this.tramite = actualizado;
                    return this.tramite;
                }
                const { data } = await tramitesService.asignarTutor(idTramite, idTutor);
                this.tramite = data;
                return this.tramite;
            } catch (error) {
                this.errorMessage =
                    error?.response?.data?.message ||
                    error?.response?.data?.errors?.id_tutor?.[0] ||
                    'No se pudo asignar el tutor.';
                return null;
            } finally {
                this.cargando = false;
            }
        },
        /**
         * Guarda un módulo del flujo de titulación: registra los datos del
         * formulario en `hitos.modulos`, avanza el estado del trámite al estado
         * objetivo del módulo y refresca el trámite del postulante. En modo
         * simulado muta el trámite local de demostración.
         *
         * @param {string} moduloId Identificador del módulo (perfil_tesis, ...).
         * @param {Object} datos    Datos validados del formulario del módulo.
         * @returns {Promise<Object|null>} Trámite actualizado.
         */
        async guardarModuloFlujo(moduloId, datos) {
            this.cargando = true;
            this.errorMessage = null;
            try {
                if (!this.tramite) return null;
                if (this.simulado) {
                    const actualizado = aplicarModuloMock(this.tramite, moduloId, datos);
                    this.tramite = actualizado;
                    return this.tramite;
                }
                const { data } = await kardexService.guardarModuloFlujo(this.tramite.id_tramite, moduloId, datos);
                this.tramite = data;
                return this.tramite;
            } catch (error) {
                this.errorMessage =
                    error?.response?.data?.message ||
                    error?.response?.data?.errors?.datos?.[0] ||
                    'No se pudo guardar el módulo del flujo.';
                return null;
            } finally {
                this.cargando = false;
            }
        },
        /** Limpia el estado de la búsqueda/consulta actual. */
        limpiar() {
            this.postulante = null;
            this.credenciales = null;
            this.tramite = null;
            this.errorMessage = null;
        },
    },
});