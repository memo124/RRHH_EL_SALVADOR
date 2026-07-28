import { ref, unref } from 'vue';
import api from '../services/api';

/**
 * Lista paginada server-side (tablas).
 * Espera respuesta Laravel: { data, current_page, last_page, per_page, total }
 * @param {string|import('vue').Ref<string>|import('vue').ComputedRef<string>} endpoint
 */
export function usePaginatedList(endpoint, options = {}) {
    const items = ref([]);
    const loading = ref(false);
    const search = ref('');
    const page = ref(1);
    const perPage = ref(options.perPage ?? 25);
    const total = ref(0);
    const lastPage = ref(1);
    let searchTimer = null;

    const fetch = async (extraParams = {}) => {
        loading.value = true;
        try {
            const params = {
                page: page.value,
                per_page: perPage.value,
                ...(search.value.trim() ? { search: search.value.trim() } : {}),
                ...(unref(options.params) ?? {}),
                ...extraParams,
            };
            const res = await api.get(unref(endpoint), { params });
            const payload = res.data;

            if (Array.isArray(payload)) {
                items.value = payload;
                total.value = payload.length;
                lastPage.value = 1;
                page.value = 1;
            } else {
                items.value = payload.data ?? [];
                total.value = payload.total ?? items.value.length;
                lastPage.value = payload.last_page ?? 1;
                page.value = payload.current_page ?? page.value;
            }
        } catch (err) {
            console.error(err);
            items.value = [];
            total.value = 0;
            lastPage.value = 1;
        } finally {
            loading.value = false;
        }
    };

    const setPage = (p) => {
        page.value = Math.max(1, Math.min(p, lastPage.value || 1));
        return fetch();
    };

    const setSearch = (q, debounceMs = 350) => {
        search.value = q;
        page.value = 1;
        clearTimeout(searchTimer);
        return new Promise((resolve) => {
            searchTimer = setTimeout(() => fetch().then(resolve), debounceMs);
        });
    };

    const setPerPage = (n) => {
        perPage.value = n;
        page.value = 1;
        return fetch();
    };

    const reset = () => {
        search.value = '';
        page.value = 1;
        items.value = [];
        total.value = 0;
        lastPage.value = 1;
    };

    return {
        items,
        loading,
        search,
        page,
        perPage,
        total,
        lastPage,
        fetch,
        setPage,
        setSearch,
        setPerPage,
        reset,
    };
}
