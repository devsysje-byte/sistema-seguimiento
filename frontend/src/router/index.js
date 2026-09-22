import { createRouter, createWebHistory } from 'vue-router';
import { moduleRoutes } from '@/modules';
import { registerAuthGuards } from '@/modules/auth';

/**
 * Router de la aplicación.
 *
 * Este archivo es la COMPOSICIÓN RAÍZ: no conoce las rutas de cada módulo.
 * Cada módulo de dominio exporta su arreglo de rutas en su fachada
 * (`src/modules/<dominio>/index.js`) y el registry (`src/modules/index.js`)
 * las concatena. Para añadir o quitar un módulo solo se toca ese registry; el
 * router y el resto de módulos quedan intactos.
 */
const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    // Al navegar, restaura la posición de scroll guardada o vuelve al inicio.
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) return savedPosition;
        return { top: 0 };
    },
    routes: moduleRoutes,
});

// Guardias de autenticación y roles (viven dentro del módulo auth).
registerAuthGuards(router);

export default router;