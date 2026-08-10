<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Planilla ISSS</h1>
          <p class="page-subtitle mt-1 max-w-2xl">
            Genere el reporte de cotizaciones ISSS (3% laboral / 7.5% patronal, techo $1,000) a partir de una planilla
            cerrada. El archivo resultante es un plano delimitado por punto y coma, listo para transcribir al portal
            patronal del ISSS.
          </p>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Empresa</label>
            <AsyncSelect v-model="empresaId" catalog="empresas" placeholder="Todas las empresas" nullable @change="onEmpresaChange" />
          </div>
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Planilla cerrada</label>
            <AsyncSelect
              v-model="planillaId"
              catalog="planillas-cerradas"
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
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-white dark:bg-slate-800 rounded-xl border p-4">
            <div class="text-xs text-slate-500 uppercase font-semibold">N° Patronal ISSS</div>
            <div class="text-lg font-bold text-slate-800 dark:text-white mt-1">{{ reporte.numero_patronal }}</div>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-xl border p-4">
            <div class="text-xs text-slate-500 uppercase font-semibold">Cotizantes</div>
            <div class="text-2xl font-bold mt-1">{{ reporte.totales.count }}</div>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-xl border p-4">
            <div class="text-xs text-slate-500 uppercase font-semibold">Cotización laboral</div>
            <div class="text-2xl font-bold text-indigo-600 mt-1">${{ fmt(reporte.totales.laboral) }}</div>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-xl border p-4">
            <div class="text-xs text-slate-500 uppercase font-semibold">Cotización patronal</div>
            <div class="text-2xl font-bold text-indigo-600 mt-1">${{ fmt(reporte.totales.patronal) }}</div>
          </div>
        </div>

        <div class="flex justify-end">
          <button
            @click="descargar"
            :disabled="busy || !reporte.filas.length"
            class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50 inline-flex items-center gap-2"
          >
            <AppIcon name="upload" size="sm" />
            {{ busy ? 'Generando…' : 'Descargar CSV ISSS' }}
          </button>
        </div>

        <div class="table-shell table-scroll dark:border-slate-700">
          <table v-table-cards class="table-cards w-full text-sm text-left">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-xs font-semibold uppercase text-slate-600">
                <th class="px-4 py-3">N° ISSS</th>
                <th class="px-4 py-3">DUI</th>
                <th class="px-4 py-3">Nombre</th>
                <th class="px-4 py-3 text-right">Salario cotizable</th>
                <th class="px-4 py-3 text-right">Cotiz. laboral</th>
                <th class="px-4 py-3 text-right">Cotiz. patronal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-for="(f, i) in reporte.filas" :key="i" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-4 py-3 font-mono">{{ f.ISSS_EMPLEADO_NUM || '—' }}</td>
                <td class="px-4 py-3 font-mono">{{ f.DUI || '—' }}</td>
                <td class="px-4 py-3">{{ f.NOMBRE }}</td>
                <td class="px-4 py-3 text-right font-mono">${{ fmt(f.SALARIO_COTIZABLE) }}</td>
                <td class="px-4 py-3 text-right font-mono">${{ fmt(f.COTIZACION_LABORAL) }}</td>
                <td class="px-4 py-3 text-right font-mono">${{ fmt(f.COTIZACION_PATRONAL) }}</td>
              </tr>
              <tr v-if="!reporte.filas.length">
                <td colspan="6" class="px-4 py-10 text-center text-slate-400">
                  Esta planilla no tiene empleados cotizantes de ISSS.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <div v-else class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-10 text-center text-slate-400">
        Seleccione una planilla cerrada para ver el reporte de cotizaciones ISSS.
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import AsyncSelect from '../../components/AsyncSelect.vue';
import AppIcon from '../../components/AppIcon.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import api from '../../services/api';
import { useToast } from '../../composables/useToast';
import { getApiErrorMessage } from '../../utils/apiError';
import { downloadBlobResponse, getBlobErrorMessage } from '../../utils/downloadBlob';

const toast = useToast();

const empresaId = ref(null);
const planillaId = ref(null);
const reporte = ref(null);
const loading = ref(false);
const busy = ref(false);

const fmt = (v) => Number(v || 0).toFixed(2);

const onEmpresaChange = () => {
  planillaId.value = null;
  reporte.value = null;
};

const cargarPreview = async () => {
  if (!planillaId.value) {
    reporte.value = null;
    return;
  }
  loading.value = true;
  try {
    const { data } = await api.get('/cumplimiento/isss/preview', { params: { ID_PLANILLA: planillaId.value } });
    reporte.value = data;
  } catch (err) {
    reporte.value = null;
    toast.error('Error al cargar el reporte ISSS', getApiErrorMessage(err));
  } finally {
    loading.value = false;
  }
};

const descargar = async () => {
  if (!planillaId.value || busy.value) return;
  busy.value = true;
  try {
    const res = await api.get('/cumplimiento/isss/export', {
      params: { ID_PLANILLA: planillaId.value },
      responseType: 'blob',
    });
    const filename = downloadBlobResponse(res, `isss_planilla_${planillaId.value}.csv`);
    toast.success('Archivo generado', `${filename} se descargó correctamente.`);
  } catch (err) {
    toast.error('Error al descargar', await getBlobErrorMessage(err, 'No se pudo generar el archivo ISSS.'));
  } finally {
    busy.value = false;
  }
};
</script>
