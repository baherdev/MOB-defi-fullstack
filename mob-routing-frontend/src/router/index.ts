import { createRouter, createWebHistory } from 'vue-router';
import { getStoredToken } from '../services/api';
import RouteCalculator from '../components/RouteCalculator.vue';
import StatsView from '../components/StatsView.vue';
import LoginForm from '../components/LoginForm.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/login',
            name: 'Login',
            component: LoginForm,
            meta: { requiresAuth: false },
        },
        {
            path: '/',
            name: 'Home',
            component: RouteCalculator,
            meta: { requiresAuth: true },
        },
        {
            path: '/stats',
            name: 'Stats',
            component: StatsView,
            meta: { requiresAuth: true },
        },
        {
            path: '/:pathMatch(.*)*',
            redirect: '/',
        },
    ],
});

// Navigation guard
router.beforeEach((to, from, next) => {
    const authenticated = !!getStoredToken();

    if (to.meta.requiresAuth && !authenticated) {
        // Redirect to login if trying to access protected route
        next('/login');
    } else if (to.path === '/login' && authenticated) {
        // Redirect to home if already logged in
        next('/');
    } else {
        next();
    }
});

export default router;
