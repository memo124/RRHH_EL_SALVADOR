<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Altas y bajas ISSS</h1>
          <p class="page-subtitle mt-1 max-w-2xl">
            Bitácora de movimientos (altas y bajas) pendientes de transcribir al portal patronal del ISSS. Marque
            los movimientos como enviados una vez reportados.
          </p>
        </div>
        <div class="page-header-actions">
          <button
            @click="descargar"
            :disabled="busy || !items.length"
            class="btn-primary"
          >
            {{ busy ? 'Generando…' : 'Descargar CSV' }}
          </button>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="label-base">Tipo de movimiento</label>
            <AsyncSelect v-model="tipo" :options="TIPO_OPTIONS" :searchable="false" nullable placeholder="Todos" @change="cargar" />
          </div>
          <div>
            <label class="label-base">Estado</label>
            <AsyncSelect v-model="estado" :options="ESTADO_OPTIONS" :searchable="false" nullable placeholder="Todos" @change="cargar" />
          </div>
          <div class="flex items-end">
            <button
              @click="marcarEnviados"
              :disabled="!seleccionados.length || marcando"
              class="btn-secondary w-full"
            >
              {{ marcando ? 'Actualizando…' : `Marcar como enviado (${seleccionados.length})` }}
            </button>
          </div>
        </div>
      </div>

      <SkeletonTable v-if="loading" :cols="6" />

      <div v-else class="table-shell">
        <div class="overflow-x-auto">
          <table v-table-cards class="table-cards table-base">
            <thead>
              <tr class="table-head-row">
                <th class="table-head-cell">
                  <input type="checkbox" class="rounded text-indigo-600" :checked="allSelected" @change="toggleAll" />
                </th>
                <th class="table-head-cell">Tipo</th>
                <th class="table-head-cell">Código</th>
                <th class="table-head-cell">N° ISSS</th>
                <th class="table-head-cell">Empleado</th>
                <th class="table-head-cell">Fecha</th>
                <th class="table-head-cell">Estado</th>
              </tr>
            </thead>
            <tbody class="table-body">
              <tr v-for="(m, i) in items" :key="m.ID_MOVIMIENTO" :class="i % 2 === 0 ? 'table-row-even' : 'table-row-odd'">
                <td class="table-body-cell">
                  <input
                    type="checkbox"
                    class="rounded text-indigo-600"
                    :disabled="m.ESTADO !== 'pendiente'"
                    :checked="seleccionados.includes(m.ID_MOVIMIENTO)"
                    @change="toggleUno(m.ID_MOVIMIENTO)"
                  />
                </td>
                <td class="table-body-cell">
                  <span :class="m.TIPO === 'alta' ? 'badge-success' : 'badge-danger'">{{ m.TIPO === 'alta' ? 'Alta' : 'Baja' }}</span>
                </td>
                <td class="table-body-cell font-mono">{{ m.CODIGOEMPLEADO || '—' }}</td>
                <td class="table-body-cell font-mono">{{ m.ISSS_EMPLEADO || '—' }}</td>
                <td class="table-body-cell font-semibold text-slate-900 dark:text-white">{{ m.NOMBRE_EMPLEADO }}</td>
                <td class="table-body-cell">{{ fmtDate(m.FECHA) }}</td>
                <td class="table-body-cell">
                  <span :class="m.ESTADO === 'enviado' ? 'badge-success' : 'badge-danger'">{{ m.ESTADO === 'enviado' ? 'Enviado' : 'Pendiente' }}</span>
                </td>
              </tr>
              <tr v-if="!items.length">
                <td colspan="7" class="table-body-cell text-center text-slate-500 dark:text-slate-400 py-8">No hay movimientos para los filtros seleccionados.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import AsyncSelect from '../../components/AsyncSelect.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';
import { useToast } from '../../composables/useToast';
import { getApiErrorMessage } from '../../utils/apiError';
import { downloadBlobResponse, getBlobErrorMessage } from '../../utils/downloadBlob';

const TIPO_OPTIONS = [
  { value: 'alta', label: 'Alta' },
  { value: 'baja', label: 'Baja' },
];

const ESTADO_OPTIONS = [
  { value: 'pendiente', label: 'Pendiente' },
  { value: 'enviado', label: 'Enviado' },
];

const toast = useToast();

const items = ref([]);
const loading = ref(false);
const busy = ref(false);
const marcando = ref(false);
const tipo = ref(null);
const estado = ref('pendiente');
const seleccionados = ref([]);

const pendientes = computed(() => items.value.filter((m) => m.ESTADO === 'pendiente'));
const allSelected = computed(() => pendientes.value.length > 0 && seleccionados.value.length === pendientes.value.length);

const fmtDate = (d) => (d ? new Date(d).toLocaleDateString('es-SV') : '—');

const cargar = async () => {
  loading.value = true;
  seleccionados.value = [];
  try {
    const { data } = await api.get('/cumplimiento/isss-movimientos', {
      params: { tipo: tipo.value || undefined, estado: estado.value || undefined },
    });
    items.value = data.data || [];
  } catch (err) {
    items.value = [];
    toast.error('Error al cargar movimientos', getApiErrorMessage(err));
  } finally {
    loading.value = false;
  }
};

const toggleUno = (id) => {
  const idx = seleccionados.value.indexOf(id);
  if (idx === -1) {
    seleccionados.value.push(id);
  } else {
    seleccionados.value.splice(idx, 1);
  }
};

const toggleAll = () => {
  seleccionados.value = allSelected.value ? [] : pendientes.value.map((m) => m.ID_MOVIMIENTO);
};

const marcarEnviados = async () => {
  if (!seleccionados.value.length || marcando.value) return;
  if (!await dialog.confirm({
    title: 'Marcar como enviado',
    message: `¿Confirma que ${seleccionados.value.length} movimiento(s) ya fueron reportados al ISSS?`,
    confirmText: 'Sí, marcar como enviado',
  })) return;

  marcando.value = true;
  try {
    await api.post('/cumplimiento/isss-movimientos/marcar-enviado', { ids: seleccionados.value });
    await cargar();
  } catch (err) {
    await dialog.alert({ title: 'Error', message: getApiErrorMessage(err), variant: 'danger' });
  } finally {
    marcando.value = false;
  }
};

const descargar = async () => {
  if (busy.value) return;
  busy.value = true;
  try {
    const res = await api.get('/cumplimiento/isss-movimientos/export', {
      params: { tipo: tipo.value || undefined, estado: estado.value || undefined },
      responseType: 'blob',
    });
    const filename = downloadBlobResponse(res, 'isss_movimientos.csv');
    toast.success('Archivo generado', `${filename} se descargó correctamente.`);
  } catch (err) {
    toast.error('Error al descargar', await getBlobErrorMessage(err, 'No se pudo generar el archivo de movimientos.'));
  } finally {
    busy.value = false;
  }
};

onMounted(cargar);
</script>
