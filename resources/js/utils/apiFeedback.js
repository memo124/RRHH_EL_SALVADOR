const MUTATING_METHODS = new Set(['post', 'put', 'patch', 'delete']);

/** Rutas que no deben mostrar toast de éxito (preview, helpers o feedback propio en la vista). */
const SKIP_SUCCESS_URL = /\/preview(?:\/|$|\?)|numero-a-letras|\/calcular(?:\/|$|\?)|\/horas-extras\/sync/i;

export function isMutatingMethod(method) {
    return MUTATING_METHODS.has((method || 'get').toLowerCase());
}

export function shouldSkipSuccessToast(config) {
    if (!config || config.skipSuccessToast) {
        return true;
    }
    const url = String(config.url || '');
    return SKIP_SUCCESS_URL.test(url);
}

export function buildSuccessToast(config, response) {
    if (config.successToast) {
        const custom = config.successToast;
        return {
            title: custom.title || 'Listo',
            message: custom.message || '',
        };
    }

    const data = response?.data;
    const serverMessage = typeof data?.message === 'string' ? data.message.trim() : '';
    const method = (config.method || 'get').toLowerCase();
    const url = String(config.url || '');

    if (url.includes('/login')) {
        return {
            title: 'Sesión iniciada',
            message: serverMessage || 'Bienvenido al sistema.',
        };
    }

    const titles = {
        post: 'Registro creado',
        put: 'Registro actualizado',
        patch: 'Registro actualizado',
        delete: 'Registro eliminado',
    };

    const fallbacks = {
        post: 'El registro se guardó correctamente.',
        put: 'Los cambios se aplicaron correctamente.',
        patch: 'Los cambios se aplicaron correctamente.',
        delete: 'El registro se eliminó correctamente.',
    };

    const title = titles[method] || 'Operación completada';
    const message = serverMessage || fallbacks[method] || '';

    return { title, message };
}
