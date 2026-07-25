<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Catálogos MH</h1>
          <p class="text-sm text-slate-600 dark:text-slate-400">Administración de Tipos de Documento de Identidad MH.</p>
        </div>
        <button
          @click="openCreateModal"
          class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-sm transition-colors shadow-sm"
        >
          + Nuevo Tipo Documento
        </button>
      </div>

      <div class="flex items-center justify-between bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Buscar documento..."
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
                <th class="px-6 py-4">Código MH</th>
                <th class="px-6 py-4">Nombre Documento</th>
                <th class="px-6 py-4">Máscara / Formato</th>
                <th class="px-6 py-4">Estado</th>
                <th class="px-6 py-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm text-slate-700 dark:text-slate-200">
              <tr v-for="(doc, index) in filteredDocs" :key="doc.ID_TIPODOCUMENTO" :class="index % 2 === 0 ? 'table-row-even' : 'table-row-odd'" class="hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors">
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
                  <button
                    @click="editDoc(doc)"
                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold text-xs"
                  >
                    Editar
                  </button>
                  <button
                    v-if="doc.ESACTIVO"
                    @click="inactivateDoc(doc)"
                    class="text-rose-600 hover:text-rose-900 dark:text-rose-400 dark:hover:text-rose-300 font-semibold text-xs"
                  >
                    Inactivar
                  </button>
                </td>
              </tr>
              <tr v-if="filteredDocs.length === 0">
                <td colspan="6" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">No se encontraron registros.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-950 dark:text-white">{{ isEditing ? 'Editar Documento' : 'Nuevo Documento MH' }}</h3>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white font-semibold">✕</button>
          </div>
          <form v-submit-lock="saveForm" class="p-6 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Código MH</label>
              <input
                v-model="form.CODIGO_MH"
                type="text"
                required
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre del Documento</label>
              <input
                v-model="form.NOMBREDOCUMENTO"
                type="text"
                required
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Máscara / Formato</label>
              <input
                v-model="form.MASCARA_FORMATO"
                type="text"
                placeholder="Ej. 00000000-0"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none"
              />
            </div>
            <label class="flex items-center space-x-3 text-sm text-slate-700 dark:text-slate-300">
              <input type="checkbox" v-model="form.ESACTIVO" class="rounded text-indigo-600 focus:ring-indigo-500" />
              <span>Activo</span>
            </label>

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

const docs = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const showModal = ref(false);
const isEditing = ref(false);
const modalError = ref('');

const form = ref({
  ID_TIPODOCUMENTO: null,
  CODIGO_MH: '',
  NOMBREDOCUMENTO: '',
  MASCARA_FORMATO: '',
  ESACTIVO: true
});

const loadDocs = async () => {
  loading.value = true;
  try {
    const res = await api.get('/tipo-documento');
    docs.value = res.data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

onMounted(loadDocs);

const filteredDocs = computed(() => {
  if (!searchQuery.value) return docs.value;
  return docs.value.filter(d =>
    d.NOMBREDOCUMENTO.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    d.CODIGO_MH.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

const openCreateModal = () => {
  isEditing.value = false;
  modalError.value = '';
  form.value = { ID_TIPODOCUMENTO: null, CODIGO_MH: '', NOMBREDOCUMENTO: '', MASCARA_FORMATO: '', ESACTIVO: true };
  showModal.value = true;
};

const editDoc = (doc) => {
  isEditing.value = true;
  modalError.value = '';
  form.value = { ...doc };
  showModal.value = true;
};

const closeModal = () => { showModal.value = false; };

const saveForm = async () => {
  modalError.value = '';
  try {
    if (isEditing.value) {
      await api.put(`/tipo-documento/${form.value.ID_TIPODOCUMENTO}`, form.value);
    } else {
      await api.post('/tipo-documento', form.value);
    }
    closeModal();
    loadDocs();
  } catch (err) {
    modalError.value = err.response?.data?.error || err.response?.data?.message || 'Error.';
  }
};

const inactivateDoc = async (doc) => {
  if (confirm(`¿Inactivar ${doc.NOMBREDOCUMENTO}?`)) {
    try {
      await api.delete(`/tipo-documento/${doc.ID_TIPODOCUMENTO}`);
      loadDocs();
    } catch (err) {
      alert(err.response?.data?.error || 'Error.');
    }
  }
};
</script>
