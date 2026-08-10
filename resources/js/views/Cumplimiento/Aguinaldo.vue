<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Aguinaldo (corrida)</h1>
          <p class="page-subtitle mt-1 max-w-2xl">
            Previsualice el cálculo de aguinaldo de fin de año para los empleados activos de una empresa y, si
            procede, genere el encabezado de la planilla de aguinaldo.
          </p>
        </div>
        <div class="page-header-actions">
          <button class="btn-primary" :disabled="!empresaId" @click="openCrear">+ Crear planilla de aguinaldo</button>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="sm:col-span-2">
            <label class="label-base">Empresa</label>
            <AsyncSelect v-model="empresaId" catalog="empresas" placeholder="Seleccionar empresa" @change="cargarPreview" />
          </div>
          <div>
            <label class="label-base">Fecha de corte</label>
            <input v-model="fechaCorte" type="date" class="input-base" @change="cargarPreview" />
          </div>
        </div>
      </div>

      <SkeletonTable v-if="loading" :cols="6" :no-header="true" />

      <template v-else-if="reporte">
        <!-- Resumen -->
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-xs text-slate-500 uppercase font-semibold">Empleados con derecho</div>
            <div class="text-2xl font-bold mt-1">{{ reporte.totales.count }}</div>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
            <div class="text-xs text-slate-500 uppercase font-semibold">Monto total estimado</div>
            <div class="text-2xl font-bold text-indigo-600 mt-1">${{ fmt(reporte.totales.monto) }}</div>
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
                <th class="px-4 py-3">Código</th>
                <th class="px-4 py-3">Nombre</th>
                <th class="px-4 py-3">Fecha ingreso</th>
                <th class="px-4 py-3 text-right">Días aguinaldo</th>
                <th class="px-4 py-3 text-right">Salario base</th>
                <th class="px-4 py-3 text-right">Monto aguinaldo</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-for="(f, i) in reporte.filas" :key="i" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-4 py-3 font-mono">{{ f.CODIGOEMPLEADO || '—' }}</td>
                <td class="px-4 py-3">{{ f.NOMBRE }}</td>
                <td class="px-4 py-3">{{ fmtDate(f.FECHAINGRESO) }}</td>
                <td class="px-4 py-3 text-right font-mono">{{ f.DIAS_AGUINALDO }}</td>
                <td class="px-4 py-3 text-right font-mono">${{ fmt(f.SALARIO_BASE) }}</td>
                <td class="px-4 py-3 text-right font-mono font-semibold">${{ fmt(f.MONTO_AGUINALDO) }}</td>
              </tr>
              <tr v-if="!reporte.filas.length">
                <td colspan="6" class="px-4 py-10 text-center text-slate-400">
                  No hay empleados con derecho a aguinaldo para esta empresa.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <div v-else class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-10 text-center text-slate-400">
        Seleccione una empresa y una fecha de corte para calcular el aguinaldo.
      </div>

      <!-- Modal crear planilla -->
      <AppModalShell :open="showCrear" @close="showCrear = false">
        <div class="modal-panel w-full max-w-lg mx-auto">
          <div class="modal-body">
            <h3 class="modal-title">Crear planilla de aguinaldo</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Se crea únicamente el encabezado de la planilla; el detalle se calcula luego desde el módulo de
              Planilla ("Calcular").
            </p>

            <div>
              <label class="label-base">Título</label>
              <input v-model="crearForm.TITULO" type="text" class="input-base" placeholder="Aguinaldo 2026" />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label-base">Periodo laboral</label>
                <AsyncSelect v-model="crearForm.ID_PERIODO" catalog="periodos-laborales" placeholder="Seleccionar periodo" />
              </div>
              <div>
                <label class="label-base">Frecuencia de pago</label>
                <AsyncSelect v-model="crearForm.ID_FRECUENCIAPAGO" catalog="frecuencias-pago" placeholder="Seleccionar frecuencia" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="label-base">Cuenta contable</label>
                <AsyncSelect v-model="crearForm.ID_CUENTA" catalog="cuentas" placeholder="Seleccionar cuenta" />
              </div>
              <div>
                <label class="label-base">Fecha de pago</label>
                <input v-model="crearForm.FECHAPAGO" type="date" class="input-base" />
              </div>
            </div>

            <div>
              <label class="label-base">Forma de pago</label>
              <AsyncSelect v-model="crearForm.FORMAPAGO" :options="FORMA_PAGO_OPTIONS" :searchable="false" placeholder="Forma de pago" />
            </div>

            <div>
              <label class="label-base">Observación</label>
              <textarea v-model="crearForm.OBSERVACION" rows="2" class="input-base" placeholder="Opcional"></textarea>
            </div>

            <p v-if="crearError" class="text-xs text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 p-2 rounded-lg">{{ crearError }}</p>

            <div class="flex justify-end gap-2 pt-2">
              <button type="button" data-no-lock @click="showCrear = false" class="btn-secondary" :disabled="creando">Cancelar</button>
              <button type="button" @click="crearPlanilla" class="btn-primary" :disabled="creando">
                {{ creando ? 'Creando…' : 'Crear planilla' }}
              </button>
            </div>
          </div>
        </div>
      </AppModalShell>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import AsyncSelect from '../../components/AsyncSelect.vue';
import AppIcon from '../../components/AppIcon.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import AppModalShell from '../../components/AppModalShell.vue';
import api from '../../services/api';
import { useToast } from '../../composables/useToast';
import { getApiErrorMessage } from '../../utils/apiError';
import { downloadBlobResponse, getBlobErrorMessage } from '../../utils/downloadBlob';
import { FORMA_PAGO_OPTIONS } from '../../utils/staticSelectOptions';

const toast = useToast();

const empresaId = ref(null);
const fechaCorte = ref(new Date().toISOString().slice(0, 10));
const reporte = ref(null);
const loading = ref(false);
const busy = ref(false);

const showCrear = ref(false);
const creando = ref(false);
const crearError = ref('');
const crearForm = ref(defaultCrearForm());

function defaultCrearForm() {
  return {
    TITULO: '',
    ID_PERIODO: null,
    ID_FRECUENCIAPAGO: null,
    ID_CUENTA: null,
    FECHAPAGO: new Date().toISOString().slice(0, 10),
    FORMAPAGO: 'Transferencia',
    OBSERVACION: '',
  };
}

const fmt = (v) => Number(v || 0).toFixed(2);
const fmtDate = (d) => (d ? new Date(d).toLocaleDateString('es-SV') : '—');

const cargarPreview = async () => {
  if (!empresaId.value || !fechaCorte.value) {
    reporte.value = null;
    return;
  }
  loading.value = true;
  try {
    const { data } = await api.get('/cumplimiento/aguinaldo/preview', {
      params: { ID_EMPRESA: empresaId.value, FECHA_CORTE: fechaCorte.value },
    });
    reporte.value = data;
  } catch (err) {
    reporte.value = null;
    toast.error('Error al calcular el aguinaldo', getApiErrorMessage(err));
  } finally {
    loading.value = false;
  }
};

const descargar = async () => {
  if (!empresaId.value || !fechaCorte.value || busy.value) return;
  busy.value = true;
  try {
    const res = await api.get('/cumplimiento/aguinaldo/export', {
      params: { ID_EMPRESA: empresaId.value, FECHA_CORTE: fechaCorte.value },
      responseType: 'blob',
    });
    const filename = downloadBlobResponse(res, `aguinaldo_${empresaId.value}.csv`);
    toast.success('Archivo generado', `${filename} se descargó correctamente.`);
  } catch (err) {
    toast.error('Error al descargar', await getBlobErrorMessage(err, 'No se pudo generar el archivo de aguinaldo.'));
  } finally {
    busy.value = false;
  }
};

const openCrear = () => {
  if (!empresaId.value) return;
  crearForm.value = defaultCrearForm();
  crearForm.value.TITULO = `Aguinaldo ${new Date().getFullYear()}`;
  crearError.value = '';
  showCrear.value = true;
};

const crearPlanilla = async () => {
  if (creando.value) return;
  crearError.value = '';
  creando.value = true;
  try {
    const { data } = await api.post('/cumplimiento/aguinaldo/crear-planilla', {
      ID_EMPRESA: empresaId.value,
      ...crearForm.value,
    });
    showCrear.value = false;
    toast.success('Planilla creada', data.message || 'La planilla de aguinaldo fue creada correctamente.');
  } catch (err) {
    crearError.value = getApiErrorMessage(err, 'No se pudo crear la planilla de aguinaldo.');
  } finally {
    creando.value = false;
  }
};
</script>
