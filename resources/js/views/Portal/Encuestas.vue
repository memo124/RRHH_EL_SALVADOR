<template>
  <PortalLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Encuestas</h1>
          <p class="page-subtitle mt-1">Encuestas asignadas a usted. Sus respuestas se registran de forma segura.</p>
        </div>
      </div>

      <div v-if="loading" class="space-y-3">
        <div v-for="i in 3" :key="i" class="card-panel animate-pulse h-24"></div>
      </div>

      <div v-else-if="encuestas.length === 0" class="card-panel text-center text-sm text-slate-500 dark:text-slate-400 py-10">
        No tiene encuestas pendientes por el momento.
      </div>

      <div v-else class="space-y-4">
        <div v-for="item in encuestas" :key="item.encuesta.ID_ENCUESTA" class="card-panel">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="font-semibold text-slate-800 dark:text-slate-100">{{ item.encuesta.TITULO }}</h3>
              <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ item.encuesta.DESCRIPCION }}</p>
            </div>
            <span v-if="item.encuesta.ANONIMA" class="text-xs bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200 px-2 py-0.5 rounded shrink-0">Confidencial</span>
          </div>
          <div class="mt-3">
            <span v-if="item.respondida" class="text-xs bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-200 px-2 py-1 rounded font-semibold">Respondida</span>
            <button v-else type="button" class="btn-primary text-sm" @click="abrirResponder(item)">Responder</button>
          </div>
        </div>
      </div>

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
              <select v-else-if="['opcion_multiple', 'likert', 'si_no'].includes(p.TIPO)" v-model="respuestas[p.ID_PREGUNTA]" class="form-input">
                <option value="">—</option>
                <option v-for="o in opcionesPregunta(p)" :key="o" :value="o">{{ o }}</option>
              </select>
            </div>
            <p v-if="modalError" class="text-sm text-red-600">{{ modalError }}</p>
            <div class="modal-footer">
              <button type="button" data-no-lock class="btn-secondary" @click="showResponder = false">Cancelar</button>
              <LoadingButton type="submit">Enviar</LoadingButton>
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
import AppModalShell from '../../components/AppModalShell.vue';
import LoadingButton from '../../components/LoadingButton.vue';
import api from '../../services/api';
import { getApiErrorMessage } from '../../utils/apiError';

const encuestas = ref([]);
const loading = ref(true);

const showResponder = ref(false);
const encuestaResponder = ref(null);
const preguntasResponder = ref([]);
const respuestas = ref({});
const modalError = ref('');

function opcionesPregunta(p) {
  if (p.TIPO === 'si_no') return ['Sí', 'No'];
  return p.OPCIONES || [];
}

async function loadEncuestas() {
  loading.value = true;
  try {
    const { data } = await api.get('/portal/encuestas');
    encuestas.value = data;
  } finally {
    loading.value = false;
  }
}

function abrirResponder(item) {
  modalError.value = '';
  encuestaResponder.value = item.encuesta;
  preguntasResponder.value = item.preguntas;
  respuestas.value = {};
  showResponder.value = true;
}

async function enviarRespuesta() {
  modalError.value = '';
  try {
    const detalles = preguntasResponder.value.map((p) => ({
      ID_PREGUNTA: p.ID_PREGUNTA,
      VALOR_TEXTO: ['texto', 'fecha'].includes(p.TIPO) ? respuestas.value[p.ID_PREGUNTA] : null,
      VALOR_OPCION: ['opcion_multiple', 'likert', 'si_no'].includes(p.TIPO) ? respuestas.value[p.ID_PREGUNTA] : null,
    }));
    await api.post(`/portal/encuestas/${encuestaResponder.value.ID_ENCUESTA}/responder`, { detalles });
    showResponder.value = false;
    await loadEncuestas();
  } catch (err) {
    modalError.value = getApiErrorMessage(err);
  }
}

onMounted(loadEncuestas);
</script>
