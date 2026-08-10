<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Encuestas</h1>
          <p class="page-subtitle mt-1">Cree encuestas, asigne audiencia y consulte resultados.</p>
        </div>
        <div v-if="tab === 'admin'" class="page-header-actions">
          <button @click="openModal()" class="btn-primary">+ Nueva encuesta</button>
        </div>
      </div>

      <div class="flex border-b border-slate-200 dark:border-slate-700">
        <button
          v-for="t in tabs"
          :key="t.id"
          @click="tab = t.id"
          :class="tab === t.id ? 'border-indigo-500 text-indigo-600 font-bold' : 'border-transparent text-slate-500'"
          class="py-3 px-6 border-b-2 text-sm"
        >{{ t.label }}</button>
      </div>

      <!-- Mis encuestas -->
      <div v-if="tab === 'mis'" class="space-y-4">
        <div v-if="loadingMis" class="text-slate-500">Cargando...</div>
        <div v-else-if="misEncuestas.length === 0" class="text-slate-500 text-sm">No tiene encuestas pendientes.</div>
        <div v-for="item in misEncuestas" :key="item.encuesta.ID_ENCUESTA" class="border rounded-lg p-4 dark:border-slate-700">
          <h3 class="font-semibold">{{ item.encuesta.TITULO }}</h3>
          <p class="text-sm text-slate-500 mb-3">{{ item.encuesta.DESCRIPCION }}</p>
          <span v-if="item.encuesta.ANONIMA" class="text-xs bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded mr-2">Confidencial</span>
          <span v-if="item.respondida" class="text-xs bg-emerald-100 text-emerald-800 px-2 py-1 rounded">Respondida</span>
          <button v-else @click="abrirResponder(item)" class="btn-primary text-sm mt-2">Responder</button>
        </div>
      </div>

      <!-- Administración -->
      <div v-if="tab === 'admin'">
        <SkeletonTable v-if="loading" :cols="5" />
        <div v-else class="table-shell table-scroll">
          <table v-table-cards class="table-cards w-full text-sm">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-xs uppercase">
                <th class="px-4 py-3">Título</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3">Vigencia</th>
                <th class="px-4 py-3">Confidencial</th>
                <th class="px-4 py-3 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y dark:divide-slate-700">
              <tr v-for="e in items" :key="e.ID_ENCUESTA">
                <td class="px-4 py-3 font-medium">{{ e.TITULO }}</td>
                <td class="px-4 py-3"><span class="badge">{{ e.ESTADO }}</span></td>
                <td class="px-4 py-3 text-xs">{{ fmtDate(e.FECHA_INICIO) }} — {{ fmtDate(e.FECHA_FIN) }}</td>
                <td class="px-4 py-3">{{ e.ANONIMA ? 'Confidencial' : 'Identificada' }}</td>
                <td class="px-4 py-3 text-right space-x-1">
                  <IconActionButton v-if="e.ESTADO !== 'cerrada'" variant="edit" title="Editar" @click="editEncuesta(e)" />
                  <IconActionButton v-if="e.ESTADO === 'borrador'" variant="view" title="Publicar" @click="publicar(e)" />
                  <IconActionButton v-if="e.ESTADO === 'publicada'" variant="cancel" title="Cerrar encuesta" @click="cerrar(e)" />
                  <IconActionButton variant="view" title="Resultados" @click="verResultados(e)" />
                  <IconActionButton v-if="e.ESTADO !== 'publicada'" variant="delete" title="Eliminar" @click="eliminar(e)" />
                </td>
              </tr>
            </tbody>
          </table>
          <PaginationBar :page="page" :last-page="lastPage" :per-page="perPage" :total="total" :loading="loading"
            @update:page="setPage" @update:per-page="setPerPage" />
        </div>
      </div>

      <!-- Resultados -->
      <div v-if="tab === 'resultados' && resultadosData" class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 class="text-lg font-semibold">{{ encuestaResultados?.TITULO }}</h2>
          <button @click="exportExcel" class="btn-secondary text-sm">Exportar Excel</button>
        </div>
        <div class="flex flex-wrap gap-4 text-sm">
          <p class="text-slate-500">Total respuestas: <strong>{{ resultadosData.total_respuestas }}</strong></p>
          <p v-if="resultadosData.participacion" class="text-slate-500">
            Participación: <strong>{{ resultadosData.participacion.respondieron }}</strong> de
            <strong>{{ resultadosData.participacion.asignados }}</strong>
            ({{ resultadosData.participacion.porcentaje ?? 0 }}%)
          </p>
        </div>
        <p v-if="resultadosData.anonima" class="text-xs bg-indigo-50 dark:bg-indigo-900/20 text-indigo-800 dark:text-indigo-200 rounded px-3 py-2">
          Encuesta confidencial: los resultados son agregados y no muestran quién respondió cada pregunta.
        </p>
        <div v-for="(r, i) in resultadosData.preguntas" :key="i" class="border rounded-lg p-4 dark:border-slate-700">
          <h4 class="font-medium mb-2">{{ r.pregunta.ENUNCIADO }}</h4>
          <div v-if="r.distribucion?.length" class="space-y-1">
            <div v-for="d in r.distribucion" :key="d.opcion" class="flex justify-between text-sm">
              <span>{{ d.opcion }}</span>
              <span>{{ d.count }} ({{ d.porcentaje }}%)</span>
            </div>
          </div>
          <ul v-else class="text-sm text-slate-600 list-disc pl-5">
            <li v-for="(txt, j) in (r.respuestas || []).slice(0, 10)" :key="j">{{ txt }}</li>
            <li v-if="resultadosData.anonima && (r.respuestas?.length || 0) > 10" class="list-none text-xs text-slate-400 mt-1">
              … {{ r.respuestas.length - 10 }} respuestas más (sin identificar respondente)
            </li>
          </ul>
        </div>
      </div>

      <!-- Modal crear/editar -->
      <AppModalShell :open="showModal" @close="closeModal">
        <div class="modal-panel modal-panel-lg max-w-3xl mx-auto">
          <div class="modal-header"><h3 class="modal-title">{{ editingId ? 'Editar encuesta' : 'Nueva encuesta' }}</h3></div>
          <form v-submit-lock="save" class="modal-body space-y-4 max-h-[70vh] overflow-y-auto">
            <div class="grid grid-cols-2 gap-3">
              <div class="col-span-2">
                <label class="form-label">Título</label>
                <input v-model="form.TITULO" class="form-input" required />
              </div>
              <div class="col-span-2">
                <label class="form-label">Descripción</label>
                <textarea v-model="form.DESCRIPCION" class="form-input" rows="2" />
              </div>
              <div>
                <label class="form-label">Inicio</label>
                <input v-model="form.FECHA_INICIO" type="datetime-local" class="form-input" />
              </div>
              <div>
                <label class="form-label">Fin</label>
                <input v-model="form.FECHA_FIN" type="datetime-local" class="form-input" />
              </div>
              <div class="col-span-2">
                <label class="flex items-start gap-2 text-sm cursor-pointer">
                  <input v-model="form.ANONIMA" type="checkbox" class="mt-0.5" />
                  <span>
                    <span class="font-medium">Confidencial</span>
                    <span class="block text-xs text-slate-500 mt-0.5">
                      El reporte solo muestra totales y porcentajes; no se identifica quién respondió.
                      El sistema registra internamente que usted ya participó (evita duplicados y calcula participación).
                    </span>
                  </span>
                </label>
              </div>
              <label class="flex items-center gap-2 text-sm"><input v-model="form.ENVIAR_RECORDATORIOS" type="checkbox" /> Enviar recordatorios por email al publicar</label>
            </div>

            <div>
              <div class="flex justify-between items-center mb-2">
                <label class="form-label mb-0">Preguntas</label>
                <button type="button" class="text-sm text-indigo-600" @click="addPregunta">+ Agregar</button>
              </div>
              <div v-for="(p, i) in form.preguntas" :key="i" class="border rounded p-3 mb-2 dark:border-slate-600 space-y-2">
                <input v-model="p.ENUNCIADO" class="form-input" placeholder="Enunciado" required />
                <div class="grid grid-cols-2 gap-2">
                  <select v-model="p.TIPO" class="form-input">
                    <option value="texto">Texto</option>
                    <option value="opcion_multiple">Opción múltiple</option>
                    <option value="likert">Escala Likert</option>
                    <option value="si_no">Sí/No</option>
                    <option value="fecha">Fecha</option>
                  </select>
                  <input v-if="['opcion_multiple','likert'].includes(p.TIPO)" v-model="p.opcionesStr"
                    class="form-input" placeholder="Opciones separadas por coma" />
                </div>
              </div>
            </div>

            <div>
              <label class="form-label">Audiencia</label>
              <select v-model="asignacion.TIPO_AUDIENCIA" class="form-input">
                <option value="todos">Todos los empleados</option>
                <option value="empresa">Por empresa</option>
                <option value="departamento">Por departamento</option>
                <option value="cargo">Por cargo</option>
              </select>
              <AsyncSelect v-if="asignacion.TIPO_AUDIENCIA === 'empresa'" v-model="asignacion.ID_REFERENCIA" catalog="empresas" class="mt-2" />
              <AsyncSelect v-if="asignacion.TIPO_AUDIENCIA === 'departamento'" v-model="asignacion.ID_REFERENCIA" catalog="departamentos" class="mt-2" />
              <AsyncSelect v-if="asignacion.TIPO_AUDIENCIA === 'cargo'" v-model="asignacion.ID_REFERENCIA" catalog="cargos" class="mt-2" />
            </div>

            <p v-if="modalError" class="text-sm text-red-600">{{ modalError }}</p>
            <div class="modal-footer">
              <button type="button" data-no-lock class="btn-secondary" @click="closeModal">Cancelar</button>
              <LoadingButton type="submit">Guardar</LoadingButton>
            </div>
          </form>
        </div>
      </AppModalShell>

      <!-- Modal responder -->
      <AppModalShell :open="showResponder" @close="showResponder = false">
        <div class="modal-panel max-w-lg mx-auto">
          <div class="modal-header">
            <h3 class="modal-title">{{ encuestaResponder?.TITULO }}</h3>
            <p v-if="encuestaResponder?.ANONIMA" class="text-xs text-indigo-600 dark:text-indigo-300 mt-1">
              Sus respuestas son confidenciales. Solo se usarán datos agregados en el reporte.
            </p>
          </div>
          <form v-submit-lock="enviarRespuesta" class="modal-body space-y-4">
            <div v-for="p in preguntasResponder" :key="p.ID_PREGUNTA">
              <label class="form-label">{{ p.ENUNCIADO }}</label>
              <input v-if="p.TIPO === 'texto'" v-model="respuestas[p.ID_PREGUNTA]" class="form-input" />
              <input v-else-if="p.TIPO === 'fecha'" v-model="respuestas[p.ID_PREGUNTA]" type="date" class="form-input" />
              <select v-else-if="['opcion_multiple','likert','si_no'].includes(p.TIPO)" v-model="respuestas[p.ID_PREGUNTA]" class="form-input">
                <option value="">—</option>
                <option v-for="o in opcionesPregunta(p)" :key="o" :value="o">{{ o }}</option>
              </select>
            </div>
            <div class="modal-footer">
              <button type="button" data-no-lock class="btn-secondary" @click="showResponder = false">Cancelar</button>
              <LoadingButton type="submit">Enviar</LoadingButton>
            </div>
          </form>
        </div>
      </AppModalShell>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import * as XLSX from 'xlsx';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import AppModalShell from '../../components/AppModalShell.vue';
import PaginationBar from '../../components/PaginationBar.vue';
import { usePaginatedList } from '../../composables/usePaginatedList';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';
import { getApiErrorMessage } from '../../utils/apiError';

const tab = ref('admin');
const tabs = [
  { id: 'mis', label: 'Mis encuestas' },
  { id: 'admin', label: 'Administración' },
  { id: 'resultados', label: 'Resultados' },
];

const { items, loading, page, perPage, total, lastPage, fetch: reload, setPage, setPerPage } = usePaginatedList('/encuestas');

const misEncuestas = ref([]);
const loadingMis = ref(false);
const showModal = ref(false);
const editingId = ref(null);
const modalError = ref('');
const form = ref(emptyForm());
const asignacion = ref({ TIPO_AUDIENCIA: 'todos', ID_REFERENCIA: null });

const showResponder = ref(false);
const encuestaResponder = ref(null);
const preguntasResponder = ref([]);
const respuestas = ref({});

const encuestaResultados = ref(null);
const resultadosData = ref(null);

function emptyForm() {
  return { TITULO: '', DESCRIPCION: '', FECHA_INICIO: '', FECHA_FIN: '', ANONIMA: false, ENVIAR_RECORDATORIOS: false, preguntas: [] };
}

function fmtDate(d) { return d ? new Date(d).toLocaleDateString('es-SV') : '—'; }

function addPregunta() {
  form.value.preguntas.push({ ENUNCIADO: '', TIPO: 'texto', REQUERIDA: true, opcionesStr: '' });
}

function opcionesPregunta(p) {
  if (p.TIPO === 'si_no') return ['Sí', 'No'];
  return p.OPCIONES || [];
}

function buildPayload() {
  const preguntas = form.value.preguntas.map((p, i) => ({
    ORDEN: i + 1,
    ENUNCIADO: p.ENUNCIADO,
    TIPO: p.TIPO,
    REQUERIDA: true,
    OPCIONES: p.opcionesStr ? p.opcionesStr.split(',').map(s => s.trim()) : (p.TIPO === 'likert' ? ['1','2','3','4','5'] : p.TIPO === 'si_no' ? ['Sí','No'] : null),
  }));
  const asignaciones = [{ TIPO_AUDIENCIA: asignacion.value.TIPO_AUDIENCIA, ID_REFERENCIA: asignacion.value.ID_REFERENCIA }];
  return { ...form.value, preguntas, asignaciones };
}

function openModal() {
  editingId.value = null;
  form.value = emptyForm();
  addPregunta();
  asignacion.value = { TIPO_AUDIENCIA: 'todos', ID_REFERENCIA: null };
  modalError.value = '';
  showModal.value = true;
}

function closeModal() { showModal.value = false; }

async function save() {
  modalError.value = '';
  try {
    const payload = buildPayload();
    if (editingId.value) {
      await api.put(`/encuestas/${editingId.value}`, payload);
    } else {
      await api.post('/encuestas', payload);
    }
    closeModal();
    reload();
  } catch (err) {
    modalError.value = getApiErrorMessage(err);
  }
}

async function editEncuesta(e) {
  const { data } = await api.get(`/encuestas/${e.ID_ENCUESTA}`);
  editingId.value = e.ID_ENCUESTA;
  form.value = { ...data.encuesta, preguntas: (data.preguntas || []).map(p => ({ ...p, opcionesStr: (p.OPCIONES || []).join(', ') })) };
  if (form.value.preguntas.length === 0) addPregunta();
  const a = data.asignaciones?.[0];
  asignacion.value = a ? { TIPO_AUDIENCIA: a.TIPO_AUDIENCIA, ID_REFERENCIA: a.ID_REFERENCIA } : { TIPO_AUDIENCIA: 'todos', ID_REFERENCIA: null };
  showModal.value = true;
}

async function publicar(e) {
  if (!await dialog.confirm({ title: 'Publicar encuesta', message: `¿Publicar "${e.TITULO}"?`, confirmText: 'Publicar' })) return;
  await api.post(`/encuestas/${e.ID_ENCUESTA}/publicar`);
  reload();
}

async function cerrar(e) {
  if (!await dialog.confirm({ title: 'Cerrar encuesta', message: `¿Cerrar "${e.TITULO}"? Ya no aceptará más respuestas.`, confirmText: 'Cerrar' })) return;
  try {
    await api.post(`/encuestas/${e.ID_ENCUESTA}/cerrar`);
    reload();
  } catch (err) {
    await dialog.alert({ title: 'Error', message: getApiErrorMessage(err), variant: 'danger' });
  }
}

async function eliminar(e) {
  if (!await dialog.confirm({
    title: 'Eliminar encuesta',
    message: `¿Inactivar/eliminar "${e.TITULO}"?`,
    confirmText: 'Eliminar',
    variant: 'danger',
  })) return;
  try {
    await api.delete(`/encuestas/${e.ID_ENCUESTA}`);
    reload();
  } catch (err) {
    await dialog.alert({ title: 'Error', message: getApiErrorMessage(err), variant: 'danger' });
  }
}

async function verResultados(e) {
  encuestaResultados.value = e;
  const { data } = await api.get(`/encuestas/${e.ID_ENCUESTA}/resultados`);
  resultadosData.value = data;
  tab.value = 'resultados';
}

function exportExcel() {
  if (!resultadosData.value) return;
  const rows = [];
  for (const r of resultadosData.value.preguntas) {
    if (r.distribucion?.length) {
      for (const d of r.distribucion) {
        rows.push({ Pregunta: r.pregunta.ENUNCIADO, Opción: d.opcion, Cantidad: d.count, Porcentaje: d.porcentaje });
      }
    }
  }
  const ws = XLSX.utils.json_to_sheet(rows);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Resultados');
  XLSX.writeFile(wb, `encuesta_${encuestaResultados.value?.ID_ENCUESTA}.xlsx`);
}

async function loadMis() {
  loadingMis.value = true;
  try {
    const { data } = await api.get('/encuestas/mis-encuestas');
    misEncuestas.value = data;
  } finally {
    loadingMis.value = false;
  }
}

function abrirResponder(item) {
  encuestaResponder.value = item.encuesta;
  preguntasResponder.value = item.preguntas;
  respuestas.value = {};
  showResponder.value = true;
}

async function enviarRespuesta() {
  const detalles = preguntasResponder.value.map(p => ({
    ID_PREGUNTA: p.ID_PREGUNTA,
    VALOR_TEXTO: ['texto', 'fecha'].includes(p.TIPO) ? respuestas.value[p.ID_PREGUNTA] : null,
    VALOR_OPCION: ['opcion_multiple', 'likert', 'si_no'].includes(p.TIPO) ? respuestas.value[p.ID_PREGUNTA] : null,
  }));
  await api.post(`/encuestas/${encuestaResponder.value.ID_ENCUESTA}/responder`, { detalles });
  showResponder.value = false;
  loadMis();
}

watch(tab, (t) => { if (t === 'mis') loadMis(); });

onMounted(() => { reload(); loadMis(); });
</script>
