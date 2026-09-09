import { defineStore } from 'pinia';
import api from '../services/api';

export const useTramitesStore = defineStore('tramites', {
    state: () => ({
        perfilEstudiante: JSON.parse(localStorage.getItem('perfilEstudiante')) || null,
        modalidades: [],
        tramitesPendientes: [],
        tramiteActivo: null,
        tutorias: [],
        docentes: [],
        cargandoTramite: false,
    }),
    actions: {
        async cargarPerfil() {
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
        },
        async guardarPerfil(datos) {
            const { data } = await api.post('/estudiante/perfil', datos);
            this.perfilEstudiante = data;
            localStorage.setItem('perfilEstudiante', JSON.stringify(data));
        },
        async cargarModalidades() {
            try {
                const { data } = await api.get('/modalidades');
                this.modalidades = data;
            } catch (error) {
                this.modalidades = [];
            }
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
        async cargarPendientes() {
            const { data } = await api.get('/tramites/pendientes');
            this.tramitesPendientes = data;
        },
        async revisarTramite(id, accion, observaciones) {
            const { data } = await api.post(`/tramites/${id}/revisar`, { accion, observaciones });
            await this.cargarPendientes();
            return data;
        },
        async cargarTutorias() {
            try {
                const { data } = await api.get('/tutorias');
                this.tutorias = data;
            } catch (error) {
                this.tutorias = [];
            }
        },
        async cargarDocentes() {
            try {
                const { data } = await api.get('/usuarios/docentes');
                this.docentes = data;
            } catch (error) {
                this.docentes = [];
            }
        },
        async asignarTutor(id, idTutor) {
            const { data } = await api.post(`/tramites/${id}/asignar-tutor`, { id_tutor: idTutor });
            return data;
        }
    }
});