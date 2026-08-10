<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Catálogos MH</h1>
          <p class="page-subtitle">Documentos de identidad y establecimientos registrados ante el Ministerio de Hacienda.</p>
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

      <div class="flex items-center justify-between bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="`Buscar ${currentTab?.label?.toLowerCase()}...`"
          class="w-full max-w-xs px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
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
            <!-- ── DOCUMENTOS DE IDENTIDAD ─────────────────────────────────── -->
            <template v-if="activeTab === 'documentos'">
              <thead>
                <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200 dark:border-slate-700">
                  <th class="px-6 py-4">ID</th>
                  <th class="px-6 py-4">Código MH</th>
                  <th class="px-6 py-4">Nombre Documento</th>
                  <th class="px-6 py-4">Máscara / Formato</th>
                  <th class="px-6 py-4">Estado</th>
                  <th class="px-6 py-4 text-right">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm text-slate-700 dark:text-slate-200">
                <tr v-for="(doc, index) in records" :key="doc.ID_TIPODOCUMENTO" :class="index % 2 === 0 ? 'table-row-even' : 'table-row-odd'" class="hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors">
                  <td class="px-6 py-4 font-medium">{{ doc.ID_TIPODOCUMENTO }}</td>
                  <td class="px-6 py-4 font-bold text-indigo-600 dark:text-indigo-400">{{ doc.CODIGO_MH }}</td>
                  <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ doc.NOMBREDOCUMENTO }}</td>
                  <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ doc.MASCARA_FORMATO || 'Ninguno' }}</td>
                  <td class="px-6 py-4">
                    <span :class="doc.ESACTIVO ? 'bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700' : 'bg-rose-50 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-700'" class="px-2.5 py-1 rounded-full text-xs font-semibold inline-block">
                      {{ doc.ESACTIVO ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-right space-x-2">
                    <IconActionButton variant="edit" @click="openEdit(doc)" />
                    <IconActionButton v-if="doc.ESACTIVO" variant="inactivate" @click="inactivateRecord(doc)" />
                  </td>
                </tr>
                <tr v-if="!records.length"><td colspan="6" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">No se encontraron registros.</td></tr>
              </tbody>
            </template>

            <!-- ── ESTABLECIMIENTOS ─────────────────────────────────────────── -->
            <template v-else-if="activeTab === 'establecimientos'">
              <thead>
                <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200 dark:border-slate-700">
                  <th class="px-6 py-4">ID</th>
                  <th class="px-6 py-4">Establecimiento</th>
                  <th class="px-6 py-4">Empresa</th>
                  <th class="px-6 py-4">Cód. MH / P. Venta</th>
                  <th class="px-6 py-4">Dirección</th>
                  <th class="px-6 py-4">Estado</th>
                  <th class="px-6 py-4 text-right">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm text-slate-700 dark:text-slate-200">
                <tr v-for="(r, index) in records" :key="r.ID_ESTABLECIMIENTO" :class="index % 2 === 0 ? 'table-row-even' : 'table-row-odd'" class="hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors">
                  <td class="px-6 py-4 font-medium">{{ r.ID_ESTABLECIMIENTO }}</td>
                  <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ r.NOMBRE_ESTABLECIMIENTO }}</td>
                  <td class="px-6 py-4">{{ r.EMPRESA_NOMBRE || 'N/A' }}</td>
                  <td class="px-6 py-4">
                    <span class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 px-2.5 py-1 rounded text-xs font-bold uppercase">
                      {{ r.CODIGO_MH_TIPO }}{{ r.CODIGO_PUNTO_VENTA_MH ? ' / ' + r.CODIGO_PUNTO_VENTA_MH : '' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ r.DIRECCION || 'N/A' }}</td>
                  <td class="px-6 py-4">
                    <span :class="r.ESACTIVO ? 'bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700' : 'bg-rose-50 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-700'" class="px-2.5 py-1 rounded-full text-xs font-semibold inline-block">
                      {{ r.ESACTIVO ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-right space-x-2">
                    <IconActionButton variant="edit" @click="openEdit(r)" />
                    <IconActionButton v-if="r.ESACTIVO" variant="inactivate" @click="inactivateRecord(r)" />
                  </td>
                </tr>
                <tr v-if="!records.length"><td colspan="7" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">No se encontraron registros.</td></tr>
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

      <!-- Modal -->
      <AppModalShell :open="showModal" @close="closeModal">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full mx-auto overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-950 dark:text-white">{{ isEditing ? 'Editar' : 'Nuevo' }} {{ currentTab?.label }}</h3>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white font-semibold" aria-label="Cerrar"><AppIcon name="x" size="md" /></button>
          </div>
          <form v-submit-lock="saveForm" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
            <!-- DOCUMENTO FIELDS -->
            <template v-if="activeTab === 'documentos'">
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Código MH</label>
                <input v-model="form.CODIGO_MH" type="text" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre del Documento</label>
                <input v-model="form.NOMBREDOCUMENTO" type="text" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Máscara / Formato</label>
                <input v-model="form.MASCARA_FORMATO" type="text" placeholder="Ej. 00000000-0" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <label class="flex items-center space-x-3 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" v-model="form.ESACTIVO" class="rounded text-indigo-600 focus:ring-indigo-500" />
                <span>Activo</span>
              </label>
            </template>

            <!-- ESTABLECIMIENTO FIELDS -->
            <template v-else-if="activeTab === 'establecimientos'">
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Empresa *</label>
                <AsyncSelect v-model="form.ID_EMPRESA" catalog="empresas" placeholder="Seleccionar empresa" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre del Establecimiento *</label>
                <input v-model="form.NOMBRE_ESTABLECIMIENTO" type="text" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Distrito (Dirección MH)</label>
                <AsyncSelect v-model="form.ID_DISTRITO" catalog="distritos" nullable placeholder="Seleccionar distrito" />
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Código Tipo MH</label>
                  <input v-model="form.CODIGO_MH_TIPO" type="text" placeholder="01" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Cód. Punto de Venta MH</label>
                  <input v-model="form.CODIGO_PUNTO_VENTA_MH" type="text" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
                </div>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Dirección</label>
                <input v-model="form.DIRECCION" type="text" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Teléfono</label>
                <input v-model="form.TELEFONO" type="text" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <label v-if="isEditing" class="flex items-center space-x-3 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" v-model="form.ESACTIVO" class="rounded text-indigo-600 focus:ring-indigo-500" />
                <span>Activo</span>
              </label>
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
  { key: 'documentos',      label: 'Documentos de Identidad', addLabel: 'Nuevo Tipo Documento', api: 'tipo-documento',   idKey: 'ID_TIPODOCUMENTO' },
  { key: 'establecimientos',label: 'Establecimientos',         addLabel: 'Nuevo Establecimiento',api: 'establecimientos',idKey: 'ID_ESTABLECIMIENTO' },
];

const activeTab = ref('documentos');
const currentTab = computed(() => tabs.find(t => t.key === activeTab.value));
const endpoint = computed(() => `/${currentTab.value?.api}`);

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
} = usePaginatedList(endpoint, { perPage: 25 });

const showModal = ref(false);
const isEditing = ref(false);
const modalError = ref('');
const form = ref({});

watch(searchQuery, (q) => setSearch(q));
watch(activeTab, () => { reset(); loadTab(); });
onMounted(loadTab);

const defaultForms = {
  documentos: { CODIGO_MH: '', NOMBREDOCUMENTO: '', MASCARA_FORMATO: '', ESACTIVO: true },
  establecimientos: { ID_EMPRESA: null, ID_DISTRITO: null, CODIGO_MH_TIPO: '01', CODIGO_PUNTO_VENTA_MH: '', NOMBRE_ESTABLECIMIENTO: '', DIRECCION: '', TELEFONO: '', ESACTIVO: true },
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

const inactivateRecord = async (r) => {
  const tab = currentTab.value;
  if (!await dialog.confirm({
    title: `Inactivar ${tab.label.toLowerCase()}`,
    message: `¿Inactivar este registro?`,
    variant: 'warning',
    confirmText: 'Sí, inactivar',
  })) return;
  try {
    await api.delete(`/${tab.api}/${r[tab.idKey]}`);
    loadTab();
  } catch (err) {
    await dialog.alert({
      title: 'Error',
      message: getApiErrorMessage(err, 'No se pudo inactivar el registro.'),
      variant: 'danger',
    });
  }
};
</script>
