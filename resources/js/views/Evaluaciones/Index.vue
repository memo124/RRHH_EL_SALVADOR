<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Evaluación de desempeño</h1>
          <p class="page-subtitle mt-1">Periodos, metas, evaluadores y resultados agregados.</p>
        </div>
        <div class="page-header-actions">
          <button @click="openPeriodo()" class="btn-primary">+ Nuevo periodo</button>
        </div>
      </div>

      <div class="flex border-b border-slate-200 dark:border-slate-700">
        <button v-for="t in tabs" :key="t.id" @click="tab = t.id"
          :class="tab === t.id ? 'border-indigo-500 text-indigo-600 font-bold' : 'border-transparent text-slate-500'"
          class="py-3 px-6 border-b-2 text-sm">{{ t.label }}</button>
      </div>

      <SkeletonTable v-if="loading" :cols="5" />
      <div v-else class="table-shell table-scroll">
        <table v-table-cards class="table-cards w-full text-sm">
          <thead>
            <tr class="text-xs uppercase bg-slate-50 dark:bg-slate-700/50">
              <th class="px-4 py-3">Periodo</th>
              <th class="px-4 py-3">Año</th>
              <th class="px-4 py-3">Vigencia</th>
              <th class="px-4 py-3">Estado</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y dark:divide-slate-700">
            <tr v-for="p in items" :key="p.ID_PERIODO">
              <td class="px-4 py-3 font-medium">{{ p.NOMBRE }}</td>
              <td class="px-4 py-3">{{ p.ANIO }}</td>
              <td class="px-4 py-3 text-xs">{{ fmtDate(p.FECHA_INICIO) }} — {{ fmtDate(p.FECHA_FIN) }}</td>
              <td class="px-4 py-3"><span class="badge">{{ p.ESTADO }}</span></td>
              <td class="px-4 py-3 text-right space-x-1">
                <IconActionButton variant="view" title="Ver resultados" @click="verPeriodo(p)" />
                <IconActionButton v-if="p.ESTADO === 'borrador'" variant="edit" title="Activar" @click="activar(p)" />
                <IconActionButton v-if="p.ESTADO === 'activo'" variant="permissions" title="Asignar" @click="asignar(p)" />
                <IconActionButton v-if="p.ESTADO === 'activo'" variant="cancel" title="Cerrar periodo" @click="cerrarPeriodo(p)" />
              </td>
            </tr>
          </tbody>
        </table>
        <PaginationBar :page="page" :last-page="lastPage" :per-page="perPage" :total="total" :loading="loading"
          @update:page="setPage" @update:per-page="setPerPage" />
      </div>

      <!-- Resultados periodo -->
      <div v-if="tab === 'resultados' && resultados" class="space-y-4 border rounded-lg p-4 dark:border-slate-700">
        <h3 class="font-semibold">{{ periodoSel?.NOMBRE }}</h3>
        <div class="grid grid-cols-3 gap-4 text-sm">
          <div class="bg-slate-50 dark:bg-slate-700/50 rounded p-3">Total: <strong>{{ resultados.total }}</strong></div>
          <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded p-3">Completadas: <strong>{{ resultados.completadas }}</strong></div>
          <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded p-3">Promedio: <strong>{{ resultados.promedio_puntuacion ?? '—' }}</strong></div>
        </div>
        <table class="w-full text-sm">
          <thead><tr class="text-xs uppercase text-slate-500"><th class="py-2 text-left">Empleado</th><th>Evaluador</th><th>Puntuación</th><th>Estado</th><th></th></tr></thead>
          <tbody>
            <tr v-for="e in resultados.evaluaciones" :key="e.ID_EVALUACION" class="border-b dark:border-slate-700">
              <td class="py-2">{{ e.EMPLEADO_NOMBRE }}</td>
              <td class="py-2 text-center text-xs">{{ e.EVALUADOR_NOMBRE }}</td>
              <td class="py-2 text-center">{{ e.PUNTUACION_GLOBAL ?? '—' }}</td>
              <td class="py-2 text-center"><span class="badge">{{ e.ESTADO }}</span></td>
              <td class="py-2 text-right">
                <button v-if="e.ESTADO !== 'completada'" @click="evaluar(e)" class="text-xs text-indigo-600">Evaluar</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AppModalShell :open="showPeriodo" @close="showPeriodo = false">
        <div class="modal-panel max-w-md mx-auto">
          <div class="modal-header"><h3 class="modal-title">Nuevo periodo</h3></div>
          <form v-submit-lock="savePeriodo" class="modal-body space-y-4">
            <input v-model="periodoForm.NOMBRE" class="form-input" placeholder="Ej. Evaluación 2026" required />
            <input v-model.number="periodoForm.ANIO" type="number" class="form-input" placeholder="Año" required />
            <div class="grid grid-cols-2 gap-3">
              <input v-model="periodoForm.FECHA_INICIO" type="date" class="form-input" />
              <input v-model="periodoForm.FECHA_FIN" type="date" class="form-input" />
            </div>
            <div class="modal-footer">
              <button type="button" data-no-lock class="btn-secondary" @click="showPeriodo = false">Cancelar</button>
              <LoadingButton type="submit">Crear</LoadingButton>
            </div>
          </form>
        </div>
      </AppModalShell>

      <AppModalShell :open="showEvaluar" @close="showEvaluar = false">
        <div class="modal-panel modal-panel-lg max-w-2xl mx-auto">
          <div class="modal-header">
            <h3 class="modal-title">Evaluar: {{ evalSel?.EMPLEADO_NOMBRE }}</h3>
          </div>
          <form v-submit-lock="completarEval" class="modal-body space-y-4 max-h-[65vh] overflow-y-auto">
            <div v-for="(m, i) in metasForm" :key="i" class="border rounded p-3 dark:border-slate-600 grid grid-cols-2 gap-2">
              <input v-model="m.DESCRIPCION" class="form-input col-span-2" placeholder="Meta / KPI" />
              <input v-model.number="m.VALOR_OBJETIVO" type="number" class="form-input" placeholder="Objetivo" />
              <input v-model.number="m.VALOR_ALCANZADO" type="number" class="form-input" placeholder="Alcanzado" />
              <input v-model.number="m.PESO" type="number" step="0.1" class="form-input col-span-2" placeholder="Peso" />
            </div>
            <button type="button" class="text-sm text-indigo-600" @click="metasForm.push({ DESCRIPCION: '', PESO: 1 })">+ Meta</button>
            <textarea v-model="comentarios" class="form-input" rows="3" placeholder="Comentarios del evaluador" />
            <div class="modal-footer">
              <button type="button" data-no-lock class="btn-secondary" @click="showEvaluar = false">Cancelar</button>
              <LoadingButton type="submit">Completar evaluación</LoadingButton>
            </div>
          </form>
        </div>
      </AppModalShell>

      <AppModalShell :open="showAsignar" @close="showAsignar = false">
        <div class="modal-panel max-w-md mx-auto">
          <div class="modal-header"><h3 class="modal-title">Asignar evaluación</h3></div>
          <div class="modal-body space-y-4">
            <AsyncSelect v-model="asignEmpleado" endpoint="/empleados/select" placeholder="Empleado a evaluar" />
            <AsyncSelect v-model="asignEvaluador" endpoint="/empleados/select" placeholder="Evaluador (jefe)" />
            <button @click="confirmarAsignar" class="btn-primary w-full">Asignar</button>
            <ul class="text-xs space-y-1">
              <li v-for="(a, i) in asignaciones" :key="i">Empleado #{{ a.ID_EMPLEADO }} → Evaluador #{{ a.ID_EVALUADOR }}</li>
            </ul>
            <button v-if="asignaciones.length" @click="enviarAsignaciones" class="btn-secondary w-full">Confirmar {{ asignaciones.length }} asignación(es)</button>
          </div>
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
import { usePaginatedList } from '../../composables/usePaginatedList';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';

const tab = ref('periodos');
const tabs = [{ id: 'periodos', label: 'Periodos' }, { id: 'resultados', label: 'Resultados' }];

const { items, loading, page, perPage, total, lastPage, fetch: reload, setPage, setPerPage } =
  usePaginatedList('/evaluaciones/periodos');

const showPeriodo = ref(false);
const periodoForm = ref({ NOMBRE: '', ANIO: new Date().getFullYear(), FECHA_INICIO: '', FECHA_FIN: '' });

const periodoSel = ref(null);
const resultados = ref(null);

const showAsignar = ref(false);
const asignPeriodoId = ref(null);
const asignEmpleado = ref(null);
const asignEvaluador = ref(null);
const asignaciones = ref([]);

const showEvaluar = ref(false);
const evalSel = ref(null);
const metasForm = ref([{ DESCRIPCION: '', PESO: 1, VALOR_OBJETIVO: null, VALOR_ALCANZADO: null }]);
const comentarios = ref('');

function fmtDate(d) { return d ? new Date(d).toLocaleDateString('es-SV') : '—'; }

function openPeriodo() {
  periodoForm.value = { NOMBRE: '', ANIO: new Date().getFullYear(), FECHA_INICIO: '', FECHA_FIN: '' };
  showPeriodo.value = true;
}

async function savePeriodo() {
  await api.post('/evaluaciones/periodos', periodoForm.value);
  showPeriodo.value = false;
  reload();
}

async function activar(p) {
  if (!await dialog.confirm({ title: 'Activar periodo', message: `¿Activar "${p.NOMBRE}"?`, confirmText: 'Activar' })) return;
  await api.post(`/evaluaciones/periodos/${p.ID_PERIODO}/activar`);
  reload();
}

async function cerrarPeriodo(p) {
  if (!await dialog.confirm({
    title: 'Cerrar periodo',
    message: `¿Cerrar "${p.NOMBRE}"? No se podrán asignar más evaluaciones.`,
    confirmText: 'Cerrar',
  })) return;
  await api.post(`/evaluaciones/periodos/${p.ID_PERIODO}/cerrar`);
  reload();
}

async function verPeriodo(p) {
  periodoSel.value = p;
  const { data } = await api.get(`/evaluaciones/periodos/${p.ID_PERIODO}`);
  resultados.value = data.resultados;
  tab.value = 'resultados';
}

function asignar(p) {
  asignPeriodoId.value = p.ID_PERIODO;
  asignaciones.value = [];
  asignEmpleado.value = null;
  asignEvaluador.value = null;
  showAsignar.value = true;
}

function confirmarAsignar() {
  if (!asignEmpleado.value || !asignEvaluador.value) return;
  asignaciones.value.push({ ID_EMPLEADO: asignEmpleado.value, ID_EVALUADOR: asignEvaluador.value });
  asignEmpleado.value = null;
  asignEvaluador.value = null;
}

async function enviarAsignaciones() {
  await api.post(`/evaluaciones/periodos/${asignPeriodoId.value}/asignar`, { asignaciones: asignaciones.value });
  showAsignar.value = false;
  reload();
}

async function evaluar(e) {
  evalSel.value = e;
  const { data } = await api.get(`/evaluaciones/${e.ID_EVALUACION}`);
  metasForm.value = data.metas.length
    ? data.metas.map(m => ({ DESCRIPCION: m.DESCRIPCION, PESO: m.PESO, VALOR_OBJETIVO: m.VALOR_OBJETIVO, VALOR_ALCANZADO: m.VALOR_ALCANZADO }))
    : [{ DESCRIPCION: '', PESO: 1, VALOR_OBJETIVO: null, VALOR_ALCANZADO: null }];
  comentarios.value = data.evaluacion.COMENTARIOS_EVALUADOR || '';
  showEvaluar.value = true;
}

async function completarEval() {
  await api.put(`/evaluaciones/${evalSel.value.ID_EVALUACION}/metas`, { metas: metasForm.value });
  await api.post(`/evaluaciones/${evalSel.value.ID_EVALUACION}/completar`, { COMENTARIOS_EVALUADOR: comentarios.value });
  showEvaluar.value = false;
  if (periodoSel.value) verPeriodo(periodoSel.value);
}

onMounted(reload);
</script>
