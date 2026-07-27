<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[92vh] overflow-hidden border border-slate-200 dark:border-slate-700 flex flex-col">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-gradient-to-r from-blue-600 to-indigo-700 text-white flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold">Archivo bancario</h3>
          <p class="text-blue-100 text-xs mt-0.5">{{ planillaTitulo }} · Seleccione banco, formato y columnas</p>
        </div>
        <button @click="$emit('close')" class="text-white/80 hover:text-white text-xl leading-none">✕</button>
      </div>

      <div v-if="loading" class="p-10 text-center text-slate-500 animate-pulse">Cargando opciones...</div>

      <div v-else class="overflow-y-auto flex-1 p-6 space-y-6">
        <!-- Banco -->
        <section>
          <h4 class="text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-3">1. Banco destino</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <button v-for="b in catalog.banks" :key="b.ID_BANCO ?? 'all'" type="button"
              @click="selectBank(b)"
              :class="form.ID_BANCO === b.ID_BANCO ? 'ring-2 ring-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 border-indigo-300' : 'border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700/40'"
              class="text-left p-3 rounded-xl border transition-all">
              <div class="font-semibold text-sm text-slate-900 dark:text-white">{{ b.NOMBREBANCO }}</div>
              <div class="text-xs text-slate-500 mt-0.5">{{ b.TOTAL }} empleado(s) · ${{ fmt(b.LIQUIDO_TOTAL) }}</div>
            </button>
          </div>
        </section>

        <!-- Formato -->
        <section>
          <h4 class="text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-3">2. Tipo de archivo</h4>
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
            <button v-for="f in catalog.formats" :key="f.key" type="button"
              @click="form.format = f.key"
              :class="form.format === f.key ? 'ring-2 ring-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 border-emerald-300' : 'border-slate-200 dark:border-slate-600'"
              class="p-3 rounded-xl border text-left transition-all">
              <div class="font-semibold text-sm text-slate-900 dark:text-white">{{ f.label }}</div>
              <div class="text-[10px] text-slate-500 mt-1 leading-snug">{{ f.description }}</div>
            </button>
          </div>
        </section>

        <!-- Opciones extra -->
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div v-if="showDelimiter">
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Delimitador</label>
            <AsyncSelect
              v-model="form.delimiter"
              :options="BANK_DELIMITER_OPTIONS"
              :searchable="false"
              placeholder="Delimitador"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Formato montos</label>
            <AsyncSelect
              v-model="form.amount_format"
              :options="BANK_AMOUNT_FORMAT_OPTIONS"
              :searchable="false"
              placeholder="Formato montos"
            />
          </div>
          <div class="flex flex-col justify-end gap-2 text-sm">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" v-model="form.include_header" class="rounded text-indigo-600" />
              <span>Incluir encabezados</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" v-model="form.solo_con_cuenta" class="rounded text-indigo-600" />
              <span>Solo empleados con cuenta</span>
            </label>
          </div>
        </section>

        <!-- Columnas -->
        <section>
          <div class="flex justify-between items-center mb-3">
            <h4 class="text-xs font-bold uppercase text-slate-500 dark:text-slate-400">3. Columnas a exportar</h4>
            <div class="space-x-2 text-xs">
              <button type="button" @click="selectDefaultColumns" class="text-indigo-600 font-semibold hover:underline">Predeterminadas</button>
              <button type="button" @click="selectAllColumns" class="text-indigo-600 font-semibold hover:underline">Todas</button>
              <button type="button" @click="clearColumns" class="text-rose-600 font-semibold hover:underline">Ninguna</button>
            </div>
          </div>

          <div v-for="(groupLabel, groupKey) in catalog.column_groups" :key="groupKey" class="mb-4">
            <p class="text-[11px] font-bold text-slate-400 uppercase mb-2">{{ groupLabel }}</p>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
              <label v-for="col in columnsByGroup(groupKey)" :key="col.key"
                class="flex items-start gap-2 p-2 rounded-lg border border-slate-200 dark:border-slate-600 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700/30 text-xs">
                <input type="checkbox" :value="col.key" v-model="form.columns" class="mt-0.5 rounded text-indigo-600" />
                <span class="text-slate-700 dark:text-slate-200">{{ col.label }}</span>
              </label>
            </div>
          </div>
        </section>

        <!-- Preview -->
        <section v-if="previewData" class="border border-slate-200 dark:border-slate-600 rounded-xl overflow-hidden">
          <div class="px-4 py-2 bg-slate-50 dark:bg-slate-700/50 flex justify-between items-center text-xs">
            <span class="font-semibold text-slate-700 dark:text-slate-200">Vista previa ({{ previewData.count }} registros · Total ${{ fmt(previewData.total_liquido) }})</span>
            <button type="button" data-no-lock @click="loadPreview" :disabled="busy" class="text-indigo-600 font-semibold hover:underline disabled:opacity-50">Actualizar</button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-[11px]">
              <thead>
                <tr class="bg-slate-100 dark:bg-slate-700/60">
                  <th v-for="col in previewData.columns" :key="col.key" class="px-3 py-2 font-semibold whitespace-nowrap">{{ col.label }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, i) in previewData.preview" :key="i" class="border-t border-slate-200 dark:border-slate-600">
                  <td v-for="col in previewData.columns" :key="col.key" class="px-3 py-1.5 whitespace-nowrap">{{ row[col.key] }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <p v-if="error" class="text-xs text-rose-600 bg-rose-50 dark:bg-rose-900/20 p-2 rounded">{{ error }}</p>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/40 flex justify-between items-center">
        <p class="text-xs text-slate-500">{{ form.columns.length }} columna(s) seleccionada(s)</p>
        <div class="flex gap-2">
          <button type="button" data-no-lock @click="$emit('close')" class="px-4 py-2 border rounded-lg text-sm hover:bg-white dark:hover:bg-slate-700">Cancelar</button>
          <button type="button" data-no-lock @click="loadPreview" :disabled="!form.columns.length || busy"
            class="px-4 py-2 border border-indigo-300 text-indigo-700 rounded-lg text-sm font-semibold disabled:opacity-50">
            {{ busyAction === 'preview' ? 'Cargando...' : 'Vista previa' }}
          </button>
          <button type="button" data-no-lock @click="generate" :disabled="!form.columns.length || busy"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold disabled:opacity-50">
            {{ busyAction === 'generate' ? 'Generando...' : 'Generar archivo' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import api from '../services/api';
import { useToast } from '../composables/useToast';
import { buildBankPreviewSheetRows, downloadXlsx } from '../utils/exportXlsx';
import { BANK_DELIMITER_OPTIONS, BANK_AMOUNT_FORMAT_OPTIONS } from '../utils/staticSelectOptions';

const props = defineProps({
  open: Boolean,
  planillaId: Number,
  planillaTitulo: { type: String, default: '' },
});

defineEmits(['close']);

const toast = useToast();
const loading = ref(false);
const busy = ref(false);
const busyAction = ref(null);
const error = ref('');
const catalog = ref({ banks: [], formats: [], delimiters: [], amount_formats: [], column_groups: {}, columns: [], default_columns: [], bank_presets: {} });
const previewData = ref(null);

const form = ref({
  ID_BANCO: null,
  format: 'csv',
  delimiter: ',',
  amount_format: 'decimal',
  columns: [],
  include_header: true,
  solo_con_cuenta: true,
});

const showDelimiter = computed(() => ['csv', 'txt_csv'].includes(form.value.format));

const fmt = (v) => Number(v ?? 0).toFixed(2);

const columnsByGroup = (groupKey) => catalog.value.columns?.filter(c => c.group === groupKey) || [];

const loadCatalog = async () => {
  if (!props.planillaId) return;
  loading.value = true;
  error.value = '';
  try {
    const res = await api.get(`/planillas/${props.planillaId}/archivo-banco/catalogo`);
    catalog.value = res.data;
    form.value.columns = [...(res.data.default_columns || [])];
    previewData.value = null;
  } catch (err) {
    error.value = err.response?.data?.error || 'No se pudo cargar el catálogo bancario.';
  } finally {
    loading.value = false;
  }
};

const selectBank = (bank) => {
  form.value.ID_BANCO = bank.ID_BANCO;
  const preset = bank.ID_BANCO
    ? (catalog.value.bank_presets?.[bank.ID_BANCO] || catalog.value.bank_presets?.[String(bank.ID_BANCO)])
    : null;
  if (preset) {
    form.value.format = preset.format || form.value.format;
    form.value.delimiter = preset.delimiter || form.value.delimiter;
    form.value.amount_format = preset.amount_format || form.value.amount_format;
    form.value.columns = [...(preset.columns || form.value.columns)];
  }
  previewData.value = null;
};

const selectDefaultColumns = () => {
  form.value.columns = [...(catalog.value.default_columns || [])];
};

const selectAllColumns = () => {
  form.value.columns = catalog.value.columns?.map(c => c.key) || [];
};

const clearColumns = () => {
  form.value.columns = [];
};

const payload = () => ({ ...form.value });

const loadPreview = async () => {
  if (!form.value.columns.length || busy.value) return;
  busy.value = true;
  busyAction.value = 'preview';
  error.value = '';
  try {
    const res = await api.post(`/planillas/${props.planillaId}/archivo-banco/preview`, payload());
    previewData.value = res.data;
  } catch (err) {
    error.value = err.response?.data?.error || 'Error en vista previa.';
    previewData.value = null;
  } finally {
    busy.value = false;
    busyAction.value = null;
  }
};

const generate = async () => {
  if (!form.value.columns.length || busy.value) return;
  busy.value = true;
  busyAction.value = 'generate';
  error.value = '';
  try {
    const bank = form.value.ID_BANCO ? `banco_${form.value.ID_BANCO}` : 'todos';

    if (form.value.format === 'xlsx') {
      const res = await api.post(`/planillas/${props.planillaId}/archivo-banco/preview`, {
        ...payload(),
        limit: 0,
      });
      const filename = `planilla_${props.planillaId}_${bank}.xlsx`;
      const rows = buildBankPreviewSheetRows(res.data);
      downloadXlsx(rows, filename, 'Archivo banco');
      toast.success('Excel generado', `${filename} se descargó correctamente.`);
      return;
    }

    const res = await api.post(`/planillas/${props.planillaId}/archivo-banco/generar`, payload(), {
      responseType: 'blob',
    });
    const disposition = res.headers['content-disposition'] || '';
    const match = disposition.match(/filename="?([^";\n]+)"?/i);
    const ext = form.value.format === 'json' ? 'json' : (form.value.format === 'csv' ? 'csv' : 'txt');
    const filename = match?.[1] || `planilla_${props.planillaId}_${bank}.${ext}`;
    const url = window.URL.createObjectURL(new Blob([res.data]));
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
    toast.success('Archivo generado', `${filename} se descargó correctamente.`);
  } catch (err) {
    if (err.response?.data instanceof Blob) {
      const text = await err.response.data.text();
      try {
        error.value = JSON.parse(text).error || 'Error al generar archivo.';
      } catch {
        error.value = 'Error al generar archivo.';
      }
    } else {
      error.value = err.response?.data?.error || 'Error al generar archivo.';
    }
  } finally {
    busy.value = false;
    busyAction.value = null;
  }
};

watch(() => [props.open, props.planillaId], ([isOpen]) => {
  if (isOpen) {
    busy.value = false;
    busyAction.value = null;
    loadCatalog();
  }
}, { immediate: true });
</script>
