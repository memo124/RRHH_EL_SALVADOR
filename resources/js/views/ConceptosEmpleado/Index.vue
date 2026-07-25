<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Conceptos por Empleado</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400">
          Administre préstamos, descuentos e ingresos adicionales que se aplican en la planilla.
        </p>
      </div>

      <!-- Tabs -->
      <div class="flex border-b border-slate-200 dark:border-slate-700 overflow-x-auto">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="activeTab === tab.id
            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 font-bold'
            : 'border-transparent text-slate-500 hover:text-slate-700'"
          class="py-3 px-6 border-b-2 text-sm font-medium transition-all whitespace-nowrap"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Filtro común -->
      <div class="flex flex-wrap gap-4 items-center bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
        <div class="flex-1 min-w-[200px]">
          <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Filtrar por empleado</label>
          <select v-model="filtroEmpleado" @change="loadTabData" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
            <option :value="null">Todos los empleados</option>
            <option v-for="emp in catalogs.empleados" :key="emp.ID_EMPLEADO" :value="emp.ID_EMPLEADO">
              {{ emp.CODIGOEMPLEADO }} — {{ emp.NOMBRE_COMPLETO }}
            </option>
          </select>
        </div>
        <label class="flex items-center gap-2 text-sm mt-5">
          <input type="checkbox" v-model="soloActivos" @change="loadTabData" />
          <span>Solo activos</span>
        </label>
      </div>

      <!-- Préstamos -->
      <div v-if="activeTab === 'prestamos'" class="space-y-4">
        <div class="flex justify-end">
          <button @click="openPrestamoModal()" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold">
            + Nuevo Préstamo
          </button>
        </div>
        <SkeletonTable v-if="loading" />
        <div v-else class="bg-white dark:bg-slate-800 rounded-xl border overflow-hidden shadow-sm">
          <table class="w-full text-left text-sm">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-xs font-semibold uppercase text-slate-600">
                <th class="px-4 py-3">Empleado</th>
                <th class="px-4 py-3">Tipo</th>
                <th class="px-4 py-3 text-right">Monto</th>
                <th class="px-4 py-3 text-right">Cuota</th>
                <th class="px-4 py-3 text-right">Saldo</th>
                <th class="px-4 py-3 text-center">Cuotas</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-for="p in prestamos" :key="p.ID_PRESTAMO">
                <td class="px-4 py-3">
                  <div class="font-semibold">{{ p.NOMBRE_EMPLEADO }}</div>
                  <div class="text-xs text-slate-500">{{ p.CODIGOEMPLEADO }}</div>
                </td>
                <td class="px-4 py-3">{{ p.NOMBREPRESTAMO }}</td>
                <td class="px-4 py-3 text-right font-mono">${{ fmt(p.MONTOPRESTAMO) }}</td>
                <td class="px-4 py-3 text-right font-mono">${{ fmt(p.CUOTA) }}</td>
                <td class="px-4 py-3 text-right font-mono">${{ fmt(p.SALDO_ACTUAL) }}</td>
                <td class="px-4 py-3 text-center">{{ p.NUMCUOTAS }}</td>
                <td class="px-4 py-3">
                  <span :class="p.PRESTAMOESTADO ? 'text-emerald-700 bg-emerald-50' : 'text-rose-700 bg-rose-50'" class="px-2 py-0.5 rounded text-xs font-semibold">
                    {{ p.PRESTAMOESTADO ? 'Activo' : 'Cancelado' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right space-x-2">
                  <button @click="openPrestamoModal(p)" class="text-indigo-600 text-xs font-semibold">Editar</button>
                  <button v-if="p.PRESTAMOESTADO" @click="cancelarPrestamo(p)" class="text-rose-600 text-xs font-semibold">Cancelar</button>
                </td>
              </tr>
              <tr v-if="prestamos.length === 0">
                <td colspan="8" class="px-4 py-8 text-center text-slate-500">No hay préstamos registrados.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Descuentos -->
      <div v-if="activeTab === 'descuentos'" class="space-y-4">
        <div class="flex justify-end">
          <button @click="openDescuentoModal()" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold">
            + Nuevo Descuento
          </button>
        </div>
        <SkeletonTable v-if="loading" />
        <div v-else class="bg-white dark:bg-slate-800 rounded-xl border overflow-hidden shadow-sm">
          <table class="w-full text-left text-sm">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-xs font-semibold uppercase text-slate-600">
                <th class="px-4 py-3">Empleado</th>
                <th class="px-4 py-3">Tipo Descuento</th>
                <th class="px-4 py-3 text-right">Monto / %</th>
                <th class="px-4 py-3">Vigencia</th>
                <th class="px-4 py-3">Recurrente</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-for="d in descuentos" :key="d.ID_DESCUENTOEMPLEADO">
                <td class="px-4 py-3">
                  <div class="font-semibold">{{ d.NOMBRE_EMPLEADO }}</div>
                  <div class="text-xs text-slate-500">{{ d.CODIGOEMPLEADO }}</div>
                </td>
                <td class="px-4 py-3">{{ d.NOMBRETIPODESC }}</td>
                <td class="px-4 py-3 text-right font-mono">
                  {{ d.ES_PORCENTAJE ? d.PORCENTAJE + '%' : '$' + fmt(d.MONTO) }}
                </td>
                <td class="px-4 py-3 text-xs">
                  {{ fmtDate(d.FECHAINICIO) }} — {{ d.FECHAFIN ? fmtDate(d.FECHAFIN) : 'Indefinido' }}
                </td>
                <td class="px-4 py-3">{{ d.ES_RECURRENTE ? 'Sí' : 'No' }}</td>
                <td class="px-4 py-3">
                  <span :class="d.ESACTIVO ? 'text-emerald-700 bg-emerald-50' : 'text-rose-700 bg-rose-50'" class="px-2 py-0.5 rounded text-xs font-semibold">
                    {{ d.ESACTIVO ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right space-x-2">
                  <button @click="openDescuentoModal(d)" class="text-indigo-600 text-xs font-semibold">Editar</button>
                  <button v-if="d.ESACTIVO" @click="inactivarDescuento(d)" class="text-rose-600 text-xs font-semibold">Inactivar</button>
                </td>
              </tr>
              <tr v-if="descuentos.length === 0">
                <td colspan="7" class="px-4 py-8 text-center text-slate-500">No hay descuentos registrados.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Ingresos adicionales -->
      <div v-if="activeTab === 'ingresos'" class="space-y-4">
        <div class="flex justify-end">
          <button @click="openIngresoModal()" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold">
            + Nuevo Ingreso
          </button>
        </div>
        <SkeletonTable v-if="loading" />
        <div v-else class="bg-white dark:bg-slate-800 rounded-xl border overflow-hidden shadow-sm">
          <table class="w-full text-left text-sm">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-xs font-semibold uppercase text-slate-600">
                <th class="px-4 py-3">Empleado</th>
                <th class="px-4 py-3">Tipo Ingreso</th>
                <th class="px-4 py-3 text-right">Monto</th>
                <th class="px-4 py-3">Vigencia</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-for="i in ingresos" :key="i.ID_OTROINGRESO">
                <td class="px-4 py-3">
                  <div class="font-semibold">{{ i.NOMBRE_EMPLEADO }}</div>
                  <div class="text-xs text-slate-500">{{ i.CODIGOEMPLEADO }}</div>
                </td>
                <td class="px-4 py-3">{{ i.TIPOINGRESO }}</td>
                <td class="px-4 py-3 text-right font-mono">${{ fmt(i.MONTOINGRESO) }}</td>
                <td class="px-4 py-3 text-xs">
                  {{ fmtDate(i.FECHAINICIO) }} — {{ i.FECHAFIN ? fmtDate(i.FECHAFIN) : 'Indefinido' }}
                </td>
                <td class="px-4 py-3">
                  <span :class="i.ESACTIVO ? 'text-emerald-700 bg-emerald-50' : 'text-rose-700 bg-rose-50'" class="px-2 py-0.5 rounded text-xs font-semibold">
                    {{ i.ESACTIVO ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right space-x-2">
                  <button @click="openIngresoModal(i)" class="text-indigo-600 text-xs font-semibold">Editar</button>
                  <button v-if="i.ESACTIVO" @click="inactivarIngreso(i)" class="text-rose-600 text-xs font-semibold">Inactivar</button>
                </td>
              </tr>
              <tr v-if="ingresos.length === 0">
                <td colspan="6" class="px-4 py-8 text-center text-slate-500">No hay ingresos adicionales registrados.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Préstamo -->
      <div v-if="showPrestamoModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full border">
          <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 class="font-bold">{{ editingPrestamo ? 'Editar Préstamo' : 'Nuevo Préstamo' }}</h3>
            <button @click="showPrestamoModal = false" class="text-slate-400">✕</button>
          </div>
          <form v-submit-lock="savePrestamo" class="p-6 space-y-4">
            <div v-if="!editingPrestamo">
              <label class="block text-xs font-semibold uppercase mb-1">Empleado *</label>
              <select v-model="prestamoForm.ID_EMPLEADO" required class="w-full px-3 py-2 border rounded-lg text-sm">
                <option :value="null" disabled>Seleccione...</option>
                <option v-for="emp in catalogs.empleados" :key="emp.ID_EMPLEADO" :value="emp.ID_EMPLEADO">
                  {{ emp.CODIGOEMPLEADO }} — {{ emp.NOMBRE_COMPLETO }}
                </option>
              </select>
            </div>
            <div v-if="!editingPrestamo" class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Tipo Préstamo *</label>
                <select v-model="prestamoForm.ID_TIPOPRESTAMO" required class="w-full px-3 py-2 border rounded-lg text-sm">
                  <option v-for="t in catalogs.tiposPrestamo" :key="t.ID_TIPOPRESTAMO" :value="t.ID_TIPOPRESTAMO">{{ t.NOMBREPRESTAMO }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Tipo Descuento *</label>
                <select v-model="prestamoForm.ID_TIPODESCUENTO" required class="w-full px-3 py-2 border rounded-lg text-sm">
                  <option v-for="t in catalogs.tiposDescuentoPrestamo" :key="t.ID_TIPODESCUENTO" :value="t.ID_TIPODESCUENTO">{{ t.NOMBRETIPODESC }}</option>
                </select>
              </div>
            </div>
            <div v-if="!editingPrestamo" class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Monto Total *</label>
                <input v-model.number="prestamoForm.MONTOPRESTAMO" type="number" step="0.01" min="0.01" required class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">N° Cuotas *</label>
                <input v-model.number="prestamoForm.NUMCUOTAS" type="number" min="1" required class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
            </div>
            <div v-if="!editingPrestamo">
              <label class="block text-xs font-semibold uppercase mb-1">Fecha Inicio *</label>
              <input v-model="prestamoForm.FECHAINICIO" type="date" required class="w-full px-3 py-2 border rounded-lg text-sm" />
            </div>
            <div v-if="editingPrestamo">
              <label class="block text-xs font-semibold uppercase mb-1">Cuota Mensual</label>
              <input v-model.number="prestamoForm.CUOTA" type="number" step="0.01" min="0.01" class="w-full px-3 py-2 border rounded-lg text-sm" />
            </div>
            <div>
              <label class="block text-xs font-semibold uppercase mb-1">Observaciones</label>
              <textarea v-model="prestamoForm.OBSERVACIONES" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"></textarea>
            </div>
            <p v-if="!editingPrestamo && prestamoForm.MONTOPRESTAMO && prestamoForm.NUMCUOTAS" class="text-sm text-indigo-600">
              Cuota calculada: ${{ fmt(prestamoForm.MONTOPRESTAMO / prestamoForm.NUMCUOTAS) }}
            </p>
            <div v-if="modalError" class="text-xs text-red-500">{{ modalError }}</div>
            <div class="flex justify-end gap-3 pt-4 border-t">
              <button data-no-lock type="button" @click="showPrestamoModal = false" class="px-4 py-2 border rounded-lg text-sm">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold">Guardar</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Descuento -->
      <div v-if="showDescuentoModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full border">
          <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 class="font-bold">{{ editingDescuento ? 'Editar Descuento' : 'Nuevo Descuento' }}</h3>
            <button @click="showDescuentoModal = false" class="text-slate-400">✕</button>
          </div>
          <form v-submit-lock="saveDescuento" class="p-6 space-y-4">
            <div v-if="!editingDescuento">
              <label class="block text-xs font-semibold uppercase mb-1">Empleado *</label>
              <select v-model="descuentoForm.ID_EMPLEADO" required class="w-full px-3 py-2 border rounded-lg text-sm">
                <option :value="null" disabled>Seleccione...</option>
                <option v-for="emp in catalogs.empleados" :key="emp.ID_EMPLEADO" :value="emp.ID_EMPLEADO">
                  {{ emp.CODIGOEMPLEADO }} — {{ emp.NOMBRE_COMPLETO }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold uppercase mb-1">Tipo Descuento *</label>
              <select v-model="descuentoForm.ID_TIPODESCUENTO" required class="w-full px-3 py-2 border rounded-lg text-sm">
                <option v-for="t in catalogs.tiposDescuento" :key="t.ID_TIPODESCUENTO" :value="t.ID_TIPODESCUENTO">{{ t.NOMBRETIPODESC }}</option>
              </select>
            </div>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="descuentoForm.ES_PORCENTAJE" />
              <span>Descuento por porcentaje del salario</span>
            </label>
            <div v-if="descuentoForm.ES_PORCENTAJE">
              <label class="block text-xs font-semibold uppercase mb-1">Porcentaje (%)</label>
              <input v-model.number="descuentoForm.PORCENTAJE" type="number" step="0.01" min="0" max="100" required class="w-full px-3 py-2 border rounded-lg text-sm" />
            </div>
            <div v-else>
              <label class="block text-xs font-semibold uppercase mb-1">Monto Fijo ($)</label>
              <input v-model.number="descuentoForm.MONTO" type="number" step="0.01" min="0" required class="w-full px-3 py-2 border rounded-lg text-sm" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Fecha Inicio *</label>
                <input v-model="descuentoForm.FECHAINICIO" type="date" required class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Fecha Fin</label>
                <input v-model="descuentoForm.FECHAFIN" type="date" class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
            </div>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="descuentoForm.ES_RECURRENTE" />
              <span>Aplicar en cada planilla del periodo</span>
            </label>
            <div v-if="editingDescuento">
              <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="descuentoForm.ESACTIVO" />
                <span>Activo</span>
              </label>
            </div>
            <div>
              <label class="block text-xs font-semibold uppercase mb-1">Observaciones</label>
              <input v-model="descuentoForm.OBSERVACIONES" type="text" class="w-full px-3 py-2 border rounded-lg text-sm" />
            </div>
            <div v-if="modalError" class="text-xs text-red-500">{{ modalError }}</div>
            <div class="flex justify-end gap-3 pt-4 border-t">
              <button data-no-lock type="button" @click="showDescuentoModal = false" class="px-4 py-2 border rounded-lg text-sm">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold">Guardar</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal Ingreso -->
      <div v-if="showIngresoModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full border">
          <div class="px-6 py-4 border-b flex justify-between items-center">
            <h3 class="font-bold">{{ editingIngreso ? 'Editar Ingreso' : 'Nuevo Ingreso Adicional' }}</h3>
            <button @click="showIngresoModal = false" class="text-slate-400">✕</button>
          </div>
          <form v-submit-lock="saveIngreso" class="p-6 space-y-4">
            <div v-if="!editingIngreso">
              <label class="block text-xs font-semibold uppercase mb-1">Empleado *</label>
              <select v-model="ingresoForm.ID_EMPLEADO" required class="w-full px-3 py-2 border rounded-lg text-sm">
                <option :value="null" disabled>Seleccione...</option>
                <option v-for="emp in catalogs.empleados" :key="emp.ID_EMPLEADO" :value="emp.ID_EMPLEADO">
                  {{ emp.CODIGOEMPLEADO }} — {{ emp.NOMBRE_COMPLETO }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold uppercase mb-1">Tipo Ingreso *</label>
              <select v-model="ingresoForm.ID_TIPOINGRESO" required class="w-full px-3 py-2 border rounded-lg text-sm">
                <option v-for="t in catalogs.tiposIngreso" :key="t.ID_TIPOINGRESO" :value="t.ID_TIPOINGRESO">{{ t.TIPOINGRESO }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold uppercase mb-1">Monto ($) *</label>
              <input v-model.number="ingresoForm.MONTOINGRESO" type="number" step="0.01" min="0.01" required class="w-full px-3 py-2 border rounded-lg text-sm" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Fecha Inicio *</label>
                <input v-model="ingresoForm.FECHAINICIO" type="date" required class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase mb-1">Fecha Fin</label>
                <input v-model="ingresoForm.FECHAFIN" type="date" class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
            </div>
            <div v-if="editingIngreso">
              <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="ingresoForm.ESACTIVO" />
                <span>Activo</span>
              </label>
            </div>
            <div v-if="modalError" class="text-xs text-red-500">{{ modalError }}</div>
            <div class="flex justify-end gap-3 pt-4 border-t">
              <button data-no-lock type="button" @click="showIngresoModal = false" class="px-4 py-2 border rounded-lg text-sm">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold">Guardar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import api from '../../services/api';

const tabs = [
  { id: 'prestamos', label: 'Préstamos' },
  { id: 'descuentos', label: 'Descuentos' },
  { id: 'ingresos', label: 'Ingresos Adicionales' },
];

const activeTab = ref('prestamos');
const loading = ref(false);
const filtroEmpleado = ref(null);
const soloActivos = ref(true);
const modalError = ref('');

const catalogs = ref({ empleados: [], tiposDescuento: [], tiposDescuentoPrestamo: [], tiposIngreso: [], tiposPrestamo: [] });
const prestamos = ref([]);
const descuentos = ref([]);
const ingresos = ref([]);

const showPrestamoModal = ref(false);
const showDescuentoModal = ref(false);
const showIngresoModal = ref(false);
const editingPrestamo = ref(false);
const editingDescuento = ref(false);
const editingIngreso = ref(false);

const prestamoForm = ref({});
const descuentoForm = ref({});
const ingresoForm = ref({});

const fmt = (v) => Number(v || 0).toFixed(2);
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('es-SV') : '';

const queryParams = () => {
  const p = {};
  if (filtroEmpleado.value) p.ID_EMPLEADO = filtroEmpleado.value;
  if (soloActivos.value) p.solo_activos = 1;
  return p;
};

const loadCatalogs = async () => {
  const res = await api.get('/conceptos-empleado/catalogs');
  catalogs.value = res.data;
};

const loadTabData = async () => {
  loading.value = true;
  modalError.value = '';
  try {
    const params = queryParams();
    if (activeTab.value === 'prestamos') {
      const res = await api.get('/prestamos', { params });
      prestamos.value = res.data;
    } else if (activeTab.value === 'descuentos') {
      const res = await api.get('/descuentos-empleado', { params });
      descuentos.value = res.data;
    } else {
      const res = await api.get('/otros-ingresos', { params });
      ingresos.value = res.data;
    }
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

watch(activeTab, loadTabData);

onMounted(async () => {
  await loadCatalogs();
  await loadTabData();
});

// Préstamos
const openPrestamoModal = (p = null) => {
  editingPrestamo.value = !!p;
  modalError.value = '';
  prestamoForm.value = p
    ? { ID_PRESTAMO: p.ID_PRESTAMO, CUOTA: p.CUOTA, OBSERVACIONES: p.OBSERVACIONES || '' }
    : { ID_EMPLEADO: filtroEmpleado.value, ID_TIPOPRESTAMO: null, ID_TIPODESCUENTO: null, MONTOPRESTAMO: null, NUMCUOTAS: 12, FECHAINICIO: new Date().toISOString().slice(0, 10), OBSERVACIONES: '' };
  showPrestamoModal.value = true;
};

const savePrestamo = async () => {
  try {
    if (editingPrestamo.value) {
      await api.put(`/prestamos/${prestamoForm.value.ID_PRESTAMO}`, prestamoForm.value);
    } else {
      await api.post('/prestamos', prestamoForm.value);
    }
    showPrestamoModal.value = false;
    loadTabData();
  } catch (err) {
    modalError.value = err.response?.data?.message || 'Error al guardar el préstamo.';
  }
};

const cancelarPrestamo = async (p) => {
  if (!confirm('¿Cancelar este préstamo?')) return;
  await api.delete(`/prestamos/${p.ID_PRESTAMO}`);
  loadTabData();
};

// Descuentos
const openDescuentoModal = (d = null) => {
  editingDescuento.value = !!d;
  modalError.value = '';
  descuentoForm.value = d
    ? { ...d, FECHAINICIO: d.FECHAINICIO?.slice(0, 10), FECHAFIN: d.FECHAFIN?.slice(0, 10) || '' }
    : { ID_EMPLEADO: filtroEmpleado.value, ID_TIPODESCUENTO: null, MONTO: null, PORCENTAJE: null, ES_PORCENTAJE: false, FECHAINICIO: new Date().toISOString().slice(0, 10), FECHAFIN: '', ES_RECURRENTE: true, OBSERVACIONES: '' };
  showDescuentoModal.value = true;
};

const saveDescuento = async () => {
  try {
    const payload = { ...descuentoForm.value, FECHAFIN: descuentoForm.value.FECHAFIN || null };
    if (editingDescuento.value) {
      await api.put(`/descuentos-empleado/${payload.ID_DESCUENTOEMPLEADO}`, payload);
    } else {
      await api.post('/descuentos-empleado', payload);
    }
    showDescuentoModal.value = false;
    loadTabData();
  } catch (err) {
    modalError.value = err.response?.data?.message || 'Error al guardar el descuento.';
  }
};

const inactivarDescuento = async (d) => {
  if (!confirm('¿Inactivar este descuento?')) return;
  await api.delete(`/descuentos-empleado/${d.ID_DESCUENTOEMPLEADO}`);
  loadTabData();
};

// Ingresos
const openIngresoModal = (i = null) => {
  editingIngreso.value = !!i;
  modalError.value = '';
  ingresoForm.value = i
    ? { ...i, FECHAINICIO: i.FECHAINICIO?.slice(0, 10), FECHAFIN: i.FECHAFIN?.slice(0, 10) || '' }
    : { ID_EMPLEADO: filtroEmpleado.value, ID_TIPOINGRESO: null, MONTOINGRESO: null, FECHAINICIO: new Date().toISOString().slice(0, 10), FECHAFIN: '' };
  showIngresoModal.value = true;
};

const saveIngreso = async () => {
  try {
    const payload = { ...ingresoForm.value, FECHAFIN: ingresoForm.value.FECHAFIN || null };
    if (editingIngreso.value) {
      await api.put(`/otros-ingresos/${payload.ID_OTROINGRESO}`, payload);
    } else {
      await api.post('/otros-ingresos', payload);
    }
    showIngresoModal.value = false;
    loadTabData();
  } catch (err) {
    modalError.value = err.response?.data?.message || 'Error al guardar el ingreso.';
  }
};

const inactivarIngreso = async (i) => {
  if (!confirm('¿Inactivar este ingreso?')) return;
  await api.delete(`/otros-ingresos/${i.ID_OTROINGRESO}`);
  loadTabData();
};
</script>
