import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';

// État global partagé entre tous les composants
const isLoggedIn = ref(!!localStorage.getItem('jwt_token'));

export function useAuth() {
    const router = useRouter();

    const isAuthenticated = computed(() => isLoggedIn.value);

    const login = (token: string) => {
        localStorage.setItem('jwt_token', token);
        isLoggedIn.value = true;
    };

    const logout = () => {
        localStorage.removeItem('jwt_token');
        isLoggedIn.value = false;
        router.push('/login');
    };

    const checkAuth = () => {
        isLoggedIn.value = !!localStorage.getItem('jwt_token');
    };

    return {
        isAuthenticated,
        login,
        logout,
        checkAuth,
    };
}
