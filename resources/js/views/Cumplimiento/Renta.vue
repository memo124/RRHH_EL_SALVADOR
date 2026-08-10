<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">F-14 / Renta retenida MH</h1>
          <p class="page-subtitle mt-1 max-w-2xl">
            Acumulado anual de renta retenida por empleado, como insumo para el Formulario F-14 (Declaración e
            Informe Anual de Retenciones) del Ministerio de Hacienda.
          </p>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Empresa</label>
            <AsyncSelect v-model="empresaId" catalog="empresas" placeholder="Seleccionar empresa" @change="cargarPreview" />
          </div>
          <div>
            <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Año</label>
            <input v-model.number="anio" type="number" min="2000" max="2100" class="input-base" @change="cargarPreview" />
          </div>
        </div>
      </div>

      <SkeletonTable v-if="loading" :cols="6" :no-header="true" />

      <template v-else-if="reporte">
        <!-- Resumen -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-xs text-slate-500 uppercase font-semibold">Empleados</div>
            <div class="text-2xl font-bold mt-1">{{ reporte.totales.count }}</div>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-xs text-slate-500 uppercase font-semibold">Total devengado {{ reporte.anio }}</div>
            <div class="text-xl font-bold text-slate-800 dark:text-white mt-1">${{ fmt(reporte.totales.devengado) }}</div>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 col-span-2 sm:col-span-1">
            <div class="text-xs text-slate-500 uppercase font-semibold">Total renta retenida</div>
            <div class="text-2xl font-bold text-indigo-600 mt-1">${{ fmt(reporte.totales.renta) }}</div>
          </div>
        </div>

        <div class="flex justify-end">
          <button
            @click="descargar"
            :disabled="busy || !reporte.filas.length"
            class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50 inline-flex items-center gap-2"
          >
            <AppIcon name="upload" size="sm" />
            {{ busy ? 'Generando…' : 'Descargar CSV F-14' }}
          </button>
        </div>

        <div class="table-shell table-scroll dark:border-slate-700">
          <table v-table-cards class="table-cards w-full text-sm text-left">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-xs font-semibold uppercase text-slate-600">
                <th class="px-4 py-3">NIT</th>
                <th class="px-4 py-3">DUI</th>
                <th class="px-4 py-3">Nombre</th>
                <th class="px-4 py-3 text-right">Devengado anual</th>
                <th class="px-4 py-3 text-right">Renta retenida</th>
                <th class="px-4 py-3 text-right">Periodos</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-for="(f, i) in reporte.filas" :key="i" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-4 py-3 font-mono">{{ f.NIT || '—' }}</td>
                <td class="px-4 py-3 font-mono">{{ f.DUI || '—' }}</td>
                <td class="px-4 py-3">{{ f.NOMBRE }}</td>
                <td class="px-4 py-3 text-right font-mono">${{ fmt(f.TOTAL_DEVENGADO) }}</td>
                <td class="px-4 py-3 text-right font-mono">${{ fmt(f.TOTAL_RENTA) }}</td>
                <td class="px-4 py-3 text-right font-mono">{{ f.PERIODOS }}</td>
              </tr>
              <tr v-if="!reporte.filas.length">
                <td colspan="6" class="px-4 py-10 text-center text-slate-400">
                  No hay planillas cerradas con renta retenida para la empresa y año seleccionados.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <div v-else class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-10 text-center text-slate-400">
        Seleccione una empresa y un año para generar el acumulado de renta retenida.
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
const anio = ref(new Date().getFullYear());
const reporte = ref(null);
const loading = ref(false);
const busy = ref(false);

const fmt = (v) => Number(v || 0).toFixed(2);

const cargarPreview = async () => {
  if (!empresaId.value || !anio.value) {
    reporte.value = null;
    return;
  }
  loading.value = true;
  try {
    const { data } = await api.get('/cumplimiento/renta/preview', {
      params: { ID_EMPRESA: empresaId.value, ANIO: anio.value },
    });
    reporte.value = data;
  } catch (err) {
    reporte.value = null;
    toast.error('Error al cargar el reporte de renta retenida', getApiErrorMessage(err));
  } finally {
    loading.value = false;
  }
};

const descargar = async () => {
  if (!empresaId.value || !anio.value || busy.value) return;
  busy.value = true;
  try {
    const res = await api.get('/cumplimiento/renta/export', {
      params: { ID_EMPRESA: empresaId.value, ANIO: anio.value },
      responseType: 'blob',
    });
    const filename = downloadBlobResponse(res, `f14_renta_${empresaId.value}_${anio.value}.csv`);
    toast.success('Archivo generado', `${filename} se descargó correctamente.`);
  } catch (err) {
    toast.error('Error al descargar', await getBlobErrorMessage(err, 'No se pudo generar el archivo de renta retenida.'));
  } finally {
    busy.value = false;
  }
};
</script>
