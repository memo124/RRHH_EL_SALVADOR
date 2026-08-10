<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-900 px-4">
    <div class="max-w-md w-full space-y-8 bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 transition-all duration-300">
      <div>
        <h2 class="text-center text-3xl font-extrabold text-slate-900 dark:text-white">
          RRHH El Salvador
        </h2>
        <p class="mt-2 text-center text-sm text-slate-600 dark:text-slate-400">
          Inicie sesión para acceder al sistema administrativo
        </p>
      </div>
      <form class="mt-8 space-y-6" v-submit-lock="handleLogin">
        <div class="rounded-md shadow-sm space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Usuario / Email</label>
            <input
              type="text"
              required
              v-model="usuario"
              class="appearance-none rounded-lg relative block w-full px-3 py-2.5 border border-slate-300 dark:border-slate-600 placeholder-slate-400 text-slate-900 dark:text-white bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
              placeholder="admin@rrhh.sv"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Contraseña</label>
            <input
              type="password"
              required
              v-model="contrasena"
              class="appearance-none rounded-lg relative block w-full px-3 py-2.5 border border-slate-300 dark:border-slate-600 placeholder-slate-400 text-slate-900 dark:text-white bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
              placeholder="••••••••"
            />
          </div>
        </div>

        <div v-if="error" class="text-red-500 text-sm bg-red-50 dark:bg-red-900/20 p-3 rounded-lg border border-red-200 dark:border-red-800">
          {{ error }}
        </div>

        <div>
          <button
            type="submit"
            :disabled="loading"
            class="group relative w-full flex justify-center py-2.5 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50"
          >
            <span v-if="loading">Conectando...</span>
            <span v-else>Iniciar Sesión</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';
import { loadUserTheme } from '../utils/theme';

const usuario = ref('');
const contrasena = ref('');
const error = ref('');
const loading = ref(false);
const router = useRouter();

const handleLogin = async () => {
  loading.value = true;
  error.value = '';
  try {
    const response = await api.post('/login', {
      usuario: usuario.value,
      contrasena: contrasena.value
    });
    localStorage.setItem('token', response.data.token);
    localStorage.setItem('user', JSON.stringify(response.data.user));
    loadUserTheme(response.data.user.theme);
    router.push(response.data.user.is_employee_portal ? '/portal' : '/');
  } catch (err) {
    error.value = err.response?.data?.error || err.response?.data?.message || 'Error de conexión.';
  } finally {
    loading.value = false;
  }
};
</script>
