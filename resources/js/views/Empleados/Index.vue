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
      <SkeletonTable v-if="loading" />

      <!-- Data Table -->
      <div v-else class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
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
              <tr v-for="(emp, index) in filteredEmpleados" :key="emp.ID_EMPLEADO" :class="index % 2 === 0 ? 'table-row-even' : 'table-row-odd'" class="hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors">
                <td class="px-6 py-4 font-mono text-xs">{{ emp.CODIGOEMPLEADO }}</td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">
                  {{ emp.NOMBRES }} {{ emp.APELLIDO_1 }} {{ emp.APELLIDO_2 || '' }}
                </td>
                <td class="px-6 py-4">{{ emp.DUI }}</td>
                <td class="px-6 py-4">{{ emp.EMPRESA_NOMBRE || 'N/A' }}</td>
                <td class="px-6 py-4">
                  <div class="font-semibold">{{ emp.CARGO_NOMBRE || 'N/A' }}</div>
                  <div class="text-xs text-slate-500">{{ emp.DEPARTAMENTO_NOMBRE || 'N/A' }}</div>
                </td>
                <td class="px-6 py-4 font-semibold">${{ Number(emp.SALARIOMENSUAL).toFixed(2) }}</td>
                <td class="px-6 py-4">
                  <span :class="emp.ESACTIVO ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200' : 'bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 border border-rose-200'" class="px-2.5 py-1 rounded-full text-xs font-semibold inline-block">
                    {{ emp.ESACTIVO ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                  <button
                    @click="editEmpleado(emp)"
                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 font-semibold text-xs transition-colors"
                  >
                    Editar
                  </button>
                  <button
                    v-if="emp.ESACTIVO"
                    @click="inactivateEmpleado(emp)"
                    class="text-rose-600 hover:text-rose-900 dark:text-rose-400 font-semibold text-xs transition-colors"
                  >
                    Inactivar
                  </button>
                </td>
              </tr>
              <tr v-if="filteredEmpleados.length === 0">
                <td colspan="8" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">No se encontraron registros.</td>
              </tr>
            </tbody>
          </table>
        </div>
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
                <select v-model="form.GENERO" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none">
                  <option value="M">Masculino</option>
                  <option value="F">Femenino</option>
                </select>
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
                <select v-model="form.ID_EMPRESA" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none">
                  <option v-for="emp in catalogs.empresas" :key="emp.ID_EMPRESA" :value="emp.ID_EMPRESA">{{ emp.NOMBREEMPRESA }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Departamento *</label>
                <select v-model="form.ID_DEPARTAMENTO" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none">
                  <option v-for="dep in catalogs.departamentos" :key="dep.ID_DEPARTAMENTO" :value="dep.ID_DEPARTAMENTO">{{ dep.NOMBREDEPARTAMENTO }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Cargo *</label>
                <select v-model="form.ID_CARGO" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none">
                  <option v-for="c in catalogs.cargos" :key="c.ID_CARGO" :value="c.ID_CARGO">{{ c.NOMBRECARGO }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Tipo Contratación *</label>
                <select v-model="form.ID_TIPOCONTRATACION" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none">
                  <option v-for="tc in catalogs.tipos_contratacion" :key="tc.ID_TIPOCONTRATACION" :value="tc.ID_TIPOCONTRATACION">{{ tc.TIPOCONTRATACION }}</option>
                </select>
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
                <select v-model="selectedGeoDepto" @change="onDeptoChange" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none">
                  <option value="">Seleccione Departamento</option>
                  <option v-for="dep in catalogs.departamentos_geograficos" :key="dep.ID_DEPARTAMENTOPAIS" :value="dep.ID_DEPARTAMENTOPAIS">
                    {{ dep.NOMBREDEPARTAMENTO }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Municipio *</label>
                <select v-model="selectedGeoMuni" @change="onMuniChange" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none">
                  <option value="">Seleccione Municipio</option>
                  <option v-for="mun in filteredGeoMunis" :key="mun.ID_MUNICIPIO" :value="mun.ID_MUNICIPIO">
                    {{ mun.NOMBREMUNICIPIO }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Distrito (Dirección MH) *</label>
                <select v-model="form.ID_DISTRITO" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none">
                  <option value="">Seleccione Distrito</option>
                  <option v-for="d in filteredGeoDistritos" :key="d.ID_DISTRITO" :value="d.ID_DISTRITO">
                    {{ d.NOMBREDISTRITO }}
                  </option>
                </select>
              </div>

              <!-- AFP y Banco -->
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Institución AFP</label>
                <select v-model="form.ID_AFP" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none">
                  <option :value="null">Ninguna</option>
                  <option v-for="afp in catalogs.afps" :key="afp.ID_AFP" :value="afp.ID_AFP">{{ afp.NOMBREAFP }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Banco Cuenta</label>
                <select v-model="form.ID_BANCO" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none">
                  <option :value="null">Ninguno / Efectivo</option>
                  <option v-for="b in catalogs.bancos" :key="b.ID_BANCO" :value="b.ID_BANCO">{{ b.NOMBREBANCO }}</option>
                </select>
              </div>
              <div v-if="form.ID_BANCO">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Número de Cuenta Bancaria *</label>
                <input v-model="form.NUMEROCUENTA" type="text" required placeholder="Digite número de cuenta" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div v-if="isEditing">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Estado</label>
                <select v-model="form.ESACTIVO" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none">
                  <option :value="true">Activo</option>
                  <option :value="false">Inactivo</option>
                </select>
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
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import api from '../../services/api';

const empleados = ref([]);
const loading = ref(false);
const showModal = ref(false);
const isEditing = ref(false);
const modalError = ref('');
const searchQuery = ref('');

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

const loadEmpleados = async () => {
  loading.value = true;
  try {
    const res = await api.get('/empleados');
    empleados.value = res.data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

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

const filteredEmpleados = computed(() => {
  const query = searchQuery.value.toLowerCase().trim();
  if (!query) return empleados.value;
  return empleados.value.filter(emp => {
    const fullName = `${emp.NOMBRES} ${emp.APELLIDO_1} ${emp.APELLIDO_2 || ''}`.toLowerCase();
    return fullName.includes(query) ||
           emp.CODIGOEMPLEADO.toLowerCase().includes(query) ||
           emp.DUI.includes(query);
  });
});

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
