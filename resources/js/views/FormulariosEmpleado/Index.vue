<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Formularios — Actualización de datos</h1>
          <p class="page-subtitle mt-1">Plantillas, campañas con link único y bandeja de aprobación RRHH.</p>
        </div>
      </div>

      <div class="flex border-b border-slate-200 dark:border-slate-700">
        <button v-for="t in tabs" :key="t.id" @click="tab = t.id"
          :class="tab === t.id ? 'border-indigo-500 text-indigo-600 font-bold' : 'border-transparent text-slate-500'"
          class="py-3 px-6 border-b-2 text-sm">{{ t.label }}</button>
      </div>

      <!-- Plantillas -->
      <div v-if="tab === 'plantillas'" class="space-y-4">
        <div class="flex gap-2">
          <button @click="openPlantillaModal()" class="btn-primary">+ Nueva plantilla</button>
          <button @click="seedDefault" class="btn-secondary">Cargar plantilla estándar</button>
        </div>
        <SkeletonTable v-if="loadingPlantillas" :cols="3" />
        <div v-else class="table-shell table-scroll">
          <table v-table-cards class="table-cards w-full text-sm">
            <thead><tr class="text-xs uppercase bg-slate-50 dark:bg-slate-700/50">
              <th class="px-4 py-3">Nombre</th><th class="px-4 py-3">Descripción</th><th class="px-4 py-3 text-right">Acciones</th>
            </tr></thead>
            <tbody class="divide-y dark:divide-slate-700">
              <tr v-for="p in plantillas" :key="p.ID_PLANTILLA">
                <td class="px-4 py-3 font-medium">{{ p.NOMBRE }}</td>
                <td class="px-4 py-3 text-slate-500">{{ p.DESCRIPCION }}</td>
                <td class="px-4 py-3 text-right">
                  <IconActionButton icon="eye" title="Ver campos" @click="verPlantilla(p)" />
                </td>
              </tr>
            </tbody>
          </table>
          <PaginationBar :page="pageP" :last-page="lastPageP" :per-page="perPageP" :total="totalP" :loading="loadingPlantillas"
            @update:page="setPageP" @update:per-page="setPerPageP" />
        </div>
      </div>

      <!-- Campañas -->
      <div v-if="tab === 'campanas'" class="space-y-4">
        <button @click="openCampanaModal()" class="btn-primary">+ Nueva campaña</button>
        <SkeletonTable v-if="loadingCampanas" :cols="5" />
        <div v-else class="table-shell table-scroll">
          <table v-table-cards class="table-cards w-full text-sm">
            <thead><tr class="text-xs uppercase bg-slate-50 dark:bg-slate-700/50">
              <th class="px-4 py-3">Campaña</th><th class="px-4 py-3">Plantilla</th><th class="px-4 py-3">Estado</th>
              <th class="px-4 py-3">Vigencia</th><th class="px-4 py-3 text-right">Acciones</th>
            </tr></thead>
            <tbody class="divide-y dark:divide-slate-700">
              <tr v-for="c in campanas" :key="c.ID_CAMPANA">
                <td class="px-4 py-3 font-medium">{{ c.NOMBRE }}</td>
                <td class="px-4 py-3">{{ c.PLANTILLA_NOMBRE }}</td>
                <td class="px-4 py-3"><span class="badge">{{ c.ESTADO }}</span></td>
                <td class="px-4 py-3 text-xs">{{ fmtDate(c.FECHA_INICIO) }} — {{ fmtDate(c.FECHA_FIN) }}</td>
                <td class="px-4 py-3 text-right space-x-1">
                  <IconActionButton v-if="c.ESTADO === 'borrador'" icon="check" title="Activar" @click="activarCampana(c)" />
                  <IconActionButton icon="link" title="Invitaciones" @click="openInvitaciones(c)" />
                </td>
              </tr>
            </tbody>
          </table>
          <PaginationBar :page="pageC" :last-page="lastPageC" :per-page="perPageC" :total="totalC" :loading="loadingCampanas"
            @update:page="setPageC" @update:per-page="setPerPageC" />
        </div>
      </div>

      <!-- Bandeja -->
      <div v-if="tab === 'bandeja'">
        <SkeletonTable v-if="loadingBandeja" :cols="5" />
        <div v-else class="table-shell table-scroll">
          <table v-table-cards class="table-cards w-full text-sm">
            <thead><tr class="text-xs uppercase bg-slate-50 dark:bg-slate-700/50">
              <th class="px-4 py-3">Empleado</th><th class="px-4 py-3">Campaña</th><th class="px-4 py-3">Enviado</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3 text-right">Acciones</th>
            </tr></thead>
            <tbody class="divide-y dark:divide-slate-700">
              <tr v-for="r in bandeja" :key="r.ID_RESPUESTA">
                <td class="px-4 py-3">{{ r.EMPLEADO_NOMBRE }} <span class="text-xs text-slate-500">{{ r.CODIGOEMPLEADO }}</span></td>
                <td class="px-4 py-3">{{ r.CAMPANA_NOMBRE }}</td>
                <td class="px-4 py-3 text-xs">{{ fmtDate(r.FECHA_ENVIO) }}</td>
                <td class="px-4 py-3"><span class="badge">{{ r.ESTADO }}</span></td>
                <td class="px-4 py-3 text-right space-x-1">
                  <IconActionButton icon="eye" title="Revisar" @click="revisar(r)" />
                </td>
              </tr>
            </tbody>
          </table>
          <PaginationBar :page="pageB" :last-page="lastPageB" :per-page="perPageB" :total="totalB" :loading="loadingBandeja"
            @update:page="setPageB" @update:per-page="setPerPageB" />
        </div>
      </div>

      <!-- Modal plantilla -->
      <AppModalShell :open="showPlantilla" @close="showPlantilla = false">
        <div class="modal-panel modal-panel-lg max-w-3xl mx-auto">
          <div class="modal-header"><h3 class="modal-title">Nueva plantilla</h3></div>
          <form v-submit-lock="savePlantilla" class="modal-body space-y-4 max-h-[70vh] overflow-y-auto">
            <input v-model="plantillaForm.NOMBRE" class="form-input" placeholder="Nombre" required />
            <textarea v-model="plantillaForm.DESCRIPCION" class="form-input" rows="2" placeholder="Descripción" />
            <div v-for="(c, i) in plantillaForm.campos" :key="i" class="border rounded p-3 dark:border-slate-600 grid grid-cols-2 gap-2">
              <input v-model="c.ETIQUETA" class="form-input col-span-2" placeholder="Etiqueta" />
              <select v-model="c.TIPO_CAMPO" class="form-input">
                <option value="texto">Texto</option><option value="textarea">Texto largo</option>
                <option value="fecha">Fecha</option><option value="archivo">Archivo</option>
              </select>
              <select v-model="c.MAPEO_TABLA" class="form-input">
                <option value="EMPLEADO">Empleado</option>
                <option value="EMPLEADO_EDUCACION">Educación</option>
                <option value="EMPLEADO_CERTIFICACION">Certificación</option>
                <option value="EMPLEADO_DEPENDIENTE">Dependiente</option>
                <option value="CUSTOM">Custom</option>
              </select>
              <input v-model="c.MAPEO_COLUMNA" class="form-input col-span-2" placeholder="Columna (EMPLEADO)" />
            </div>
            <button type="button" class="text-sm text-indigo-600" @click="plantillaForm.campos.push(emptyCampo())">+ Campo</button>
            <div class="modal-footer">
              <button type="button" data-no-lock class="btn-secondary" @click="showPlantilla = false">Cancelar</button>
              <LoadingButton type="submit">Guardar</LoadingButton>
            </div>
          </form>
        </div>
      </AppModalShell>

      <!-- Modal campaña -->
      <AppModalShell :open="showCampana" @close="showCampana = false">
        <div class="modal-panel max-w-lg mx-auto">
          <div class="modal-header"><h3 class="modal-title">Nueva campaña</h3></div>
          <form v-submit-lock="saveCampana" class="modal-body space-y-4">
            <input v-model="campanaForm.NOMBRE" class="form-input" placeholder="Ej. Actualización fin de año 2026" required />
            <AsyncSelect v-model="campanaForm.ID_PLANTILLA" endpoint="/formularios/plantillas/select" placeholder="Plantilla" />
            <textarea v-model="campanaForm.DESCRIPCION" class="form-input" rows="2" />
            <div class="grid grid-cols-2 gap-3">
              <input v-model="campanaForm.FECHA_INICIO" type="datetime-local" class="form-input" />
              <input v-model="campanaForm.FECHA_FIN" type="datetime-local" class="form-input" />
            </div>
            <div class="modal-footer">
              <button type="button" data-no-lock class="btn-secondary" @click="showCampana = false">Cancelar</button>
              <LoadingButton type="submit">Crear</LoadingButton>
            </div>
          </form>
        </div>
      </AppModalShell>

      <!-- Modal invitaciones -->
      <AppModalShell :open="showInvitaciones" @close="showInvitaciones = false">
        <div class="modal-panel modal-panel-lg max-w-2xl mx-auto">
          <div class="modal-header"><h3 class="modal-title">Invitaciones — {{ campanaSel?.NOMBRE }}</h3></div>
          <div class="modal-body space-y-4">
            <AsyncSelect v-model="empleadosInvitar" endpoint="/empleados/select" placeholder="Agregar empleado" @change="addEmpleadoInv" />
            <div class="flex flex-wrap gap-2">
              <span v-for="id in idsInvitar" :key="id" class="text-xs bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded">#{{ id }}</span>
            </div>
            <button @click="generarLinks" class="btn-primary">Generar links</button>
            <div v-if="linksGenerados.length" class="space-y-2 max-h-60 overflow-y-auto">
              <div v-for="l in linksGenerados" :key="l.ID_INVITACION" class="text-xs border rounded p-2 dark:border-slate-600">
                <div>Empleado #{{ l.ID_EMPLEADO }}</div>
                <a :href="l.URL" target="_blank" class="text-indigo-600 break-all">{{ l.URL }}</a>
              </div>
            </div>
          </div>
        </div>
      </AppModalShell>

      <!-- Modal revisión -->
      <AppModalShell :open="showRevision" @close="showRevision = false">
        <div class="modal-panel max-w-lg mx-auto">
          <div class="modal-header"><h3 class="modal-title">Revisar cambios</h3></div>
          <div class="modal-body space-y-3">
            <div v-for="c in revisionCampos" :key="c.ID_RESPUESTA_CAMPO" class="border-b pb-2 dark:border-slate-600">
              <div class="font-medium text-sm">{{ c.ETIQUETA }}</div>
              <div class="text-sm text-slate-600">{{ c.VALOR_TEXTO || c.NOMBRE_ARCHIVO || c.VALOR_JSON }}</div>
            </div>
            <div class="modal-footer">
              <button @click="rechazar" class="btn-secondary text-red-600">Rechazar</button>
              <button @click="aprobar" class="btn-primary">Aprobar y aplicar</button>
            </div>
          </div>
        </div>
      </AppModalShell>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import AppModalShell from '../../components/AppModalShell.vue';
import PaginationBar from '../../components/PaginationBar.vue';
import { usePaginatedList } from '../../composables/usePaginatedList';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';
import { getApiErrorMessage } from '../../utils/apiError';

const tab = ref('campanas');
const tabs = [
  { id: 'plantillas', label: 'Plantillas' },
  { id: 'campanas', label: 'Campañas' },
  { id: 'bandeja', label: 'Bandeja aprobación' },
];

const {
  items: plantillas, loading: loadingPlantillas, page: pageP, perPage: perPageP, total: totalP, lastPage: lastPageP,
  fetch: loadPlantillas, setPage: setPageP, setPerPage: setPerPageP,
} = usePaginatedList('/formularios/plantillas');

const {
  items: campanas, loading: loadingCampanas, page: pageC, perPage: perPageC, total: totalC, lastPage: lastPageC,
  fetch: loadCampanas, setPage: setPageC, setPerPage: setPerPageC,
} = usePaginatedList('/formularios/campanas');

const {
  items: bandeja, loading: loadingBandeja, page: pageB, perPage: perPageB, total: totalB, lastPage: lastPageB,
  fetch: loadBandeja, setPage: setPageB, setPerPage: setPerPageB,
} = usePaginatedList('/formularios/respuestas/pendientes');

const showPlantilla = ref(false);
const plantillaForm = ref({ NOMBRE: '', DESCRIPCION: '', campos: [] });
const showCampana = ref(false);
const campanaForm = ref({ NOMBRE: '', ID_PLANTILLA: null, DESCRIPCION: '', FECHA_INICIO: '', FECHA_FIN: '' });
const showInvitaciones = ref(false);
const campanaSel = ref(null);
const idsInvitar = ref([]);
const empleadosInvitar = ref(null);
const linksGenerados = ref([]);
const showRevision = ref(false);
const revisionId = ref(null);
const revisionCampos = ref([]);

function fmtDate(d) { return d ? new Date(d).toLocaleDateString('es-SV') : '—'; }
function emptyCampo() { return { ETIQUETA: '', TIPO_CAMPO: 'texto', MAPEO_TABLA: 'EMPLEADO', MAPEO_COLUMNA: '', REQUERIDO: false }; }

function openPlantillaModal() {
  plantillaForm.value = { NOMBRE: '', DESCRIPCION: '', campos: [emptyCampo()] };
  showPlantilla.value = true;
}

async function savePlantilla() {
  await api.post('/formularios/plantillas', plantillaForm.value);
  showPlantilla.value = false;
  loadPlantillas();
}

async function seedDefault() {
  await api.post('/formularios/plantillas/seed-default');
  loadPlantillas();
}

async function verPlantilla(p) {
  const { data } = await api.get(`/formularios/plantillas/${p.ID_PLANTILLA}`);
  await dialog.alert({ title: p.NOMBRE, message: `${data.campos.length} campos configurados.`, variant: 'info' });
}

function openCampanaModal() {
  campanaForm.value = { NOMBRE: '', ID_PLANTILLA: null, DESCRIPCION: '', FECHA_INICIO: '', FECHA_FIN: '' };
  showCampana.value = true;
}

async function saveCampana() {
  await api.post('/formularios/campanas', campanaForm.value);
  showCampana.value = false;
  loadCampanas();
}

async function activarCampana(c) {
  if (!await dialog.confirm({ title: 'Activar campaña', message: `¿Activar "${c.NOMBRE}"? Se creará evento en calendario.`, confirmText: 'Activar' })) return;
  await api.post(`/formularios/campanas/${c.ID_CAMPANA}/activar`);
  loadCampanas();
}

function openInvitaciones(c) {
  campanaSel.value = c;
  idsInvitar.value = [];
  linksGenerados.value = [];
  showInvitaciones.value = true;
}

function addEmpleadoInv(id) {
  if (id && !idsInvitar.value.includes(id)) idsInvitar.value.push(id);
  empleadosInvitar.value = null;
}

async function generarLinks() {
  const { data } = await api.post(`/formularios/campanas/${campanaSel.value.ID_CAMPANA}/invitaciones`, { ID_EMPLEADOS: idsInvitar.value });
  linksGenerados.value = data.invitaciones;
}

async function revisar(r) {
  const { data } = await api.get(`/formularios/respuestas/${r.ID_RESPUESTA}`);
  revisionId.value = r.ID_RESPUESTA;
  revisionCampos.value = data.campos;
  showRevision.value = true;
}

async function aprobar() {
  await api.post(`/formularios/respuestas/${revisionId.value}/aprobar`);
  showRevision.value = false;
  loadBandeja();
}

async function rechazar() {
  const values = await dialog.form({
    title: 'Rechazar cambios',
    fields: [{ name: 'motivo', type: 'textarea', label: 'Motivo', required: true, rows: 3 }],
    variant: 'danger',
    confirmText: 'Rechazar',
  });
  if (!values) return;
  await api.post(`/formularios/respuestas/${revisionId.value}/rechazar`, { MOTIVO_RECHAZO: values.motivo });
  showRevision.value = false;
  loadBandeja();
}

watch(tab, (t) => {
  if (t === 'plantillas') loadPlantillas();
  if (t === 'campanas') loadCampanas();
  if (t === 'bandeja') loadBandeja();
});

onMounted(() => loadCampanas());
</script>
