import { defineStore } from 'pinia';
import api from '../services/api';

export const useNotificacionesStore = defineStore('notificaciones', {
    state: () => ({
        items: [],
        noLeidas: 0,
        cargando: false,
        _ts: {},
    }),
    getters: {
        tienesPendientes: (state) => state.noLeidas > 0,
    },
    actions: {
        _fresco(clave, ttl) {
            return this._ts[clave] && Date.now() - this._ts[clave] < ttl;
        },
        async cargar(force = false) {
            if (!force && this._fresco('notificaciones', 60000)) return { items: this.items, noLeidas: this.noLeidas };
            this.cargando = true;
            try {
                const { data } = await api.get('/notificaciones');
                this.items = data.notificaciones;
                this.noLeidas = data.no_leidas;
            } catch (error) {
                this.items = [];
                this.noLeidas = 0;
            } finally {
                this.cargando = false;
            }
            this._ts.notificaciones = Date.now();
            return { items: this.items, noLeidas: this.noLeidas };
        },
        async marcarLeida(id) {
            const notificacion = this.items.find((n) => n.id_notificacion === id);
            if (notificacion && !notificacion.leida) {
                notificacion.leida = true;
                this.noLeidas = Math.max(0, this.noLeidas - 1);
            }
            try {
                await api.patch(`/notificaciones/${id}`, { leida: true });
            } catch (error) {
                await this.cargar(true);
            }
        },
        async marcarTodasLeidas() {
            try {
                await api.post('/notificaciones/leer-todas');
                this.items.forEach((n) => (n.leida = true));
                this.noLeidas = 0;
            } catch (error) {
                /* sin cambios, se reintenta con la próxima carga */
            }
        },
    },
});