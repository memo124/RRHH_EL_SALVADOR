<template>
  <div class="min-h-screen flex bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
    <!-- Sidebar -->
    <aside
      :class="sidebarCollapsed ? 'w-16' : 'w-64'"
      class="bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 flex flex-col transition-all duration-300 shrink-0"
    >
      <div class="h-16 flex items-center px-4 border-b border-slate-200 dark:border-slate-700 justify-between">
        <span v-if="!sidebarCollapsed" class="text-lg font-bold text-indigo-600 dark:text-indigo-400 truncate">RRHH EL SALVADOR</span>
        <span v-else class="text-lg font-bold text-indigo-600 dark:text-indigo-400 mx-auto">R</span>
        <button
          data-no-lock
          @click="toggleSidebar"
          type="button"
          class="p-1.5 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
          :title="sidebarCollapsed ? 'Expandir menú' : 'Colapsar menú'"
        >
          {{ sidebarCollapsed ? '»' : '«' }}
        </button>
      </div>
      <nav class="flex-1 py-4 space-y-1 overflow-y-auto bg-slate-50/50 dark:bg-slate-900/30">
        <!-- Inicio/Dashboard Link -->
        <router-link
          to="/"
          :title="sidebarCollapsed ? 'Inicio' : ''"
          class="flex items-center px-4 py-3 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors font-medium border-b border-slate-100 dark:border-slate-700 text-sm"
          active-class="bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 font-semibold shadow-sm"
        >
          <span v-if="!sidebarCollapsed" class="flex-1">Inicio</span>
          <span class="text-base" :class="sidebarCollapsed ? 'mx-auto' : ''">🏠</span>
        </router-link>

        <!-- Dynamic Menu Groups and Options -->
        <div v-for="group in menu" :key="group.group" class="border-b border-slate-100 dark:border-slate-700">
          <button
            data-no-lock
            @click="toggleGroup(group.group)"
            type="button"
            :title="sidebarCollapsed ? group.group : ''"
            class="w-full flex items-center px-4 py-3 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors font-bold text-sm"
          >
            <span v-if="!sidebarCollapsed" class="flex items-center space-x-2 flex-1 text-left">
              <span>{{ group.group }}</span>
              <span
                class="text-[10px] text-slate-400 dark:text-slate-500 transition-transform duration-200"
                :class="{ 'rotate-180': openGroups[group.group] }"
              >▼</span>
            </span>
            <span class="text-base" :class="sidebarCollapsed ? 'mx-auto' : 'ml-2'">{{ group.icon }}</span>
          </button>

          <div
            v-show="!sidebarCollapsed && openGroups[group.group]"
            class="bg-white dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700 border-t border-slate-100 dark:border-slate-700"
          >
            <router-link
              v-for="option in group.options"
              :key="option.route"
              :to="option.route"
              class="flex items-center px-8 py-2.5 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-xs font-semibold"
              active-class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-bold border-l-4 border-indigo-600 dark:border-indigo-400"
            >
              <span>{{ option.name }}</span>
            </router-link>
          </div>
        </div>
      </nav>
      <div class="p-4 border-t border-slate-200 dark:border-slate-700">
        <button
          @click="handleLogout"
          :title="sidebarCollapsed ? 'Cerrar Sesión' : ''"
          class="w-full flex items-center justify-center space-x-2 px-4 py-2 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-sm font-semibold"
        >
          <span v-if="!sidebarCollapsed">Cerrar Sesión</span>
          <span v-else>⎋</span>
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <header class="h-16 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between px-8">
        <div class="text-lg font-semibold text-slate-800 dark:text-white">
          Sistema de Recursos Humanos
        </div>
        <div class="flex items-center space-x-4">
          <div class="flex items-center gap-2">
            <label for="theme-preference" class="sr-only">Preferencia de tema</label>
            <select
              id="theme-preference"
              data-no-lock
              v-model="themePreference"
              @change="onThemeChange"
              class="text-sm rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-100 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              :title="themeHint"
            >
              <option value="auto">🕐 Automático (horario)</option>
              <option value="system">💻 Seguir navegador</option>
              <option value="light">☀️ Claro</option>
              <option value="dark">🌙 Oscuro</option>
            </select>
          </div>
          <div class="text-sm font-medium text-slate-600 dark:text-slate-300">
            {{ user?.username }}
          </div>
        </div>
      </header>

      <main class="flex-1 p-8 overflow-y-auto">
        <slot>
          <div class="space-y-6">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Panel de Administración</h1>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
              <template v-if="statsLoading">
                <div v-for="i in 4" :key="i" class="bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700 animate-pulse">
                  <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-2/3"></div>
                  <div class="h-9 bg-slate-200 dark:bg-slate-700 rounded w-1/3 mt-3"></div>
                </div>
              </template>
              <template v-else>
              <div class="bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400">Colaboradores Activos</h3>
                <p class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ stats.empleados_activos ?? '—' }}</p>
              </div>
              <div class="bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400">Planillas Pendientes</h3>
                <p class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ stats.planillas_pendientes ?? '—' }}</p>
              </div>
              <div class="bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400">Incapacidades Activas</h3>
                <p class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ stats.incapacidades_activas ?? '—' }}</p>
              </div>
              <div class="bg-white dark:bg-slate-800 p-6 rounded-xl border border-slate-200 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400">Préstamos Activos</h3>
                <p class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ stats.prestamos_activos ?? '—' }}</p>
              </div>
              </template>
            </div>
          </div>
        </slot>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';
import {
  themePreference,
  setThemePreference,
  themePreferenceDescription,
} from '../utils/theme';

const user = ref(null);
const stats = ref({});
const statsLoading = ref(true);
const router = useRouter();
const openGroups = ref({});
const sidebarCollapsed = ref(false);

const menu = computed(() => user.value?.menu || []);

const themeHint = computed(() => themePreferenceDescription(themePreference.value));

const onThemeChange = async () => {
  await setThemePreference(themePreference.value);
};

const toggleGroup = (groupName) => {
  if (sidebarCollapsed.value) {
    sidebarCollapsed.value = false;
    localStorage.setItem('sidebarCollapsed', 'false');
  }
  openGroups.value[groupName] = !openGroups.value[groupName];
  localStorage.setItem('menuOpenGroups', JSON.stringify(openGroups.value));
};

const toggleSidebar = () => {
  sidebarCollapsed.value = !sidebarCollapsed.value;
  localStorage.setItem('sidebarCollapsed', sidebarCollapsed.value ? 'true' : 'false');
};

onMounted(async () => {
  sidebarCollapsed.value = localStorage.getItem('sidebarCollapsed') === 'true';

  try {
    user.value = JSON.parse(localStorage.getItem('user'));
    const savedGroups = localStorage.getItem('menuOpenGroups');
    if (savedGroups) {
      openGroups.value = JSON.parse(savedGroups);
    } else if (user.value?.menu) {
      user.value.menu.forEach(group => {
        openGroups.value[group.group] = false;
      });
    }
  } catch (e) {
    user.value = null;
  }

  try {
    statsLoading.value = true;
    const res = await api.get('/dashboard/stats');
    stats.value = res.data;
  } catch (e) { /* ignore if no permission */ }
  finally { statsLoading.value = false; }
});

const handleLogout = async () => {
  try {
    await api.post('/logout');
  } catch (err) {
    // Ignore error on logout
  } finally {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    router.push('/login');
  }
};
</script>
