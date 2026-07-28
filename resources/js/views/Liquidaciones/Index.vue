<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="flex flex-wrap justify-between items-center gap-4">
        <div>
          <h1 class="page-title">Liquidaciones</h1>
          <p class="page-subtitle">Cálculo de finiquito por terminación laboral.</p>
        </div>
        <button @click="openCalc" class="btn-primary">+ Nueva Liquidación</button>
      </div>

      <SkeletonTable v-if="loading" :cols="6" />

      <div v-else class="table-shell">
        <div class="overflow-x-auto">
          <table v-table-cards class="table-cards table-base">
            <thead>
              <tr class="table-head-row">
                <th class="table-head-cell">Empleado</th>
                <th class="table-head-cell">Fecha</th>
                <th class="table-head-cell text-right">Vacación</th>
                <th class="table-head-cell text-right">Aguinaldo</th>
                <th class="table-head-cell text-right">Indemnización</th>
                <th class="table-head-cell text-right">Líquido</th>
              </tr>
            </thead>
            <tbody class="table-body">
              <tr v-for="(l, i) in items" :key="field(l, 'ID_LIQUIDACION', 'id_liquidacion')" :class="i % 2 === 0 ? 'table-row-even' : 'table-row-odd'">
                <td class="table-body-cell font-semibold text-slate-900 dark:text-white">{{ fieldStr(l, 'NOMBRE_EMPLEADO', 'nombre_empleado') || '—' }}</td>
                <td class="table-body-cell">{{ fmtDate(field(l, 'FECHA_LIQUIDACION', 'fecha_liquidacion')) }}</td>
                <td class="table-body-cell text-right font-mono">${{ fmt(field(l, 'VACACION_PROPORCIONAL', 'vacacion_proporcional')) }}</td>
                <td class="table-body-cell text-right font-mono">${{ fmt(field(l, 'AGUINALDO_PROPORCIONAL', 'aguinaldo_proporcional')) }}</td>
                <td class="table-body-cell text-right font-mono">${{ fmt(field(l, 'INDEMNIZACION_PROPORCIONAL', 'indemnizacion_proporcional')) }}</td>
                <td class="table-body-cell text-right font-bold text-emerald-600 dark:text-emerald-400 font-mono">${{ fmt(field(l, 'LIQUIDO_A_RECIBIR', 'liquido_a_recibir')) }}</td>
              </tr>
              <tr v-if="!items.length">
                <td colspan="6" class="table-body-cell text-center text-slate-500 dark:text-slate-400 py-8">No hay liquidaciones registradas.</td>
              </tr>
            </tbody>
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

      <AppModalShell :open="showCalc" @close="closeCalc">
        <div class="modal-panel w-full max-w-lg mx-auto">
          <div class="modal-body">
            <h3 class="modal-title">Calcular Liquidación</h3>

            <div>
              <label class="label-base">Empleado</label>
              <AsyncSelect
                v-model="form.ID_EMPLEADO"
                endpoint="/empleados/select"
                placeholder="Seleccionar empleado"
                search-placeholder="Buscar empleado…"
                @update:model-value="preview"
              />
            </div>

            <div>
              <label class="label-base">Fecha de liquidación</label>
              <input v-model="form.FECHA_LIQUIDACION" @change="preview" type="date" class="input-base" />
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
              <input type="checkbox" v-model="form.INCLUIR_INDEMNIZACION" @change="preview" class="rounded text-indigo-600" />
              Incluir indemnización
            </label>

            <div v-if="previewData" class="preview-box">
              <div class="flex justify-between"><span>Vacación proporcional</span><span class="font-mono">${{ fmt(previewData.VACACION_PROPORCIONAL ?? previewData.vacacion_proporcional) }}</span></div>
              <div class="flex justify-between"><span>Aguinaldo proporcional</span><span class="font-mono">${{ fmt(previewData.AGUINALDO_PROPORCIONAL ?? previewData.aguinaldo_proporcional) }}</span></div>
              <div class="flex justify-between"><span>Indemnización</span><span class="font-mono">${{ fmt(previewData.INDEMNIZACION_PROPORCIONAL ?? previewData.indemnizacion_proporcional) }}</span></div>
              <div class="flex justify-between font-bold border-t border-slate-200 dark:border-slate-600 pt-2 mt-2">
                <span>Líquido</span>
                <span class="text-emerald-600 dark:text-emerald-400 font-mono">${{ fmt(previewData.LIQUIDO_A_RECIBIR ?? previewData.liquido_a_recibir) }}</span>
              </div>
            </div>

            <p v-if="modalError" class="text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 p-2 rounded-lg">{{ modalError }}</p>

            <div class="flex justify-end gap-2 pt-2">
              <button data-no-lock type="button" @click="closeCalc" class="btn-secondary" :disabled="saving">Cancelar</button>
              <button type="button" @click="guardar" class="btn-primary" :disabled="saving || !previewData">
                {{ saving ? 'Guardando...' : 'Confirmar Liquidación' }}
              </button>
            </div>
          </div>
        </div>
      </AppModalShell>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import AppModalShell from '../../components/AppModalShell.vue';
import PaginationBar from '../../components/PaginationBar.vue';
import AsyncSelect from '../../components/AsyncSelect.vue';
import { usePaginatedList } from '../../composables/usePaginatedList';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';
import { field, fieldStr } from '../../utils/fields';

const {
  items,
  loading,
  page,
  perPage,
  total,
  lastPage,
  fetch: load,
  setPage,
  setPerPage,
} = usePaginatedList('/liquidaciones', { perPage: 25 });

const showCalc = ref(false);
const previewData = ref(null);
const modalError = ref('');
const saving = ref(false);

const defaultForm = () => ({
  ID_EMPLEADO: null,
  FECHA_LIQUIDACION: new Date().toISOString().slice(0, 10),
  INCLUIR_INDEMNIZACION: false,
});

const form = ref(defaultForm());

const fmt = (v) => Number(v ?? 0).toFixed(2);
const fmtDate = (d) => (d ? new Date(d).toLocaleDateString('es-SV') : '');

onMounted(load);

const resetForm = () => {
  form.value = defaultForm();
  previewData.value = null;
  modalError.value = '';
};

const openCalc = async () => {
  resetForm();
  showCalc.value = true;
  await preview();
};

const closeCalc = () => {
  showCalc.value = false;
  resetForm();
};

const preview = async () => {
  modalError.value = '';
  if (!form.value.ID_EMPLEADO) {
    previewData.value = null;
    return;
  }
  try {
    previewData.value = (await api.post('/liquidaciones/preview', form.value)).data;
  } catch (err) {
    previewData.value = null;
    modalError.value = err.response?.data?.message || err.response?.data?.error || 'No se pudo calcular la liquidación.';
  }
};

const guardar = async () => {
  if (saving.value || !previewData.value) return;
  if (!await dialog.confirm({
    title: 'Confirmar liquidación',
    message: '¿Confirma registrar esta liquidación? El empleado será inactivado.',
    variant: 'danger',
    confirmText: 'Sí, liquidar',
  })) return;

  saving.value = true;
  modalError.value = '';
  try {
    await api.post('/liquidaciones', form.value);
    closeCalc();
    await load();
  } catch (err) {
    modalError.value = err.response?.data?.message || err.response?.data?.error || 'Error al guardar la liquidación.';
  } finally {
    saving.value = false;
  }
};
</script>
