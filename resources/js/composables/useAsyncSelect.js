import { ref, computed, unref } from 'vue';
import api from '../services/api';

/**
 * Opciones async paginadas para AsyncSelect (server-side + acumulación por scroll).
 */
export function useAsyncSelect(endpoint, options = {}) {
    const optionsList = ref([]);
    const loading = ref(false);
    const query = ref('');
    const page = ref(1);
    const lastPage = ref(1);
    const total = ref(0);
    const selectedOption = ref(null);
    let debounceTimer = null;
    let requestId = 0;

    const isEnabled = () => {
        if (options.enabled !== undefined) return !!unref(options.enabled);
        return !!unref(endpoint);
    };

    const hasMore = computed(() => page.value < lastPage.value);

    const mapOption = (row) => {
        if (options.mapOption) return options.mapOption(row);
        return {
            value: row.value ?? row.ID_EMPLEADO ?? row.id,
            label: row.label ?? row.NOMBRE_COMPLETO ?? String(row.value ?? row.ID_EMPLEADO),
        };
    };

    const mergeOptions = (incoming, append) => {
        const mapped = incoming.map(mapOption);
        if (!append) {
            optionsList.value = mapped;
            return;
        }
        const seen = new Set(optionsList.value.map((o) => o.value));
        for (const opt of mapped) {
            if (!seen.has(opt.value)) {
                optionsList.value.push(opt);
                seen.add(opt.value);
            }
        }
    };

    const fetchOptions = async ({ append = false, q = query.value, pageNum = page.value } = {}) => {
        if (!isEnabled()) return;
        const url = unref(endpoint);
        if (!url) return;

        const currentRequest = ++requestId;
        loading.value = true;
        try {
            const params = {
                page: pageNum,
                per_page: options.perPage ?? 30,
                ...(q.trim() ? { q: q.trim() } : {}),
                ...(unref(options.params) ?? {}),
            };
            const res = await api.get(url, { params });
            if (currentRequest !== requestId) return;

            const payload = res.data;
            mergeOptions(payload.data ?? [], append);
            page.value = payload.current_page ?? pageNum;
            lastPage.value = payload.last_page ?? 1;
            total.value = payload.total ?? optionsList.value.length;
        } catch (err) {
            console.error(err);
            if (!append) optionsList.value = [];
        } finally {
            if (currentRequest === requestId) loading.value = false;
        }
    };

    const search = (q) => {
        if (!isEnabled()) return Promise.resolve();
        query.value = q;
        page.value = 1;
        clearTimeout(debounceTimer);
        return new Promise((resolve) => {
            debounceTimer = setTimeout(() => fetchOptions({ append: false }).then(resolve), options.debounce ?? 300);
        });
    };

    const loadMore = () => {
        if (loading.value || !hasMore.value) return Promise.resolve();
        return fetchOptions({ append: true, pageNum: page.value + 1 });
    };

    const resolveSelected = async (value) => {
        if (value == null || value === '') {
            selectedOption.value = null;
            return;
        }
        const found = optionsList.value.find((o) => o.value == value);
        if (found) {
            selectedOption.value = found;
            return;
        }
        const url = unref(endpoint);
        if (!url || !isEnabled()) return;

        try {
            const params = { id: value, per_page: 1, ...(unref(options.params) ?? {}) };
            const res = await api.get(url, { params });
            const row = (res.data.data ?? [])[0];
            if (row) {
                const opt = mapOption(row);
                selectedOption.value = opt;
                if (!optionsList.value.some((o) => o.value == opt.value)) {
                    optionsList.value.unshift(opt);
                }
            }
        } catch (err) {
            console.error(err);
        }
    };

    const reset = () => {
        optionsList.value = [];
        query.value = '';
        page.value = 1;
        lastPage.value = 1;
        total.value = 0;
        selectedOption.value = null;
    };

    return {
        optionsList,
        loading,
        query,
        total,
        hasMore,
        selectedOption,
        fetchOptions,
        search,
        loadMore,
        resolveSelected,
        reset,
    };
}
