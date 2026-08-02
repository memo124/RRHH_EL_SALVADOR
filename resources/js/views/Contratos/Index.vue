<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Contratos Laborales</h1>
          <p class="page-subtitle">Gestión de contratos, plantillas dinámicas y generación de documentos.</p>
        </div>
      </div>

      <div class="flex border-b border-slate-200 dark:border-slate-700">
        <button v-for="t in tabs" :key="t.key" @click="activeTab = t.key"
          :class="activeTab === t.key ? 'border-indigo-500 text-indigo-600 font-bold' : 'border-transparent text-slate-500'"
          class="py-3 px-5 border-b-2 text-sm font-medium whitespace-nowrap">
          {{ t.label }}
        </button>
      </div>

      <div class="page-toolbar">
        <input v-model="searchQuery" type="text" :placeholder="`Buscar ${activeTab === 'contratos' ? 'contrato' : 'plantilla'}...`"
          class="w-full max-w-xs px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700" />
        <div class="flex gap-2">
          <button v-if="activeTab === 'contratos'" @click="openLoteModal"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold">
            Generar lote
          </button>
          <button @click="openCreate" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold">
            + {{ activeTab === 'contratos' ? 'Nuevo Contrato' : 'Nueva Plantilla' }}
          </button>
        </div>
      </div>

      <SkeletonTable v-if="loading" />

      <!-- CONTRATOS -->
      <div v-else-if="activeTab === 'contratos'" class="table-shell table-scroll">
        <table v-table-cards class="table-cards w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 dark:bg-slate-700/50 text-xs font-semibold uppercase border-b">
              <th class="px-6 py-4">Número</th>
              <th class="px-6 py-4">Empleado</th>
              <th class="px-6 py-4">Empresa</th>
              <th class="px-6 py-4">Plantilla</th>
              <th class="px-6 py-4">Vigencia</th>
              <th class="px-6 py-4">Salario</th>
              <th class="px-6 py-4">Estado</th>
              <th class="px-6 py-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y text-sm">
            <tr v-for="r in records" :key="r.ID_CONTRATO" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
              <td class="px-6 py-4 font-mono text-xs">{{ r.NUMERO_CONTRATO }}</td>
              <td class="px-6 py-4 font-semibold">{{ r.NOM_EMPLEADO }}</td>
              <td class="px-6 py-4">{{ r.NOMBREEMPRESA }}</td>
              <td class="px-6 py-4">{{ r.NOMBRE_PLANTILLA || '—' }}</td>
              <td class="px-6 py-4">{{ vigenciaTexto(r) }}</td>
              <td class="px-6 py-4">${{ fmt(r.SALARIO) }}</td>
              <td class="px-6 py-4">
                <span :class="estadoClass(r.ESTADO)" class="px-2 py-0.5 rounded text-xs font-semibold">{{ r.ESTADO }}</span>
              </td>
              <td class="px-6 py-4 text-right space-x-1">
                <IconActionButton variant="view" title="Ver PDF" @click="verContrato(r)" />
                <IconActionButton variant="edit" @click="openEdit(r)" />
                <IconActionButton v-if="r.ESACTIVO" variant="inactivate" @click="anularContrato(r)" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- PLANTILLAS -->
      <div v-else class="table-shell table-scroll">
        <table v-table-cards class="table-cards w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 dark:bg-slate-700/50 text-xs font-semibold uppercase border-b">
              <th class="px-6 py-4">Nombre</th>
              <th class="px-6 py-4">Empresa</th>
              <th class="px-6 py-4">Descripción</th>
              <th class="px-6 py-4">Estado</th>
              <th class="px-6 py-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y text-sm">
            <tr v-for="r in records" :key="r.ID_PLANTILLA" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
              <td class="px-6 py-4 font-semibold">{{ r.NOMBRE }}</td>
              <td class="px-6 py-4">{{ r.NOMBREEMPRESA || 'Global' }}</td>
              <td class="px-6 py-4 text-slate-500 truncate max-w-xs">{{ r.DESCRIPCION || '—' }}</td>
              <td class="px-6 py-4">
                <span :class="r.ESACTIVO ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'" class="px-2 py-0.5 rounded text-xs font-semibold">
                  {{ r.ESACTIVO ? 'Activa' : 'Inactiva' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right space-x-1">
                <button @click="previewPlantilla(r)" class="text-indigo-600 hover:underline text-xs font-semibold">Vista previa</button>
                <IconActionButton variant="edit" @click="openEdit(r)" />
                <IconActionButton v-if="r.ESACTIVO" variant="inactivate" @click="inactivarPlantilla(r)" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <PaginationBar v-if="!loading" :page="page" :last-page="lastPage" :per-page="perPage" :total="total" :loading="loading"
        @update:page="setPage" @update:per-page="setPerPage" />

      <!-- MODAL -->
      <AppModalShell :open="showModal" @close="showModal = false">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-3xl w-full mx-auto border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b flex justify-between items-center">
            <h3 class="text-base font-bold">{{ isEditing ? 'Editar' : 'Nuevo' }} {{ activeTab === 'contratos' ? 'Contrato' : 'Plantilla' }}</h3>
            <button @click="showModal = false" class="text-slate-400 font-bold"><AppIcon name="x" size="md" /></button>
          </div>
          <form v-submit-lock="save" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">

            <!-- Contrato form -->
            <template v-if="activeTab === 'contratos'">
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Empleado *</label>
                <AsyncSelect v-model="form.ID_EMPLEADO" endpoint="/empleados/select" placeholder="Seleccionar empleado" />
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Empresa *</label>
                <AsyncSelect v-model="form.ID_EMPRESA" catalog="empresas" placeholder="Seleccionar empresa" />
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Plantilla</label>
                <AsyncSelect v-model="form.ID_PLANTILLA" catalog="plantillas-contrato" :params="plantillaParams" placeholder="Seleccionar plantilla" />
              </div>
              <FormField label="Número de contrato" v-model="form.NUMERO_CONTRATO" />
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-semibold uppercase mb-1">Fecha inicio</label>
                  <input v-model="form.FECHA_INICIO" type="date" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700" />
                </div>
                <div>
                  <label class="block text-xs font-semibold uppercase mb-1">Fecha fin</label>
                  <input v-model="form.FECHA_FIN" type="date" :disabled="form.SIN_FECHA_DEFINIDA" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 disabled:opacity-50" />
                </div>
              </div>
              <label class="flex items-center space-x-2 text-sm cursor-pointer">
                <input type="checkbox" v-model="form.SIN_FECHA_DEFINIDA" class="rounded text-indigo-600" />
                <span>Contrato por tiempo indefinido / sin fecha fin</span>
              </label>
              <div class="grid grid-cols-2 gap-4">
                <FormField label="Salario mensual" v-model.number="form.SALARIO" type="number" />
                <div>
                  <label class="block text-xs font-semibold uppercase mb-1">Salario en letras</label>
                  <div class="flex gap-2">
                    <input :value="salarioLetras" readonly class="flex-1 px-3 py-2 border rounded-lg text-sm bg-slate-50 dark:bg-slate-700/50" placeholder="Calcule con el botón" />
                    <button type="button" @click="calcularLetras" class="px-3 py-2 bg-slate-200 dark:bg-slate-600 rounded-lg text-xs font-semibold">Convertir</button>
                  </div>
                </div>
              </div>
              <FormField label="Observaciones" v-model="form.OBSERVACIONES" />
            </template>

            <!-- Plantilla form -->
            <template v-else>
              <FormField label="Nombre *" v-model="form.NOMBRE" required />
              <FormField label="Descripción" v-model="form.DESCRIPCION" />
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Empresa (opcional)</label>
                <AsyncSelect v-model="form.ID_EMPRESA" catalog="empresas" nullable placeholder="Global — todas las empresas" />
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Contenido del contrato *</label>
                <RichTextEditor :key="'contenido-' + editorKey" ref="editorContenido" v-model="form.CONTENIDO"
                  placeholder="Redacte el contrato usando el editor. Inserte variables con los botones de abajo." />
                <p class="text-xs text-slate-500 mt-1">Use la barra de herramientas para negritas, listas, alineación, etc. Las variables se insertan donde esté el cursor.</p>
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Cláusulas adicionales</label>
                <RichTextEditor :key="'clausulas-' + editorKey" ref="editorClausulas" v-model="form.CLAUSULAS"
                  placeholder="Cláusulas extra que se insertan con {{clausulas}}" />
              </div>
              <div v-if="camposDisponibles.length" class="bg-slate-50 dark:bg-slate-700/30 rounded-lg p-3">
                <p class="text-xs font-semibold uppercase text-slate-500 mb-2">Campos dinámicos disponibles</p>
                <div class="flex flex-wrap gap-1">
                  <button v-for="c in camposDisponibles" :key="c.key" type="button"
                    @click="insertarCampo(c.key)"
                    class="text-xs px-2 py-1 bg-white dark:bg-slate-600 border rounded hover:bg-indigo-50 dark:hover:bg-indigo-900/30"
                    :title="c.label">{{ c.key }}</button>
                </div>
              </div>
            </template>

            <p v-if="modalError" class="text-rose-600 text-sm">{{ modalError }}</p>
            <div class="flex justify-end space-x-3 pt-2">
              <button type="button" @click="showModal = false" class="px-4 py-2 text-sm text-slate-600">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold">Guardar</button>
            </div>
          </form>
        </div>
      </AppModalShell>

      <!-- Preview modal -->
      <AppModalShell :open="showPreview" @close="showPreview = false">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-3xl w-full mx-auto border p-6 max-h-[80vh] overflow-y-auto">
          <h3 class="font-bold mb-4">Vista previa de plantilla</h3>
          <div class="prose prose-sm max-w-none dark:prose-invert" v-html="previewContent"></div>
        </div>
      </AppModalShell>

      <!-- Lote modal -->
      <AppModalShell :open="showLoteModal" @close="showLoteModal = false">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-4xl w-full mx-auto border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b flex justify-between items-center">
            <div>
              <h3 class="text-base font-bold">Generar contratos por lote</h3>
              <p class="text-xs text-slate-500 mt-0.5">Renovación masiva filtrada por tipo de contratación, con aguinaldo y quincena 25 según antigüedad.</p>
            </div>
            <button @click="showLoteModal = false" class="text-slate-400 font-bold"><AppIcon name="x" size="md" /></button>
          </div>
          <form v-submit-lock="generarLote" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Empresa *</label>
                <AsyncSelect v-model="loteForm.ID_EMPRESA" catalog="empresas" placeholder="Seleccionar empresa" />
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Tipo de contratación *</label>
                <AsyncSelect v-model="loteForm.ID_TIPOCONTRATACION" catalog="tipos-contratacion" placeholder="Filtrar empleados por régimen" />
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Plantilla *</label>
                <AsyncSelect v-model="loteForm.ID_PLANTILLA" catalog="plantillas-contrato" :params="lotePlantillaParams" placeholder="Plantilla del contrato" />
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Prefijo número (opcional)</label>
                <input v-model="loteForm.PREFIJO_NUMERO" type="text" placeholder="CONT-2026"
                  class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700" />
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Fecha inicio *</label>
                <input v-model="loteForm.FECHA_INICIO" type="date" required
                  class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700" />
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Fecha fin</label>
                <input v-model="loteForm.FECHA_FIN" type="date" :disabled="loteForm.SIN_FECHA_DEFINIDA"
                  class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 disabled:opacity-50" />
              </div>
            </div>
            <label class="flex items-center space-x-2 text-sm cursor-pointer">
              <input type="checkbox" v-model="loteForm.SIN_FECHA_DEFINIDA" class="rounded text-indigo-600" />
              <span>Contrato por tiempo indefinido / sin fecha fin</span>
            </label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
              <label class="flex items-start space-x-2 cursor-pointer">
                <input type="checkbox" v-model="loteForm.RENOVAR_VENCIDOS" class="rounded text-indigo-600 mt-0.5" />
                <span>Solo empleados sin contrato vigente que cubra el nuevo periodo (renovación fin de año)</span>
              </label>
              <label class="flex items-start space-x-2 cursor-pointer">
                <input type="checkbox" v-model="loteForm.MARCAR_ANTERIORES_VENCIDOS" class="rounded text-indigo-600 mt-0.5" />
                <span>Marcar contratos anteriores como VENCIDO al generar el lote</span>
              </label>
            </div>
            <div class="flex gap-2">
              <button type="button" @click="previewLote" :disabled="lotePreviewLoading"
                class="px-4 py-2 bg-slate-200 dark:bg-slate-600 rounded-lg text-sm font-semibold disabled:opacity-50">
                {{ lotePreviewLoading ? 'Calculando…' : 'Vista previa del lote' }}
              </button>
            </div>

            <div v-if="lotePreview" class="bg-slate-50 dark:bg-slate-700/30 rounded-lg p-4 space-y-3 text-sm">
              <div class="flex flex-wrap gap-4 font-semibold">
                <span>{{ lotePreview.total_elegibles }} empleado(s) elegible(s)</span>
                <span class="text-emerald-700">{{ lotePreview.resumen?.con_aguinaldo ?? 0 }} con aguinaldo</span>
                <span class="text-indigo-700">{{ lotePreview.resumen?.con_quincena25 ?? 0 }} con quincena 25</span>
                <span class="text-amber-700">{{ lotePreview.resumen?.sin_quincena25 ?? 0 }} sin quincena 25</span>
              </div>
              <div v-if="lotePreview.truncado" class="text-xs text-slate-500">Mostrando los primeros {{ lotePreview.empleados?.length }} registros.</div>
              <div class="table-scroll max-h-48 overflow-y-auto border rounded-lg bg-white dark:bg-slate-800">
                <table class="w-full text-xs">
                  <thead class="bg-slate-100 dark:bg-slate-700 sticky top-0">
                    <tr>
                      <th class="px-2 py-2 text-left">Empleado</th>
                      <th class="px-2 py-2 text-left">Antigüedad</th>
                      <th class="px-2 py-2 text-right">Aguinaldo</th>
                      <th class="px-2 py-2 text-right">Quincena 25</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="e in lotePreview.empleados" :key="e.ID_EMPLEADO" class="border-t">
                      <td class="px-2 py-1.5">{{ e.NOM_EMPLEADO }}</td>
                      <td class="px-2 py-1.5">{{ e.ANTIGUEDAD_TEXTO }}</td>
                      <td class="px-2 py-1.5 text-right">{{ e.AGUINALDO_APLICA ? `${e.DIAS_AGUINALDO} d / $${fmt(e.MONTO_AGUINALDO)}` : 'No' }}</td>
                      <td class="px-2 py-1.5 text-right">{{ e.QUINCENA25_APLICA ? `$${fmt(e.QUINCENA25_MONTO)}` : (e.QUINCENA25_DETALLE || 'No') }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <p v-if="loteError" class="text-rose-600 text-sm">{{ loteError }}</p>
            <div class="flex justify-end space-x-3 pt-2 border-t">
              <button type="button" @click="showLoteModal = false" class="px-4 py-2 text-sm text-slate-600">Cancelar</button>
              <button type="submit" :disabled="!lotePreview?.total_elegibles"
                class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold disabled:opacity-50">
                Generar {{ lotePreview?.total_elegibles || 0 }} contrato(s)
              </button>
            </div>
          </form>
        </div>
      </AppModalShell>

      <!-- Post-lote: imprimir o descargar -->
      <AppModalShell :open="showLotePostModal" @close="cerrarLotePostModal">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full mx-auto border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-emerald-50 dark:bg-emerald-900/20 border-b border-emerald-100 dark:border-emerald-800">
            <h3 class="text-base font-bold text-emerald-900 dark:text-emerald-100">Lote generado correctamente</h3>
            <p class="text-sm text-emerald-800 dark:text-emerald-200 mt-1">
              Se crearon {{ loteGeneradoCount }} contrato(s). ¿Cómo desea obtener los documentos?
            </p>
          </div>
          <div class="p-6 space-y-3">
            <button type="button" @click="verPdfLoteGenerado"
              class="w-full px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold text-left">
              <span class="block">Ver PDF del lote</span>
              <span class="block text-xs font-normal text-indigo-100 mt-0.5">Genera y abre un solo PDF con todos los contratos en el visor del navegador</span>
            </button>
            <button type="button" @click="descargarZipLoteGenerado"
              class="w-full px-4 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold text-left">
              <span class="block">Descargar todos (ZIP)</span>
              <span class="block text-xs font-normal text-emerald-100 mt-0.5">Un archivo ZIP con un PDF por cada contrato</span>
            </button>
            <button type="button" @click="cerrarLotePostManual"
              class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg text-sm font-semibold text-left hover:bg-slate-50 dark:hover:bg-slate-700/50">
              <span class="block text-slate-800 dark:text-slate-100">Descargar manualmente</span>
              <span class="block text-xs font-normal text-slate-500 mt-0.5">Cerrar y usar el botón PDF de cada fila en la lista</span>
            </button>
          </div>
        </div>
      </AppModalShell>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import PaginationBar from '../../components/PaginationBar.vue';
import AppModalShell from '../../components/AppModalShell.vue';
import IconActionButton from '../../components/IconActionButton.vue';
import AsyncSelect from '../../components/AsyncSelect.vue';
import RichTextEditor from '../../components/RichTextEditor.vue';
import AppIcon from '../../components/AppIcon.vue';
import api from '../../services/api';
import { usePaginatedList } from '../../composables/usePaginatedList';
import { dialog } from '../../composables/useDialog';

const FormField = {
  props: ['label', 'modelValue', 'required', 'type'],
  emits: ['update:modelValue'],
  template: `
    <div>
      <label class="block text-xs font-semibold uppercase mb-1">{{ label }}</label>
      <input :value="modelValue" @input="$emit('update:modelValue', type === 'number' ? Number($event.target.value) : $event.target.value)"
        :type="type || 'text'" :required="required"
        class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700" />
    </div>
  `,
};

const route = useRoute();

const tabs = [
  { key: 'contratos', label: 'Contratos', api: 'contratos' },
  { key: 'plantillas', label: 'Plantillas', api: 'plantillas-contrato' },
];

const activeTab = ref('contratos');
const endpoint = computed(() => `/${tabs.find(t => t.key === activeTab.value)?.api}`);

const { items: records, loading, search: searchQuery, page, perPage, total, lastPage, fetch: loadTab, setPage, setSearch, setPerPage, reset } =
  usePaginatedList(endpoint, { perPage: 25 });

const showModal = ref(false);
const showPreview = ref(false);
const previewContent = ref('');
const isEditing = ref(false);
const modalError = ref('');
const form = ref({});
const camposDisponibles = ref([]);
const salarioLetras = ref('');
const editorKey = ref(0);
const editorContenido = ref(null);

const showLoteModal = ref(false);
const lotePreviewLoading = ref(false);
const lotePreview = ref(null);
const loteError = ref('');
const loteForm = ref({
  ID_EMPRESA: null,
  ID_TIPOCONTRATACION: null,
  ID_PLANTILLA: null,
  FECHA_INICIO: '',
  FECHA_FIN: '',
  SIN_FECHA_DEFINIDA: false,
  PREFIJO_NUMERO: '',
  RENOVAR_VENCIDOS: true,
  MARCAR_ANTERIORES_VENCIDOS: true,
});
const showLotePostModal = ref(false);
const loteGeneradoIds = ref([]);
const loteGeneradoCount = ref(0);

const lotePlantillaParams = computed(() => (loteForm.value.ID_EMPRESA ? { ID_EMPRESA: loteForm.value.ID_EMPRESA } : {}));

const plantillaParams = computed(() => (form.value.ID_EMPRESA ? { ID_EMPRESA: form.value.ID_EMPRESA } : {}));

const defaultContrato = { ID_EMPLEADO: null, ID_EMPRESA: null, ID_PLANTILLA: null, NUMERO_CONTRATO: '', FECHA_INICIO: '', FECHA_FIN: '', SIN_FECHA_DEFINIDA: false, SALARIO: null, OBSERVACIONES: '' };
const defaultPlantilla = { NOMBRE: '', DESCRIPCION: '', ID_EMPRESA: null, FORMATO: 'HTML', CONTENIDO: '', CLAUSULAS: '', ESACTIVO: true };

watch(() => route.meta.tab, (tab) => {
  if (tab && tab !== activeTab.value) {
    activeTab.value = tab;
  }
}, { immediate: true });

watch(searchQuery, (q) => setSearch(q));
watch(activeTab, () => { reset(); loadTab(); });

onMounted(async () => {
  loadTab();
  try {
    const res = await api.get('/plantillas-contrato/campos');
    camposDisponibles.value = res.data;
  } catch (_) {}
});

const fmt = (v) => Number(v ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const vigenciaTexto = (r) => {
  if (r.SIN_FECHA_DEFINIDA) return 'Indefinido';
  if (r.FECHA_INICIO && r.FECHA_FIN) return `${r.FECHA_INICIO} → ${r.FECHA_FIN}`;
  if (r.FECHA_INICIO) return `Desde ${r.FECHA_INICIO}`;
  return 'Sin fechas';
};

const estadoClass = (estado) => ({
  VIGENTE: 'bg-emerald-50 text-emerald-700',
  VENCIDO: 'bg-amber-50 text-amber-700',
  ANULADO: 'bg-rose-50 text-rose-700',
}[estado] || 'bg-slate-50 text-slate-600');

const openCreate = () => {
  isEditing.value = false;
  modalError.value = '';
  salarioLetras.value = '';
  editorKey.value += 1;
  form.value = activeTab.value === 'contratos' ? { ...defaultContrato } : { ...defaultPlantilla };
  showModal.value = true;
};

const openLoteModal = () => {
  loteError.value = '';
  lotePreview.value = null;
  const year = new Date().getFullYear();
  loteForm.value = {
    ID_EMPRESA: null,
    ID_TIPOCONTRATACION: null,
    ID_PLANTILLA: null,
    FECHA_INICIO: `${year + 1}-01-01`,
    FECHA_FIN: `${year + 1}-12-31`,
    SIN_FECHA_DEFINIDA: false,
    PREFIJO_NUMERO: `CONT-${year + 1}`,
    RENOVAR_VENCIDOS: true,
    MARCAR_ANTERIORES_VENCIDOS: true,
  };
  showLoteModal.value = true;
};

const previewLote = async () => {
  loteError.value = '';
  lotePreviewLoading.value = true;
  try {
    const res = await api.post('/contratos/lote/preview', loteForm.value);
    lotePreview.value = res.data;
  } catch (err) {
    loteError.value = err.response?.data?.message || err.response?.data?.error || 'No se pudo calcular la vista previa.';
    lotePreview.value = null;
  } finally {
    lotePreviewLoading.value = false;
  }
};

const generarLote = async () => {
  loteError.value = '';
  if (!lotePreview.value?.total_elegibles) {
    await previewLote();
    if (!lotePreview.value?.total_elegibles) return;
  }
  if (!await dialog.confirm({
    title: 'Generar lote de contratos',
    message: `¿Confirma generar ${lotePreview.value.total_elegibles} contrato(s) para el tipo de contratación seleccionado?`,
    variant: 'warning',
    confirmText: 'Sí, generar',
  })) return;

  try {
    const res = await api.post('/contratos/lote/generar', loteForm.value);
    const ids = (res.data.contratos ?? []).map((c) => c.ID_CONTRATO).filter(Boolean);
    loteGeneradoIds.value = ids;
    loteGeneradoCount.value = res.data.generados ?? ids.length;
    showLoteModal.value = false;
    loadTab();
    if (loteGeneradoCount.value > 0) {
      showLotePostModal.value = true;
    } else {
      await dialog.alert({
        title: 'Sin contratos generados',
        message: res.data.omitidos
          ? `No se generó ningún contrato. Omitidos: ${res.data.omitidos}.`
          : 'No se generó ningún contrato.',
        variant: 'warning',
      });
    }
  } catch (err) {
    loteError.value = err.response?.data?.message || err.response?.data?.error || 'Error al generar el lote.';
  }
};

const loteReportUrl = (path) => {
  const token = localStorage.getItem('token');
  const ids = loteGeneradoIds.value.join(',');
  return `${path}?ids=${encodeURIComponent(ids)}&token=${encodeURIComponent(token ?? '')}`;
};

const verPdfLoteGenerado = () => {
  window.open(loteReportUrl('/reportes/contratos/lote/pdf'), '_blank');
  showLotePostModal.value = false;
};

const descargarZipLoteGenerado = () => {
  window.location.href = loteReportUrl('/reportes/contratos/lote/zip');
  showLotePostModal.value = false;
};

const cerrarLotePostManual = async () => {
  showLotePostModal.value = false;
  await dialog.alert({
    title: 'Descarga manual',
    message: 'Puede ver o descargar cada contrato desde la lista usando el botón PDF en la columna Acciones.',
    variant: 'info',
    confirmText: 'Entendido',
  });
};

const cerrarLotePostModal = () => {
  showLotePostModal.value = false;
};

const openEdit = (r) => {
  isEditing.value = true;
  modalError.value = '';
  salarioLetras.value = '';
  editorKey.value += 1;
  form.value = {
    ...r,
    SIN_FECHA_DEFINIDA: !!r.SIN_FECHA_DEFINIDA,
    FECHA_INICIO: r.FECHA_INICIO ? String(r.FECHA_INICIO).slice(0, 10) : '',
    FECHA_FIN: r.FECHA_FIN ? String(r.FECHA_FIN).slice(0, 10) : '',
  };
  showModal.value = true;
};

const calcularLetras = async () => {
  if (!form.value.SALARIO) return;
  const res = await api.post('/contratos/numero-a-letras', { monto: form.value.SALARIO });
  salarioLetras.value = res.data.letras;
};

const insertarCampo = (key) => {
  editorContenido.value?.insertText(key);
};

const save = async () => {
  const tab = tabs.find(t => t.key === activeTab.value);
  const idKey = activeTab.value === 'contratos' ? 'ID_CONTRATO' : 'ID_PLANTILLA';
  try {
    const payload = { ...form.value };
    if (activeTab.value === 'contratos') {
      payload.generar_contenido = true;
      if (isEditing.value) payload.regenerar_contenido = !!payload.ID_PLANTILLA;
    }
    if (isEditing.value) {
      await api.put(`/${tab.api}/${form.value[idKey]}`, payload);
    } else {
      await api.post(`/${tab.api}`, payload);
    }
    showModal.value = false;
    loadTab();
  } catch (err) {
    modalError.value = err.response?.data?.error || err.response?.data?.message || 'Error al guardar.';
  }
};

const verContrato = (r) => {
  const token = localStorage.getItem('token');
  window.open(`/reportes/contratos/${r.ID_CONTRATO}/pdf?token=${encodeURIComponent(token ?? '')}`, '_blank');
};

const anularContrato = async (r) => {
  if (!await dialog.confirm({ title: 'Anular contrato', message: '¿Confirma anular este contrato?', variant: 'warning' })) return;
  await api.delete(`/contratos/${r.ID_CONTRATO}`);
  loadTab();
};

const previewPlantilla = async (r) => {
  const res = await api.get(`/plantillas-contrato/${r.ID_PLANTILLA}/preview`);
  previewContent.value = res.data.contenido;
  showPreview.value = true;
};

const inactivarPlantilla = async (r) => {
  if (!await dialog.confirm({ title: 'Inactivar plantilla', message: '¿Confirma inactivar esta plantilla?', variant: 'warning' })) return;
  await api.delete(`/plantillas-contrato/${r.ID_PLANTILLA}`);
  loadTab();
};
</script>
