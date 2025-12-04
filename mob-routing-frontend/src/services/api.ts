import axios, { AxiosInstance } from 'axios';
import type { RouteRequest, RouteResponse, AnalyticDistanceList } from '../types';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1';

// Create axios instance
const api: AxiosInstance = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
    },
});

// Request interceptor to add JWT token
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('jwt_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Response interceptor to handle 401 errors
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Token expired or invalid
            localStorage.removeItem('jwt_token');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

// Auth functions
export async function loginApi(email: string, password: string): Promise<string> {
    const response = await axios.post(`${API_BASE_URL.replace('/v1', '')}/login`, {
        email,
        password,
    });
    return response.data.token;
}

export function getStoredToken(): string | null {
    return localStorage.getItem('jwt_token');
}

export function isAuthenticated(): boolean {
    return !!localStorage.getItem('jwt_token');
}

// Existing API functions
export async function calculateRoute(data: RouteRequest): Promise<RouteResponse> {
    const response = await api.post('/routes', data);
    return response.data;
}

export async function getDistanceStats(params?: {
    from?: string;
    to?: string;
    groupBy?: 'none' | 'day' | 'month' | 'year';
}): Promise<AnalyticDistanceList> {
    const response = await api.get('/stats/distances', { params });
    return response.data;
}
