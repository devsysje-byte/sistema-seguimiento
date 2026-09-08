import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import RevisionKardex from '../views/RevisionKardex.vue';

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        { path: '/login', name: 'Login', component: () => import('../views/LoginView.vue') },
        { 
            path: '/admin', name: 'AdminDashboard', 
            component: () => import('../views/AdminDashboard.vue'),
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        { 
            path: '/estudiante', name: 'EstudianteDashboard', 
            component: () => import('../views/EstudianteDashboard.vue'),
            meta: { requiresAuth: true }
        },
         { 
            path: '/kardex', 
            name: 'RevisionKardex', 
            component: RevisionKardex,
            meta: { requiresAuth: true, roles: ['kardex', 'secretaria', 'direccion', 'admin'] }
        },
        { 
            path: '/tramites/:id/gestion', 
            name: 'GestionTramite', 
            component: () => import('../views/GestionTramite.vue'),
            meta: { requiresAuth: true, roles: ['kardex', 'secretaria', 'direccion', 'admin'] }
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