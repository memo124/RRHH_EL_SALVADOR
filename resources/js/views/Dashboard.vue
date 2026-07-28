<template>
  <div class="min-h-screen flex bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
    <!-- Mobile menu backdrop -->
    <div
      v-if="mobileMenuOpen"
      class="fixed inset-0 z-40 bg-slate-900/70 backdrop-blur-sm lg:hidden"
      aria-hidden="true"
      @click="mobileMenuOpen = false"
    />

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-50 flex flex-col bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 transition-transform duration-300 w-[min(100vw-2rem,18rem)] lg:static lg:z-auto lg:shrink-0 lg:translate-x-0"
      :class="[
        mobileMenuOpen ? 'translate-x-0' : '-translate-x-full',
        sidebarCollapsed ? 'lg:w-16' : 'lg:w-64',
      ]"
    >
      <div class="h-14 lg:h-16 flex items-center px-3 lg:px-4 border-b border-slate-200 dark:border-slate-700 justify-between gap-2 shrink-0">
        <span v-if="!sidebarCollapsed || isMobile" class="text-base lg:text-lg font-bold text-indigo-600 dark:text-indigo-400 truncate">
          RRHH EL SALVADOR
        </span>
        <span v-else class="text-lg font-bold text-indigo-600 dark:text-indigo-400 mx-auto">R</span>
        <button
          type="button"
          class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700"
          aria-label="Cerrar menú"
          data-no-lock
          @click="mobileMenuOpen = false"
        >
          <AppIcon name="x" size="md" />
        </button>
        <button
          data-no-lock
          @click="toggleSidebar"
          type="button"
          class="hidden lg:block p-1.5 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors shrink-0"
          :title="sidebarCollapsed ? 'Expandir menú' : 'Colapsar menú'"
        >
          <AppIcon :name="sidebarCollapsed ? 'chevron-right' : 'chevron-left'" size="sm" />
        </button>
      </div>

      <nav class="flex-1 py-3 lg:py-4 space-y-1 overflow-y-auto bg-slate-50/50 dark:bg-slate-900/30">
        <router-link
          to="/"
          :title="sidebarCollapsed && !isMobile ? 'Inicio' : ''"
          class="flex items-center px-4 py-3 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors font-medium border-b border-slate-100 dark:border-slate-700 text-sm"
          active-class="bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 font-semibold shadow-sm"
          @click="mobileMenuOpen = false"
        >
          <span v-if="!sidebarCollapsed || isMobile" class="flex-1">Inicio</span>
          <AppIcon name="home" size="md" :class="sidebarCollapsed && !isMobile ? 'mx-auto' : ''" />
        </router-link>

        <div v-for="group in menu" :key="group.group" class="border-b border-slate-100 dark:border-slate-700">
          <button
            data-no-lock
            @click="toggleGroup(group.group)"
            type="button"
            :title="sidebarCollapsed && !isMobile ? group.group : ''"
            class="w-full flex items-center px-4 py-3 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors font-bold text-sm"
          >
            <span v-if="!sidebarCollapsed || isMobile" class="flex items-center space-x-2 flex-1 text-left">
              <span>{{ group.group }}</span>
              <AppIcon
                name="chevron-down"
                size="xs"
                class="text-slate-400 dark:text-slate-500 transition-transform duration-200"
                :class="{ 'rotate-180': openGroups[group.group] }"
              />
            </span>
            <AppIcon :name="group.icon" size="md" :class="sidebarCollapsed && !isMobile ? 'mx-auto' : 'ml-2 shrink-0'" />
          </button>

          <div
            v-show="(!sidebarCollapsed || isMobile) && openGroups[group.group]"
            class="bg-white dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700 border-t border-slate-100 dark:border-slate-700"
          >
            <router-link
              v-for="option in group.options"
              :key="option.route"
              :to="option.route"
              class="flex items-center px-6 lg:px-8 py-2.5 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-xs font-semibold"
              active-class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-bold border-l-4 border-indigo-600 dark:border-indigo-400"
              @click="mobileMenuOpen = false"
            >
              <span>{{ option.name }}</span>
            </router-link>
          </div>
        </div>
      </nav>

      <div class="p-3 lg:p-4 border-t border-slate-200 dark:border-slate-700 shrink-0">
        <button
          @click="handleLogout"
          :title="sidebarCollapsed && !isMobile ? 'Cerrar Sesión' : ''"
          class="w-full flex items-center justify-center space-x-2 px-4 py-2 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-sm font-semibold"
        >
          <span v-if="!sidebarCollapsed || isMobile">Cerrar Sesión</span>
          <AppIcon v-else name="log-out" size="md" />
        </button>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0 w-full">
      <header class="sticky top-0 z-30 h-14 sm:h-16 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 flex items-center gap-2 sm:gap-4 px-3 sm:px-5 lg:px-8 shrink-0">
        <button
          type="button"
          class="lg:hidden p-2 -ml-1 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700"
          aria-label="Abrir menú"
          data-no-lock
          @click="mobileMenuOpen = true"
        >
          <AppIcon name="menu" size="lg" />
        </button>

        <div class="flex-1 min-w-0">
          <p class="text-sm sm:text-base lg:text-lg font-semibold text-slate-800 dark:text-white truncate">
            <span class="sm:hidden">RRHH El Salvador</span>
            <span class="hidden sm:inline">Sistema de Recursos Humanos</span>
          </p>
        </div>

        <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
          <div class="w-10 sm:w-36 shrink-0" data-no-lock :title="themeHint">
            <label for="theme-preference" class="sr-only">Preferencia de tema</label>
            <AsyncSelect
              v-model="themePreference"
              :options="THEME_OPTIONS"
              :searchable="false"
              compact
              dropdown-align="end"
              placeholder="Tema"
              input-class="!bg-slate-100 dark:!bg-slate-700 !text-xs sm:!text-sm"
              @change="onThemeChange"
            />
          </div>
          <div class="hidden sm:block text-sm font-medium text-slate-600 dark:text-slate-300 truncate max-w-[10rem] lg:max-w-none">
            {{ user?.username }}
          </div>
        </div>
      </header>

      <main class="app-main flex-1 p-3 sm:p-5 lg:p-8 overflow-y-auto overflow-x-hidden min-w-0 w-full">
        <slot>
          <div class="space-y-4 sm:space-y-6">
            <h1 class="page-title">Panel de Administración</h1>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6">
              <template v-if="statsLoading">
                <div v-for="i in 4" :key="i" class="bg-white dark:bg-slate-800 p-5 sm:p-6 rounded-xl border border-slate-200 dark:border-slate-700 animate-pulse">
                  <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-2/3"></div>
                  <div class="h-9 bg-slate-200 dark:bg-slate-700 rounded w-1/3 mt-3"></div>
                </div>
              </template>
              <template v-else>
                <div class="bg-white dark:bg-slate-800 p-5 sm:p-6 rounded-xl border border-slate-200 dark:border-slate-700">
                  <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400">Colaboradores Activos</h3>
                  <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ stats.empleados_activos ?? '—' }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 p-5 sm:p-6 rounded-xl border border-slate-200 dark:border-slate-700">
                  <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400">Planillas Pendientes</h3>
                  <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ stats.planillas_pendientes ?? '—' }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 p-5 sm:p-6 rounded-xl border border-slate-200 dark:border-slate-700">
                  <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400">Incapacidades Activas</h3>
                  <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ stats.incapacidades_activas ?? '—' }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 p-5 sm:p-6 rounded-xl border border-slate-200 dark:border-slate-700">
                  <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400">Préstamos Activos</h3>
                  <p class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ stats.prestamos_activos ?? '—' }}</p>
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
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from '../services/api';
import {
  themePreference,
  setThemePreference,
  themePreferenceDescription,
} from '../utils/theme';
import { THEME_OPTIONS } from '../utils/staticSelectOptions';
import AsyncSelect from '../components/AsyncSelect.vue';

const user = ref(null);
const stats = ref({});
const statsLoading = ref(true);
const router = useRouter();
const route = useRoute();
const openGroups = ref({});
const sidebarCollapsed = ref(false);
const mobileMenuOpen = ref(false);
const isMobile = ref(false);

const menu = computed(() => user.value?.menu || []);
const themeHint = computed(() => themePreferenceDescription(themePreference.value));

const syncViewport = () => {
  isMobile.value = window.matchMedia('(max-width: 1023px)').matches;
  if (!isMobile.value) {
    mobileMenuOpen.value = false;
  }
};

watch(() => route.path, () => {
  mobileMenuOpen.value = false;
});

watch(mobileMenuOpen, (open) => {
  if (isMobile.value) {
    document.body.classList.toggle('modal-open', open);
  }
});

const onThemeChange = async () => {
  await setThemePreference(themePreference.value);
};

const toggleGroup = (groupName) => {
  if (sidebarCollapsed.value && !isMobile.value) {
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
  syncViewport();
  window.addEventListener('resize', syncViewport);

  sidebarCollapsed.value = localStorage.getItem('sidebarCollapsed') === 'true';

  try {
    user.value = JSON.parse(localStorage.getItem('user'));
    const savedGroups = localStorage.getItem('menuOpenGroups');
    if (savedGroups) {
      openGroups.value = JSON.parse(savedGroups);
    } else if (user.value?.menu) {
      user.value.menu.forEach((group) => {
        openGroups.value[group.group] = false;
      });
    }
  } catch {
    user.value = null;
  }

  try {
    statsLoading.value = true;
    const res = await api.get('/dashboard/stats');
    stats.value = res.data;
  } catch {
    /* ignore if no permission */
  } finally {
    statsLoading.value = false;
  }
});

onUnmounted(() => {
  window.removeEventListener('resize', syncViewport);
  document.body.classList.remove('modal-open');
});

const handleLogout = async () => {
  try {
    await api.post('/logout');
  } catch {
    /* ignore */
  } finally {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    router.push('/login');
  }
};
</script>
