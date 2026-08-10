<template>
  <div class="min-h-screen bg-slate-100 dark:bg-slate-900 flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
      <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Actualización de datos</h1>
        <p v-if="meta.CAMPANA_NOMBRE" class="text-slate-600 dark:text-slate-400 mt-1">{{ meta.CAMPANA_NOMBRE }}</p>
      </div>

      <div v-if="loading" class="bg-white dark:bg-slate-800 rounded-xl p-8 text-center text-slate-500">
        Cargando formulario...
      </div>

      <div v-else-if="error" class="bg-white dark:bg-slate-800 rounded-xl p-8 text-center">
        <p class="text-red-600">{{ error }}</p>
      </div>

      <div v-else-if="enviado" class="bg-white dark:bg-slate-800 rounded-xl p-8 text-center space-y-3">
        <div class="text-4xl">✓</div>
        <h2 class="text-lg font-semibold text-emerald-700 dark:text-emerald-400">Datos enviados</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400">
          Sus cambios están pendientes de revisión por el departamento de Recursos Humanos.
        </p>
      </div>

      <form v-else @submit.prevent="submit" class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 p-6 space-y-5">
        <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4 text-sm">
          <p><strong>Empleado:</strong> {{ meta.EMPLEADO_NOMBRE }}</p>
          <p><strong>Código:</strong> {{ meta.CODIGOEMPLEADO }}</p>
          <p v-if="meta.FECHA_EXPIRACION" class="text-xs text-slate-500 mt-1">
            Vence: {{ fmtDate(meta.FECHA_EXPIRACION) }}
          </p>
        </div>

        <div v-for="campo in campos" :key="campo.ID_CAMPO" class="space-y-1">
          <label class="form-label">
            {{ campo.ETIQUETA }}
            <span v-if="campo.REQUERIDO" class="text-red-500">*</span>
          </label>

          <textarea
            v-if="campo.TIPO_CAMPO === 'textarea'"
            v-model="valores[keyFor(campo)]"
            class="form-input"
            rows="3"
            :required="campo.REQUERIDO"
          />
          <FileUpload
            v-else-if="campo.TIPO_CAMPO === 'archivo'"
            :model-value="valores[keyFor(campo)]"
            :uploading="uploadingField === campo.ID_CAMPO"
            @upload="(f) => uploadArchivo(campo, f)"
            @clear="valores[keyFor(campo)] = null"
          />
          <input
            v-else
            v-model="valores[keyFor(campo)]"
            :type="campo.TIPO_CAMPO === 'fecha' ? 'date' : 'text'"
            class="form-input"
            :required="campo.REQUERIDO"
          />

          <p v-if="datosActuales[keyFor(campo)]" class="text-xs text-slate-400">
            Actual: {{ displayActual(datosActuales[keyFor(campo)]) }}
          </p>
        </div>

        <p v-if="submitError" class="text-sm text-red-600">{{ submitError }}</p>

        <button
          type="submit"
          :disabled="saving"
          class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-lg font-semibold"
        >
          {{ saving ? 'Enviando...' : 'Enviar actualización' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import FileUpload from '../../components/FileUpload.vue';
import { getApiErrorMessage } from '../../utils/apiError';

const route = useRoute();
const token = route.params.token;

const loading = ref(true);
const error = ref('');
const enviado = ref(false);
const saving = ref(false);
const submitError = ref('');
const meta = ref({});
const campos = ref([]);
const datosActuales = ref({});
const valores = ref({});
const uploadingField = ref(null);

const publicApi = axios.create({
  baseURL: '/api',
  headers: { Accept: 'application/json' },
});

function keyFor(campo) {
  return `campo_${campo.ID_CAMPO}`;
}

function fmtDate(d) {
  if (!d) return '';
  return new Date(d).toLocaleDateString('es-SV');
}

function displayActual(v) {
  if (v == null) return '—';
  if (typeof v === 'object') return JSON.stringify(v);
  return String(v);
}

async function load() {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await publicApi.get(`/formularios/responder/${token}`);
    meta.value = data.invitacion;
    campos.value = data.campos;
    datosActuales.value = data.datos_actuales || {};
    const init = {};
    for (const c of campos.value) {
      init[keyFor(c)] = '';
    }
    valores.value = init;
  } catch (err) {
    error.value = getApiErrorMessage(err) || 'Enlace inválido o expirado.';
  } finally {
    loading.value = false;
  }
}

async function uploadArchivo(campo, file) {
  uploadingField.value = campo.ID_CAMPO;
  try {
    const fd = new FormData();
    fd.append('archivo', file);
    fd.append('ID_TIPO_DOCUMENTO_ADJUNTO', campo.ETIQUETA.includes('título') ? 1 : 5);
    const { data } = await publicApi.post(`/formularios/responder/${token}/adjunto`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    valores.value[keyFor(campo)] = data.ID_ADJUNTO;
  } catch (err) {
    submitError.value = getApiErrorMessage(err);
  } finally {
    uploadingField.value = null;
  }
}

async function submit() {
  saving.value = true;
  submitError.value = '';
  try {
    await publicApi.post(`/formularios/responder/${token}`, { valores: valores.value });
    enviado.value = true;
  } catch (err) {
    submitError.value = getApiErrorMessage(err);
  } finally {
    saving.value = false;
  }
}

onMounted(load);
</script>
