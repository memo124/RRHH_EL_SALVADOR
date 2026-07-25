import axios from 'axios';
import { ref } from 'vue';

let loadingCount = 0;
export const isLoading = ref(false);

function setGlobalLoading(active) {
    loadingCount += active ? 1 : -1;
    if (loadingCount < 0) {
        loadingCount = 0;
    }
    isLoading.value = loadingCount > 0;
}

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Request Interceptor
api.interceptors.request.use(
    (config) => {
        setGlobalLoading(true);
        const token = localStorage.getItem('token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        setGlobalLoading(false);
        return Promise.reject(error);
    }
);

// Response Interceptor
api.interceptors.response.use(
    (response) => {
        setGlobalLoading(false);
        return response;
    },
    (error) => {
        setGlobalLoading(false);
        if (error.response && error.response.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            // If page is not login, redirect to login
            if (window.location.pathname !== '/login') {
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);

export default api;
