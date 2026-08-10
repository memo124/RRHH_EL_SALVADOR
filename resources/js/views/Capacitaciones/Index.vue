<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Capacitaciones</h1>
          <p class="page-subtitle mt-1">Cursos, inscripciones, asistencia y certificados.</p>
        </div>
        <div class="page-header-actions">
          <button @click="openModal()" class="btn-primary">+ Nueva capacitación</button>
        </div>
      </div>

      <SkeletonTable v-if="loading" :cols="6" />
      <div v-else class="table-shell table-scroll">
        <table v-table-cards class="table-cards w-full text-sm">
          <thead>
            <tr class="text-xs uppercase bg-slate-50 dark:bg-slate-700/50">
              <th class="px-4 py-3">Título</th>
              <th class="px-4 py-3">Modalidad</th>
              <th class="px-4 py-3">Fechas</th>
              <th class="px-4 py-3">Estado</th>
              <th class="px-4 py-3">Empresa</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y dark:divide-slate-700">
            <tr v-for="c in items" :key="c.ID_CAPACITACION">
              <td class="px-4 py-3 font-medium">{{ c.TITULO }}</td>
              <td class="px-4 py-3 capitalize">{{ c.MODALIDAD }}</td>
              <td class="px-4 py-3 text-xs">{{ fmtDate(c.FECHA_INICIO) }} — {{ fmtDate(c.FECHA_FIN) }}</td>
              <td class="px-4 py-3"><span class="badge">{{ c.ESTADO }}</span></td>
              <td class="px-4 py-3 text-xs">{{ c.NOMBREEMPRESA || 'Todas' }}</td>
              <td class="px-4 py-3 text-right space-x-1">
                <IconActionButton variant="view" title="Detalle" @click="verDetalle(c)" />
                <IconActionButton v-if="c.ESTADO === 'borrador'" variant="edit" title="Publicar" @click="publicar(c)" />
                <IconActionButton v-if="c.ESTADO === 'publicada'" variant="permissions" title="Inscribir" @click="openInscribir(c)" />
                <IconActionButton v-if="c.ESTADO === 'publicada'" variant="cancel" title="Cerrar curso" @click="cerrar(c)" />
                <IconActionButton v-if="c.ESTADO !== 'publicada'" variant="delete" title="Inactivar" @click="eliminar(c)" />
              </td>
            </tr>
          </tbody>
        </table>
        <PaginationBar :page="page" :last-page="lastPage" :per-page="perPage" :total="total" :loading="loading"
          @update:page="setPage" @update:per-page="setPerPage" />
      </div>

      <!-- Modal crear/editar -->
      <AppModalShell :open="showModal" @close="showModal = false">
        <div class="modal-panel modal-panel-lg max-w-2xl mx-auto">
          <div class="modal-header"><h3 class="modal-title">{{ editingId ? 'Editar' : 'Nueva' }} capacitación</h3></div>
          <form v-submit-lock="save" class="modal-body space-y-4">
            <input v-model="form.TITULO" class="form-input" placeholder="Título" required />
            <textarea v-model="form.DESCRIPCION" class="form-input" rows="2" placeholder="Descripción" />
            <div class="grid grid-cols-2 gap-3">
              <select v-model="form.MODALIDAD" class="form-input">
                <option value="presencial">Presencial</option>
                <option value="virtual">Virtual</option>
                <option value="mixta">Mixta</option>
              </select>
              <input v-model.number="form.CUPO_MAX" type="number" min="1" class="form-input" placeholder="Cupo máximo" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <input v-model="form.FECHA_INICIO" type="datetime-local" class="form-input" />
              <input v-model="form.FECHA_FIN" type="datetime-local" class="form-input" />
            </div>
            <AsyncSelect v-model="form.ID_EMPRESA" catalog="empresas" nullable placeholder="Empresa (opcional)" />
            <input v-model="form.LUGAR" class="form-input" placeholder="Lugar / enlace" />
            <p v-if="modalError" class="text-sm text-red-600">{{ modalError }}</p>
            <div class="modal-footer">
              <button type="button" data-no-lock class="btn-secondary" @click="showModal = false">Cancelar</button>
              <LoadingButton type="submit">Guardar</LoadingButton>
            </div>
          </form>
        </div>
      </AppModalShell>

      <!-- Modal detalle + inscripciones -->
      <AppModalShell :open="showDetalle" @close="showDetalle = false">
        <div class="modal-panel modal-panel-lg max-w-3xl mx-auto">
          <div class="modal-header">
            <h3 class="modal-title">{{ detalle?.TITULO }}</h3>
            <p class="text-sm text-slate-500">{{ detalle?.DESCRIPCION }}</p>
          </div>
          <div class="modal-body space-y-4 max-h-[65vh] overflow-y-auto">
            <div class="flex gap-4 text-sm">
              <span>Estado: <strong>{{ detalle?.ESTADO }}</strong></span>
              <span v-if="cupos != null">Cupos: <strong>{{ cupos }}</strong></span>
            </div>

            <div v-if="detalle?.ESTADO === 'publicada'" class="flex gap-2 items-end">
              <div class="flex-1">
                <AsyncSelect v-model="empleadoInscribir" endpoint="/empleados/select" placeholder="Inscribir empleado" />
              </div>
              <button @click="inscribirEmpleado" class="btn-primary text-sm">Inscribir</button>
            </div>

            <table class="w-full text-sm">
              <thead>
                <tr class="text-xs uppercase text-slate-500 border-b dark:border-slate-600">
                  <th class="py-2 text-left">Empleado</th>
                  <th class="py-2">Estado</th>
                  <th class="py-2">Calificación</th>
                  <th class="py-2">Certificado</th>
                  <th class="py-2 text-right">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="i in inscripciones" :key="i.ID_INSCRIPCION" class="border-b dark:border-slate-700">
                  <td class="py-2">{{ i.EMPLEADO_NOMBRE }}</td>
                  <td class="py-2 text-center"><span class="badge">{{ i.ESTADO }}</span></td>
                  <td class="py-2 text-center">{{ i.CALIFICACION ?? '—' }}</td>
                  <td class="py-2 text-center">
                    <button
                      v-if="i.ID_ADJUNTO_CERTIFICADO"
                      type="button"
                      class="text-xs text-indigo-600 hover:underline"
                      @click="downloadCert(i)"
                    >{{ i.CERTIFICADO_NOMBRE || 'Descargar' }}</button>
                    <span v-else class="text-xs text-slate-400">—</span>
                  </td>
                  <td class="py-2 text-right space-x-1">
                    <button v-if="i.ESTADO === 'inscrito'" @click="marcarAsistencia(i)" class="text-xs text-indigo-600">Asistencia</button>
                    <button v-if="i.ESTADO === 'inscrito'" @click="openCompletar(i)" class="text-xs text-emerald-600">Completar</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </AppModalShell>

      <AppModalShell :open="showCompletar" @close="showCompletar = false">
        <div class="modal-panel max-w-md mx-auto">
          <div class="modal-header">
            <h3 class="modal-title">Completar inscripción</h3>
            <p class="text-sm text-slate-500">{{ inscCompletar?.EMPLEADO_NOMBRE }}</p>
          </div>
          <form v-submit-lock="submitCompletar" class="modal-body space-y-4">
            <div>
              <label class="form-label">Calificación (0-100, opcional)</label>
              <input v-model.number="completarForm.CALIFICACION" type="number" min="0" max="100" step="0.1" class="form-input" />
            </div>
            <div>
              <label class="form-label">Certificado (opcional)</label>
              <FileUpload :uploading="certUploading" @upload="onCertFile" @clear="certFile = null" />
            </div>
            <p v-if="completarError" class="text-sm text-red-600">{{ completarError }}</p>
            <div class="modal-footer">
              <button type="button" data-no-lock class="btn-secondary" @click="showCompletar = false">Cancelar</button>
              <LoadingButton type="submit">Completar</LoadingButton>
            </div>
          </form>
        </div>
      </AppModalShell>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import AppModalShell from '../../components/AppModalShell.vue';
import PaginationBar from '../../components/PaginationBar.vue';
import FileUpload from '../../components/FileUpload.vue';
import { usePaginatedList } from '../../composables/usePaginatedList';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';
import { getApiErrorMessage } from '../../utils/apiError';

const TIPO_ADJUNTO_OTRO = 6;

const { items, loading, page, perPage, total, lastPage, fetch: reload, setPage, setPerPage } =
  usePaginatedList('/capacitaciones');

const showModal = ref(false);
const editingId = ref(null);
const modalError = ref('');
const form = ref(emptyForm());

const showDetalle = ref(false);
const detalle = ref(null);
const inscripciones = ref([]);
const cupos = ref(null);
const empleadoInscribir = ref(null);
const capSelId = ref(null);

const showCompletar = ref(false);
const inscCompletar = ref(null);
const completarForm = ref({ CALIFICACION: null });
const certFile = ref(null);
const certUploading = ref(false);
const completarError = ref('');

function emptyForm() {
  return { TITULO: '', DESCRIPCION: '', MODALIDAD: 'presencial', FECHA_INICIO: '', FECHA_FIN: '', CUPO_MAX: null, ID_EMPRESA: null, LUGAR: '' };
}

function fmtDate(d) { return d ? new Date(d).toLocaleDateString('es-SV') : '—'; }

function openModal() {
  editingId.value = null;
  form.value = emptyForm();
  modalError.value = '';
  showModal.value = true;
}

async function save() {
  modalError.value = '';
  try {
    if (editingId.value) {
      await api.put(`/capacitaciones/${editingId.value}`, form.value);
    } else {
      await api.post('/capacitaciones', form.value);
    }
    showModal.value = false;
    reload();
  } catch (err) {
    modalError.value = getApiErrorMessage(err);
  }
}

async function publicar(c) {
  if (!await dialog.confirm({ title: 'Publicar', message: `¿Publicar "${c.TITULO}"? Se creará evento en calendario.`, confirmText: 'Publicar' })) return;
  await api.post(`/capacitaciones/${c.ID_CAPACITACION}/publicar`);
  reload();
}

async function cerrar(c) {
  if (!await dialog.confirm({ title: 'Cerrar curso', message: `¿Cerrar "${c.TITULO}"?`, confirmText: 'Cerrar' })) return;
  try {
    await api.post(`/capacitaciones/${c.ID_CAPACITACION}/cerrar`);
    reload();
  } catch (err) {
    await dialog.alert({ title: 'Error', message: getApiErrorMessage(err), variant: 'danger' });
  }
}

async function eliminar(c) {
  if (!await dialog.confirm({
    title: 'Inactivar capacitación',
    message: `¿Inactivar "${c.TITULO}"?`,
    confirmText: 'Inactivar',
    variant: 'danger',
  })) return;
  try {
    await api.delete(`/capacitaciones/${c.ID_CAPACITACION}`);
    reload();
  } catch (err) {
    await dialog.alert({ title: 'Error', message: getApiErrorMessage(err), variant: 'danger' });
  }
}

async function verDetalle(c) {
  capSelId.value = c.ID_CAPACITACION;
  const { data } = await api.get(`/capacitaciones/${c.ID_CAPACITACION}`);
  detalle.value = data.capacitacion;
  inscripciones.value = data.inscripciones;
  cupos.value = data.cupos_disponibles;
  showDetalle.value = true;
}

function openInscribir(c) {
  verDetalle(c);
}

async function inscribirEmpleado() {
  if (!empleadoInscribir.value) return;
  await api.post(`/capacitaciones/${capSelId.value}/inscribir`, { ID_EMPLEADOS: [empleadoInscribir.value] });
  empleadoInscribir.value = null;
  verDetalle({ ID_CAPACITACION: capSelId.value });
}

async function marcarAsistencia(insc) {
  const values = await dialog.form({
    title: 'Registrar asistencia',
    fields: [
      { name: 'fecha', type: 'text', label: 'Fecha (YYYY-MM-DD)', required: true, default: new Date().toISOString().slice(0, 10) },
      { name: 'asistio', type: 'select', label: '¿Asistió?', required: true, options: [{ value: '1', label: 'Sí' }, { value: '0', label: 'No' }] },
    ],
    confirmText: 'Guardar',
  });
  if (!values) return;
  await api.post(`/capacitaciones/inscripciones/${insc.ID_INSCRIPCION}/asistencia`, {
    FECHA: values.fecha,
    ASISTIO: values.asistio === '1',
  });
}

function openCompletar(insc) {
  inscCompletar.value = insc;
  completarForm.value = { CALIFICACION: null };
  certFile.value = null;
  completarError.value = '';
  showCompletar.value = true;
}

function onCertFile(file) {
  certFile.value = file;
}

async function uploadCertificado(idInscripcion, idEmpleado) {
  if (!certFile.value) return null;
  certUploading.value = true;
  try {
    const fd = new FormData();
    fd.append('archivo', certFile.value);
    fd.append('ID_TIPO_DOCUMENTO_ADJUNTO', TIPO_ADJUNTO_OTRO);
    fd.append('ORIGEN', 'capacitacion');
    fd.append('ID_ORIGEN', idInscripcion);
    if (idEmpleado) fd.append('ID_EMPLEADO', idEmpleado);
    const { data } = await api.post('/adjuntos', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
    return data.ID_ADJUNTO;
  } finally {
    certUploading.value = false;
  }
}

async function submitCompletar() {
  completarError.value = '';
  try {
    let idAdjunto = null;
    if (certFile.value) {
      idAdjunto = await uploadCertificado(inscCompletar.value.ID_INSCRIPCION, inscCompletar.value.ID_EMPLEADO);
    }
    await api.post(`/capacitaciones/inscripciones/${inscCompletar.value.ID_INSCRIPCION}/completar`, {
      CALIFICACION: completarForm.value.CALIFICACION,
      ID_ADJUNTO_CERTIFICADO: idAdjunto,
    });
    showCompletar.value = false;
    verDetalle({ ID_CAPACITACION: capSelId.value });
  } catch (err) {
    completarError.value = getApiErrorMessage(err);
  }
}

async function downloadCert(i) {
  const { data } = await api.get(`/adjuntos/${i.ID_ADJUNTO_CERTIFICADO}/download`, { responseType: 'blob' });
  const url = URL.createObjectURL(data);
  const link = document.createElement('a');
  link.href = url;
  link.download = i.CERTIFICADO_NOMBRE || 'certificado.pdf';
  link.click();
  URL.revokeObjectURL(url);
}

onMounted(reload);
</script>
