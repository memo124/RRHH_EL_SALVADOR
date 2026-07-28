<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Mantenimiento Geográfico</h1>
          <p class="text-sm text-slate-600 dark:text-slate-400">Administración de Países en el sistema.</p>
        </div>
        <button
          @click="openCreateModal"
          class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-sm transition-colors shadow-sm"
        >
          + Nuevo País
        </button>
      </div>

      <div class="flex items-center justify-between bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Buscar país..."
          class="w-full max-w-xs px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
      </div>

      <SkeletonTable v-if="loading" />

      <div v-else class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200 dark:border-slate-700">
                <th class="px-6 py-4">ID</th>
                <th class="px-6 py-4">Nombre del País</th>
                <th class="px-6 py-4">Código MH</th>
                <th class="px-6 py-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm text-slate-700 dark:text-slate-200">
              <tr v-for="(pais, index) in filteredPaises" :key="pais.ID_PAIS" :class="index % 2 === 0 ? 'table-row-even' : 'table-row-odd'" class="hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors">
                <td class="px-6 py-4 font-medium">{{ pais.ID_PAIS }}</td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ pais.NOMBREPAIS }}</td>
                <td class="px-6 py-4">
                  <span class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 px-2.5 py-1 rounded text-xs font-bold uppercase">
                    {{ pais.CODIGO_MH || 'N/A' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                  <button
                    @click="editPais(pais)"
                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold text-xs"
                  >
                    Editar
                  </button>
                  <button
                    @click="deletePais(pais)"
                    class="text-rose-600 hover:text-rose-900 dark:text-rose-400 dark:hover:text-rose-300 font-semibold text-xs"
                  >
                    Eliminar
                  </button>
                </td>
              </tr>
              <tr v-if="filteredPaises.length === 0">
                <td colspan="4" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">No se encontraron registros.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-955 dark:text-white">{{ isEditing ? 'Editar País' : 'Nuevo País' }}</h3>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white font-semibold">✕</button>
          </div>
          <form v-submit-lock="saveForm" class="p-6 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre del País</label>
              <input
                v-model="form.NOMBREPAIS"
                type="text"
                required
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Código Hacienda (MH)</label>
              <input
                v-model="form.CODIGO_MH"
                type="text"
                placeholder="Ej. SV"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>

            <div v-if="modalError" class="text-xs text-red-500 bg-red-50 dark:bg-red-900/10 p-2.5 rounded-lg border border-red-200 dark:border-red-800">
              {{ modalError }}
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-slate-200 dark:border-slate-700">
              <button
                type="button"
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
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';

const paises = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const showModal = ref(false);
const isEditing = ref(false);
const modalError = ref('');

const form = ref({
  ID_PAIS: null,
  NOMBREPAIS: '',
  CODIGO_MH: ''
});

const loadPaises = async () => {
  loading.value = true;
  try {
    const res = await api.get('/paises');
    paises.value = res.data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

onMounted(loadPaises);

const filteredPaises = computed(() => {
  if (!searchQuery.value) return paises.value;
  return paises.value.filter(p =>
    p.NOMBREPAIS.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    p.CODIGO_MH?.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

const openCreateModal = () => {
  isEditing.value = false;
  modalError.value = '';
  form.value = { ID_PAIS: null, NOMBREPAIS: '', CODIGO_MH: '' };
  showModal.value = true;
};

const editPais = (pais) => {
  isEditing.value = true;
  modalError.value = '';
  form.value = { ...pais };
  showModal.value = true;
};

const closeModal = () => { showModal.value = false; };

const saveForm = async () => {
  modalError.value = '';
  try {
    if (isEditing.value) {
      await api.put(`/paises/${form.value.ID_PAIS}`, form.value);
    } else {
      await api.post('/paises', form.value);
    }
    closeModal();
    loadPaises();
  } catch (err) {
    modalError.value = err.response?.data?.error || err.response?.data?.message || 'Error.';
  }
};

const deletePais = async (pais) => {
  if (!await dialog.confirm({
    title: 'Eliminar país',
    message: `¿Eliminar ${pais.NOMBREPAIS}?`,
    variant: 'danger',
    confirmText: 'Sí, eliminar',
  })) return;
  try {
    await api.delete(`/paises/${pais.ID_PAIS}`);
    loadPaises();
  } catch (err) {
    await dialog.alert({
      title: 'Error',
      message: err.response?.data?.error || 'No se pudo eliminar el país.',
      variant: 'danger',
    });
  }
};
</script>
