<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Vacaciones y permisos laborales</h1>
          <p class="page-subtitle mt-1">Solicitudes, aprobaciones, saldo de vacaciones e integración automática con planilla tipo Vacaciones.</p>
        </div>
        <div class="page-header-actions flex gap-2">
          <button @click="inicializarSaldos" class="btn-secondary text-sm">Inicializar saldos año</button>
          <button @click="openModal()" class="btn-primary">+ Nueva solicitud</button>
        </div>
      </div>

      <div class="flex border-b border-slate-200 dark:border-slate-700">
        <button v-for="t in tabs" :key="t.id" @click="tab = t.id; reload()"
          :class="tab === t.id ? 'border-indigo-500 text-indigo-600 font-bold' : 'border-transparent text-slate-500'"
          class="py-3 px-6 border-b-2 text-sm">{{ t.label }}</button>
      </div>

      <SkeletonTable v-if="loading" :cols="7" />
      <div v-else class="table-shell table-scroll">
        <table v-table-cards class="table-cards w-full text-sm">
          <thead>
            <tr class="text-xs uppercase bg-slate-50 dark:bg-slate-700/50">
              <th class="px-4 py-3">Empleado</th>
              <th class="px-4 py-3">Tipo</th>
              <th class="px-4 py-3">Periodo</th>
              <th class="px-4 py-3 text-center">Días</th>
              <th class="px-4 py-3">Estado</th>
              <th class="px-4 py-3">Solicitud</th>
              <th class="px-4 py-3">Planilla</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y dark:divide-slate-700">
            <tr v-for="s in items" :key="s.ID_SOLICITUD">
              <td class="px-4 py-3">
                <div class="font-medium">{{ s.EMPLEADO_NOMBRE }}</div>
                <div class="text-xs text-slate-500">{{ s.CODIGOEMPLEADO }}</div>
              </td>
              <td class="px-4 py-3">{{ s.TIPO_NOMBRE }}</td>
              <td class="px-4 py-3 text-xs whitespace-nowrap">{{ fmtDate(s.FECHA_INICIO) }} — {{ fmtDate(s.FECHA_FIN) }}</td>
              <td class="px-4 py-3 text-center">{{ s.DIAS_SOLICITADOS }}</td>
              <td class="px-4 py-3"><span class="badge">{{ s.ESTADO }}</span></td>
              <td class="px-4 py-3 text-xs">{{ fmtDate(s.FECHA_SOLICITUD) }}</td>
              <td class="px-4 py-3 text-xs">
                <router-link
                  v-if="s.ID_PLANILLA"
                  :to="`/planilla`"
                  class="text-indigo-600 hover:underline font-semibold"
                  :title="s.PLANILLA_TITULO || ''"
                >
                  #{{ s.ID_PLANILLA }}
                </router-link>
                <button
                  v-else-if="s.ESTADO === 'aprobada' && s.DESCUENTA_SALDO"
                  type="button"
                  class="text-amber-600 hover:underline text-xs font-semibold"
                  @click="integrarPlanilla(s)"
                >
                  Integrar
                </button>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td class="px-4 py-3 text-right space-x-1">
                <IconActionButton v-if="s.ESTADO === 'pendiente'" variant="view" title="Aprobar" @click="aprobar(s)" />
                <IconActionButton v-if="s.ESTADO === 'pendiente'" variant="delete" title="Rechazar" @click="rechazar(s)" />
                <IconActionButton v-if="s.ESTADO === 'pendiente'" variant="cancel" title="Cancelar solicitud" @click="cancelar(s)" />
                <IconActionButton variant="view" title="Ver saldo" @click="verSaldo(s)" />
              </td>
            </tr>
          </tbody>
        </table>
        <PaginationBar :page="page" :last-page="lastPage" :per-page="perPage" :total="total" :loading="loading"
          @update:page="setPage" @update:per-page="setPerPage" />
      </div>

      <AppModalShell :open="showModal" @close="showModal = false">
        <div class="modal-panel max-w-lg mx-auto">
          <div class="modal-header"><h3 class="modal-title">Nueva solicitud</h3></div>
          <form v-submit-lock="save" class="modal-body space-y-4">
            <AsyncSelect v-model="form.ID_EMPLEADO" endpoint="/empleados/select" placeholder="Empleado" @change="loadSaldoPreview" />
            <div v-if="saldoPreview" class="text-xs bg-slate-50 dark:bg-slate-700/50 rounded p-3">
              Saldo {{ saldoPreview.ANIO }}: <strong>{{ saldoPreview.DIAS_DISPONIBLES }}</strong> días disponibles
              ({{ saldoPreview.DIAS_USADOS }} usados de {{ saldoPreview.DIAS_ASIGNADOS }})
            </div>
            <select v-model="form.ID_TIPO_PERMISO" class="form-input" required>
              <option value="">Tipo de permiso</option>
              <option v-for="t in tipos" :key="t.ID_TIPO_PERMISO" :value="t.ID_TIPO_PERMISO">{{ t.NOMBRE }}</option>
            </select>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="form-label">Desde</label>
                <input v-model="form.FECHA_INICIO" type="date" class="form-input" required />
              </div>
              <div>
                <label class="form-label">Hasta</label>
                <input v-model="form.FECHA_FIN" type="date" class="form-input" required />
              </div>
            </div>
            <textarea v-model="form.MOTIVO" class="form-input" rows="2" placeholder="Motivo (opcional)" />
            <p v-if="modalError" class="text-sm text-red-600">{{ modalError }}</p>
            <div class="modal-footer">
              <button type="button" data-no-lock class="btn-secondary" @click="showModal = false">Cancelar</button>
              <LoadingButton type="submit">Registrar</LoadingButton>
            </div>
          </form>
        </div>
      </AppModalShell>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import AppModalShell from '../../components/AppModalShell.vue';
import PaginationBar from '../../components/PaginationBar.vue';
import { usePaginatedList } from '../../composables/usePaginatedList';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';
import { getApiErrorMessage } from '../../utils/apiError';

const tab = ref('todas');
const tabs = [
  { id: 'todas', label: 'Todas' },
  { id: 'pendiente', label: 'Pendientes' },
  { id: 'aprobada', label: 'Aprobadas' },
];

const listParams = computed(() => (tab.value === 'todas' ? {} : { estado: tab.value }));
const { items, loading, page, perPage, total, lastPage, fetch: reload, setPage, setPerPage } =
  usePaginatedList('/permisos', { perPage: 25, params: listParams });

const tipos = ref([]);
const showModal = ref(false);
const modalError = ref('');
const saldoPreview = ref(null);
const form = ref({ ID_EMPLEADO: null, ID_TIPO_PERMISO: '', FECHA_INICIO: '', FECHA_FIN: '', MOTIVO: '' });

function fmtDate(d) { return d ? new Date(d).toLocaleDateString('es-SV') : '—'; }

async function loadCatalogs() {
  const { data } = await api.get('/permisos/catalogs');
  tipos.value = data.tipos;
}

function openModal() {
  form.value = { ID_EMPLEADO: null, ID_TIPO_PERMISO: '', FECHA_INICIO: '', FECHA_FIN: '', MOTIVO: '' };
  saldoPreview.value = null;
  modalError.value = '';
  showModal.value = true;
}

async function loadSaldoPreview(id) {
  if (!id) { saldoPreview.value = null; return; }
  const { data } = await api.get(`/permisos/saldo/${id}`);
  saldoPreview.value = data;
}

async function save() {
  modalError.value = '';
  try {
    await api.post('/permisos', form.value);
    showModal.value = false;
    reload();
  } catch (err) {
    modalError.value = getApiErrorMessage(err);
  }
}

async function aprobar(s) {
  if (!await dialog.confirm({ title: 'Aprobar solicitud', message: `¿Aprobar ${s.TIPO_NOMBRE} de ${s.EMPLEADO_NOMBRE}?`, confirmText: 'Aprobar' })) return;
  const { data } = await api.post(`/permisos/${s.ID_SOLICITUD}/aprobar`);
  if (data.planilla_warning) {
    await dialog.alert({ title: 'Aprobada con advertencia', message: data.planilla_warning, variant: 'warning' });
  }
  reload();
}

async function integrarPlanilla(s) {
  if (!await dialog.confirm({ title: 'Integrar planilla', message: `¿Generar planilla de vacaciones para ${s.EMPLEADO_NOMBRE}?`, confirmText: 'Integrar' })) return;
  try {
    const { data } = await api.post(`/permisos/${s.ID_SOLICITUD}/integrar-planilla`);
    await dialog.alert({ title: 'Planilla generada', message: data.message + (data.planilla ? ` (#${data.planilla.ID_PLANILLA})` : ''), variant: 'success' });
    reload();
  } catch (err) {
    await dialog.alert({ title: 'Error', message: getApiErrorMessage(err), variant: 'danger' });
  }
}

async function rechazar(s) {
  const values = await dialog.form({
    title: 'Rechazar solicitud',
    fields: [{ name: 'motivo', type: 'textarea', label: 'Motivo', required: true, rows: 3 }],
    variant: 'danger',
    confirmText: 'Rechazar',
  });
  if (!values) return;
  await api.post(`/permisos/${s.ID_SOLICITUD}/rechazar`, { MOTIVO_RECHAZO: values.motivo });
  reload();
}

async function cancelar(s) {
  if (!await dialog.confirm({
    title: 'Cancelar solicitud',
    message: `¿Cancelar la solicitud de ${s.TIPO_NOMBRE} de ${s.EMPLEADO_NOMBRE}?`,
    confirmText: 'Cancelar solicitud',
    variant: 'danger',
  })) return;
  try {
    await api.post(`/permisos/${s.ID_SOLICITUD}/cancelar`);
    reload();
  } catch (err) {
    await dialog.alert({ title: 'Error', message: getApiErrorMessage(err), variant: 'danger' });
  }
}

async function verSaldo(s) {
  const { data } = await api.get(`/permisos/saldo/${s.ID_EMPLEADO}`);
  await dialog.alert({
    title: `Saldo vacaciones — ${s.EMPLEADO_NOMBRE}`,
    message: `Año ${data.ANIO}: ${data.DIAS_DISPONIBLES} disponibles (${data.DIAS_USADOS} usados de ${data.DIAS_ASIGNADOS}). Pendientes aprobación: ${data.DIAS_PENDIENTES_APROBACION}.`,
    variant: 'info',
  });
}

async function inicializarSaldos() {
  if (!await dialog.confirm({ title: 'Inicializar saldos', message: '¿Crear saldos de vacaciones para todos los empleados activos del año actual?', confirmText: 'Inicializar' })) return;
  await api.post('/permisos/saldos/inicializar', { ANIO: new Date().getFullYear() });
}

onMounted(() => { loadCatalogs(); reload(); });
</script>
