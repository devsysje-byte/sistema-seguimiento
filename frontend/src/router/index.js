import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) return savedPosition;
        return { top: 0 };
    },
    routes: [
        { path: '/login', name: 'Login', component: () => import('../views/LoginView.vue') },
        { 
            path: '/dashboard', name: 'Dashboard', 
            component: () => import('../views/DashboardView.vue'),
        },
        { 
            path: '/admin', name: 'AdminDashboard', 
            component: () => import('../views/AdminDashboard.vue'),
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        { 
            path: '/docente', name: 'DocenteTutorias', 
            component: () => import('../views/DocenteTutorias.vue'),
            meta: { requiresAuth: true, roles: ['docente'] }
        },
        { 
            path: '/estudiante', name: 'EstudianteDashboard', 
            component: () => import('../views/EstudianteDashboard.vue'),
            meta: { requiresAuth: true }
        },
         { 
            path: '/kardex', 
            name: 'RevisionKardex', 
            component: () => import('../views/RevisionKardex.vue'),
            meta: { requiresAuth: true, roles: ['kardex', 'secretaria', 'direccion', 'admin', 'concejo', 'docente'] }
        },
        { 
            path: '/tramites/:id/gestion', 
            name: 'GestionTramite', 
            component: () => import('../views/GestionTramite.vue'),
            meta: { requiresAuth: true, roles: ['kardex', 'secretaria', 'direccion', 'admin', 'concejo', 'docente'] }
        },
    ],
});

// Actualizar la guardia
router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();
    if (to.meta.requiresAuth && !authStore.isAuthenticated) return next('/login');
    if (to.meta.roles && !to.meta.roles.includes(authStore.user?.rol)) {
        // Redirigir según rol
        if (authStore.user?.rol === 'estudiante') return next('/estudiante');
        if (authStore.user?.rol === 'admin') return next('/admin');
        return next('/login'); // O página de error
    }
    next();


});

export default router;