<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Expedientes de Empleados</h1>
          <p class="text-sm text-slate-600 dark:text-slate-400">Gestione la información laboral de los empleados de la organización.</p>
        </div>
        <button
          @click="openCreateModal"
          class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-sm transition-colors shadow-sm"
        >
          + Nuevo Empleado
        </button>
      </div>

      <!-- Quick Search -->
      <div class="flex items-center justify-between bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Buscar por nombre, DUI o código..."
          class="w-full max-w-md px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
      </div>

      <!-- Loader State -->
      <SkeletonTable v-if="loading && !empleados.length" />

      <!-- Data Table -->
      <div v-else class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
        <div ref="empleadosScrollRef" class="overflow-x-auto max-h-[560px] overflow-y-auto">
          <table class="w-full text-left border-collapse">
            <thead class="sticky top-0 z-10">
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200 dark:border-slate-700">
                <th class="px-6 py-4">Código</th>
                <th class="px-6 py-4">Nombre Completo</th>
                <th class="px-6 py-4">DUI</th>
                <th class="px-6 py-4">Empresa</th>
                <th class="px-6 py-4">Cargo / Depto</th>
                <th class="px-6 py-4">Salario</th>
                <th class="px-6 py-4">Estado</th>
                <th class="px-6 py-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm text-slate-700 dark:text-slate-200">
              <tr v-if="empleadoPaddingTop > 0" aria-hidden="true">
                <td colspan="8" :style="{ height: empleadoPaddingTop + 'px', padding: 0, border: 'none' }"></td>
              </tr>
              <tr v-for="virtualRow in empleadoVirtualRows" :key="empleados[virtualRow.index].ID_EMPLEADO" :class="virtualRow.index % 2 === 0 ? 'table-row-even' : 'table-row-odd'" class="hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors">
                <td class="px-6 py-4 font-mono text-xs">{{ empleados[virtualRow.index].CODIGOEMPLEADO }}</td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">
                  {{ empleados[virtualRow.index].NOMBRES }} {{ empleados[virtualRow.index].APELLIDO_1 }} {{ empleados[virtualRow.index].APELLIDO_2 || '' }}
                </td>
                <td class="px-6 py-4">{{ empleados[virtualRow.index].DUI }}</td>
                <td class="px-6 py-4">{{ empleados[virtualRow.index].EMPRESA_NOMBRE || 'N/A' }}</td>
                <td class="px-6 py-4">
                  <div class="font-semibold">{{ empleados[virtualRow.index].CARGO_NOMBRE || 'N/A' }}</div>
                  <div class="text-xs text-slate-500">{{ empleados[virtualRow.index].DEPARTAMENTO_NOMBRE || 'N/A' }}</div>
                </td>
                <td class="px-6 py-4 font-semibold">${{ Number(empleados[virtualRow.index].SALARIOMENSUAL).toFixed(2) }}</td>
                <td class="px-6 py-4">
                  <span :class="empleados[virtualRow.index].ESACTIVO ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200' : 'bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 border border-rose-200'" class="px-2.5 py-1 rounded-full text-xs font-semibold inline-block">
                    {{ empleados[virtualRow.index].ESACTIVO ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                  <button
                    @click="editEmpleado(empleados[virtualRow.index])"
                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 font-semibold text-xs transition-colors"
                  >
                    Editar
                  </button>
                  <button
                    v-if="empleados[virtualRow.index].ESACTIVO"
                    @click="inactivateEmpleado(empleados[virtualRow.index])"
                    class="text-rose-600 hover:text-rose-900 dark:text-rose-400 font-semibold text-xs transition-colors"
                  >
                    Inactivar
                  </button>
                </td>
              </tr>
              <tr v-if="empleadoPaddingBottom > 0" aria-hidden="true">
                <td colspan="8" :style="{ height: empleadoPaddingBottom + 'px', padding: 0, border: 'none' }"></td>
              </tr>
              <tr v-if="!empleados.length && !loading">
                <td colspan="8" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">No se encontraron registros.</td>
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
          @update:page="onPageChange"
          @update:per-page="onPerPageChange"
        />
      </div>

      <!-- Modal CRUD -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 overflow-y-auto">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-4xl w-full overflow-hidden border border-slate-200 dark:border-slate-700 my-8">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-955 dark:text-white">{{ isEditing ? 'Editar Expediente de Empleado' : 'Registrar Nuevo Empleado' }}</h3>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white font-semibold">✕</button>
          </div>
          <form v-submit-lock="saveForm" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <!-- Datos Personales -->
              <div class="md:col-span-3 border-b pb-2">
                <h4 class="font-bold text-indigo-600 dark:text-indigo-400 text-sm">Información Personal</h4>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombres *</label>
                <input v-model="form.NOMBRES" type="text" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Primer Apellido *</label>
                <input v-model="form.APELLIDO_1" type="text" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Segundo Apellido</label>
                <input v-model="form.APELLIDO_2" type="text" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Género *</label>
                <AsyncSelect v-model="form.GENERO" :options="GENERO_OPTIONS" :searchable="false" placeholder="Seleccionar género" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Fecha Nacimiento *</label>
                <input v-model="form.FECHANACIMIENTO" type="date" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">DUI *</label>
                <input v-model="form.DUI" type="text" required placeholder="00000000-0" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>

              <!-- Contacto e Identificaciones -->
              <div class="md:col-span-3 border-b pb-2 pt-2">
                <h4 class="font-bold text-indigo-600 dark:text-indigo-400 text-sm">Identificaciones y Contacto</h4>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">NIT</label>
                <input v-model="form.NIT" type="text" placeholder="0000-000000-000-0" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">ISSS (Seguro)</label>
                <input v-model="form.ISSS" type="text" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">NUP (AFP)</label>
                <input v-model="form.NUP" type="text" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Correo Electrónico</label>
                <input v-model="form.CORREOELECTRONICO" type="email" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Celular</label>
                <input v-model="form.TELEFONOCELULAR" type="text" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Dirección</label>
                <input v-model="form.DIRECCION" type="text" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>

              <!-- Estructura y Relación Laboral -->
              <div class="md:col-span-3 border-b pb-2 pt-2">
                <h4 class="font-bold text-indigo-600 dark:text-indigo-400 text-sm">Información Laboral y Contratación</h4>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Código Empleado *</label>
                <input v-model="form.CODIGOEMPLEADO" type="text" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Empresa *</label>
                <AsyncSelect v-model="form.ID_EMPRESA" catalog="empresas" placeholder="Seleccionar empresa" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Departamento *</label>
                <AsyncSelect v-model="form.ID_DEPARTAMENTO" catalog="departamentos" :params="deptoParams" placeholder="Seleccionar departamento" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Cargo *</label>
                <AsyncSelect v-model="form.ID_CARGO" catalog="cargos" :params="cargoParams" :disabled="!form.ID_DEPARTAMENTO" placeholder="Seleccionar cargo" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Tipo Contratación *</label>
                <AsyncSelect v-model="form.ID_TIPOCONTRATACION" catalog="tipos-contratacion" placeholder="Seleccionar tipo" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Fecha Ingreso *</label>
                <input v-model="form.FECHAINGRESO" type="date" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Salario Mensual *</label>
                <input v-model="form.SALARIOMENSUAL" type="number" step="0.01" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>

              <!-- Horas extras autorizadas -->
              <div class="md:col-span-3 border-b pb-2 pt-2">
                <h4 class="font-bold text-indigo-600 dark:text-indigo-400 text-sm">Horas Extras Autorizadas (por periodo de planilla)</h4>
                <p class="text-xs text-slate-500 mt-1">
                  Indique cuántas horas fijas tiene autorizadas el empleado. Lo que exceda ese cupo se clasificará como hora extra adicional al sincronizar desde asistencia.
                </p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">HE Fijas Diurnas</label>
                <input v-model.number="form.HORAS_EXTRAS_FIJAS_DIURAS" type="number" step="0.5" min="0" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
                <p class="text-[11px] text-slate-400 mt-1">Jornada diurna: 06:00 a 19:00</p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">HE Fijas Nocturnas</label>
                <input v-model.number="form.HORAS_EXTRAS_FIJAS_NOCTURNAS" type="number" step="0.5" min="0" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
                <p class="text-[11px] text-slate-400 mt-1">Jornada nocturna: 19:00 a 06:00</p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Departamento (Geográfico) *</label>
                <AsyncSelect v-model="selectedGeoDepto" catalog="departamentos-pais" placeholder="Seleccionar departamento" @change="onDeptoChange" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Municipio *</label>
                <AsyncSelect v-model="selectedGeoMuni" catalog="municipios" :params="muniParams" :disabled="!selectedGeoDepto" placeholder="Seleccionar municipio" @change="onMuniChange" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Distrito (Dirección MH) *</label>
                <AsyncSelect v-model="form.ID_DISTRITO" catalog="distritos" :params="distritoParams" :disabled="!selectedGeoMuni" placeholder="Seleccionar distrito" />
              </div>

              <!-- AFP y Banco -->
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Institución AFP</label>
                <AsyncSelect v-model="form.ID_AFP" catalog="afps" nullable placeholder="Ninguna" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Banco Cuenta</label>
                <AsyncSelect v-model="form.ID_BANCO" catalog="bancos" nullable placeholder="Ninguno / Efectivo" />
              </div>
              <div v-if="form.ID_BANCO">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Número de Cuenta Bancaria *</label>
                <input v-model="form.NUMEROCUENTA" type="text" required placeholder="Digite número de cuenta" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div v-if="isEditing">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Estado</label>
                <AsyncSelect v-model="form.ESACTIVO" :options="ACTIVO_BOOL_OPTIONS" :searchable="false" placeholder="Estado" />
              </div>
            </div>

            <div v-if="modalError" class="text-xs text-red-500 font-semibold">{{ modalError }}</div>

            <div class="flex justify-end space-x-3 pt-4 border-t dark:border-slate-700">
              <button data-no-lock type="button" @click="closeModal" class="px-4 py-2 border rounded-lg text-sm">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors">Guardar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useVirtualizer } from '@tanstack/vue-virtual';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import PaginationBar from '../../components/PaginationBar.vue';
import { usePaginatedList } from '../../composables/usePaginatedList';
import { GENERO_OPTIONS, ACTIVO_BOOL_OPTIONS } from '../../utils/staticSelectOptions';
import api from '../../services/api';

const {
  items: empleados,
  loading,
  search: searchQuery,
  page,
  perPage,
  total,
  lastPage,
  fetch: loadEmpleados,
  setPage,
  setSearch,
  setPerPage,
} = usePaginatedList('/empleados', { perPage: 25 });

const empleadosScrollRef = ref(null);
const empleadoVirtualizer = useVirtualizer(computed(() => ({
  count: empleados.value.length,
  getScrollElement: () => empleadosScrollRef.value,
  estimateSize: () => 56,
  overscan: 5,
})));
const empleadoVirtualRows = computed(() => empleadoVirtualizer.value.getVirtualItems());
const empleadoPaddingTop = computed(() => empleadoVirtualRows.value[0]?.start ?? 0);
const empleadoPaddingBottom = computed(() => {
  const items = empleadoVirtualRows.value;
  if (!items.length) return 0;
  return empleadoVirtualizer.value.getTotalSize() - items[items.length - 1].end;
});

const onPageChange = (p) => setPage(p);
const onPerPageChange = (n) => setPerPage(n);

const deptoParams = computed(() => (form.value.ID_EMPRESA ? { ID_EMPRESA: form.value.ID_EMPRESA } : {}));
const cargoParams = computed(() => (form.value.ID_DEPARTAMENTO ? { ID_DEPARTAMENTO: form.value.ID_DEPARTAMENTO } : {}));
const muniParams = computed(() => (selectedGeoDepto.value ? { ID_DEPARTAMENTOPAIS: selectedGeoDepto.value } : {}));
const distritoParams = computed(() => (selectedGeoMuni.value ? { ID_MUNICIPIO: selectedGeoMuni.value } : {}));

watch(searchQuery, (q) => setSearch(q));

const showModal = ref(false);
const isEditing = ref(false);
const modalError = ref('');
const selectedGeoDepto = ref('');
const selectedGeoMuni = ref('');

const catalogs = ref({
  empresas: [],
  departamentos: [],
  cargos: [],
  tipos_contratacion: [],
  afps: [],
  bancos: [],
  departamentos_geograficos: [],
  municipios: [],
  distritos: []
});

const form = ref({
  ID_EMPLEADO: null,
  ID_EMPRESA: '',
  ID_DEPARTAMENTO: '',
  ID_CARGO: '',
  ID_TIPOCONTRATACION: '',
  ID_DISTRITO: '',
  ID_AFP: null,
  ID_BANCO: null,
  CODIGOEMPLEADO: '',
  NOMBRES: '',
  APELLIDO_1: '',
  APELLIDO_2: '',
  DUI: '',
  NIT: '',
  ISSS: '',
  NUP: '',
  GENERO: 'M',
  FECHANACIMIENTO: '',
  FECHAINGRESO: '',
  SALARIOMENSUAL: 0,
  HORAS_EXTRAS_FIJAS_DIURAS: 0,
  HORAS_EXTRAS_FIJAS_NOCTURNAS: 0,
  CORREOELECTRONICO: '',
  TELEFONOCELULAR: '',
  DIRECCION: '',
  NUMEROCUENTA: '',
  ESACTIVO: true
});

const loadCatalogs = async () => {
  try {
    const res = await api.get('/empleados/catalogs');
    catalogs.value = res.data;
  } catch (err) {
    console.error(err);
  }
};

onMounted(() => {
  loadEmpleados();
  loadCatalogs();
});

const filteredGeoMunis = computed(() => {
  if (!selectedGeoDepto.value) return [];
  return catalogs.value.municipios.filter(m => m.ID_DEPARTAMENTOPAIS === selectedGeoDepto.value);
});

const filteredGeoDistritos = computed(() => {
  if (!selectedGeoMuni.value) return [];
  return catalogs.value.distritos.filter(d => d.ID_MUNICIPIO === selectedGeoMuni.value);
});

const onDeptoChange = () => {
  selectedGeoMuni.value = '';
  form.value.ID_DISTRITO = '';
};

const onMuniChange = () => {
  form.value.ID_DISTRITO = '';
};

// Auto generate employee code based on selected company, surnames, and DUI
const autoGenerateEmployeeCode = () => {
  if (isEditing.value) return;

  const selectedEmp = catalogs.value.empresas.find(e => e.ID_EMPRESA === form.value.ID_EMPRESA);
  const companyPart = selectedEmp ? (selectedEmp.ABREVIATURA || selectedEmp.NOMBREEMPRESA.substring(0, 3)).toUpperCase() : 'EMP';

  const ap1 = form.value.APELLIDO_1 ? form.value.APELLIDO_1.trim().charAt(0).toUpperCase() : '';
  const ap2 = form.value.APELLIDO_2 ? form.value.APELLIDO_2.trim().charAt(0).toUpperCase() : '';
  const duiPart = form.value.DUI ? form.value.DUI.replace(/[^0-9]/g, '') : '';

  if (duiPart) {
    form.value.CODIGOEMPLEADO = `${companyPart}-${ap1}${ap2}-${duiPart}`;
  } else {
    form.value.CODIGOEMPLEADO = '';
  }
};

watch(
  () => [form.value.ID_EMPRESA, form.value.APELLIDO_1, form.value.APELLIDO_2, form.value.DUI],
  () => {
    autoGenerateEmployeeCode();
  },
  { deep: true }
);

const openCreateModal = () => {
  isEditing.value = false;
  modalError.value = '';
  selectedGeoDepto.value = '';
  selectedGeoMuni.value = '';
  form.value = {
    ID_EMPLEADO: null,
    ID_EMPRESA: catalogs.value.empresas[0]?.ID_EMPRESA || '',
    ID_DEPARTAMENTO: catalogs.value.departamentos[0]?.ID_DEPARTAMENTO || '',
    ID_CARGO: catalogs.value.cargos[0]?.ID_CARGO || '',
    ID_TIPOCONTRATACION: catalogs.value.tipos_contratacion[0]?.ID_TIPOCONTRATACION || '',
    ID_DISTRITO: '',
    ID_AFP: null,
    ID_BANCO: null,
    CODIGOEMPLEADO: '',
    NOMBRES: '',
    APELLIDO_1: '',
    APELLIDO_2: '',
    DUI: '',
    NIT: '',
    ISSS: '',
    NUP: '',
    GENERO: 'M',
    FECHANACIMIENTO: '',
    FECHAINGRESO: new Date().toISOString().split('T')[0],
    SALARIOMENSUAL: 365.00,
    HORAS_EXTRAS_FIJAS_DIURAS: 0,
    HORAS_EXTRAS_FIJAS_NOCTURNAS: 0,
    CORREOELECTRONICO: '',
    TELEFONOCELULAR: '',
    DIRECCION: '',
    NUMEROCUENTA: '',
    ESACTIVO: true
  };
  showModal.value = true;
};

const resolveGeoHierarchy = (distritoId) => {
  const dist = catalogs.value.distritos.find(d => d.ID_DISTRITO === distritoId);
  if (dist) {
    selectedGeoMuni.value = dist.ID_MUNICIPIO;
    const muni = catalogs.value.municipios.find(m => m.ID_MUNICIPIO === dist.ID_MUNICIPIO);
    if (muni) {
      selectedGeoDepto.value = muni.ID_DEPARTAMENTOPAIS;
    }
  }
};

const editEmpleado = (emp) => {
  isEditing.value = false; // set false temporarily to not trigger code auto-generation during hydration
  modalError.value = '';
  form.value = {
    ...emp,
    FECHANACIMIENTO: emp.FECHANACIMIENTO ? emp.FECHANACIMIENTO.split('T')[0] : '',
    FECHAINGRESO: emp.FECHAINGRESO ? emp.FECHAINGRESO.split('T')[0] : ''
  };
  resolveGeoHierarchy(emp.ID_DISTRITO);
  isEditing.value = true;
  showModal.value = true;
};

const saveForm = async () => {
  try {
    if (isEditing.value) {
      await api.put(`/empleados/${form.value.ID_EMPLEADO}`, form.value);
    } else {
      await api.post('/empleados', form.value);
    }
    showModal.value = false;
    loadEmpleados();
  } catch (err) {
    if (err.response?.data?.errors) {
      modalError.value = Object.values(err.response.data.errors).flat().join(' ');
    } else {
      modalError.value = 'Ocurrió un error al guardar el registro.';
    }
  }
};

const inactivateEmpleado = async (emp) => {
  if (confirm(`¿Está seguro de inactivar a ${emp.NOMBRES}?`)) {
    try {
      await api.delete(`/empleados/${emp.ID_EMPLEADO}`);
      loadEmpleados();
    } catch (err) {
      alert('Error al inactivar empleado.');
    }
  }
};

const closeModal = () => {
  showModal.value = false;
};
</script>
