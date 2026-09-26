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
    routes: [
        // Acceso por la raíz del sitio: siempre aterriza en una vista real.
        // Sin esta ruta, abrir la URL principal muestra PANTALLA EN BLANCO
        // (vue-router no encuentra coincidencia y <router-view> no renderiza nada).
        { path: '/', redirect: '/login' },
        ...moduleRoutes,
        // Ruta comodín: cualquier URL sin coincidencia termina en el login en
        // lugar de una pantalla en blanco silenciosa.
        { path: '/:pathMatch(.*)*', redirect: '/login' },
    ],
});

// Marca de recarga por fallo de carga de un chunk. Evita bucles: si el chunk
// sigue sin cargarse tras el refresh, no se insiste.
const RECARGA_CHUNK_KEY = 'spa:chunk-reload';

// Un fallo de navegación (p. ej. un chunk lazy que no pudo cargarse en el
// primer intento) no debe dejar la app congelada en silencio: se registra y
// se recupera. El caso típico es un despliegue nuevo con el index.html
// cacheado del navegador: los hashes de los chunks viejos ya no existen, la
// vista no llega a montarse y sin este rebote la única salida sería recargar
// a mano. Se recarga una única vez contra la ruta destino para tomar los
// bundles vigentes.
router.onError((error, to) => {
    console.error('[router] fallo de navegación hacia', to?.fullPath, error);

    const mensaje = error?.message || '';
    const falloDeChunk = /dynamically imported module|ChunkLoadError|Importing a module script failed/i.test(mensaje);
    if (!falloDeChunk || !to?.fullPath) return;
    if (sessionStorage.getItem(RECARGA_CHUNK_KEY) === to.fullPath) return;

    sessionStorage.setItem(RECARGA_CHUNK_KEY, to.fullPath);
    window.location.assign(to.fullPath);
});

// Una navegación correcta invalida la marca: el siguiente fallo de chunk puede
// intentar de nuevo el rebote.
router.afterEach(() => {
    sessionStorage.removeItem(RECARGA_CHUNK_KEY);
});

// Guardias de autenticación y roles (viven dentro del módulo auth).
registerAuthGuards(router);

export default router;