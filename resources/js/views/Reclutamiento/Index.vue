<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Reclutamiento y selección</h1>
          <p class="page-subtitle mt-1">Vacantes, candidatos, etapas del proceso y entrevistas.</p>
        </div>
        <div class="page-header-actions">
          <button @click="openVacante()" class="btn-primary">+ Nueva vacante</button>
        </div>
      </div>

      <SkeletonTable v-if="loading" :cols="6" />
      <div v-else class="table-shell table-scroll">
        <table v-table-cards class="table-cards w-full text-sm">
          <thead>
            <tr class="text-xs uppercase bg-slate-50 dark:bg-slate-700/50">
              <th class="px-4 py-3">Vacante</th>
              <th class="px-4 py-3">Empresa</th>
              <th class="px-4 py-3">Estado</th>
              <th class="px-4 py-3 text-center">Candidatos</th>
              <th class="px-4 py-3">Plazas</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y dark:divide-slate-700">
            <tr v-for="v in items" :key="v.ID_VACANTE">
              <td class="px-4 py-3 font-medium">{{ v.TITULO }}</td>
              <td class="px-4 py-3 text-xs">{{ v.NOMBREEMPRESA || '—' }}</td>
              <td class="px-4 py-3"><span class="badge">{{ v.ESTADO }}</span></td>
              <td class="px-4 py-3 text-center">{{ v.TOTAL_CANDIDATOS }}</td>
              <td class="px-4 py-3 text-center">{{ v.PLAZAS }}</td>
              <td class="px-4 py-3 text-right space-x-1">
                <IconActionButton variant="view" title="Ver candidatos" @click="verVacante(v)" />
                <IconActionButton v-if="v.ESTADO === 'abierta'" variant="cancel" title="Cerrar vacante" @click="cerrar(v)" />
              </td>
            </tr>
          </tbody>
        </table>
        <PaginationBar :page="page" :last-page="lastPage" :per-page="perPage" :total="total" :loading="loading"
          @update:page="setPage" @update:per-page="setPerPage" />
      </div>

      <!-- Modal vacante -->
      <AppModalShell :open="showVacante" @close="showVacante = false">
        <div class="modal-panel modal-panel-lg max-w-2xl mx-auto">
          <div class="modal-header"><h3 class="modal-title">Nueva vacante</h3></div>
          <form v-submit-lock="saveVacante" class="modal-body space-y-4">
            <input v-model="vacanteForm.TITULO" class="form-input" placeholder="Título del puesto" required />
            <textarea v-model="vacanteForm.DESCRIPCION" class="form-input" rows="2" placeholder="Descripción" />
            <textarea v-model="vacanteForm.REQUISITOS" class="form-input" rows="2" placeholder="Requisitos" />
            <div class="grid grid-cols-2 gap-3">
              <AsyncSelect v-model="vacanteForm.ID_EMPRESA" catalog="empresas" nullable placeholder="Empresa" />
              <input v-model.number="vacanteForm.PLAZAS" type="number" min="1" class="form-input" placeholder="Plazas" />
            </div>
            <AsyncSelect v-model="vacanteForm.ID_DEPARTAMENTO" catalog="departamentos" nullable placeholder="Departamento" :params="deptoParams" />
            <AsyncSelect v-model="vacanteForm.ID_CARGO" catalog="cargos" nullable placeholder="Cargo" />
            <div class="modal-footer">
              <button type="button" data-no-lock class="btn-secondary" @click="showVacante = false">Cancelar</button>
              <LoadingButton type="submit">Guardar</LoadingButton>
            </div>
          </form>
        </div>
      </AppModalShell>

      <!-- Modal detalle vacante + candidatos -->
      <AppModalShell :open="showDetalle" @close="showDetalle = false">
        <div class="modal-panel modal-panel-lg max-w-4xl mx-auto">
          <div class="modal-header">
            <h3 class="modal-title">{{ detalle?.TITULO }}</h3>
            <p class="text-sm text-slate-500">{{ detalle?.DESCRIPCION }}</p>
          </div>
          <div class="modal-body space-y-4 max-h-[70vh] overflow-y-auto">
            <button @click="openCandidato()" class="btn-primary text-sm">+ Agregar candidato</button>
            <table class="w-full text-sm">
              <thead>
                <tr class="text-xs uppercase text-slate-500 border-b dark:border-slate-600">
                  <th class="py-2 text-left">Candidato</th>
                  <th class="py-2">Etapa</th>
                  <th class="py-2">Contacto</th>
                  <th class="py-2">CV</th>
                  <th class="py-2 text-right">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in candidatos" :key="c.ID_CANDIDATO" class="border-b dark:border-slate-700">
                  <td class="py-2">{{ c.NOMBRES }} {{ c.APELLIDOS }}</td>
                  <td class="py-2 text-center"><span class="badge">{{ c.ETAPA_NOMBRE || '—' }}</span></td>
                  <td class="py-2 text-xs">{{ c.EMAIL || c.TELEFONO || '—' }}</td>
                  <td class="py-2 text-center">
                    <button
                      v-if="c.ID_ADJUNTO_CV"
                      type="button"
                      class="text-xs text-indigo-600 hover:underline"
                      @click="downloadCv(c)"
                    >{{ c.CV_NOMBRE || 'Descargar' }}</button>
                    <span v-else class="text-xs text-slate-400">—</span>
                  </td>
                  <td class="py-2 text-right space-x-1">
                    <select @change="avanzar(c, $event)" class="form-input text-xs inline-block w-auto">
                      <option value="">Mover etapa</option>
                      <option v-for="e in etapas" :key="e.ID_ETAPA" :value="e.ID_ETAPA">{{ e.NOMBRE }}</option>
                    </select>
                    <button @click="programarEntrevista(c)" class="text-xs text-indigo-600 ml-1">Entrevista</button>
                    <button
                      v-if="c.ESTADO === 'contratado' || c.ETAPA_NOMBRE === 'Contratado'"
                      type="button"
                      class="text-xs text-emerald-600 ml-1 font-semibold"
                      @click="openContratar(c)"
                    >{{ c.ID_EMPLEADO ? `Empleado #${c.ID_EMPLEADO}` : 'Contratar' }}</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </AppModalShell>

      <!-- Modal candidato -->
      <AppModalShell :open="showCandidato" @close="showCandidato = false">
        <div class="modal-panel max-w-md mx-auto">
          <div class="modal-header"><h3 class="modal-title">Nuevo candidato</h3></div>
          <form v-submit-lock="saveCandidato" class="modal-body space-y-4">
            <input v-model="candidatoForm.NOMBRES" class="form-input" placeholder="Nombres" required />
            <input v-model="candidatoForm.APELLIDOS" class="form-input" placeholder="Apellidos" />
            <input v-model="candidatoForm.EMAIL" type="email" class="form-input" placeholder="Email" />
            <input v-model="candidatoForm.TELEFONO" class="form-input" placeholder="Teléfono" />
            <div>
              <label class="form-label">Currículum (opcional)</label>
              <FileUpload :uploading="cvUploading" @upload="onCvFile" @clear="cvFile = null" />
            </div>
            <p v-if="candidatoError" class="text-sm text-red-600">{{ candidatoError }}</p>
            <div class="modal-footer">
              <button type="button" data-no-lock class="btn-secondary" @click="showCandidato = false">Cancelar</button>
              <LoadingButton type="submit">Registrar</LoadingButton>
            </div>
          </form>
        </div>
      </AppModalShell>

      <AppModalShell :open="showContratar" @close="showContratar = false">
        <div class="modal-panel modal-panel-lg max-w-2xl mx-auto">
          <div class="modal-header">
            <h3 class="modal-title">Contratar / Crear empleado</h3>
            <p class="text-sm text-slate-500">{{ contratarNombre }}</p>
          </div>
          <form v-submit-lock="submitContratar" class="modal-body space-y-4 max-h-[70vh] overflow-y-auto">
            <div class="grid grid-cols-2 gap-3">
              <AsyncSelect v-model="contratarForm.ID_EMPRESA" catalog="empresas" placeholder="Empresa" />
              <AsyncSelect v-model="contratarForm.ID_DEPARTAMENTO" catalog="departamentos" placeholder="Departamento" :params="contratarDeptoParams" />
              <AsyncSelect v-model="contratarForm.ID_CARGO" catalog="cargos" placeholder="Cargo" />
              <AsyncSelect v-model="contratarForm.ID_TIPOCONTRATACION" catalog="tipos-contratacion" placeholder="Tipo contratación" />
              <AsyncSelect v-model="contratarForm.ID_DISTRITO" catalog="distritos" placeholder="Distrito" />
              <input v-model="contratarForm.CODIGOEMPLEADO" class="form-input" placeholder="Código empleado" required />
              <input v-model="contratarForm.DUI" class="form-input" placeholder="DUI" required />
              <select v-model="contratarForm.GENERO" class="form-input" required>
                <option value="">Género</option>
                <option value="M">Masculino</option>
                <option value="F">Femenino</option>
              </select>
              <div>
                <label class="form-label">Nacimiento</label>
                <input v-model="contratarForm.FECHANACIMIENTO" type="date" class="form-input" required />
              </div>
              <div>
                <label class="form-label">Ingreso</label>
                <input v-model="contratarForm.FECHAINGRESO" type="date" class="form-input" required />
              </div>
              <input v-model.number="contratarForm.SALARIOMENSUAL" type="number" min="0" step="0.01" class="form-input" placeholder="Salario mensual" required />
              <input v-model="contratarForm.CORREOELECTRONICO" type="email" class="form-input" placeholder="Correo" />
              <input v-model="contratarForm.TELEFONOCELULAR" class="form-input" placeholder="Teléfono" />
            </div>
            <p v-if="contratarError" class="text-sm text-red-600">{{ contratarError }}</p>
            <div class="modal-footer">
              <button type="button" data-no-lock class="btn-secondary" @click="showContratar = false">Cancelar</button>
              <LoadingButton type="submit">Crear empleado</LoadingButton>
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
import { getApiErrorMessage } from '../../utils/apiError';

/** TIPO_DOCUMENTO_ADJUNTO.CODIGO = OTRO (seed Fase 1) */
const TIPO_ADJUNTO_OTRO = 6;

const { items, loading, page, perPage, total, lastPage, fetch: reload, setPage, setPerPage } =
  usePaginatedList('/reclutamiento/vacantes');

const etapas = ref([]);
const showVacante = ref(false);
const vacanteForm = ref({ TITULO: '', DESCRIPCION: '', REQUISITOS: '', ID_EMPRESA: null, ID_DEPARTAMENTO: null, ID_CARGO: null, PLAZAS: 1 });
const deptoParams = computed(() => vacanteForm.value.ID_EMPRESA ? { ID_EMPRESA: vacanteForm.value.ID_EMPRESA } : {});

const showDetalle = ref(false);
const detalle = ref(null);
const candidatos = ref([]);
const vacanteSelId = ref(null);

const showCandidato = ref(false);
const candidatoForm = ref({ NOMBRES: '', APELLIDOS: '', EMAIL: '', TELEFONO: '' });
const cvFile = ref(null);
const cvUploading = ref(false);
const candidatoError = ref('');

const showContratar = ref(false);
const contratarCandidatoId = ref(null);
const contratarNombre = ref('');
const contratarError = ref('');
const contratarForm = ref(emptyContratar());
const contratarDeptoParams = computed(() => contratarForm.value.ID_EMPRESA ? { ID_EMPRESA: contratarForm.value.ID_EMPRESA } : {});

function emptyContratar() {
  return {
    ID_EMPRESA: null, ID_DEPARTAMENTO: null, ID_CARGO: null, ID_TIPOCONTRATACION: null, ID_DISTRITO: null,
    CODIGOEMPLEADO: '', DUI: '', GENERO: '', FECHANACIMIENTO: '', FECHAINGRESO: new Date().toISOString().slice(0, 10),
    SALARIOMENSUAL: null, CORREOELECTRONICO: '', TELEFONOCELULAR: '',
  };
}
async function loadCatalogs() {
  const { data } = await api.get('/reclutamiento/catalogs');
  etapas.value = data.etapas;
}

function openVacante() {
  vacanteForm.value = { TITULO: '', DESCRIPCION: '', REQUISITOS: '', ID_EMPRESA: null, ID_DEPARTAMENTO: null, ID_CARGO: null, PLAZAS: 1 };
  showVacante.value = true;
}

async function saveVacante() {
  await api.post('/reclutamiento/vacantes', vacanteForm.value);
  showVacante.value = false;
  reload();
}

async function verVacante(v) {
  vacanteSelId.value = v.ID_VACANTE;
  const { data } = await api.get(`/reclutamiento/vacantes/${v.ID_VACANTE}`);
  detalle.value = data.vacante;
  candidatos.value = data.candidatos;
  showDetalle.value = true;
}

async function cerrar(v) {
  if (!await dialog.confirm({ title: 'Cerrar vacante', message: `¿Cerrar "${v.TITULO}"?`, confirmText: 'Cerrar' })) return;
  await api.post(`/reclutamiento/vacantes/${v.ID_VACANTE}/cerrar`);
  reload();
}

function openCandidato() {
  candidatoForm.value = { NOMBRES: '', APELLIDOS: '', EMAIL: '', TELEFONO: '' };
  cvFile.value = null;
  candidatoError.value = '';
  showCandidato.value = true;
}

function onCvFile(file) {
  cvFile.value = file;
}

async function uploadCv(idCandidato) {
  if (!cvFile.value) return null;
  cvUploading.value = true;
  try {
    const fd = new FormData();
    fd.append('archivo', cvFile.value);
    fd.append('ID_TIPO_DOCUMENTO_ADJUNTO', TIPO_ADJUNTO_OTRO);
    fd.append('ORIGEN', 'reclutamiento');
    fd.append('ID_ORIGEN', idCandidato);
    const { data } = await api.post('/adjuntos', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
    return data.ID_ADJUNTO;
  } finally {
    cvUploading.value = false;
  }
}

async function saveCandidato() {
  candidatoError.value = '';
  try {
    const { data } = await api.post('/reclutamiento/candidatos', {
      ...candidatoForm.value,
      ID_VACANTE: vacanteSelId.value,
    });
    if (cvFile.value) {
      const idAdjunto = await uploadCv(data.ID_CANDIDATO);
      if (idAdjunto) {
        await api.put(`/reclutamiento/candidatos/${data.ID_CANDIDATO}/cv`, { ID_ADJUNTO_CV: idAdjunto });
      }
    }
    showCandidato.value = false;
    cvFile.value = null;
    verVacante({ ID_VACANTE: vacanteSelId.value });
  } catch (err) {
    candidatoError.value = getApiErrorMessage(err);
  }
}

async function downloadCv(c) {
  const { data } = await api.get(`/adjuntos/${c.ID_ADJUNTO_CV}/download`, { responseType: 'blob' });
  const url = URL.createObjectURL(data);
  const link = document.createElement('a');
  link.href = url;
  link.download = c.CV_NOMBRE || 'cv.pdf';
  link.click();
  URL.revokeObjectURL(url);
}

async function avanzar(c, ev) {
  const idEtapa = ev.target.value;
  if (!idEtapa) return;
  const etapa = etapas.value.find(e => e.ID_ETAPA == idEtapa);
  await api.post(`/reclutamiento/candidatos/${c.ID_CANDIDATO}/etapa`, {
    ID_ETAPA: parseInt(idEtapa),
    ESTADO: etapa?.NOMBRE === 'Contratado' ? 'contratado' : undefined,
  });
  ev.target.value = '';
  verVacante({ ID_VACANTE: vacanteSelId.value });
}

async function programarEntrevista(c) {
  const values = await dialog.form({
    title: 'Programar entrevista',
    fields: [
      { name: 'fecha', type: 'text', label: 'Fecha y hora (YYYY-MM-DD HH:MM)', required: true },
      { name: 'tipo', type: 'select', label: 'Tipo', options: [{ value: 'presencial', label: 'Presencial' }, { value: 'virtual', label: 'Virtual' }] },
    ],
    confirmText: 'Programar',
  });
  if (!values) return;
  await api.post('/reclutamiento/entrevistas', {
    ID_CANDIDATO: c.ID_CANDIDATO,
    FECHA_HORA: values.fecha.replace(' ', 'T'),
    TIPO: values.tipo || 'presencial',
  });
}

async function openContratar(c) {
  if (c.ID_EMPLEADO) {
    await dialog.alert({
      title: 'Ya contratado',
      message: `Este candidato ya tiene empleado #${c.ID_EMPLEADO}. Puede continuar en Empleados o Contratos.`,
      variant: 'info',
    });
    return;
  }
  contratarError.value = '';
  contratarCandidatoId.value = c.ID_CANDIDATO;
  contratarNombre.value = `${c.NOMBRES} ${c.APELLIDOS || ''}`.trim();
  try {
    const { data } = await api.get(`/reclutamiento/candidatos/${c.ID_CANDIDATO}/contratar`);
    contratarForm.value = {
      ...emptyContratar(),
      ...data.defaults,
      CORREOELECTRONICO: data.defaults.CORREOELECTRONICO || '',
      TELEFONOCELULAR: data.defaults.TELEFONOCELULAR || '',
      CODIGOEMPLEADO: data.defaults.CODIGOEMPLEADO || '',
    };
  } catch (err) {
    contratarForm.value = emptyContratar();
    contratarError.value = getApiErrorMessage(err);
  }
  showContratar.value = true;
}

async function submitContratar() {
  contratarError.value = '';
  try {
    const { data } = await api.post(`/reclutamiento/candidatos/${contratarCandidatoId.value}/contratar`, contratarForm.value);
    showContratar.value = false;
    await dialog.alert({
      title: 'Empleado creado',
      message: `${data.message} Código ${data.CODIGOEMPLEADO} (ID ${data.ID_EMPLEADO}). Puede generar contrato en /contratos o revisar en /empleados.`,
      variant: 'success',
    });
    verVacante({ ID_VACANTE: vacanteSelId.value });
  } catch (err) {
    contratarError.value = getApiErrorMessage(err);
  }
}

onMounted(() => { loadCatalogs(); reload(); });
</script>
