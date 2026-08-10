<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Planilla AFP</h1>
          <p class="page-subtitle mt-1 max-w-2xl">
            Genere el reporte de cotizaciones AFP (NUP, aporte laboral y patronal) de una planilla cerrada. Cada
            administradora (CRECER, CONFÍA, etc.) recibe su propio archivo, por lo que se descarga un CSV por AFP.
          </p>
        </div>
      </div>

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
              @change="onPlanillaChange"
            />
          </div>
        </div>
      </div>

      <SkeletonTable v-if="loadingCatalogo" :cols="4" :no-header="true" />

      <template v-else-if="catalogo.length">
        <!-- Catálogo por AFP -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
          <button
            v-for="c in catalogo"
            :key="c.ID_AFP ?? 'sin-afp'"
            type="button"
            @click="seleccionarAfp(c.ID_AFP)"
            :class="afpId === c.ID_AFP ? 'ring-2 ring-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 border-indigo-300' : 'border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700/40'"
            class="text-left p-4 rounded-xl border transition-all bg-white dark:bg-slate-800"
          >
            <div class="font-semibold text-sm text-slate-900 dark:text-white">{{ c.NOMBREAFP }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ c.TOTAL }} cotizante(s)</div>
            <div class="text-xs text-slate-500">Laboral: ${{ fmt(c.APORTE_LABORAL_TOTAL) }} · Patronal: ${{ fmt(c.APORTE_PATRONAL_TOTAL) }}</div>
          </button>
        </div>

        <div class="flex flex-wrap justify-end gap-2">
          <button
            @click="descargarSeleccionada"
            :disabled="busy"
            class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50 inline-flex items-center gap-2"
          >
            <AppIcon name="upload" size="sm" />
            {{ busy === 'one' ? 'Generando…' : (afpId ? `Descargar CSV — ${afpActual?.NOMBREAFP || ''}` : 'Descargar CSV (todas juntas)') }}
          </button>
          <button
            @click="descargarTodasPorAfp"
            :disabled="busy"
            class="px-4 py-2.5 border border-indigo-300 text-indigo-700 dark:text-indigo-300 rounded-lg text-sm font-semibold disabled:opacity-50 inline-flex items-center gap-2"
          >
            <AppIcon name="upload" size="sm" />
            {{ busy === 'all' ? 'Generando archivos…' : 'Descargar un archivo por AFP' }}
          </button>
        </div>

        <SkeletonTable v-if="loadingPreview" :cols="5" :no-header="true" />

        <div v-else-if="preview" class="table-shell table-scroll dark:border-slate-700">
          <table v-table-cards class="table-cards w-full text-sm text-left">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-xs font-semibold uppercase text-slate-600">
                <th class="px-4 py-3">NUP</th>
                <th class="px-4 py-3">Nombre</th>
                <th class="px-4 py-3">AFP</th>
                <th class="px-4 py-3 text-right">Aporte laboral</th>
                <th class="px-4 py-3 text-right">Aporte patronal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-for="(f, i) in preview.filas" :key="i" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-4 py-3 font-mono">{{ f.NUP || '—' }}</td>
                <td class="px-4 py-3">{{ f.NOMBRE }}</td>
                <td class="px-4 py-3">{{ f.AFP }}</td>
                <td class="px-4 py-3 text-right font-mono">${{ fmt(f.APORTE_LABORAL) }}</td>
                <td class="px-4 py-3 text-right font-mono">${{ fmt(f.APORTE_PATRONAL) }}</td>
              </tr>
              <tr v-if="!preview.filas.length">
                <td colspan="5" class="px-4 py-10 text-center text-slate-400">Sin cotizantes para este filtro.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <div v-else-if="planillaId && !loadingCatalogo" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-10 text-center text-slate-400">
        Esta planilla no tiene empleados cotizantes de AFP.
      </div>

      <div v-else class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-10 text-center text-slate-400">
        Seleccione una planilla cerrada para ver las cotizaciones AFP.
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
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
const afpId = ref(null);
const catalogo = ref([]);
const preview = ref(null);
const loadingCatalogo = ref(false);
const loadingPreview = ref(false);
const busy = ref(null);

const fmt = (v) => Number(v || 0).toFixed(2);
const afpActual = computed(() => catalogo.value.find((c) => c.ID_AFP === afpId.value) || null);

const onEmpresaChange = () => {
  planillaId.value = null;
  catalogo.value = [];
  preview.value = null;
};

const onPlanillaChange = async () => {
  afpId.value = null;
  preview.value = null;
  catalogo.value = [];
  if (!planillaId.value) return;

  loadingCatalogo.value = true;
  try {
    const { data } = await api.get('/cumplimiento/afp/catalogo', { params: { ID_PLANILLA: planillaId.value } });
    catalogo.value = data.data || [];
    await cargarPreview();
  } catch (err) {
    toast.error('Error al cargar catálogo AFP', getApiErrorMessage(err));
  } finally {
    loadingCatalogo.value = false;
  }
};

const cargarPreview = async () => {
  if (!planillaId.value) return;
  loadingPreview.value = true;
  try {
    const { data } = await api.get('/cumplimiento/afp/preview', {
      params: { ID_PLANILLA: planillaId.value, ID_AFP: afpId.value || undefined },
    });
    preview.value = data;
  } catch (err) {
    preview.value = null;
    toast.error('Error al cargar la vista previa', getApiErrorMessage(err));
  } finally {
    loadingPreview.value = false;
  }
};

const seleccionarAfp = (id) => {
  afpId.value = afpId.value === id ? null : id;
  cargarPreview();
};

const descargarUnAfp = async (id) => {
  const res = await api.get('/cumplimiento/afp/export', {
    params: { ID_PLANILLA: planillaId.value, ID_AFP: id || undefined },
    responseType: 'blob',
  });
  return downloadBlobResponse(res, `afp_planilla_${planillaId.value}.csv`);
};

const descargarSeleccionada = async () => {
  if (!planillaId.value || busy.value) return;
  busy.value = 'one';
  try {
    const filename = await descargarUnAfp(afpId.value);
    toast.success('Archivo generado', `${filename} se descargó correctamente.`);
  } catch (err) {
    toast.error('Error al descargar', await getBlobErrorMessage(err, 'No se pudo generar el archivo AFP.'));
  } finally {
    busy.value = null;
  }
};

const descargarTodasPorAfp = async () => {
  if (!planillaId.value || busy.value || !catalogo.value.length) return;
  busy.value = 'all';
  try {
    for (const c of catalogo.value) {
      await descargarUnAfp(c.ID_AFP);
    }
    toast.success('Archivos generados', `Se descargó un archivo por cada una de las ${catalogo.value.length} AFP.`);
  } catch (err) {
    toast.error('Error al descargar', await getBlobErrorMessage(err, 'No se pudo generar alguno de los archivos AFP.'));
  } finally {
    busy.value = null;
  }
};
</script>
