<template>
  <PortalLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Permisos y vacaciones</h1>
          <p class="page-subtitle mt-1">Consulte su saldo de vacaciones y solicite permisos.</p>
        </div>
        <div class="page-header-actions">
          <button @click="openModal()" class="btn-primary">+ Nueva solicitud</button>
        </div>
      </div>

      <div v-if="saldo" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="card-panel">
          <p class="text-xs text-slate-500 dark:text-slate-400">Días asignados</p>
          <p class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ saldo.DIAS_ASIGNADOS }}</p>
        </div>
        <div class="card-panel">
          <p class="text-xs text-slate-500 dark:text-slate-400">Días usados</p>
          <p class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ saldo.DIAS_USADOS }}</p>
        </div>
        <div class="card-panel">
          <p class="text-xs text-slate-500 dark:text-slate-400">Pendientes de aprobación</p>
          <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ saldo.DIAS_PENDIENTES_APROBACION }}</p>
        </div>
        <div class="card-panel">
          <p class="text-xs text-slate-500 dark:text-slate-400">Disponibles ({{ saldo.ANIO }})</p>
          <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ saldo.DIAS_DISPONIBLES }}</p>
        </div>
      </div>

      <SkeletonTable v-if="loading" :cols="6" />

      <div v-else-if="items.length === 0" class="card-panel text-center text-sm text-slate-500 dark:text-slate-400 py-10">
        No tiene solicitudes de permisos o vacaciones registradas.
      </div>

      <div v-else class="table-shell table-scroll">
        <table v-table-cards class="table-cards w-full text-sm">
          <thead>
            <tr class="text-xs uppercase bg-slate-50 dark:bg-slate-700/50">
              <th class="px-4 py-3">Tipo</th>
              <th class="px-4 py-3">Periodo</th>
              <th class="px-4 py-3 text-center">Días</th>
              <th class="px-4 py-3">Estado</th>
              <th class="px-4 py-3">Solicitado</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y dark:divide-slate-700">
            <tr v-for="s in items" :key="s.ID_SOLICITUD">
              <td class="px-4 py-3 font-medium">{{ s.TIPO_NOMBRE }}</td>
              <td class="px-4 py-3 text-xs whitespace-nowrap">{{ fmtDate(s.FECHA_INICIO) }} — {{ fmtDate(s.FECHA_FIN) }}</td>
              <td class="px-4 py-3 text-center">{{ s.DIAS_SOLICITADOS }}</td>
              <td class="px-4 py-3"><span class="badge">{{ s.ESTADO }}</span></td>
              <td class="px-4 py-3 text-xs">{{ fmtDate(s.FECHA_SOLICITUD) }}</td>
              <td class="px-4 py-3 text-right">
                <button
                  v-if="s.ESTADO === 'pendiente'"
                  type="button"
                  data-no-lock
                  class="text-red-600 hover:text-red-800 dark:text-red-400 text-xs font-semibold"
                  @click="cancelar(s)"
                >
                  Cancelar
                </button>
                <span v-else class="text-slate-400 text-xs">—</span>
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
            <div>
              <label class="form-label">Tipo de permiso</label>
              <select v-model="form.ID_TIPO_PERMISO" class="form-input" required>
                <option value="">Seleccione…</option>
                <option v-for="t in tipos" :key="t.ID_TIPO_PERMISO" :value="t.ID_TIPO_PERMISO">{{ t.NOMBRE }}</option>
              </select>
            </div>
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
            <div>
              <label class="form-label">Motivo (opcional)</label>
              <textarea v-model="form.MOTIVO" class="form-input" rows="2" />
            </div>
            <p v-if="modalError" class="text-sm text-red-600">{{ modalError }}</p>
            <div class="modal-footer">
              <button type="button" data-no-lock class="btn-secondary" @click="showModal = false">Cancelar</button>
              <LoadingButton type="submit">Enviar solicitud</LoadingButton>
            </div>
          </form>
        </div>
      </AppModalShell>
    </div>
  </PortalLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import PortalLayout from './PortalLayout.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import AppModalShell from '../../components/AppModalShell.vue';
import PaginationBar from '../../components/PaginationBar.vue';
import LoadingButton from '../../components/LoadingButton.vue';
import { usePaginatedList } from '../../composables/usePaginatedList';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';
import { getApiErrorMessage } from '../../utils/apiError';

const { items, loading, page, perPage, total, lastPage, fetch: reload, setPage, setPerPage } =
  usePaginatedList('/portal/permisos', { perPage: 10 });

const tipos = ref([]);
const saldo = ref(null);
const showModal = ref(false);
const modalError = ref('');
const form = ref(emptyForm());

function emptyForm() {
  return { ID_TIPO_PERMISO: '', FECHA_INICIO: '', FECHA_FIN: '', MOTIVO: '' };
}

function fmtDate(d) {
  return d ? new Date(d).toLocaleDateString('es-SV') : '—';
}

async function loadCatalogs() {
  const { data } = await api.get('/portal/permisos/catalogs');
  tipos.value = data.tipos;
  saldo.value = data.saldo;
}

function openModal() {
  form.value = emptyForm();
  modalError.value = '';
  showModal.value = true;
}

async function save() {
  modalError.value = '';
  try {
    await api.post('/portal/permisos', form.value);
    showModal.value = false;
    await Promise.all([reload(), loadCatalogs()]);
  } catch (err) {
    modalError.value = getApiErrorMessage(err);
  }
}

async function cancelar(s) {
  if (!await dialog.confirm({
    title: 'Cancelar solicitud',
    message: `¿Cancelar la solicitud de ${s.TIPO_NOMBRE}?`,
    confirmText: 'Cancelar solicitud',
    variant: 'danger',
  })) return;
  try {
    await api.post(`/portal/permisos/${s.ID_SOLICITUD}/cancelar`);
    await Promise.all([reload(), loadCatalogs()]);
  } catch (err) {
    await dialog.alert({ title: 'Error', message: getApiErrorMessage(err), variant: 'danger' });
  }
}

onMounted(() => {
  loadCatalogs();
  reload();
});
</script>
