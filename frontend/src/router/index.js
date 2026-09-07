import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

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
    ],
});

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();
    if (to.meta.requiresAuth && !authStore.isAuthenticated) return next('/login');
    if (to.meta.requiresAdmin && !authStore.isAdmin) return next('/estudiante');
    next();
});

export default router;