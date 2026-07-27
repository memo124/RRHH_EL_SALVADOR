<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Mantenimiento de Deducciones e Ingresos</h1>
          <p class="text-sm text-slate-600 dark:text-slate-400">Configure los tipos de descuentos e ingresos del personal.</p>
        </div>
      </div>

      <!-- Tabs Navigation -->
      <div class="flex border-b border-slate-200 dark:border-slate-700">
        <button
          @click="activeTab = 'descuentos'"
          :class="activeTab === 'descuentos' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
          class="py-3 px-6 border-b-2 text-sm font-medium transition-all"
        >
          Tipos de Descuento
        </button>
        <button
          @click="activeTab = 'ingresos'"
          :class="activeTab === 'ingresos' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
          class="py-3 px-6 border-b-2 text-sm font-medium transition-all"
        >
          Tipos de Ingreso
        </button>
      </div>

      <!-- Tab Content: Descuentos -->
      <div v-if="activeTab === 'descuentos'" class="space-y-4">
        <div class="flex justify-between items-center">
          <input
            v-model="searchDescuentos"
            type="text"
            placeholder="Buscar descuento..."
            class="w-full max-w-xs px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none"
          />
          <button
            @click="openCreateDescuento"
            class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors"
          >
            + Nuevo Descuento
          </button>
        </div>

        <SkeletonTable v-if="loading" />

        <div v-else class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200">
                <th class="px-6 py-4">ID</th>
                <th class="px-6 py-4">Categoría</th>
                <th class="px-6 py-4">Nombre Descuento</th>
                <th class="px-6 py-4">Descripción</th>
                <th class="px-6 py-4">Estado</th>
                <th class="px-6 py-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
              <tr v-for="desc in filteredDescuentos" :key="desc.ID_TIPODESCUENTO">
                <td class="px-6 py-4">{{ desc.ID_TIPODESCUENTO }}</td>
                <td class="px-6 py-4">
                  <span :class="categoriaBadgeClass(desc.CATEGORIA)" class="px-2 py-0.5 rounded text-xs font-semibold">
                    {{ categoriaLabel(desc.CATEGORIA) }}
                  </span>
                </td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ desc.NOMBRETIPODESC }}</td>
                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ desc.DESCRIPCIONTIPODESC || 'N/A' }}</td>
                <td class="px-6 py-4">
                  <span :class="desc.ESACTIVO ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'" class="px-2 py-0.5 rounded text-xs font-semibold">
                    {{ desc.ESACTIVO ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                  <button @click="editDescuento(desc)" class="text-indigo-600 font-semibold text-xs">Editar</button>
                  <button v-if="desc.ESACTIVO" @click="inactivateDescuento(desc)" class="text-rose-600 font-semibold text-xs">Inactivar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab Content: Ingresos -->
      <div v-if="activeTab === 'ingresos'" class="space-y-4">
        <div class="flex justify-between items-center">
          <input
            v-model="searchIngresos"
            type="text"
            placeholder="Buscar ingreso..."
            class="w-full max-w-xs px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900"
          />
          <button
            @click="openCreateIngreso"
            class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors"
          >
            + Nuevo Ingreso
          </button>
        </div>

        <SkeletonTable v-if="loading" />

        <div v-else class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200">
                <th class="px-6 py-4">ID</th>
                <th class="px-6 py-4">Tipo de Ingreso</th>
                <th class="px-6 py-4">Estado</th>
                <th class="px-6 py-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
              <tr v-for="ing in filteredIngresos" :key="ing.ID_TIPOINGRESO">
                <td class="px-6 py-4">{{ ing.ID_TIPOINGRESO }}</td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ ing.TIPOINGRESO }}</td>
                <td class="px-6 py-4">
                  <span :class="ing.ESACTIVO ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'" class="px-2 py-0.5 rounded text-xs font-semibold">
                    {{ ing.ESACTIVO ? 'Activo' : 'Inactiva' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                  <button @click="editIngreso(ing)" class="text-indigo-600 font-semibold text-xs">Editar</button>
                  <button v-if="ing.ESACTIVO" @click="inactivateIngreso(ing)" class="text-rose-600 font-semibold text-xs">Inactivar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Descuento Modal -->
      <div v-if="showDescuentoModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-955 dark:text-white">{{ isEditing ? 'Editar Descuento' : 'Nuevo Descuento' }}</h3>
            <button @click="showDescuentoModal = false" class="text-slate-400 font-semibold">✕</button>
          </div>
          <form v-submit-lock="saveDescuento" class="p-6 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Categoría</label>
              <AsyncSelect
                v-model="descuentoForm.CATEGORIA"
                :options="CATEGORIA_DESCUENTO_OPTIONS"
                :searchable="false"
                placeholder="Categoría"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre Descuento</label>
              <input v-model="descuentoForm.NOMBRETIPODESC" type="text" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Descripción</label>
              <textarea v-model="descuentoForm.DESCRIPCIONTIPODESC" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white"></textarea>
            </div>
            <label class="flex items-center space-x-2 text-sm">
              <input type="checkbox" v-model="descuentoForm.ESACTIVO" />
              <span>Activo</span>
            </label>
            <div v-if="modalError" class="text-xs text-red-500">{{ modalError }}</div>
            <div class="flex justify-end space-x-3 pt-4 border-t">
              <button data-no-lock type="button" @click="showDescuentoModal = false" class="px-4 py-2 border rounded-lg text-sm">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold">Guardar</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Ingreso Modal -->
      <div v-if="showIngresoModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-955 dark:text-white">{{ isEditing ? 'Editar Ingreso' : 'Nuevo Ingreso' }}</h3>
            <button @click="showIngresoModal = false" class="text-slate-400 font-semibold">✕</button>
          </div>
          <form v-submit-lock="saveIngreso" class="p-6 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombre Tipo Ingreso</label>
              <input v-model="ingresoForm.TIPOINGRESO" type="text" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
            </div>
            <label class="flex items-center space-x-2 text-sm">
              <input type="checkbox" v-model="ingresoForm.ESACTIVO" />
              <span>Activo</span>
            </label>
            <div v-if="modalError" class="text-xs text-red-500">{{ modalError }}</div>
            <div class="flex justify-end space-x-3 pt-4 border-t">
              <button data-no-lock type="button" @click="showIngresoModal = false" class="px-4 py-2 border rounded-lg text-sm">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold">Guardar</button>
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
import { CATEGORIA_DESCUENTO_OPTIONS } from '../../utils/staticSelectOptions';

const activeTab = ref('descuentos');
const descuentos = ref([]);
const ingresos = ref([]);
const loading = ref(false);
const searchDescuentos = ref('');
const searchIngresos = ref('');
const showDescuentoModal = ref(false);
const showIngresoModal = ref(false);
const isEditing = ref(false);
const modalError = ref('');

const descuentoForm = ref({ ID_TIPODESCUENTO: null, NOMBRETIPODESC: '', DESCRIPCIONTIPODESC: '', CATEGORIA: 'DESCUENTO', ESACTIVO: true });
const ingresoForm = ref({ ID_TIPOINGRESO: null, TIPOINGRESO: '', ESACTIVO: true });

const categoriaLabel = (cat) => ({ LEY: 'Ley', PRESTAMO: 'Préstamo', DESCUENTO: 'Descuento' }[cat] || cat || '—');
const categoriaBadgeClass = (cat) => ({
  LEY: 'bg-blue-50 text-blue-700',
  PRESTAMO: 'bg-amber-50 text-amber-700',
  DESCUENTO: 'bg-violet-50 text-violet-700',
}[cat] || 'bg-slate-50 text-slate-600');

const loadData = async () => {
  loading.value = true;
  try {
    const [descRes, ingRes] = await Promise.all([api.get('/tipo-descuento'), api.get('/tipo-ingreso')]);
    descuentos.value = descRes.data;
    ingresos.value = ingRes.data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

onMounted(loadData);

const filteredDescuentos = computed(() => {
  if (!searchDescuentos.value) return descuentos.value;
  return descuentos.value.filter(d => d.NOMBRETIPODESC.toLowerCase().includes(searchDescuentos.value.toLowerCase()));
});

const filteredIngresos = computed(() => {
  if (!searchIngresos.value) return ingresos.value;
  return ingresos.value.filter(i => i.TIPOINGRESO.toLowerCase().includes(searchIngresos.value.toLowerCase()));
});

// Descuento Handlers
const openCreateDescuento = () => {
  isEditing.value = false;
  modalError.value = '';
  descuentoForm.value = { ID_TIPODESCUENTO: null, NOMBRETIPODESC: '', DESCRIPCIONTIPODESC: '', CATEGORIA: 'DESCUENTO', ESACTIVO: true };
  showDescuentoModal.value = true;
};
const editDescuento = (desc) => {
  isEditing.value = true;
  modalError.value = '';
  descuentoForm.value = { ...desc };
  showDescuentoModal.value = true;
};
const saveDescuento = async () => {
  try {
    if (isEditing.value) {
      await api.put(`/tipo-descuento/${descuentoForm.value.ID_TIPODESCUENTO}`, descuentoForm.value);
    } else {
      await api.post('/tipo-descuento', descuentoForm.value);
    }
    showDescuentoModal.value = false;
    loadData();
  } catch (err) { modalError.value = 'Error al guardar.'; }
};
const inactivateDescuento = async (desc) => {
  if (confirm('¿Inactivar descuento?')) {
    await api.delete(`/tipo-descuento/${desc.ID_TIPODESCUENTO}`);
    loadData();
  }
};

// Ingreso Handlers
const openCreateIngreso = () => {
  isEditing.value = false;
  modalError.value = '';
  ingresoForm.value = { ID_TIPOINGRESO: null, TIPOINGRESO: '', ESACTIVO: true };
  showIngresoModal.value = true;
};
const editIngreso = (ing) => {
  isEditing.value = true;
  modalError.value = '';
  ingresoForm.value = { ...ing };
  showIngresoModal.value = true;
};
const saveIngreso = async () => {
  try {
    if (isEditing.value) {
      await api.put(`/tipo-ingreso/${ingresoForm.value.ID_TIPOINGRESO}`, ingresoForm.value);
    } else {
      await api.post('/tipo-ingreso', ingresoForm.value);
    }
    showIngresoModal.value = false;
    loadData();
  } catch (err) { modalError.value = 'Error al guardar.'; }
};
const inactivateIngreso = async (ing) => {
  if (confirm('¿Inactivar ingreso?')) {
    await api.delete(`/tipo-ingreso/${ing.ID_TIPOINGRESO}`);
    loadData();
  }
};
</script>
