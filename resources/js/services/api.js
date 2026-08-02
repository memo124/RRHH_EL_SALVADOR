import axios from 'axios';
import { ref } from 'vue';
import { buildSuccessToast, isMutatingMethod, shouldSkipSuccessToast } from '../utils/apiFeedback';
import { getApiErrorMessage, isRequestCancelled, isServerError } from '../utils/apiError';

let loadingCount = 0;
export const isLoading = ref(false);

let toastHandler = null;

/** AbortControllers de peticiones en curso (cancelables al cambiar de ruta). */
const pendingControllers = new Set();

export function registerApiToast(handler) {
    toastHandler = handler;
}

function setGlobalLoading(active) {
    loadingCount += active ? 1 : -1;
    if (loadingCount < 0) {
        loadingCount = 0;
    }
    isLoading.value = loadingCount > 0;
}

function trackRequest(config) {
    if (config.signal || config.skipRouteCancellation) {
        return;
    }

    const controller = new AbortController();
    config.signal = controller.signal;
    config.__abortController = controller;
    pendingControllers.add(controller);
}

function releaseRequest(config) {
    const controller = config?.__abortController;
    if (controller) {
        pendingControllers.delete(controller);
    }
}

/** Cancela todas las peticiones API pendientes (p. ej. al salir de una pantalla). */
export function cancelPendingRequests() {
    if (pendingControllers.size === 0) {
        return;
    }

    const controllers = [...pendingControllers];
    pendingControllers.clear();
    loadingCount = 0;
    isLoading.value = false;

    for (const controller of controllers) {
        controller.abort();
    }
}

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

api.interceptors.request.use(
    (config) => {
        setGlobalLoading(true);
        const token = localStorage.getItem('token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        trackRequest(config);
        return config;
    },
    (error) => {
        setGlobalLoading(false);
        return Promise.reject(error);
    }
);

api.interceptors.response.use(
    (response) => {
        releaseRequest(response.config);
        setGlobalLoading(false);

        const config = response.config;
        if (
            toastHandler
            && isMutatingMethod(config.method)
            && !shouldSkipSuccessToast(config)
        ) {
            const { title, message } = buildSuccessToast(config, response);
            toastHandler.success(title, message);
        }

        return response;
    },
    (error) => {
        releaseRequest(error.config);
        setGlobalLoading(false);

        if (isRequestCancelled(error)) {
            return Promise.reject(error);
        }

        if (error.response && error.response.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            if (window.location.pathname !== '/login') {
                window.location.href = '/login';
            }
            return Promise.reject(error);
        }

        const skipToast = error.config?.skipErrorToast === true;
        if (!skipToast && toastHandler && isServerError(error)) {
            toastHandler.error('Error del servidor', getApiErrorMessage(error));
        }

        return Promise.reject(error);
    }
);

export default api;
