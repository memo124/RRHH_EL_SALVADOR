<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Mantenimiento Geográfico</h1>
          <p class="page-subtitle">Administración de países, departamentos, municipios y distritos.</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex border-b border-slate-200 dark:border-slate-700 overflow-x-auto">
        <button v-for="t in tabs" :key="t.key" @click="activeTab = t.key"
          :class="activeTab === t.key ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
          class="py-3 px-5 border-b-2 text-sm font-medium transition-all whitespace-nowrap">
          {{ t.label }}
        </button>
      </div>

      <div class="space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-3 bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
          <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-[220px]">
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Buscar</label>
              <input
                v-model="searchQuery"
                type="text"
                :placeholder="`Buscar ${currentTab?.label?.toLowerCase()}...`"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>
            <div v-if="activeTab === 'departamentos'" class="min-w-[220px]">
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Filtrar por País</label>
              <AsyncSelect v-model="filterPais" catalog="paises" nullable placeholder="Todos los países" @change="reloadTab" />
            </div>
            <div v-if="activeTab === 'municipios'" class="min-w-[220px]">
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Filtrar por Departamento</label>
              <AsyncSelect v-model="filterDepto" catalog="departamentos-pais" nullable placeholder="Todos los departamentos" @change="reloadTab" />
            </div>
            <div v-if="activeTab === 'distritos'" class="min-w-[220px]">
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Filtrar por Municipio</label>
              <AsyncSelect v-model="filterMuni" catalog="municipios" nullable placeholder="Todos los municipios" @change="reloadTab" />
            </div>
          </div>
          <button
            @click="openCreateModal"
            class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-sm transition-colors shadow-sm"
          >
            + {{ currentTab?.addLabel }}
          </button>
        </div>

        <SkeletonTable v-if="loading" />

        <div v-else class="table-shell table-scroll">
          <div class="overflow-x-auto">
            <table v-table-cards class="table-cards w-full text-left border-collapse">
              <!-- ── PAÍS ─────────────────────────────────────────────────── -->
              <template v-if="activeTab === 'paises'">
                <thead>
                  <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200 dark:border-slate-700">
                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4">Nombre del País</th>
                    <th class="px-6 py-4">Código MH</th>
                    <th class="px-6 py-4 text-right">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm text-slate-700 dark:text-slate-200">
                  <tr v-for="(r, index) in records" :key="r.ID_PAIS" :class="index % 2 === 0 ? 'table-row-even' : 'table-row-odd'" class="hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors">
                    <td class="px-6 py-4 font-medium">{{ r.ID_PAIS }}</td>
                    <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ r.NOMBREPAIS }}</td>
                    <td class="px-6 py-4">
                      <span class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 px-2.5 py-1 rounded text-xs font-bold uppercase">
                        {{ r.CODIGO_MH || 'N/A' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                      <IconActionButton variant="edit" @click="openEdit(r)" />
                      <IconActionButton variant="delete" @click="deleteRecord(r)" />
                    </td>
                  </tr>
                  <tr v-if="!records.length"><td colspan="4" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">No se encontraron registros.</td></tr>
                </tbody>
              </template>

              <!-- ── DEPARTAMENTO ─────────────────────────────────────────── -->
              <template v-else-if="activeTab === 'departamentos'">
                <thead>
                  <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200 dark:border-slate-700">
                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4">Departamento</th>
                    <th class="px-6 py-4">País</th>
                    <th class="px-6 py-4">Código MH</th>
                    <th class="px-6 py-4 text-right">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm text-slate-700 dark:text-slate-200">
                  <tr v-for="(r, index) in records" :key="r.ID_DEPARTAMENTOPAIS" :class="index % 2 === 0 ? 'table-row-even' : 'table-row-odd'" class="hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors">
                    <td class="px-6 py-4 font-medium">{{ r.ID_DEPARTAMENTOPAIS }}</td>
                    <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ r.NOMBREDEPARTAMENTO }}</td>
                    <td class="px-6 py-4">{{ paisNombre(r.ID_PAIS) }}</td>
                    <td class="px-6 py-4"><span class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 px-2.5 py-1 rounded text-xs font-bold uppercase">{{ r.CODIGO_MH || 'N/A' }}</span></td>
                    <td class="px-6 py-4 text-right space-x-2">
                      <IconActionButton variant="edit" @click="openEdit(r)" />
                      <IconActionButton variant="delete" @click="deleteRecord(r)" />
                    </td>
                  </tr>
                  <tr v-if="!records.length"><td colspan="5" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">No se encontraron registros.</td></tr>
                </tbody>
              </template>

              <!-- ── MUNICIPIO ────────────────────────────────────────────── -->
              <template v-else-if="activeTab === 'municipios'">
                <thead>
                  <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200 dark:border-slate-700">
                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4">Municipio</th>
                    <th class="px-6 py-4">Departamento</th>
                    <th class="px-6 py-4">Código MH</th>
                    <th class="px-6 py-4 text-right">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm text-slate-700 dark:text-slate-200">
                  <tr v-for="(r, index) in records" :key="r.ID_MUNICIPIO" :class="index % 2 === 0 ? 'table-row-even' : 'table-row-odd'" class="hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors">
                    <td class="px-6 py-4 font-medium">{{ r.ID_MUNICIPIO }}</td>
                    <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ r.NOMBREMUNICIPIO }}</td>
                    <td class="px-6 py-4">{{ deptoNombre(r.ID_DEPARTAMENTOPAIS) }}</td>
                    <td class="px-6 py-4"><span class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 px-2.5 py-1 rounded text-xs font-bold uppercase">{{ r.CODIGO_MH || 'N/A' }}</span></td>
                    <td class="px-6 py-4 text-right space-x-2">
                      <IconActionButton variant="edit" @click="openEdit(r)" />
                      <IconActionButton variant="delete" @click="deleteRecord(r)" />
                    </td>
                  </tr>
                  <tr v-if="!records.length"><td colspan="5" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">No se encontraron registros.</td></tr>
                </tbody>
              </template>

              <!-- ── DISTRITO ─────────────────────────────────────────────── -->
              <template v-else-if="activeTab === 'distritos'">
                <thead>
                  <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200 dark:border-slate-700">
                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4">Distrito</th>
                    <th class="px-6 py-4">Municipio</th>
                    <th class="px-6 py-4">Código MH</th>
                    <th class="px-6 py-4 text-right">Acciones</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm text-slate-700 dark:text-slate-200">
                  <tr v-for="(r, index) in records" :key="r.ID_DISTRITO" :class="index % 2 === 0 ? 'table-row-even' : 'table-row-odd'" class="hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors">
                    <td class="px-6 py-4 font-medium">{{ r.ID_DISTRITO }}</td>
                    <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ r.NOMBREDISTRITO }}</td>
                    <td class="px-6 py-4">{{ muniNombre(r.ID_MUNICIPIO) }}</td>
                    <td class="px-6 py-4"><span class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 px-2.5 py-1 rounded text-xs font-bold uppercase">{{ r.CODIGO_MH || 'N/A' }}</span></td>
                    <td class="px-6 py-4 text-right space-x-2">
                      <IconActionButton variant="edit" @click="openEdit(r)" />
                      <IconActionButton variant="delete" @click="deleteRecord(r)" />
                    </td>
                  </tr>
                  <tr v-if="!records.length"><td colspan="5" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">No se encontraron registros.</td></tr>
                </tbody>
              </template>
            </table>
          </div>
          <PaginationBar
            :page="page"
            :last-page="lastPage"
            :per-page="perPage"
            :total="total"
            :loading="loading"
            @update:page="setPage"
            @update:per-page="setPerPage"
          />
        </div>
      </div>

      <!-- Modal -->
      <AppModalShell :open="showModal" @close="closeModal">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full mx-auto overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-950 dark:text-white">{{ isEditing ? 'Editar' : 'Nuevo' }} {{ currentTab?.label }}</h3>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white font-semibold" aria-label="Cerrar"><AppIcon name="x" size="md" /></button>
          </div>
          <form v-submit-lock="saveForm" class="p-6 space-y-4">
            <!-- PAÍS FIELDS -->
            <template v-if="activeTab === 'paises'">
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre del País</label>
                <input v-model="form.NOMBREPAIS" type="text" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Código Hacienda (MH)</label>
                <input v-model="form.CODIGO_MH" type="text" placeholder="Ej. SV" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
              </div>
            </template>

            <!-- DEPARTAMENTO FIELDS -->
            <template v-else-if="activeTab === 'departamentos'">
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">País *</label>
                <AsyncSelect v-model="form.ID_PAIS" catalog="paises" placeholder="Seleccionar país" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre del Departamento</label>
                <input v-model="form.NOMBREDEPARTAMENTO" type="text" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Código Hacienda (MH)</label>
                <input v-model="form.CODIGO_MH" type="text" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
              </div>
            </template>

            <!-- MUNICIPIO FIELDS -->
            <template v-else-if="activeTab === 'municipios'">
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Departamento *</label>
                <AsyncSelect v-model="form.ID_DEPARTAMENTOPAIS" catalog="departamentos-pais" placeholder="Seleccionar departamento" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre del Municipio</label>
                <input v-model="form.NOMBREMUNICIPIO" type="text" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Código Hacienda (MH)</label>
                <input v-model="form.CODIGO_MH" type="text" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
              </div>
            </template>

            <!-- DISTRITO FIELDS -->
            <template v-else-if="activeTab === 'distritos'">
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Municipio *</label>
                <AsyncSelect v-model="form.ID_MUNICIPIO" catalog="municipios" placeholder="Seleccionar municipio" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre del Distrito</label>
                <input v-model="form.NOMBREDISTRITO" type="text" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Código Hacienda (MH)</label>
                <input v-model="form.CODIGO_MH" type="text" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
              </div>
            </template>

            <div v-if="modalError" class="text-xs text-red-500 bg-red-50 dark:bg-red-900/10 p-2.5 rounded-lg border border-red-200 dark:border-red-800">
              {{ modalError }}
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-slate-200 dark:border-slate-700">
              <button
                type="button"
                data-no-lock
                @click="closeModal"
                class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg text-sm hover:bg-slate-50 dark:hover:bg-slate-700"
              >
                Cancelar
              </button>
              <button
                type="submit"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold"
              >
                Guardar
              </button>
            </div>
          </form>
        </div>
      </AppModalShell>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import PaginationBar from '../../components/PaginationBar.vue';
import AppModalShell from '../../components/AppModalShell.vue';
import { usePaginatedList } from '../../composables/usePaginatedList';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';
import { getApiErrorMessage } from '../../utils/apiError';

const tabs = [
  { key: 'paises',       label: 'País',         addLabel: 'Nuevo País',         api: 'paises',            idKey: 'ID_PAIS' },
  { key: 'departamentos',label: 'Departamento', addLabel: 'Nuevo Departamento', api: 'departamentos-pais', idKey: 'ID_DEPARTAMENTOPAIS' },
  { key: 'municipios',   label: 'Municipio',    addLabel: 'Nuevo Municipio',    api: 'municipios',         idKey: 'ID_MUNICIPIO' },
  { key: 'distritos',    label: 'Distrito',     addLabel: 'Nuevo Distrito',     api: 'distritos',          idKey: 'ID_DISTRITO' },
];

const activeTab = ref('paises');
const currentTab = computed(() => tabs.find(t => t.key === activeTab.value));
const endpoint = computed(() => `/${currentTab.value?.api}`);

const filterPais = ref(null);
const filterDepto = ref(null);
const filterMuni = ref(null);

const listParams = computed(() => {
  const p = {};
  if (activeTab.value === 'departamentos' && filterPais.value) p.ID_PAIS = filterPais.value;
  if (activeTab.value === 'municipios' && filterDepto.value) p.ID_DEPARTAMENTOPAIS = filterDepto.value;
  if (activeTab.value === 'distritos' && filterMuni.value) p.ID_MUNICIPIO = filterMuni.value;
  return p;
});

const {
  items: records,
  loading,
  search: searchQuery,
  page,
  perPage,
  total,
  lastPage,
  fetch: loadTab,
  setPage,
  setSearch,
  setPerPage,
  reset,
} = usePaginatedList(endpoint, { perPage: 25, params: listParams });

const reloadTab = () => { page.value = 1; loadTab(); };

const showModal = ref(false);
const isEditing = ref(false);
const modalError = ref('');
const form = ref({});

// Lookup catalogs (used to render human-readable columns in tables)
const lookups = ref({ paises: [], departamentos: [], municipios: [] });

const loadLookups = async () => {
  try {
    const [paises, deptos, munis] = await Promise.all([
      api.get('/catalogs/paises/select', { params: { per_page: 250 } }),
      api.get('/catalogs/departamentos-pais/select', { params: { per_page: 250 } }),
      api.get('/catalogs/municipios/select', { params: { per_page: 250 } }),
    ]);
    lookups.value.paises = paises.data.data ?? paises.data;
    lookups.value.departamentos = deptos.data.data ?? deptos.data;
    lookups.value.municipios = munis.data.data ?? munis.data;
  } catch (err) { console.error(err); }
};

const paisNombre = (id) => lookups.value.paises.find(p => p.value === id)?.label || id;
const deptoNombre = (id) => lookups.value.departamentos.find(d => d.value === id)?.label || id;
const muniNombre = (id) => lookups.value.municipios.find(m => m.value === id)?.label || id;

watch(searchQuery, (q) => setSearch(q));
watch(activeTab, () => {
  filterPais.value = null;
  filterDepto.value = null;
  filterMuni.value = null;
  reset();
  loadTab();
});

onMounted(() => {
  loadLookups();
  loadTab();
});

const defaultForms = {
  paises: { NOMBREPAIS: '', CODIGO_MH: '' },
  departamentos: { ID_PAIS: null, NOMBREDEPARTAMENTO: '', CODIGO_MH: '' },
  municipios: { ID_DEPARTAMENTOPAIS: null, NOMBREMUNICIPIO: '', CODIGO_MH: '' },
  distritos: { ID_MUNICIPIO: null, NOMBREDISTRITO: '', CODIGO_MH: '' },
};

const openCreateModal = () => {
  isEditing.value = false;
  modalError.value = '';
  form.value = { ...defaultForms[activeTab.value] };
  showModal.value = true;
};

const openEdit = (r) => {
  isEditing.value = true;
  modalError.value = '';
  form.value = { ...r };
  showModal.value = true;
};

const closeModal = () => { showModal.value = false; };

const saveForm = async () => {
  modalError.value = '';
  const tab = currentTab.value;
  try {
    if (isEditing.value) {
      await api.put(`/${tab.api}/${form.value[tab.idKey]}`, form.value);
    } else {
      await api.post(`/${tab.api}`, form.value);
    }
    closeModal();
    loadTab();
  } catch (err) {
    modalError.value = getApiErrorMessage(err, 'Ocurrió un error al guardar el registro.');
  }
};

const deleteRecord = async (r) => {
  const tab = currentTab.value;
  if (!await dialog.confirm({
    title: `Eliminar ${tab.label.toLowerCase()}`,
    message: `¿Eliminar este registro?`,
    variant: 'danger',
    confirmText: 'Sí, eliminar',
  })) return;
  try {
    await api.delete(`/${tab.api}/${r[tab.idKey]}`);
    loadTab();
  } catch (err) {
    await dialog.alert({
      title: 'Error',
      message: getApiErrorMessage(err, 'No se pudo eliminar el registro.'),
      variant: 'danger',
    });
  }
};
</script>
