<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Cálculo de Planilla</h1>
          <p class="text-sm text-slate-600 dark:text-slate-400">Procesamiento de planillas mensuales y extraordinarias.</p>
        </div>
        <button @click="openCreateModal"
          class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-sm transition-colors shadow-sm">
          + Nueva Planilla
        </button>
      </div>

      <SkeletonTable v-if="initialLoading" />

      <!-- Planillas List -->
      <div v-else class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200 dark:border-slate-700">
                <th class="px-6 py-4">ID</th>
                <th class="px-6 py-4">Título</th>
                <th class="px-6 py-4">Tipo</th>
                <th class="px-6 py-4">Periodo</th>
                <th class="px-6 py-4">Fecha Pago</th>
                <th class="px-6 py-4">Estado</th>
                <th class="px-6 py-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm text-slate-700 dark:text-slate-200">
              <tr v-for="plan in planillas" :key="plan.ID_PLANILLA"
                :class="selectedPayroll?.ID_PLANILLA === plan.ID_PLANILLA ? 'bg-indigo-50 dark:bg-indigo-900/20' : 'hover:bg-slate-50 dark:hover:bg-slate-700/30'"
                class="transition-colors">
                <td class="px-6 py-4 font-medium text-slate-500">{{ plan.ID_PLANILLA }}</td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ plan.TITULO }}</td>
                <td class="px-6 py-4">{{ plan.TIPOPLANILLA }}</td>
                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ plan.CALPERIODO }}</td>
                <td class="px-6 py-4">{{ formatDate(plan.FECHAPAGO) }}</td>
                <td class="px-6 py-4">
                  <span :class="estadoClass(plan)" class="px-2.5 py-1 rounded text-xs font-bold uppercase">{{ estadoLabel(plan) }}</span>
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                  <button v-if="!plan.CERRADA && !plan.ANULADA" @click="calculatePayroll(plan)" :disabled="calculating"
                    class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 rounded text-xs font-semibold disabled:opacity-50 transition-colors">
                    {{ calculating && selectedPayroll?.ID_PLANILLA === plan.ID_PLANILLA ? '...' : 'Calcular' }}
                  </button>
                  <button @click="viewDetails(plan)"
                    class="px-2.5 py-1 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 rounded text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors">
                    Ver Detalles
                  </button>
                  <button v-if="plan.RECALCULADA && !plan.CERRADA && !plan.ANULADA" @click="cerrarPlanilla(plan)" class="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded text-xs font-semibold">Cerrar</button>
                  <button v-if="plan.CERRADA && !plan.CONTABILIZADA && !plan.ANULADA" @click="contabilizarPlanilla(plan)" class="px-2.5 py-1 bg-blue-50 text-blue-700 rounded text-xs font-semibold">Contabilizar</button>
                  <button v-if="!plan.ANULADA && !plan.CONTABILIZADA" @click="anularPlanilla(plan)" class="px-2.5 py-1 bg-rose-50 text-rose-700 rounded text-xs font-semibold">Anular</button>
                </td>
              </tr>
              <tr v-if="!planillas.length">
                <td colspan="7" class="px-6 py-10 text-center text-slate-400 text-sm">No hay planillas registradas. Cree una nueva.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Details Panel -->
      <div v-if="selectedPayroll" id="detail-panel" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <!-- Detail Header -->
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-700/40">
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Detalle de Planilla: {{ selectedPayroll.TITULO }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{ selectedPayroll.CALPERIODO }} · {{ selectedPayroll.TIPOPLANILLA }}
              <span class="ml-2 text-indigo-600 dark:text-indigo-400 font-semibold">{{ payrollTotales.COUNT }} empleados</span>
            </p>
          </div>
          <div class="flex items-center space-x-3">
            <button v-if="selectedPayroll && !selectedPayroll.CERRADA && !selectedPayroll.ANULADA" @click="toggleHePanel" class="text-xs border border-slate-300 dark:border-slate-600 rounded px-3 py-1.5 font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700">Horas Extras</button>
            <button v-if="loadingDetails" class="text-xs text-slate-400 animate-pulse">Cargando...</button>
            <button @click="closeDetails" class="text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 font-semibold border border-slate-200 dark:border-slate-600 rounded px-3 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
              ✕ Cerrar Detalles
            </button>
          </div>
        </div>

        <!-- Horas Extras Panel -->
        <div v-if="showHePanel && selectedPayroll" class="px-6 py-4 border-b border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 space-y-3">
          <div class="flex justify-between items-center">
            <h4 class="text-sm font-bold text-slate-800 dark:text-amber-100">Horas Extras — Planilla #{{ selectedPayroll.ID_PLANILLA }}</h4>
            <button @click="syncHe" class="text-xs bg-amber-600 hover:bg-amber-700 text-white px-3 py-1 rounded font-semibold">Sincronizar desde Asistencia</button>
          </div>
          <div v-if="loadingHe" class="text-xs text-slate-500 dark:text-slate-400 animate-pulse">Cargando horas extras...</div>
          <form v-else v-submit-lock="addHe" class="flex flex-wrap gap-2 items-end">
            <select v-model="heForm.ID_EMPLEADO" required class="min-w-[180px] px-2 py-1.5 border border-slate-300 dark:border-slate-600 rounded text-xs bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
              <option :value="null" disabled>Seleccione empleado</option>
              <option v-for="e in empleadosPlanilla" :key="e.ID_EMPLEADO" :value="e.ID_EMPLEADO">{{ e.NOMBRE_COMPLETO }}</option>
            </select>
            <select v-model="heForm.ID_HORASEXTRAS" required class="min-w-[220px] px-2 py-1.5 border border-slate-300 dark:border-slate-600 rounded text-xs bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
              <option :value="null" disabled>Seleccione tipo de hora extra</option>
              <option v-for="t in catalogs.tiposHorasExtras" :key="field(t, 'ID_HORASEXTRAS', 'id_horasextras')" :value="field(t, 'ID_HORASEXTRAS', 'id_horasextras')">
                {{ field(t, 'TIPOHORAEXTRA', 'tipohoraextra') }} ({{ field(t, 'MODALIDAD', 'modalidad') }} · {{ field(t, 'JORNADA', 'jornada') }}{{ field(t, 'ES_DOMINICAL', 'es_dominical') ? ' · Dom.' : '' }})
              </option>
            </select>
            <input v-model.number="heForm.CANTIDADHORAS" type="number" step="0.5" min="0.5" placeholder="Horas" required class="px-2 py-1.5 border border-slate-300 dark:border-slate-600 rounded text-xs w-20 bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
            <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-semibold">Agregar</button>
          </form>
          <div v-if="!loadingHe && horasExtras.length" class="text-xs space-y-1">
            <div v-for="he in horasExtras" :key="he.ID_DETALLEHORAEXTRA" class="flex justify-between bg-white dark:bg-slate-800 rounded px-3 py-1.5 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200">
              <span>{{ heNombre(he) }} — {{ heTipo(he) }} ({{ he.CANTIDADHORAS ?? he.cantidadhoras }} hrs)</span>
              <span class="font-mono">${{ fmt(he.MONTOAPAGAR) }} <button @click="deleteHe(he)" class="text-rose-600 dark:text-rose-400 ml-2">✕</button></span>
            </div>
          </div>
          <p v-else-if="!loadingHe && !empleadosPlanilla.length" class="text-xs text-amber-700 dark:text-amber-300">
            Calcule la planilla primero para cargar los empleados de esta corrida.
          </p>
          <p v-else-if="!loadingHe && !catalogs.tiposHorasExtras?.length" class="text-xs text-amber-700 dark:text-amber-300">
            No hay tipos de horas extras configurados. Vaya a Catálogos RRHH → Horas Extras o ejecute el seeder del sistema.
          </p>
          <p v-else-if="!loadingHe" class="text-xs text-slate-500 dark:text-slate-400">No hay horas extras registradas. Agregue manualmente o sincronice desde asistencia.</p>
        </div>

        <!-- Loading state -->
        <div v-if="loadingDetails" class="p-6">
          <SkeletonTable :cols="6" :rows="4" :no-header="true" />
        </div>

        <!-- Empty state -->
        <div v-else-if="!payrollDetails.length" class="p-10 text-center">
          <div class="text-4xl mb-3">📋</div>
          <p class="text-slate-600 dark:text-slate-400 font-semibold">Sin detalles calculados</p>
          <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Haz clic en "Calcular" para procesar los empleados de esta planilla.</p>
        </div>

        <!-- Details Table -->
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 font-semibold uppercase border-b border-slate-200 dark:border-slate-700">
                <th class="px-4 py-3 sticky left-0 bg-slate-50 dark:bg-slate-700/50 z-10">#</th>
                <th class="px-4 py-3 min-w-[160px]">Empleado</th>
                <th class="px-4 py-3 min-w-[120px]">Cargo</th>
                <th class="px-4 py-3 min-w-[80px]">Días</th>
                <th class="px-4 py-3 text-right min-w-[100px]">Salario Base</th>
                <th class="px-4 py-3 text-right min-w-[90px]">Hrs Extras</th>
                <th class="px-4 py-3 text-right min-w-[110px]">Total Devengado</th>
                <th class="px-4 py-3 text-right min-w-[80px]">AFP Emp.</th>
                <th class="px-4 py-3 text-right min-w-[80px]">ISSS Emp.</th>
                <th class="px-4 py-3 text-right min-w-[80px]">Renta</th>
                <th class="px-4 py-3 text-right min-w-[80px]">Préstamos</th>
                <th class="px-4 py-3 text-right min-w-[100px]">Total Desc.</th>
                <th class="px-4 py-3 text-right min-w-[110px] text-emerald-600">Líquido</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-for="(det, i) in payrollDetails" :key="det.ID_DETALLEPLANILLA"
                class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                <td class="px-4 py-3 text-slate-400 sticky left-0 bg-white dark:bg-slate-800">{{ i + 1 }}</td>
                <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">{{ det.NOM_EMPLEADO }}</td>
                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ det.CARGO || '—' }}</td>
                <td class="px-4 py-3">{{ Number(det.DIASLABORADOS).toFixed(1) }}</td>
                <td class="px-4 py-3 text-right font-medium">${{ fmt(det.SALARIO_BASE) }}</td>
                <td class="px-4 py-3 text-right">${{ fmt(det.HORAEXTRAS) }}</td>
                <td class="px-4 py-3 text-right font-semibold text-indigo-600 dark:text-indigo-400">${{ fmt(det.TOTAL_DEVENGADO) }}</td>
                <td class="px-4 py-3 text-right text-rose-500">${{ fmt(det.AFP_EMPLEADO) }}</td>
                <td class="px-4 py-3 text-right text-rose-500">${{ fmt(det.ISSS_EMPLEADO) }}</td>
                <td class="px-4 py-3 text-right text-rose-500">${{ fmt(det.RENTA_EMPLEADO) }}</td>
                <td class="px-4 py-3 text-right text-amber-600">${{ fmt(det.PRESTAMOS) }}</td>
                <td class="px-4 py-3 text-right text-rose-600 font-semibold">${{ fmt(det.TOTAL_DEDUCCIONES) }}</td>
                <td class="px-4 py-3 text-right font-bold text-emerald-600 dark:text-emerald-400">${{ fmt(det.LIQUIDO_A_RECIBIR) }}</td>
              </tr>
            </tbody>
            <!-- Totals Footer -->
            <tfoot>
              <tr class="bg-slate-100 dark:bg-slate-700/60 font-bold text-xs border-t-2 border-slate-300 dark:border-slate-600">
                <td class="px-4 py-3 text-slate-600 dark:text-slate-300" colspan="6">TOTALES ({{ payrollTotales.COUNT }} empleados)</td>
                <td class="px-4 py-3 text-right text-indigo-700 dark:text-indigo-300">${{ fmt(payrollTotales.TOTAL_DEVENGADO) }}</td>
                <td class="px-4 py-3 text-right text-rose-600">${{ fmt(payrollTotales.AFP_EMPLEADO) }}</td>
                <td class="px-4 py-3 text-right text-rose-600">${{ fmt(payrollTotales.ISSS_EMPLEADO) }}</td>
                <td class="px-4 py-3 text-right text-rose-600">${{ fmt(payrollTotales.RENTA_EMPLEADO) }}</td>
                <td class="px-4 py-3 text-right text-amber-600">${{ fmt(payrollTotales.PRESTAMOS) }}</td>
                <td class="px-4 py-3 text-right text-rose-700">${{ fmt(payrollTotales.TOTAL_DEDUCCIONES) }}</td>
                <td class="px-4 py-3 text-right text-emerald-700 dark:text-emerald-300">${{ fmt(payrollTotales.LIQUIDO_A_RECIBIR) }}</td>
              </tr>
              <!-- Costo Patronal -->
              <tr class="bg-slate-50 dark:bg-slate-700/30 text-xs border-t border-slate-200 dark:border-slate-700">
                <td class="px-4 py-2 text-slate-500 font-semibold" colspan="6">Costo Patronal</td>
                <td class="px-4 py-2 text-right text-slate-500" colspan="2">AFP Pat: ${{ fmt(payrollTotales.AFP_PATRONAL) }}</td>
                <td class="px-4 py-2 text-right text-slate-500" colspan="2">ISSS Pat: ${{ fmt(payrollTotales.ISSS_PATRONAL) }}</td>
                <td class="px-4 py-2 text-right text-slate-500" colspan="3">INSAFORP: ${{ fmt(payrollTotales.INSAFORP_PATRONAL) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Create Modal -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-950 dark:text-white">Nueva Corrida de Planilla</h3>
            <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">✕</button>
          </div>
          <form v-submit-lock="saveForm" class="p-6 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Empresa</label>
              <select v-model="form.ID_EMPRESA" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option v-for="emp in catalogs.empresas" :key="emp.ID_EMPRESA" :value="emp.ID_EMPRESA">{{ emp.NOMBREEMPRESA }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Título de Planilla</label>
              <input v-model="form.TITULO" type="text" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Tipo Planilla</label>
              <select v-model="form.ID_TIPOPLANILLA" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none">
                <option v-for="t in catalogs.tiposPlanilla" :key="t.ID_TIPOPLANILLA" :value="t.ID_TIPOPLANILLA">
                  {{ t.TIPOPLANILLA }}{{ t.GRUPO_NOMINA ? ` (${t.GRUPO_NOMINA})` : '' }}
                </option>
              </select>
              <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">Use planillas separadas: Permanente, Honorarios y Comercial. No combine grupos.</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Frecuencia de Pago</label>
              <select v-model="form.ID_FRECUENCIAPAGO" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none">
                <option v-for="f in catalogs.frecuencias" :key="f.ID_FRECUENCIAPAGO" :value="f.ID_FRECUENCIAPAGO">{{ f.NOMBREFRECUENCIA }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Periodo Laboral</label>
              <select v-model="form.ID_PERIODO" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none">
                <option v-for="p in catalogs.periodos" :key="p.ID_PERIODO" :value="p.ID_PERIODO">{{ p.CALPERIODO }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Cuenta Bancaria</label>
              <select v-model="form.ID_CUENTA" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none">
                <option v-for="c in catalogs.cuentas" :key="c.ID_CUENTA" :value="c.ID_CUENTA">{{ c.CONCEPTOCUENTA || c.NUMEROCUENTA }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Forma de Pago</label>
              <select v-model="form.FORMAPAGO" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none">
                <option value="Transferencia">Transferencia Bancaria</option>
                <option value="Cheque">Cheque</option>
                <option value="Efectivo">Efectivo</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Fecha de Pago</label>
              <input v-model="form.FECHAPAGO" type="date" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div v-if="modalError" class="text-xs text-red-500 bg-red-50 p-2 rounded">{{ modalError }}</div>
            <div class="flex justify-end space-x-3 pt-4 border-t">
              <button data-no-lock type="button" @click="showModal = false" class="px-4 py-2 border rounded-lg text-sm hover:bg-slate-50">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Guardar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import api from '../../services/api';

const planillas     = ref([]);
const catalogs      = ref({ empresas: [], tiposPlanilla: [], periodos: [], frecuencias: [], cuentas: [], tiposHorasExtras: [], empleados: [] });
const horasExtras   = ref([]);
const showHePanel   = ref(false);
const heForm        = ref({ ID_EMPLEADO: null, ID_HORASEXTRAS: null, CANTIDADHORAS: null });
const initialLoading = ref(true);
const loadingDetails= ref(false);
const loadingHe     = ref(false);
const calculating   = ref(false);
const showModal     = ref(false);
const modalError    = ref('');
const selectedPayroll = ref(null);
const payrollDetails  = ref([]);
const payrollTotales  = ref({ COUNT: 0, TOTAL_DEVENGADO: 0, AFP_EMPLEADO: 0, ISSS_EMPLEADO: 0, RENTA_EMPLEADO: 0, PRESTAMOS: 0, TOTAL_DEDUCCIONES: 0, LIQUIDO_A_RECIBIR: 0, AFP_PATRONAL: 0, ISSS_PATRONAL: 0, INSAFORP_PATRONAL: 0 });

const form = ref({
  TITULO: '', ID_EMPRESA: '', ID_TIPOPLANILLA: 1, ID_PERIODO: 1,
  ID_FRECUENCIAPAGO: 1, ID_CUENTA: 1, FORMAPAGO: 'Transferencia',
  FECHAPAGO: new Date().toISOString().split('T')[0], OBSERVACION: ''
});

const estadoLabel = (p) => { if (p.ANULADA) return 'Anulada'; if (p.CONTABILIZADA) return 'Contabilizada'; if (p.CERRADA) return 'Cerrada'; if (p.RECALCULADA) return 'Calculada'; return 'Pendiente'; };
const estadoClass = (p) => {
  if (p.ANULADA) return 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300';
  if (p.CONTABILIZADA) return 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300';
  if (p.CERRADA) return 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200';
  if (p.RECALCULADA) return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
  return 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
};

const field = (row, ...keys) => {
  if (!row) return null;
  for (const key of keys) {
    if (row[key] !== undefined && row[key] !== null) return row[key];
  }
  return null;
};

const empleadosPlanilla = computed(() => {
  const seen = new Set();
  const list = [];

  for (const det of payrollDetails.value || []) {
    const id = field(det, 'ID_EMPLEADO', 'id_empleado');
    if (!id || seen.has(id)) continue;
    seen.add(id);
    list.push({
      ID_EMPLEADO: id,
      NOMBRE_COMPLETO: field(det, 'NOM_EMPLEADO', 'nom_empleado') || `Empleado #${id}`,
    });
  }

  return list;
});

const heNombre = (he) => field(he, 'NOMBRE_EMPLEADO', 'nombre_empleado') || '—';
const heTipo = (he) => field(he, 'TIPOHORAEXTRA', 'tipohoraextra') || '—';

// ── Helpers ──────────────────────────────────────────────────────────────────
const fmt = (val) => Number(val ?? 0).toFixed(2);
const formatDate = (d) => {
  try { return new Date(d).toLocaleDateString('es-SV'); } catch { return d; }
};

// ── Data loading ─────────────────────────────────────────────────────────────
const loadPlanillas = async () => {
  try {
    const res = await api.get('/planillas');
    planillas.value = res.data;
  } catch (err) { console.error(err); }
  finally { initialLoading.value = false; }
};

const loadCatalogs = async () => {
  try {
    const res = await api.get('/planillas/catalogs');
    catalogs.value = res.data;
    if (res.data.empresas?.length) form.value.ID_EMPRESA = res.data.empresas[0].ID_EMPRESA;
    if (res.data.tiposPlanilla?.length) form.value.ID_TIPOPLANILLA = res.data.tiposPlanilla[0].ID_TIPOPLANILLA;
    if (res.data.periodos?.length) form.value.ID_PERIODO = res.data.periodos[0].ID_PERIODO;
    if (res.data.frecuencias?.length) form.value.ID_FRECUENCIAPAGO = res.data.frecuencias[0].ID_FRECUENCIAPAGO;
    if (res.data.cuentas?.length) form.value.ID_CUENTA = res.data.cuentas[0].ID_CUENTA;
    resetHeForm(res.data.tiposHorasExtras);
  } catch (err) { console.error(err); }
};

const resetHeForm = (tipos) => {
  const primerTipo = tipos?.[0];
  heForm.value = {
    ID_EMPLEADO: empleadosPlanilla.value[0]?.ID_EMPLEADO ?? null,
    ID_HORASEXTRAS: field(primerTipo, 'ID_HORASEXTRAS', 'id_horasextras'),
    CANTIDADHORAS: null,
  };
};

const toggleHePanel = async () => {
  if (!showHePanel.value && !payrollDetails.value.length) {
    alert('Calcule la planilla primero para ver los empleados de esta corrida.');
    return;
  }
  showHePanel.value = !showHePanel.value;
  if (showHePanel.value && !catalogs.value.tiposHorasExtras?.length) {
    await loadCatalogs();
  }
  if (showHePanel.value) {
    resetHeForm(catalogs.value.tiposHorasExtras);
  }
};

onMounted(() => { loadPlanillas(); loadCatalogs(); });

// ── CRUD ─────────────────────────────────────────────────────────────────────
const openCreateModal = () => {
  modalError.value = '';
  form.value = { TITULO: '', ID_EMPRESA: catalogs.value.empresas[0]?.ID_EMPRESA || '', ID_TIPOPLANILLA: catalogs.value.tiposPlanilla[0]?.ID_TIPOPLANILLA || 1, ID_PERIODO: catalogs.value.periodos[0]?.ID_PERIODO || 1, ID_FRECUENCIAPAGO: catalogs.value.frecuencias[0]?.ID_FRECUENCIAPAGO || 1, ID_CUENTA: catalogs.value.cuentas[0]?.ID_CUENTA || 1, FORMAPAGO: 'Transferencia', FECHAPAGO: new Date().toISOString().split('T')[0], OBSERVACION: '' };
  showModal.value = true;
};

const saveForm = async () => {
  try {
    await api.post('/planillas', form.value);
    showModal.value = false;
    loadPlanillas();
  } catch (err) { modalError.value = 'Error al crear planilla.'; }
};

const calculatePayroll = async (plan) => {
  calculating.value = true;
  selectedPayroll.value = plan;
  try {
    await api.post(`/planillas/${plan.ID_PLANILLA}/calcular`);
    await loadPlanillas();
    await viewDetails(plan);
  } catch (err) {
    alert('Error al calcular planilla. Revise la consola.');
    console.error(err);
  } finally {
    calculating.value = false;
  }
};

const loadHorasExtras = async (id) => {
  loadingHe.value = true;
  try {
    horasExtras.value = (await api.get(`/planillas/${id}/horas-extras`)).data;
  } catch (err) {
    console.error('Error cargando horas extras:', err);
    horasExtras.value = [];
  } finally {
    loadingHe.value = false;
  }
};
const addHe = async () => {
  try {
    await api.post(`/planillas/${selectedPayroll.value.ID_PLANILLA}/horas-extras`, heForm.value);
    await loadHorasExtras(selectedPayroll.value.ID_PLANILLA);
    heForm.value.CANTIDADHORAS = null;
  } catch (err) {
    alert('Error al agregar hora extra.');
    console.error(err);
  }
};
const deleteHe = async (he) => {
  await api.delete(`/planillas/${selectedPayroll.value.ID_PLANILLA}/horas-extras/${he.ID_DETALLEHORAEXTRA}`);
  await loadHorasExtras(selectedPayroll.value.ID_PLANILLA);
};
const syncHe = async () => {
  try {
    await api.post(`/planillas/${selectedPayroll.value.ID_PLANILLA}/horas-extras/sync`);
    await loadHorasExtras(selectedPayroll.value.ID_PLANILLA);
    alert('Horas extras sincronizadas desde asistencia.');
  } catch (err) {
    alert(err.response?.data?.error || 'Error al sincronizar horas extras.');
  }
};
const cerrarPlanilla = async (plan) => { if (!confirm('¿Cerrar planilla? No podrá recalcularla.')) return; await api.post(`/planillas/${plan.ID_PLANILLA}/cerrar`); loadPlanillas(); };
const anularPlanilla = async (plan) => { if (!confirm('¿Anular planilla? Se revertirán abonos de préstamos.')) return; await api.post(`/planillas/${plan.ID_PLANILLA}/anular`); loadPlanillas(); };
const contabilizarPlanilla = async (plan) => { await api.post(`/planillas/${plan.ID_PLANILLA}/contabilizar`); loadPlanillas(); };

const viewDetails = async (plan) => {
  selectedPayroll.value = plan;
  loadingDetails.value  = true;
  showHePanel.value = false;
  try {
    const [detailRes] = await Promise.all([
      api.get(`/planillas/${plan.ID_PLANILLA}`),
      loadHorasExtras(plan.ID_PLANILLA),
    ]);
    selectedPayroll.value = detailRes.data.planilla;
    payrollDetails.value  = detailRes.data.detalles  || [];
    payrollTotales.value  = detailRes.data.totales   || payrollTotales.value;
    setTimeout(() => document.getElementById('detail-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
  } catch (err) {
    console.error('Error cargando detalles:', err);
    alert('Error al cargar detalles de planilla.');
  } finally {
    loadingDetails.value = false;
  }
};

const closeDetails = () => {
  selectedPayroll.value = null;
  payrollDetails.value  = [];
};
</script>
