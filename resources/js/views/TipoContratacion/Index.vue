<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Tipos de Contratación</h1>
          <p class="text-sm text-slate-600 dark:text-slate-400">Configure los regímenes laborales y sus retenciones de ley.</p>
        </div>
        <button
          @click="openCreateModal"
          class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-sm transition-colors shadow-sm"
        >
          + Nuevo Registro
        </button>
      </div>

      <!-- Quick Search -->
      <div class="flex items-center justify-between bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Buscar régimen..."
          class="w-full max-w-xs px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
      </div>

      <!-- Loader State -->
      <SkeletonTable v-if="loading" />

      <!-- Data Table -->
      <div v-else class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200 dark:border-slate-700">
                <th class="px-6 py-4">ID</th>
                <th class="px-6 py-4">Nombre</th>
                <th class="px-6 py-4">Descripción</th>
                <th class="px-6 py-4">ISSS</th>
                <th class="px-6 py-4">AFP</th>
                <th class="px-6 py-4">Renta Fija (10%)</th>
                <th class="px-6 py-4">Estado</th>
                <th class="px-6 py-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm text-slate-700 dark:text-slate-200">
              <tr v-for="(tipo, index) in filteredTipos" :key="tipo.ID_TIPOCONTRATACION" :class="index % 2 === 0 ? 'table-row-even' : 'table-row-odd'" class="hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors">
                <td class="px-6 py-4 font-medium">{{ tipo.ID_TIPOCONTRATACION }}</td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ tipo.TIPOCONTRATACION }}</td>
                <td class="px-6 py-4 text-slate-500 dark:text-slate-400 max-w-xs truncate">{{ tipo.DESCRIPCION }}</td>
                <td class="px-6 py-4">
                  <span :class="tipo.APLICA_ISSS ? 'text-green-600' : 'text-slate-400'">{{ tipo.APLICA_ISSS ? 'Sí' : 'No' }}</span>
                </td>
                <td class="px-6 py-4">
                  <span :class="tipo.APLICA_AFP ? 'text-green-600' : 'text-slate-400'">{{ tipo.APLICA_AFP ? 'Sí' : 'No' }}</span>
                </td>
                <td class="px-6 py-4">
                  <span v-if="tipo.APLICA_RENTA_FIJA" class="bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-2 py-0.5 rounded text-xs font-semibold">
                    {{ tipo.PORCENTAJE_RENTA_FIJA }}%
                  </span>
                  <span v-else class="text-slate-400">No aplica</span>
                </td>
                <td class="px-6 py-4">
                  <span :class="tipo.ESACTIVO ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800'" class="px-2.5 py-1 rounded-full text-xs font-semibold inline-block">
                    {{ tipo.ESACTIVO ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                  <button
                    @click="editTipo(tipo)"
                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 font-semibold text-xs transition-colors"
                  >
                    Editar
                  </button>
                  <button
                    v-if="tipo.ESACTIVO"
                    @click="inactivateTipo(tipo)"
                    class="text-rose-600 hover:text-rose-900 dark:text-rose-400 dark:hover:text-rose-300 font-semibold text-xs transition-colors"
                  >
                    Inactivar
                  </button>
                </td>
              </tr>
              <tr v-if="filteredTipos.length === 0">
                <td colspan="8" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">No se encontraron registros.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal CRUD -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-950 dark:text-white">{{ isEditing ? 'Editar Régimen' : 'Nuevo Régimen de Contratación' }}</h3>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white font-semibold">✕</button>
          </div>
          <form v-submit-lock="saveForm" class="p-6 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre del Régimen</label>
              <input
                v-model="form.TIPOCONTRATACION"
                type="text"
                required
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Descripción</label>
              <textarea
                v-model="form.DESCRIPCION"
                rows="2"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              ></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <label class="flex items-center space-x-3 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" v-model="form.APLICA_ISSS" class="rounded text-indigo-600 focus:ring-indigo-500" />
                <span>Aplica ISSS</span>
              </label>
              <label class="flex items-center space-x-3 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" v-model="form.APLICA_AFP" class="rounded text-indigo-600 focus:ring-indigo-500" />
                <span>Aplica AFP</span>
              </label>
              <label class="flex items-center space-x-3 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" v-model="form.APLICA_RENTA_TABLA" class="rounded text-indigo-600 focus:ring-indigo-500" />
                <span>Aplica Renta Tabla</span>
              </label>
              <label class="flex items-center space-x-3 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" v-model="form.APLICA_RENTA_FIJA" class="rounded text-indigo-600 focus:ring-indigo-500" />
                <span>Renta Fija (10%)</span>
              </label>
            </div>
            <div v-if="form.APLICA_RENTA_FIJA">
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Porcentaje Renta Fija</label>
              <input
                v-model="form.PORCENTAJE_RENTA_FIJA"
                type="number"
                step="0.01"
                min="0"
                max="100"
                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <label class="flex items-center space-x-3 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" v-model="form.APLICA_INSAFORP" class="rounded text-indigo-600 focus:ring-indigo-500" />
                <span>Aplica INSAFORP</span>
              </label>
              <label class="flex items-center space-x-3 text-sm text-slate-700 dark:text-slate-300">
                <input type="checkbox" v-model="form.ESACTIVO" class="rounded text-indigo-600 focus:ring-indigo-500" />
                <span>Activo</span>
              </label>
            </div>

            <!-- Validation Error Alert -->
            <div v-if="modalError" class="text-xs text-red-500 bg-red-50 dark:bg-red-900/10 p-2.5 rounded-lg border border-red-200 dark:border-red-800">
              {{ modalError }}
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-slate-200 dark:border-slate-700">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
              >
                Cancelar
              </button>
              <button
                type="submit"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors"
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

const tipos = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const showModal = ref(false);
const isEditing = ref(false);
const modalError = ref('');

const form = ref({
  ID_TIPOCONTRATACION: null,
  TIPOCONTRATACION: '',
  DESCRIPCION: '',
  ES_EVENTUAL: false,
  APLICA_ISSS: true,
  APLICA_AFP: true,
  APLICA_RENTA_TABLA: true,
  APLICA_RENTA_FIJA: false,
  PORCENTAJE_RENTA_FIJA: 0,
  APLICA_INSAFORP: true,
  ESACTIVO: true
});

const loadTipos = async () => {
  loading.value = true;
  try {
    const response = await api.get('/tipo-contratacion');
    tipos.value = response.data;
  } catch (err) {
    console.error('Error al cargar tipos de contratación', err);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadTipos();
});

const filteredTipos = computed(() => {
  if (!searchQuery.value) return tipos.value;
  return tipos.value.filter(t =>
    t.TIPOCONTRATACION.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    t.DESCRIPCION?.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

const openCreateModal = () => {
  isEditing.value = false;
  modalError.value = '';
  form.value = {
    ID_TIPOCONTRATACION: null,
    TIPOCONTRATACION: '',
    DESCRIPCION: '',
    ES_EVENTUAL: false,
    APLICA_ISSS: true,
    APLICA_AFP: true,
    APLICA_RENTA_TABLA: true,
    APLICA_RENTA_FIJA: false,
    PORCENTAJE_RENTA_FIJA: 10.00,
    APLICA_INSAFORP: true,
    ESACTIVO: true
  };
  showModal.value = true;
};

const editTipo = (tipo) => {
  isEditing.value = true;
  modalError.value = '';
  form.value = { ...tipo };
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const saveForm = async () => {
  modalError.value = '';
  try {
    if (isEditing.value) {
      await api.put(`/tipo-contratacion/${form.value.ID_TIPOCONTRATACION}`, form.value);
    } else {
      await api.post('/tipo-contratacion', form.value);
    }
    closeModal();
    loadTipos();
  } catch (err) {
    modalError.value = err.response?.data?.error || err.response?.data?.message || 'Error al guardar el registro.';
  }
};

const inactivateTipo = async (tipo) => {
  if (!await dialog.confirm({
    title: 'Inactivar tipo',
    message: `¿Seguro que desea inactivar ${tipo.TIPOCONTRATACION}?`,
    variant: 'warning',
    confirmText: 'Sí, inactivar',
  })) return;
  try {
    await api.delete(`/tipo-contratacion/${tipo.ID_TIPOCONTRATACION}`);
    loadTipos();
  } catch (err) {
    await dialog.alert({
      title: 'Error',
      message: err.response?.data?.error || err.response?.data?.message || 'No se pudo inactivar el registro.',
      variant: 'danger',
    });
  }
};
</script>
