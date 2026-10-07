// Roles con acceso al Dashboard de KARDEX (gestión académica).
const ROLES_KARDEX = ['kardex', 'secretaria', 'direccion', 'admin'];

/**
 * Rutas del módulo Dashboard de KARDEX.
 *
 * Shell propio del dashboard (encabezado + barra lateral) con rutas hijas. La
 * ruta raíz (/kardex) muestra el panel general con estadísticas de modalidades,
 * estudiantes y tutores. Los módulos con flujo propio (Registro de Postulante,
 * Tesis de Grado y Examen de Grado) avanzan por tarjetas secuenciales; Trabajo
 * Dirigido y Reporte de Modalidad comparten la vista placeholder de la próxima
 * etapa.
 */
export default [
    {
        path: '/kardex',
        name: 'KardexDashboard',
        component: () => import('@/modules/kardex/views/KardexDashboard.vue'),
        meta: { requiresAuth: true, roles: ROLES_KARDEX },
        children: [
            {
                // Vista principal del dashboard: estadísticas por defecto.
                path: '',
                name: 'KardexInicio',
                component: () => import('@/modules/kardex/views/KardexInicio.vue'),
            },
            {
                path: 'postulante',
                name: 'KardexRegistroPostulante',
                component: () => import('@/modules/kardex/views/RegistroPostulante.vue'),
            },
            {
                path: 'tesis',
                name: 'KardexTesis',
                component: () => import('@/modules/kardex/views/TesisGradoKardex.vue'),
            },
            {
                path: 'trabajo-dirigido',
                name: 'KardexTrabajoDirigido',
                component: () => import('@/modules/kardex/views/ModuloPlaceholder.vue'),
                props: { modulo: 'Trabajo Dirigido', icono: 'folder', descripcion: 'Registro y seguimiento de la modalidad de Trabajo Dirigido.' },
            },
            {
                path: 'examen',
                name: 'KardexExamen',
                component: () => import('@/modules/kardex/views/ExamenGradoKardex.vue'),
            },
            {
                path: 'reporte',
                name: 'KardexReporte',
                component: () => import('@/modules/kardex/views/ModuloPlaceholder.vue'),
                props: { modulo: 'Reporte de Modalidad', icono: 'chart', descripcion: 'Estadísticas y reportes de avance por modalidad de titulación.' },
            },
        ],
    },
];