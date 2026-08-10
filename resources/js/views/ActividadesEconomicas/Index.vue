<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Actividades económicas MH</h1>
          <p class="page-subtitle mt-1">Catálogo Ministerio de Hacienda — mantenimiento de códigos CIIU.</p>
        </div>
        <div class="page-header-actions">
          <button @click="openModal()" class="btn-primary">+ Nueva actividad</button>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <input v-model="searchQuery" type="text" placeholder="Buscar código o descripción..." class="form-input max-w-sm" @input="onSearch" />
        <label class="flex items-center gap-2 text-sm">
          <input v-model="soloActivos" type="checkbox" @change="reload" /> Solo activos
        </label>
      </div>

      <SkeletonTable v-if="loading" :cols="4" />
      <div v-else class="table-shell table-scroll">
        <table v-table-cards class="table-cards w-full text-sm">
          <thead>
            <tr class="text-xs uppercase bg-slate-50 dark:bg-slate-700/50">
              <th class="px-4 py-3">Código MH</th>
              <th class="px-4 py-3">Descripción</th>
              <th class="px-4 py-3">Estado</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y dark:divide-slate-700">
            <tr v-for="a in items" :key="a.ID_ACTIVIDAD_ECONOMICA">
              <td class="px-4 py-3 font-mono font-bold text-indigo-600">{{ a.CODIGO_MH }}</td>
              <td class="px-4 py-3">{{ a.DESCRIPCION }}</td>
              <td class="px-4 py-3"><span class="badge">{{ a.ESACTIVO ? 'Activo' : 'Inactivo' }}</span></td>
              <td class="px-4 py-3 text-right space-x-1">
                <IconActionButton icon="edit" title="Editar" @click="editar(a)" />
                <IconActionButton v-if="a.ESACTIVO" icon="trash" title="Inactivar" variant="danger" @click="inactivar(a)" />
              </td>
            </tr>
          </tbody>
        </table>
        <PaginationBar :page="page" :last-page="lastPage" :per-page="perPage" :total="total" :loading="loading"
          @update:page="setPage" @update:per-page="setPerPage" />
      </div>

      <AppModalShell :open="showModal" @close="closeModal">
        <div class="modal-panel max-w-lg mx-auto">
          <div class="modal-header"><h3 class="modal-title">{{ editingId ? 'Editar' : 'Nueva' }} actividad</h3></div>
          <form v-submit-lock="save" class="modal-body space-y-4">
            <input v-model="form.CODIGO_MH" class="form-input font-mono" placeholder="Código MH (ej. 01111)" required maxlength="10" />
            <textarea v-model="form.DESCRIPCION" class="form-input" rows="3" placeholder="Descripción" required maxlength="500" />
            <label v-if="editingId" class="flex items-center gap-2 text-sm">
              <input v-model="form.ESACTIVO" type="checkbox" /> Activo
            </label>
            <p v-if="modalError" class="text-sm text-red-600">{{ modalError }}</p>
            <div class="modal-footer">
              <button type="button" data-no-lock class="btn-secondary" @click="closeModal">Cancelar</button>
              <LoadingButton type="submit">Guardar</LoadingButton>
            </div>
          </form>
        </div>
      </AppModalShell>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import AppModalShell from '../../components/AppModalShell.vue';
import PaginationBar from '../../components/PaginationBar.vue';
import { usePaginatedList } from '../../composables/usePaginatedList';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';
import { getApiErrorMessage } from '../../utils/apiError';

const soloActivos = ref(true);
const searchQuery = ref('');
const listParams = computed(() => ({ solo_activos: soloActivos.value ? 1 : 0 }));

const { items, loading, page, perPage, total, lastPage, fetch: reload, setPage, setPerPage, setSearch } =
  usePaginatedList('/actividades-economicas', { perPage: 25, params: listParams });

const showModal = ref(false);
const editingId = ref(null);
const modalError = ref('');
const form = ref({ CODIGO_MH: '', DESCRIPCION: '', ESACTIVO: true });

let searchTimer;
function onSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => setSearch(searchQuery.value), 300);
}

function openModal() {
  editingId.value = null;
  form.value = { CODIGO_MH: '', DESCRIPCION: '', ESACTIVO: true };
  modalError.value = '';
  showModal.value = true;
}

function editar(a) {
  editingId.value = a.ID_ACTIVIDAD_ECONOMICA;
  form.value = { CODIGO_MH: a.CODIGO_MH, DESCRIPCION: a.DESCRIPCION, ESACTIVO: !!a.ESACTIVO };
  modalError.value = '';
  showModal.value = true;
}

function closeModal() { showModal.value = false; }

async function save() {
  modalError.value = '';
  try {
    if (editingId.value) {
      await api.put(`/actividades-economicas/${editingId.value}`, form.value);
    } else {
      await api.post('/actividades-economicas', form.value);
    }
    closeModal();
    reload();
  } catch (err) {
    modalError.value = getApiErrorMessage(err);
  }
}

async function inactivar(a) {
  if (!await dialog.confirm({ title: 'Inactivar', message: `¿Inactivar ${a.CODIGO_MH}?`, variant: 'danger' })) return;
  await api.delete(`/actividades-economicas/${a.ID_ACTIVIDAD_ECONOMICA}`);
  reload();
}

watch(soloActivos, () => reload());
onMounted(reload);
</script>
