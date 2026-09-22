// Roles con acceso al panel de revisión y gestión de trámites.
// El tablero de revisión (/kardex) es de la gestión académica, pero también lo
// consulta el concejo para evaluar perfiles; la gestión individual
// (/tramites/:id/gestion) suma concejo (avanza estados) y docente (tutoría).
const ROLES_TRAMITES = ['kardex', 'secretaria', 'direccion', 'admin', 'concejo', 'docente'];

/**
 * Rutas del módulo de trámites de titulación.
 *
 * Incluye el tablero de revisión (Kardex/dirección), la gestión individual de
 * cada trámite y el panel de tutorías del docente.
 */
export default [
    {
        path: '/docente',
        name: 'DocenteTutorias',
        component: () => import('@/modules/tramites/views/DocenteTutorias.vue'),
        meta: { requiresAuth: true, roles: ['docente'] }
    },
    {
        path: '/kardex',
        name: 'RevisionKardex',
        component: () => import('@/modules/tramites/views/RevisionKardex.vue'),
        meta: { requiresAuth: true, roles: ROLES_TRAMITES }
    },
    {
        path: '/tramites/:id/gestion',
        name: 'GestionTramite',
        component: () => import('@/modules/tramites/views/GestionTramite.vue'),
        meta: { requiresAuth: true, roles: ROLES_TRAMITES }
    },
];