import { defineStore } from 'pinia';
import api from '../services/api';

export const useTramitesStore = defineStore('tramites', {
    state: () => ({
        perfilEstudiante: JSON.parse(localStorage.getItem('perfilEstudiante')) || null,
        modalidades: [],
        tramitesPendientes: []
    }),
    actions: {
        async cargarPerfil() {
            try {
                const { data } = await api.get('/estudiante/perfil');
                this.perfilEstudiante = data;
                localStorage.setItem('perfilEstudiante', JSON.stringify(data));
            } catch (error) {
                this.perfilEstudiante = null;
            }
        },
        async guardarPerfil(datos) {
            const { data } = await api.post('/estudiante/perfil', datos);
            this.perfilEstudiante = data;
            localStorage.setItem('perfilEstudiante', JSON.stringify(data));
        },
        async cargarModalidades() {
            // Asumiendo que tienes un endpoint o puedes harcodearlas si no hay index público
            // Para este sprint, las hardcodeamos o usamos un endpoint simple
            this.modalidades = [
                { id_modalidad: 1, nombre: 'Examen de Grado' },
                { id_modalidad: 2, nombre: 'Tesis de Grado' },
                { id_modalidad: 3, nombre: 'Trabajo Dirigido' },
                { id_modalidad: 4, nombre: 'Excelencia Académica' }
            ];
        },
        async iniciarTramite(formData) {
            return await api.post('/tramites', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        },
        async cargarPendientes() {
            const { data } = await api.get('/tramites/pendientes');
            this.tramitesPendientes = data;
        },
        async revisarTramite(id, accion, observaciones) {
            await api.post(`/tramites/${id}/revisar`, { accion, observaciones });
            await this.cargarPendientes();
        }
    }
});