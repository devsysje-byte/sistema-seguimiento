import { defineStore } from 'pinia';

let nextId = 1;

export const useToastStore = defineStore('toast', {
  state: () => ({
    toasts: [],
  }),
  actions: {
    show(message, type = 'info', duration = 4000) {
      const id = nextId++;
      this.toasts.push({ id, message, type, duration });
      setTimeout(() => this.remove(id), duration);
      return id;
    },
    success(message, duration = 3500) {
      return this.show(message, 'success', duration);
    },
    error(message, duration = 5000) {
      return this.show(message, 'error', duration);
    },
    warning(message, duration = 4500) {
      return this.show(message, 'warning', duration);
    },
    info(message, duration = 4000) {
      return this.show(message, 'info', duration);
    },
    remove(id) {
      const index = this.toasts.findIndex((t) => t.id === id);
      if (index !== -1) this.toasts.splice(index, 1);
    },
  },
});