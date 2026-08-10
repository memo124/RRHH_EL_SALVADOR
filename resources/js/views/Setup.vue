<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-900 px-4 py-8">
    <div class="max-w-lg w-full space-y-6 bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700">
      <div class="text-center">
        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white">Configuración inicial</h2>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
          Registre su empresa y el usuario administrador para comenzar.
        </p>
        <div class="mt-4 flex justify-center gap-2">
          <span
            v-for="s in 2"
            :key="s"
            class="h-2 w-10 rounded-full transition-colors"
            :class="step >= s ? 'bg-indigo-600' : 'bg-slate-200 dark:bg-slate-600'"
          />
        </div>
      </div>

      <form v-if="step === 1" v-submit-lock="() => { step = 2; }" class="space-y-4">
        <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide">Paso 1 — Empresa</h3>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nombre de la empresa *</label>
          <input v-model="form.NOMBREEMPRESA" type="text" required class="form-input w-full" placeholder="Mi Empresa S.A. de C.V." />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Abreviatura</label>
            <input v-model="form.ABREVIATURA" type="text" maxlength="20" class="form-input w-full" placeholder="ME" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">NIT</label>
            <input v-model="form.NUMERONIT" type="text" class="form-input w-full" placeholder="0614-000000-001-5" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Dirección</label>
          <input v-model="form.DIRECCION" type="text" class="form-input w-full" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Teléfono</label>
          <input v-model="form.TELEFONO" type="text" class="form-input w-full" />
        </div>
        <button type="submit" class="w-full btn-primary py-2.5">Continuar →</button>
      </form>

      <form v-else v-submit-lock="submit" class="space-y-4">
        <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide">Paso 2 — Administrador</h3>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Usuario / email *</label>
          <input v-model="form.USUARIO" type="text" required class="form-input w-full" placeholder="admin@miempresa.sv" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email (opcional)</label>
          <input v-model="form.EMAIL" type="email" class="form-input w-full" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Contraseña *</label>
          <input v-model="form.CONTRASENA" type="password" required minlength="8" class="form-input w-full" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Confirmar contraseña *</label>
          <input v-model="form.CONTRASENA_confirmation" type="password" required minlength="8" class="form-input w-full" />
        </div>

        <div v-if="error" class="text-red-600 text-sm bg-red-50 dark:bg-red-900/20 p-3 rounded-lg border border-red-200 dark:border-red-800">
          {{ error }}
        </div>

        <div class="flex gap-3">
          <button type="button" data-no-lock class="btn-secondary flex-1 py-2.5" @click="step = 1">← Atrás</button>
          <button type="submit" :disabled="loading" class="btn-primary flex-1 py-2.5 disabled:opacity-50">
            {{ loading ? 'Guardando…' : 'Finalizar configuración' }}
          </button>
        </div>
      </form>

      <p v-if="step === 1" class="text-xs text-center text-slate-400">
        Solo se crean catálogos legales, roles y permisos. Sin datos demo.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';

const router = useRouter();
const step = ref(1);
const loading = ref(false);
const error = ref('');

const form = ref({
  NOMBREEMPRESA: '',
  ABREVIATURA: '',
  NUMERONIT: '',
  DIRECCION: '',
  TELEFONO: '',
  GIRO: '',
  USUARIO: '',
  EMAIL: '',
  CONTRASENA: '',
  CONTRASENA_confirmation: '',
});

onMounted(async () => {
  try {
    const { data } = await api.get('/setup/status');
    if (!data.setup_required) {
      router.replace('/login');
    }
  } catch {
    /* ignore */
  }
});

const submit = async () => {
  loading.value = true;
  error.value = '';
  try {
    await api.post('/setup', form.value);
    router.push('/login');
  } catch (err) {
    error.value = err.response?.data?.error
      || err.response?.data?.message
      || Object.values(err.response?.data?.errors || {}).flat().join(' ')
      || 'Error al completar la configuración.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.form-input {
  @apply appearance-none rounded-lg block w-full px-3 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500;
}
.btn-primary {
  @apply text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition-colors;
}
.btn-secondary {
  @apply text-sm font-semibold rounded-lg text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors;
}
</style>
