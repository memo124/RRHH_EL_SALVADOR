<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Incapacidades y Cobros ISSS</h1>
          <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 max-w-2xl">
            Registre aquí el <strong>certificado médico</strong> del empleado. El sistema calcula los días pagados por la empresa
            y genera automáticamente el <strong>cobro al ISSS</strong> cuando corresponde (del día 4 en adelante en enfermedad común).
          </p>
        </div>
        <button
          v-if="vista === 'incap'"
          @click="openModal"
          class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shrink-0"
        >
          + Registrar certificado
        </button>
      </div>

      <!-- Flujo explicativo -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
        <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 rounded-lg p-4">
          <div class="font-bold text-indigo-800 dark:text-indigo-300 mb-1">1. Certificado médico</div>
          <p class="text-slate-600 dark:text-slate-400">Usted registra empleado, tipo, fechas y N° de certificado ISSS.</p>
        </div>
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 rounded-lg p-4">
          <div class="font-bold text-amber-800 dark:text-amber-300 mb-1">2. Cálculo automático</div>
          <p class="text-slate-600 dark:text-slate-400">Días 1–3: paga la empresa. Día 4+: el ISSS subsidia el 75% del salario.</p>
        </div>
        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 rounded-lg p-4">
          <div class="font-bold text-emerald-800 dark:text-emerald-300 mb-1">3. Cobro al ISSS</div>
          <p class="text-slate-600 dark:text-slate-400">En la otra pestaña marca cuando el ISSS le reembolsó el dinero a la empresa.</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex border-b border-slate-200 dark:border-slate-700">
        <button
          @click="vista = 'incap'"
          :class="vista === 'incap'
            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 font-bold'
            : 'border-transparent text-slate-500 hover:text-slate-700'"
          class="py-3 px-6 border-b-2 text-sm transition-all"
        >
          Certificados médicos
          <span class="ml-1.5 px-1.5 py-0.5 bg-slate-100 dark:bg-slate-700 rounded text-xs">{{ items.length }}</span>
        </button>
        <button
          @click="switchToSubsidios"
          :class="vista === 'sub'
            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 font-bold'
            : 'border-transparent text-slate-500 hover:text-slate-700'"
          class="py-3 px-6 border-b-2 text-sm transition-all"
        >
          Cobros al ISSS
          <span v-if="subTotales.count_pendiente > 0" class="ml-1.5 px-1.5 py-0.5 bg-amber-100 text-amber-800 rounded text-xs font-bold">
            {{ subTotales.count_pendiente }} pend.
          </span>
        </button>
      </div>

      <!-- Tab: Certificados -->
      <div v-if="vista === 'incap'" class="space-y-4">
        <label class="flex items-center gap-2 text-sm text-slate-600">
          <input type="checkbox" v-model="soloActivas" @change="load" />
          Mostrar solo incapacidades vigentes
        </label>

        <SkeletonTable v-if="loading" :cols="8" :no-header="true" />

        <div v-else class="bg-white dark:bg-slate-800 rounded-xl border overflow-hidden shadow-sm">
          <table class="w-full text-sm text-left">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-xs font-semibold uppercase text-slate-600">
                <th class="px-4 py-3">Empleado</th>
                <th class="px-4 py-3">Tipo / Certificado</th>
                <th class="px-4 py-3">Periodo</th>
                <th class="px-4 py-3 text-center">Días</th>
                <th class="px-4 py-3">Distribución</th>
                <th class="px-4 py-3">Cobro ISSS</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-for="i in items" :key="i.ID_INCAPACIDAD" class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                <td class="px-4 py-3">
                  <div class="font-semibold">{{ i.NOMBRE_EMPLEADO }}</div>
                  <div class="text-xs text-slate-500">{{ i.CODIGOEMPLEADO }}</div>
                </td>
                <td class="px-4 py-3">
                  <div>{{ i.NOMBRE_TIPO }}</div>
                  <div class="text-xs text-slate-500 font-mono">{{ i.NUMERO_CERTIFICADO_ISSS }}</div>
                </td>
                <td class="px-4 py-3 text-xs whitespace-nowrap">
                  {{ fmtDate(i.FECHA_INICIO) }} — {{ fmtDate(i.FECHA_FIN) }}
                </td>
                <td class="px-4 py-3 text-center font-bold">{{ i.DIAS_TOTALES }}</td>
                <td class="px-4 py-3 text-xs space-y-0.5">
                  <div><span class="text-slate-500">Patrono:</span> {{ i.DIAS_PAGADOS_PATRONO }} días</div>
                  <div><span class="text-slate-500">ISSS:</span> {{ i.DIAS_SUBSIDIADOS_ISSS }} días</div>
                </td>
                <td class="px-4 py-3">
                  <template v-if="i.ID_SUBSIDIO">
                    <div class="font-mono text-sm">${{ fmt(i.MONTO_SUBSIDIO_CALCULADO_ISSS) }}</div>
                    <span :class="subsidioBadge(i.ESTADO_SUBSIDIO)" class="text-xs px-1.5 py-0.5 rounded font-semibold">
                      {{ i.ESTADO_SUBSIDIO }}
                    </span>
                  </template>
                  <span v-else class="text-xs text-slate-400">Sin subsidio ISSS</span>
                </td>
                <td class="px-4 py-3">
                  <span :class="estadoBadge(i.ESTADO_INCAPACIDAD)" class="text-xs px-2 py-0.5 rounded font-semibold">
                    {{ i.ESTADO_INCAPACIDAD }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <button
                    v-if="i.ESTADO_INCAPACIDAD !== 'CANCELADA'"
                    @click="cancelar(i)"
                    class="text-rose-600 text-xs font-semibold hover:underline"
                  >
                    Cancelar
                  </button>
                </td>
              </tr>
              <tr v-if="!items.length">
                <td colspan="8" class="px-4 py-10 text-center text-slate-400">
                  No hay certificados registrados. Use "+ Registrar certificado" para agregar uno.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab: Cobros ISSS -->
      <div v-else class="space-y-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-lg px-4 py-3 text-sm text-blue-900 dark:text-blue-200">
          <strong>Solo consulta y seguimiento.</strong> Los cobros se generan solos al registrar un certificado con días subsidiados por ISSS.
          Aquí marca cuándo el ISSS le depositó el reembolso a la empresa.
        </div>

        <!-- Totales -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <div class="bg-white dark:bg-slate-800 rounded-xl border p-4">
            <div class="text-xs text-slate-500 uppercase font-semibold">Pendiente de cobro</div>
            <div class="text-2xl font-bold text-amber-600 mt-1">${{ fmt(subTotales.pendiente) }}</div>
            <div class="text-xs text-slate-400">{{ subTotales.count_pendiente }} registro(s)</div>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-xl border p-4">
            <div class="text-xs text-slate-500 uppercase font-semibold">Ya cobrado al ISSS</div>
            <div class="text-2xl font-bold text-emerald-600 mt-1">${{ fmt(subTotales.cobrado) }}</div>
          </div>
        </div>

        <div class="flex gap-2">
          <button
            v-for="f in ['', 'PENDIENTE', 'COBRADO']"
            :key="f || 'all'"
            @click="filtroSub = f; loadSub()"
            :class="filtroSub === f ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-slate-800 border text-slate-600'"
            class="px-3 py-1.5 rounded-lg text-xs font-semibold"
          >
            {{ f === '' ? 'Todos' : f === 'PENDIENTE' ? 'Pendientes' : 'Cobrados' }}
          </button>
        </div>

        <SkeletonTable v-if="loadingSub" :cols="6" :no-header="true" />

        <div v-else class="bg-white dark:bg-slate-800 rounded-xl border overflow-hidden shadow-sm">
          <table class="w-full text-sm text-left">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-xs font-semibold uppercase text-slate-600">
                <th class="px-4 py-3">Empleado</th>
                <th class="px-4 py-3">Certificado origen</th>
                <th class="px-4 py-3">Días ISSS</th>
                <th class="px-4 py-3 text-right">Monto a cobrar</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3 text-right">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-for="s in subsidios" :key="s.ID_SUBSIDIO">
                <td class="px-4 py-3">
                  <div class="font-semibold">{{ s.NOMBRE_EMPLEADO }}</div>
                  <div class="text-xs text-slate-500">{{ s.NOMBRE_TIPO }}</div>
                </td>
                <td class="px-4 py-3 text-xs">
                  <div class="font-mono">{{ s.NUMERO_CERTIFICADO_ISSS }}</div>
                  <div class="text-slate-500">{{ fmtDate(s.FECHA_INICIO) }} — {{ fmtDate(s.FECHA_FIN) }}</div>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="font-bold">{{ s.DIAS_SUBSIDIADOS_ISSS }}</span>
                  <span class="text-xs text-slate-500"> / {{ s.DIAS_TOTALES }} tot.</span>
                </td>
                <td class="px-4 py-3 text-right font-mono font-bold">${{ fmt(s.MONTO_SUBSIDIO_CALCULADO_ISSS) }}</td>
                <td class="px-4 py-3">
                  <span :class="subsidioBadge(s.ESTADO_SUBSIDIO)" class="text-xs px-2 py-0.5 rounded font-semibold">
                    {{ s.ESTADO_SUBSIDIO }}
                  </span>
                  <div v-if="s.FECHA_COBRO_ISSS" class="text-xs text-slate-500 mt-0.5">Cobrado: {{ fmtDate(s.FECHA_COBRO_ISSS) }}</div>
                </td>
                <td class="px-4 py-3 text-right">
                  <button
                    v-if="s.ESTADO_SUBSIDIO === 'PENDIENTE'"
                    @click="openCobroModal(s)"
                    class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-semibold"
                  >
                    Registrar cobro
                  </button>
                  <span v-else class="text-xs text-slate-400">—</span>
                </td>
              </tr>
              <tr v-if="!subsidios.length">
                <td colspan="6" class="px-4 py-10 text-center text-slate-400">
                  No hay cobros al ISSS{{ filtroSub ? ' con este filtro' : '' }}.
                  Se generan al registrar certificados con más de 3 días de enfermedad o maternidad.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal: Registrar certificado -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full border overflow-hidden">
          <div class="px-6 py-4 border-b bg-slate-50 dark:bg-slate-700/50">
            <h3 class="font-bold text-slate-900 dark:text-white">Registrar certificado de incapacidad</h3>
            <p class="text-xs text-slate-500 mt-1">Datos del certificado médico ISSS del empleado.</p>
          </div>
          <form v-submit-lock="save" class="p-6 space-y-4">
            <div>
              <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Empleado</label>
              <AsyncSelect
                v-model="form.ID_EMPLEADO"
                endpoint="/empleados/select"
                placeholder="Seleccionar empleado"
                search-placeholder="Buscar empleado…"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Tipo de incapacidad</label>
              <AsyncSelect
                v-model="form.ID_TIPOINCAPACIDAD"
                catalog="tipos-incapacidad"
                placeholder="Seleccionar tipo"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">N° Certificado ISSS</label>
              <input v-model="form.NUMERO_CERTIFICADO_ISSS" placeholder="Ej. CERT-2026-001234" required class="w-full px-3 py-2 border rounded-lg text-sm" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Fecha inicio</label>
                <input v-model="form.FECHA_INICIO" type="date" required class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Fecha fin</label>
                <input v-model="form.FECHA_FIN" type="date" required class="w-full px-3 py-2 border rounded-lg text-sm" />
              </div>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/40 rounded-lg p-3 text-xs text-slate-600 dark:text-slate-400">
              Al guardar, el sistema marcará los días en asistencia y, si aplica, creará el cobro al ISSS en la otra pestaña.
            </div>
            <div v-if="modalError" class="text-xs text-red-500">{{ modalError }}</div>
            <div class="flex justify-end gap-2 pt-2 border-t">
              <button data-no-lock type="button" @click="showModal = false" class="px-4 py-2 border rounded-lg text-sm">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold">Guardar certificado</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Modal: Registrar cobro ISSS -->
      <div v-if="showCobroModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full border overflow-hidden">
          <div class="px-6 py-4 border-b bg-emerald-50 dark:bg-emerald-900/20">
            <h3 class="font-bold text-emerald-900 dark:text-emerald-200">Registrar cobro del ISSS</h3>
            <p class="text-xs text-slate-500 mt-1">Confirme que la empresa recibió el reembolso.</p>
          </div>
          <form v-submit-lock="confirmarCobro" class="p-6 space-y-4">
            <div class="bg-slate-50 dark:bg-slate-700/40 rounded-lg p-3 text-sm space-y-1">
              <div class="flex justify-between"><span class="text-slate-500">Empleado</span><span class="font-semibold">{{ cobroForm.NOMBRE_EMPLEADO }}</span></div>
              <div class="flex justify-between"><span class="text-slate-500">Certificado</span><span class="font-mono text-xs">{{ cobroForm.NUMERO_CERTIFICADO_ISSS }}</span></div>
              <div class="flex justify-between"><span class="text-slate-500">Monto ISSS</span><span class="font-bold text-emerald-600">${{ fmt(cobroForm.MONTO_SUBSIDIO_CALCULADO_ISSS) }}</span></div>
            </div>
            <div>
              <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Fecha en que cobró el ISSS</label>
              <input v-model="cobroForm.FECHA_COBRO_ISSS" type="date" required class="w-full px-3 py-2 border rounded-lg text-sm" />
            </div>
            <div>
              <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Comprobante / referencia (opcional)</label>
              <input v-model="cobroForm.COMPROBANTE_PAGO_ISSS" placeholder="N° transferencia, cheque, etc." class="w-full px-3 py-2 border rounded-lg text-sm" />
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t">
              <button data-no-lock type="button" @click="showCobroModal = false" class="px-4 py-2 border rounded-lg text-sm">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold">Confirmar cobro</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import api from '../../services/api';
import { dialog, dialogEmpleadoLabel } from '../../composables/useDialog';
import { useToast } from '../../composables/useToast';

const toast = useToast();

const items = ref([]);
const subsidios = ref([]);
const subTotales = ref({ pendiente: 0, cobrado: 0, count_pendiente: 0 });
const showModal = ref(false);
const showCobroModal = ref(false);
const modalError = ref('');
const vista = ref('incap');
const soloActivas = ref(false);
const filtroSub = ref('');
const loading = ref(false);
const loadingSub = ref(false);

const form = ref({
  ID_EMPLEADO: null,
  ID_TIPOINCAPACIDAD: null,
  NUMERO_CERTIFICADO_ISSS: '',
  FECHA_INICIO: '',
  FECHA_FIN: '',
});

const cobroForm = ref({});

const fmt = v => Number(v || 0).toFixed(2);
const fmtDate = d => (d ? new Date(d).toLocaleDateString('es-SV') : '');

const estadoBadge = e => ({
  REGISTRADA: 'bg-blue-50 text-blue-700',
  CANCELADA: 'bg-rose-50 text-rose-700',
}[e] || 'bg-slate-100 text-slate-600');

const subsidioBadge = e => ({
  PENDIENTE: 'bg-amber-50 text-amber-700',
  COBRADO: 'bg-emerald-50 text-emerald-700',
}[e] || 'bg-slate-100 text-slate-600');

const load = async () => {
  loading.value = true;
  try {
    const params = soloActivas.value ? { activas: 1 } : {};
    items.value = (await api.get('/incapacidades', { params })).data;
  } finally {
    loading.value = false;
  }
};

const loadSub = async () => {
  loadingSub.value = true;
  try {
    const params = filtroSub.value ? { estado: filtroSub.value } : {};
    const res = await api.get('/subsidios-isss', { params });
    subsidios.value = res.data.items || res.data;
    subTotales.value = res.data.totales || { pendiente: 0, cobrado: 0, count_pendiente: 0 };
  } finally {
    loadingSub.value = false;
  }
};

const switchToSubsidios = () => {
  vista.value = 'sub';
  loadSub();
};

const openModal = () => {
  modalError.value = '';
  form.value = {
    ID_EMPLEADO: null,
    ID_TIPOINCAPACIDAD: null,
    NUMERO_CERTIFICADO_ISSS: '',
    FECHA_INICIO: '',
    FECHA_FIN: '',
  };
  showModal.value = true;
};

const save = async () => {
  try {
    await api.post('/incapacidades', form.value);
    showModal.value = false;
    await load();
    if (vista.value === 'sub') await loadSub();
  } catch {
    modalError.value = 'Error al registrar el certificado.';
  }
};

const cancelar = async (i) => {
  const nombre = dialogEmpleadoLabel(i);
  const values = await dialog.form({
    title: 'Cancelar incapacidad',
    message: `¿Confirma cancelar el certificado médico de ${nombre}?`,
    variant: 'danger',
    confirmText: 'Sí, cancelar',
    cancelText: 'No',
    fields: [
      {
        name: 'motivo',
        type: 'textarea',
        label: 'Motivo de cancelación',
        required: true,
        rows: 3,
        placeholder: 'Ej: certificado duplicado, error de fechas, empleado reintegrado…',
      },
    ],
  });
  if (!values) return;
  try {
    await api.post(`/incapacidades/${i.ID_INCAPACIDAD}/cancelar`, { motivo: values.motivo });
    toast.success('Incapacidad cancelada');
    load();
  } catch {
    toast.error('Error', 'No se pudo cancelar la incapacidad.');
  }
};

const openCobroModal = (s) => {
  cobroForm.value = {
    ID_SUBSIDIO: s.ID_SUBSIDIO,
    NOMBRE_EMPLEADO: s.NOMBRE_EMPLEADO,
    NUMERO_CERTIFICADO_ISSS: s.NUMERO_CERTIFICADO_ISSS,
    MONTO_SUBSIDIO_CALCULADO_ISSS: s.MONTO_SUBSIDIO_CALCULADO_ISSS,
    FECHA_COBRO_ISSS: new Date().toISOString().slice(0, 10),
    COMPROBANTE_PAGO_ISSS: '',
  };
  showCobroModal.value = true;
};

const confirmarCobro = async () => {
  await api.put(`/subsidios-isss/${cobroForm.value.ID_SUBSIDIO}`, {
    ESTADO_SUBSIDIO: 'COBRADO',
    FECHA_COBRO_ISSS: cobroForm.value.FECHA_COBRO_ISSS,
    COMPROBANTE_PAGO_ISSS: cobroForm.value.COMPROBANTE_PAGO_ISSS || null,
  });
  showCobroModal.value = false;
  loadSub();
  load();
};

onMounted(load);
</script>
