<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Retención 10% servicios profesionales</h1>
          <p class="page-subtitle mt-1 max-w-2xl">
            Retención del 10% sobre honorarios por servicios profesionales (Art. 156 Código Tributario). Consulte
            una estimación vigente o el detalle ya calculado en una planilla cerrada del grupo de honorarios.
          </p>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 space-y-4">
        <div class="flex border-b border-slate-200 dark:border-slate-700">
          <button
            v-for="m in MODOS"
            :key="m.key"
            @click="modo = m.key"
            :class="modo === m.key ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
            class="py-2 px-4 border-b-2 text-sm font-medium transition-all"
          >
            {{ m.label }}
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="label-base">Empresa</label>
            <AsyncSelect v-model="empresaId" catalog="empresas" placeholder="Todas las empresas" nullable @change="onEmpresaChange" />
          </div>
          <div v-if="modo === 'planilla'" class="sm:col-span-2">
            <label class="label-base">Planilla cerrada</label>
            <AsyncSelect
              v-model="planillaId"
              endpoint="/cumplimiento/retencion10/planillas"
              :params="{ ID_EMPRESA: empresaId }"
              placeholder="Seleccionar planilla cerrada"
              @change="cargarPreview"
            />
          </div>
        </div>
      </div>

      <SkeletonTable v-if="loading" :cols="6" :no-header="true" />

      <template v-else-if="reporte">
        <!-- Resumen -->
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-xs text-slate-500 uppercase font-semibold">Cotizantes</div>
            <div class="text-2xl font-bold mt-1">{{ reporte.totales.count }}</div>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-xs text-slate-500 uppercase font-semibold">Total retención 10%</div>
            <div class="text-2xl font-bold text-indigo-600 mt-1">${{ fmt(reporte.totales.retencion) }}</div>
          </div>
        </div>

        <div class="flex justify-end">
          <button
            @click="descargar"
            :disabled="busy || !reporte.filas.length"
            class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50 inline-flex items-center gap-2"
          >
            <AppIcon name="upload" size="sm" />
            {{ busy ? 'Generando…' : 'Descargar CSV' }}
          </button>
        </div>

        <div class="table-shell table-scroll dark:border-slate-700">
          <table v-table-cards class="table-cards w-full text-sm text-left">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-xs font-semibold uppercase text-slate-600">
                <th class="px-4 py-3">Código/NIT</th>
                <th class="px-4 py-3">Nombre</th>
                <th class="px-4 py-3">Tipo de contratación</th>
                <th class="px-4 py-3 text-right">Honorario / Devengado</th>
                <th class="px-4 py-3 text-right">%</th>
                <th class="px-4 py-3 text-right">Retención</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-for="(f, i) in reporte.filas" :key="i" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-4 py-3 font-mono">{{ f.NIT || f.CODIGOEMPLEADO || '—' }}</td>
                <td class="px-4 py-3">{{ f.NOMBRE }}</td>
                <td class="px-4 py-3">{{ f.TIPO_CONTRATACION || '—' }}</td>
                <td class="px-4 py-3 text-right font-mono">${{ fmt(f.HONORARIO_MENSUAL ?? f.DEVENGADO_GRAVADO) }}</td>
                <td class="px-4 py-3 text-right font-mono">{{ Number(f.PORCENTAJE || 10).toFixed(2) }}%</td>
                <td class="px-4 py-3 text-right font-mono font-semibold">${{ fmt(f.RETENCION_ESTIMADA ?? f.RETENCION) }}</td>
              </tr>
              <tr v-if="!reporte.filas.length">
                <td colspan="6" class="px-4 py-10 text-center text-slate-400">
                  No hay empleados con retención del 10% para el filtro seleccionado.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <div v-else class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-10 text-center text-slate-400">
        {{ modo === 'planilla' ? 'Seleccione una planilla cerrada para ver la retención calculada.' : 'Consultando la estimación vigente de retención del 10%…' }}
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import AsyncSelect from '../../components/AsyncSelect.vue';
import AppIcon from '../../components/AppIcon.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import api from '../../services/api';
import { useToast } from '../../composables/useToast';
import { getApiErrorMessage } from '../../utils/apiError';
import { downloadBlobResponse, getBlobErrorMessage } from '../../utils/downloadBlob';

const MODOS = [
  { key: 'estimado', label: 'Estimación vigente' },
  { key: 'planilla', label: 'Planilla cerrada' },
];

const toast = useToast();

const modo = ref('estimado');
const empresaId = ref(null);
const planillaId = ref(null);
const reporte = ref(null);
const loading = ref(false);
const busy = ref(false);

const fmt = (v) => Number(v || 0).toFixed(2);

const onEmpresaChange = () => {
  planillaId.value = null;
  if (modo.value === 'estimado') {
    cargarPreview();
  } else {
    reporte.value = null;
  }
};

const cargarPreview = async () => {
  loading.value = true;
  try {
    if (modo.value === 'planilla') {
      if (!planillaId.value) {
        reporte.value = null;
        return;
      }
      const { data } = await api.get('/cumplimiento/retencion10/preview', { params: { ID_PLANILLA: planillaId.value } });
      reporte.value = data;
    } else {
      const { data } = await api.get('/cumplimiento/retencion10/estimacion', {
        params: { ID_EMPRESA: empresaId.value || undefined },
      });
      reporte.value = data;
    }
  } catch (err) {
    reporte.value = null;
    toast.error('Error al cargar la retención del 10%', getApiErrorMessage(err));
  } finally {
    loading.value = false;
  }
};

const descargar = async () => {
  if (busy.value) return;
  busy.value = true;
  try {
    const params = modo.value === 'planilla'
      ? { ID_PLANILLA: planillaId.value }
      : { ID_EMPRESA: empresaId.value || undefined };
    const res = await api.get('/cumplimiento/retencion10/export', { params, responseType: 'blob' });
    const filename = downloadBlobResponse(res, 'retencion10.csv');
    toast.success('Archivo generado', `${filename} se descargó correctamente.`);
  } catch (err) {
    toast.error('Error al descargar', await getBlobErrorMessage(err, 'No se pudo generar el archivo de retención.'));
  } finally {
    busy.value = false;
  }
};

watch(modo, () => {
  planillaId.value = null;
  reporte.value = null;
  cargarPreview();
});

onMounted(cargarPreview);
</script>
