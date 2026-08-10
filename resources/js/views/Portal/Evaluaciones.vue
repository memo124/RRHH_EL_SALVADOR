<template>
  <PortalLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Evaluación de desempeño</h1>
          <p class="page-subtitle mt-1">Historial de sus evaluaciones de desempeño.</p>
        </div>
      </div>

      <div v-if="loading" class="space-y-3">
        <div v-for="i in 3" :key="i" class="card-panel animate-pulse h-20"></div>
      </div>

      <div v-else-if="evaluaciones.length === 0" class="card-panel text-center text-sm text-slate-500 dark:text-slate-400 py-10">
        Aún no tiene evaluaciones de desempeño registradas.
      </div>

      <div v-else class="space-y-3">
        <button
          v-for="e in evaluaciones"
          :key="e.ID_EVALUACION"
          type="button"
          class="card-panel w-full text-left flex items-center justify-between gap-3 hover:border-indigo-300 dark:hover:border-indigo-600 transition-colors"
          @click="verDetalle(e)"
        >
          <div>
            <p class="font-semibold text-slate-800 dark:text-slate-100">{{ e.PERIODO_NOMBRE }} ({{ e.ANIO }})</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Evaluador: {{ e.EVALUADOR_NOMBRE }}</p>
          </div>
          <div class="text-right shrink-0">
            <span class="badge">{{ e.ESTADO }}</span>
            <p v-if="e.PUNTUACION_GLOBAL != null" class="text-lg font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ e.PUNTUACION_GLOBAL }}</p>
          </div>
        </button>
      </div>

      <AppModalShell :open="showDetalle" @close="showDetalle = false">
        <div class="modal-panel modal-panel-lg max-w-2xl mx-auto">
          <div class="modal-header">
            <h3 class="modal-title">{{ detalle?.evaluacion?.PERIODO_NOMBRE }}</h3>
          </div>
          <div class="modal-body space-y-4 max-h-[65vh] overflow-y-auto">
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div class="bg-slate-50 dark:bg-slate-700/50 rounded p-3">
                <p class="text-xs text-slate-500 dark:text-slate-400">Evaluador</p>
                <p class="font-medium">{{ detalle?.evaluacion?.EVALUADOR_NOMBRE }}</p>
              </div>
              <div class="bg-slate-50 dark:bg-slate-700/50 rounded p-3">
                <p class="text-xs text-slate-500 dark:text-slate-400">Puntuación global</p>
                <p class="font-bold text-indigo-600 dark:text-indigo-400">{{ detalle?.evaluacion?.PUNTUACION_GLOBAL ?? 'Pendiente' }}</p>
              </div>
            </div>

            <div v-if="detalle?.metas?.length">
              <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide mb-2">Metas / KPIs</h4>
              <div class="space-y-2">
                <div v-for="m in detalle.metas" :key="m.ID_META" class="border rounded-lg p-3 dark:border-slate-700 text-sm">
                  <p class="font-medium">{{ m.DESCRIPCION }}</p>
                  <div class="grid grid-cols-3 gap-2 mt-2 text-xs text-slate-500 dark:text-slate-400">
                    <span>Objetivo: <strong class="text-slate-700 dark:text-slate-200">{{ m.VALOR_OBJETIVO ?? '—' }}</strong></span>
                    <span>Alcanzado: <strong class="text-slate-700 dark:text-slate-200">{{ m.VALOR_ALCANZADO ?? '—' }}</strong></span>
                    <span>Cumplimiento: <strong class="text-slate-700 dark:text-slate-200">{{ m.PORCENTAJE_CUMPLIMIENTO != null ? m.PORCENTAJE_CUMPLIMIENTO + '%' : '—' }}</strong></span>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="detalle?.evaluacion?.COMENTARIOS_EVALUADOR">
              <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide mb-2">Comentarios del evaluador</h4>
              <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line">{{ detalle.evaluacion.COMENTARIOS_EVALUADOR }}</p>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" @click="showDetalle = false">Cerrar</button>
          </div>
        </div>
      </AppModalShell>
    </div>
  </PortalLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import PortalLayout from './PortalLayout.vue';
import AppModalShell from '../../components/AppModalShell.vue';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';
import { getApiErrorMessage } from '../../utils/apiError';

const evaluaciones = ref([]);
const loading = ref(true);
const showDetalle = ref(false);
const detalle = ref(null);

async function loadEvaluaciones() {
  loading.value = true;
  try {
    const { data } = await api.get('/portal/evaluaciones');
    evaluaciones.value = data;
  } finally {
    loading.value = false;
  }
}

async function verDetalle(e) {
  try {
    const { data } = await api.get(`/portal/evaluaciones/${e.ID_EVALUACION}`);
    detalle.value = data;
    showDetalle.value = true;
  } catch (err) {
    await dialog.alert({ title: 'Error', message: getApiErrorMessage(err), variant: 'danger' });
  }
}

onMounted(loadEvaluaciones);
</script>
