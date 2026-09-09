import { defineStore } from 'pinia';
import api from '../services/api';

export const useTramitesStore = defineStore('tramites', {
    state: () => ({
        perfilEstudiante: JSON.parse(localStorage.getItem('perfilEstudiante')) || null,
        modalidades: [],
        tramitesPendientes: [],
        estadisticas: { totales: { aprobados: 0, reprobados: 0, total: 0 }, porModalidad: [] },
        tramiteActivo: null,
        tutorias: [],
        docentes: [],
        cargandoTramite: false,
        _ts: {},
    }),
    actions: {
        _fresco(clave, ttl) {
            return this._ts[clave] && Date.now() - this._ts[clave] < ttl;
        },
        async cargarPerfil(force = false) {
            if (!force && this._fresco('perfil', 30000)) return this.perfilEstudiante;
            try {
                const { data } = await api.get('/estudiante/perfil');
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
        async guardarPerfil(datos) {
            const { data } = await api.post('/estudiante/perfil', datos);
            this.perfilEstudiante = data;
            localStorage.setItem('perfilEstudiante', JSON.stringify(data));
        },
        async cargarModalidades(force = false) {
            if (!force && this._fresco('modalidades', 300000)) return this.modalidades;
            try {
                const { data } = await api.get('/modalidades');
                this.modalidades = data;
            } catch (error) {
                this.modalidades = [];
            }
            this._ts.modalidades = Date.now();
        },
        async cargarTramiteActivo() {
            this.cargandoTramite = true;
            try {
                const { data } = await api.get('/estudiante/tramite-activo');
                this.tramiteActivo = data?.id_tramite ? data : null;
            } catch (error) {
                this.tramiteActivo = null;
            } finally {
                this.cargandoTramite = false;
            }
            return this.tramiteActivo;
        },
        async iniciarTramite(formData) {
            const response = await api.post('/tramites', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            await this.cargarTramiteActivo();
            return response;
        },
        async cargarPendientes(force = false) {
            if (!force && this._fresco('pendientes', 30000)) return this.tramitesPendientes;
            const { data } = await api.get('/tramites/pendientes');
            this.tramitesPendientes = data;
            this._ts.pendientes = Date.now();
        },
        async cargarEstadisticas(force = false) {
            if (!force && this._fresco('estadisticas', 30000)) return this.estadisticas;
            try {
                const { data } = await api.get('/tramites/estadisticas');
                this.estadisticas = data;
            } catch (error) {
                this.estadisticas = { totales: { aprobados: 0, reprobados: 0, total: 0 }, porModalidad: [] };
            }
            this._ts.estadisticas = Date.now();
            return this.estadisticas;
        },
        async revisarTramite(id, accion, observaciones) {
            const { data } = await api.post(`/tramites/${id}/revisar`, { accion, observaciones });
            await this.cargarPendientes(true);
            return data;
        },
        async cargarTutorias(force = false) {
            if (!force && this._fresco('tutorias', 30000)) return this.tutorias;
            try {
                const { data } = await api.get('/tutorias');
                this.tutorias = data;
            } catch (error) {
                this.tutorias = [];
            }
            this._ts.tutorias = Date.now();
        },
        async cargarDocentes(force = false) {
            if (!force && this._fresco('docentes', 300000)) return this.docentes;
            try {
                const { data } = await api.get('/usuarios/docentes');
                this.docentes = data;
            } catch (error) {
                this.docentes = [];
            }
            this._ts.docentes = Date.now();
        },
        async asignarTutor(id, idTutor) {
            const { data } = await api.post(`/tramites/${id}/asignar-tutor`, { id_tutor: idTutor });
            this.cargarDocentes(true);
            return data;
        }
    }
});