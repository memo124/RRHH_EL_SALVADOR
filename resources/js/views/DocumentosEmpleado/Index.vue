<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Documentos del empleado</h1>
          <p class="page-subtitle mt-1">Repositorio de adjuntos por empleado, origen y tipo de documento.</p>
        </div>
        <div class="page-header-actions">
          <button @click="showUpload = true" class="btn-primary">+ Subir documento</button>
        </div>
      </div>

      <div class="flex flex-wrap gap-3">
        <div class="min-w-[220px]">
          <label class="form-label">Empleado</label>
          <AsyncSelect v-model="filtroEmpleado" endpoint="/empleados/select" nullable placeholder="Todos" @change="reload" />
        </div>
        <div>
          <label class="form-label">Origen</label>
          <select v-model="filtroOrigen" @change="reload" class="form-input">
            <option value="">Todos</option>
            <option value="manual">Manual</option>
            <option value="formulario">Formulario</option>
            <option value="encuesta">Encuesta</option>
            <option value="reclutamiento">Reclutamiento</option>
            <option value="capacitacion">Capacitación</option>
          </select>
        </div>
      </div>

      <SkeletonTable v-if="loading" :cols="6" />
      <div v-else class="table-shell table-scroll">
        <table v-table-cards class="table-cards w-full text-sm">
          <thead>
            <tr class="text-xs uppercase bg-slate-50 dark:bg-slate-700/50">
              <th class="px-4 py-3">Archivo</th>
              <th class="px-4 py-3">Empleado</th>
              <th class="px-4 py-3">Tipo</th>
              <th class="px-4 py-3">Origen</th>
              <th class="px-4 py-3">Fecha</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y dark:divide-slate-700">
            <tr v-for="a in items" :key="a.ID_ADJUNTO">
              <td class="px-4 py-3">
                <div class="font-medium">{{ a.NOMBRE_ARCHIVO }}</div>
                <div class="text-xs text-slate-500">{{ formatBytes(a.TAMANO_BYTES) }}</div>
              </td>
              <td class="px-4 py-3">{{ a.EMPLEADO_NOMBRE || '—' }}</td>
              <td class="px-4 py-3">{{ a.TIPO_NOMBRE || '—' }}</td>
              <td class="px-4 py-3"><span class="badge">{{ a.ORIGEN }}</span></td>
              <td class="px-4 py-3 text-xs">{{ fmtDate(a.FECHA_SUBIDA) }}</td>
              <td class="px-4 py-3 text-right space-x-1">
                <IconActionButton icon="download" title="Descargar" @click="download(a)" />
                <IconActionButton icon="trash" title="Eliminar" variant="danger" @click="remove(a)" />
              </td>
            </tr>
          </tbody>
        </table>
        <PaginationBar :page="page" :last-page="lastPage" :per-page="perPage" :total="total" :loading="loading"
          @update:page="setPage" @update:per-page="setPerPage" />
      </div>

      <AppModalShell :open="showUpload" @close="showUpload = false">
        <div class="modal-panel max-w-md mx-auto">
          <div class="modal-header"><h3 class="modal-title">Subir documento</h3></div>
          <form v-submit-lock="upload" class="modal-body space-y-4">
            <AsyncSelect v-model="uploadForm.ID_EMPLEADO" endpoint="/empleados/select" placeholder="Empleado" />
            <select v-model="uploadForm.ID_TIPO_DOCUMENTO_ADJUNTO" class="form-input">
              <option :value="null">Tipo de documento</option>
              <option v-for="t in tipos" :key="t.ID_TIPO_DOCUMENTO_ADJUNTO" :value="t.ID_TIPO_DOCUMENTO_ADJUNTO">{{ t.NOMBRE }}</option>
            </select>
            <FileUpload :uploading="uploading" @upload="onFile" />
            <div class="modal-footer">
              <button type="button" data-no-lock class="btn-secondary" @click="showUpload = false">Cancelar</button>
              <LoadingButton type="submit" :disabled="!uploadFile">Subir</LoadingButton>
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
import FileUpload from '../../components/FileUpload.vue';
import { usePaginatedList } from '../../composables/usePaginatedList';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';

const filtroEmpleado = ref(null);
const filtroOrigen = ref('');
const tipos = ref([]);
const showUpload = ref(false);
const uploadForm = ref({ ID_EMPLEADO: null, ID_TIPO_DOCUMENTO_ADJUNTO: null });
const uploadFile = ref(null);
const uploading = ref(false);

const listParams = computed(() => {
  const p = {};
  if (filtroEmpleado.value) p.ID_EMPLEADO = filtroEmpleado.value;
  if (filtroOrigen.value) p.ORIGEN = filtroOrigen.value;
  return p;
});

const { items, loading, page, perPage, total, lastPage, fetch: reload, setPage, setPerPage } =
  usePaginatedList('/adjuntos', { perPage: 25, params: listParams });

function fmtDate(d) { return d ? new Date(d).toLocaleDateString('es-SV') : '—'; }
function formatBytes(b) {
  if (!b) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB'];
  const i = Math.floor(Math.log(b) / Math.log(k));
  return `${(b / Math.pow(k, i)).toFixed(1)} ${sizes[i]}`;
}

function onFile(f) { uploadFile.value = f; }

async function loadTipos() {
  const { data } = await api.get('/adjuntos/tipos');
  tipos.value = data;
}

async function upload() {
  if (!uploadFile.value) return;
  uploading.value = true;
  try {
    const fd = new FormData();
    fd.append('archivo', uploadFile.value);
    if (uploadForm.value.ID_EMPLEADO) fd.append('ID_EMPLEADO', uploadForm.value.ID_EMPLEADO);
    if (uploadForm.value.ID_TIPO_DOCUMENTO_ADJUNTO) fd.append('ID_TIPO_DOCUMENTO_ADJUNTO', uploadForm.value.ID_TIPO_DOCUMENTO_ADJUNTO);
    fd.append('ORIGEN', 'manual');
    await api.post('/adjuntos', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
    showUpload.value = false;
    uploadFile.value = null;
    reload();
  } finally {
    uploading.value = false;
  }
}

async function download(a) {
  const { data } = await api.get(`/adjuntos/${a.ID_ADJUNTO}/download`, { responseType: 'blob' });
  const url = URL.createObjectURL(data);
  const link = document.createElement('a');
  link.href = url;
  link.download = a.NOMBRE_ARCHIVO;
  link.click();
  URL.revokeObjectURL(url);
}

async function remove(a) {
  if (!await dialog.confirm({ title: 'Eliminar documento', message: `¿Inactivar "${a.NOMBRE_ARCHIVO}"?`, variant: 'danger' })) return;
  await api.delete(`/adjuntos/${a.ID_ADJUNTO}`);
  reload();
}

onMounted(loadTipos);
</script>
