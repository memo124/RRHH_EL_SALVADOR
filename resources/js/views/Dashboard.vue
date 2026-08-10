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
      class="fixed inset-y-0 left-0 z-50 flex flex-col bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 transition-[width,transform] duration-300 w-[min(100vw-2rem,18rem)] lg:static lg:z-auto lg:shrink-0 lg:translate-x-0"
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

      <!-- Búsqueda en menú -->
      <div
        v-if="!sidebarCollapsed || isMobile"
        class="px-3 py-2 border-b border-slate-200 dark:border-slate-700 shrink-0"
      >
        <label for="menu-search" class="sr-only">Buscar en el menú</label>
        <div class="relative">
          <AppIcon
            name="search"
            size="sm"
            class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"
          />
          <input
            id="menu-search"
            v-model="menuSearch"
            type="search"
            placeholder="Buscar módulo..."
            class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
            data-no-lock
          />
        </div>
      </div>
      <div
        v-else
        class="hidden lg:flex justify-center py-2 border-b border-slate-200 dark:border-slate-700 shrink-0"
      >
        <button
          type="button"
          data-no-lock
          class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700"
          title="Buscar módulo"
          @click="openCollapsedSearch"
        >
          <AppIcon name="search" size="md" />
        </button>
      </div>

      <nav class="flex-1 py-2 lg:py-3 space-y-0.5 overflow-y-auto overflow-x-hidden bg-slate-50/50 dark:bg-slate-900/30">
        <router-link
          to="/"
          :title="sidebarCollapsed && !isMobile ? 'Inicio' : ''"
          class="flex items-center px-4 py-3 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors font-medium border-b border-slate-100 dark:border-slate-700 text-sm"
          active-class="bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 font-semibold shadow-sm"
          @click="mobileMenuOpen = false"
        >
          <AppIcon
            name="home"
            size="md"
            :class="sidebarCollapsed && !isMobile ? 'mx-auto' : 'shrink-0'"
          />
          <span v-if="!sidebarCollapsed || isMobile" class="flex-1 ml-3">Inicio</span>
        </router-link>

        <p
          v-if="menuSearch.trim() && filteredMenu.length === 0"
          class="px-4 py-6 text-xs text-slate-500 dark:text-slate-400 text-center"
        >
          Sin resultados para «{{ menuSearch.trim() }}»
        </p>

        <div
          v-for="group in filteredMenu"
          :key="group.group"
          class="border-b border-slate-100 dark:border-slate-700 relative"
          @mouseenter="(e) => showGroupFlyout(group.group, e)"
          @mouseleave="scheduleHideFlyout"
        >
          <button
            data-no-lock
            @click="onGroupClick(group.group, $event)"
            type="button"
            :title="sidebarCollapsed && !isMobile ? group.group : ''"
            class="w-full flex items-center px-4 py-3 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors font-bold text-sm"
            :class="{ 'bg-slate-100 dark:bg-slate-700/60': sidebarCollapsed && !isMobile && hoveredGroup === group.group }"
          >
            <AppIcon
              :name="group.icon"
              size="md"
              :class="sidebarCollapsed && !isMobile ? 'mx-auto' : 'shrink-0'"
            />
            <span v-if="!sidebarCollapsed || isMobile" class="flex items-center space-x-2 flex-1 text-left ml-3">
              <span class="truncate">{{ group.group }}</span>
              <AppIcon
                name="chevron-down"
                size="xs"
                class="text-slate-400 dark:text-slate-500 transition-transform duration-200 shrink-0"
                :class="{ 'rotate-180': openGroups[group.group] }"
              />
            </span>
          </button>

          <!-- Submenú expandido (sidebar abierto o móvil) -->
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
    </aside>

    <!-- Flyout colapsado (desktop) -->
    <Teleport to="body">
      <div
        v-if="sidebarCollapsed && !isMobile && hoveredGroup && activeFlyoutGroup"
        class="fixed z-[60] min-w-[14rem] max-w-[18rem] py-2 rounded-xl shadow-xl border border-slate-600/50 bg-slate-800 text-white"
        :style="flyoutStyle"
        @mouseenter="cancelHideFlyout"
        @mouseleave="scheduleHideFlyout"
      >
        <p class="px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-300 border-b border-slate-600/60 mb-1">
          {{ activeFlyoutGroup.group }}
        </p>
        <router-link
          v-for="option in activeFlyoutGroup.options"
          :key="option.route"
          :to="option.route"
          class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-100 hover:bg-slate-700/80 transition-colors"
          active-class="bg-indigo-600/40 text-white font-semibold"
          @click="hoveredGroup = null"
        >
          <AppIcon name="chevron-right" size="xs" class="text-slate-400 shrink-0" />
          <span>{{ option.name }}</span>
        </router-link>
      </div>
    </Teleport>

    <!-- Búsqueda flotante (sidebar colapsado) -->
    <Teleport to="body">
      <div
        v-if="collapsedSearchOpen"
        class="fixed inset-0 z-[55]"
        data-no-lock
        @click="collapsedSearchOpen = false"
      />
      <div
        v-if="collapsedSearchOpen && sidebarCollapsed && !isMobile"
        class="fixed z-[60] w-72 rounded-xl shadow-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 p-3"
        :style="{ top: '4.5rem', left: '4.25rem' }"
        data-no-lock
        @click.stop
      >
        <div class="relative mb-2">
          <AppIcon name="search" size="sm" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
          <input
            ref="collapsedSearchInput"
            v-model="menuSearch"
            type="search"
            placeholder="Buscar módulo..."
            class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
            data-no-lock
            @keydown.esc="collapsedSearchOpen = false"
          />
        </div>
        <div class="max-h-64 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
          <template v-if="filteredMenu.length">
            <div v-for="group in filteredMenu" :key="group.group" class="py-1">
              <p class="px-2 py-1 text-[10px] font-bold uppercase text-slate-400">{{ group.group }}</p>
              <router-link
                v-for="option in group.options"
                :key="option.route"
                :to="option.route"
                class="block px-2 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg"
                @click="collapsedSearchOpen = false; hoveredGroup = null"
              >
                {{ option.name }}
              </router-link>
            </div>
          </template>
          <p v-else class="px-2 py-4 text-sm text-slate-500 text-center">Sin resultados</p>
        </div>
      </div>
    </Teleport>

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

          <!-- Notificaciones -->
          <div ref="notifRef" class="relative" data-no-lock>
            <button
              type="button"
              class="relative p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
              :aria-expanded="notifOpen"
              aria-haspopup="true"
              title="Notificaciones"
              @click="toggleNotifications"
            >
              <AppIcon name="bell" size="md" />
              <span
                v-if="unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-rose-600 text-white text-[10px] font-bold flex items-center justify-center"
              >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
              </span>
            </button>

            <Transition
              enter-active-class="transition ease-out duration-150"
              enter-from-class="opacity-0 scale-95"
              enter-to-class="opacity-100 scale-100"
              leave-active-class="transition ease-in duration-100"
              leave-from-class="opacity-100 scale-100"
              leave-to-class="opacity-0 scale-95"
            >
              <div
                v-if="notifOpen"
                class="absolute right-0 top-full mt-2 w-80 rounded-xl shadow-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 overflow-hidden z-50"
              >
                <div class="px-4 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                  <p class="text-sm font-bold text-slate-800 dark:text-white">Notificaciones</p>
                  <button
                    v-if="unreadCount > 0"
                    type="button"
                    class="text-xs font-semibold text-indigo-600 hover:underline"
                    @click="markAllNotificationsRead"
                  >
                    Marcar todas leídas
                  </button>
                </div>
                <div class="max-h-96 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                  <p v-if="loadingNotifs" class="px-4 py-6 text-sm text-slate-500 text-center">Cargando…</p>
                  <p v-else-if="!notificaciones.length" class="px-4 py-6 text-sm text-slate-500 text-center">Sin notificaciones.</p>
                  <button
                    v-for="n in notificaciones"
                    :key="n.ID_NOTIFICACION"
                    type="button"
                    class="w-full text-left px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
                    :class="!n.LEIDA ? 'bg-indigo-50/60 dark:bg-indigo-900/20' : ''"
                    @click="onNotificationClick(n)"
                  >
                    <div class="flex items-start gap-2">
                      <span
                        class="w-2 h-2 mt-1.5 rounded-full shrink-0"
                        :class="!n.LEIDA ? 'bg-indigo-500' : 'bg-transparent'"
                      />
                      <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">{{ n.TITULO }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">{{ n.MENSAJE }}</p>
                        <p class="text-[10px] text-slate-400 mt-1">{{ formatNotifDate(n.FECHA_CREACION) }}</p>
                      </div>
                    </div>
                  </button>
                </div>
              </div>
            </Transition>
          </div>

          <!-- Perfil + cerrar sesión -->
          <div ref="profileRef" class="relative" data-no-lock>
            <button
              type="button"
              class="flex items-center gap-2 pl-1 pr-2 py-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
              :aria-expanded="profileOpen"
              aria-haspopup="true"
              @click="profileOpen = !profileOpen"
            >
              <span
                class="w-8 h-8 rounded-full bg-indigo-600 dark:bg-indigo-500 text-white flex items-center justify-center text-xs font-bold shrink-0"
                :title="user?.username"
              >
                {{ userInitials }}
              </span>
              <span class="hidden md:block text-sm font-medium text-slate-700 dark:text-slate-200 max-w-[8rem] truncate">
                {{ user?.username }}
              </span>
              <AppIcon
                name="chevron-down"
                size="xs"
                class="hidden md:block text-slate-400 transition-transform"
                :class="{ 'rotate-180': profileOpen }"
              />
            </button>

            <Transition
              enter-active-class="transition ease-out duration-150"
              enter-from-class="opacity-0 scale-95"
              enter-to-class="opacity-100 scale-100"
              leave-active-class="transition ease-in duration-100"
              leave-from-class="opacity-100 scale-100"
              leave-to-class="opacity-0 scale-95"
            >
              <div
                v-if="profileOpen"
                class="absolute right-0 top-full mt-2 w-72 rounded-xl shadow-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 overflow-hidden z-50"
              >
                <div class="px-4 py-4 bg-indigo-50 dark:bg-indigo-950/40 border-b border-slate-200 dark:border-slate-700">
                  <div class="flex items-center gap-3">
                    <span class="w-11 h-11 rounded-full bg-indigo-600 text-white flex items-center justify-center text-sm font-bold shrink-0">
                      {{ userInitials }}
                    </span>
                    <div class="min-w-0">
                      <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ user?.username }}</p>
                      <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ user?.email || 'Sin correo' }}</p>
                    </div>
                  </div>
                </div>

                <div class="px-4 py-3 space-y-2 border-b border-slate-100 dark:border-slate-700">
                  <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Mi perfil</p>
                  <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between gap-2">
                      <dt class="text-slate-500 dark:text-slate-400">Usuario</dt>
                      <dd class="font-medium text-slate-800 dark:text-slate-100 truncate">{{ user?.username }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                      <dt class="text-slate-500 dark:text-slate-400">Correo</dt>
                      <dd class="font-medium text-slate-800 dark:text-slate-100 truncate">{{ user?.email || '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                      <dt class="text-slate-500 dark:text-slate-400">Permisos</dt>
                      <dd class="font-medium text-slate-800 dark:text-slate-100">{{ user?.permissions?.length ?? 0 }}</dd>
                    </div>
                  </dl>
                </div>

                <div class="p-2">
                  <button
                    type="button"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-sm font-semibold"
                    @click="handleLogout"
                  >
                    <AppIcon name="log-out" size="sm" />
                    Cerrar sesión
                  </button>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </header>

      <main class="app-main flex-1 p-3 sm:p-5 lg:p-8 overflow-y-auto overflow-x-hidden min-w-0 w-full">
        <slot>
          <DashboardHome
            :username="user?.username ?? user?.email ?? ''"
            :permissions="user?.permissions ?? []"
          />
        </slot>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from '../services/api';
import {
  themePreference,
  setThemePreference,
  themePreferenceDescription,
} from '../utils/theme';
import { THEME_OPTIONS } from '../utils/staticSelectOptions';
import AsyncSelect from '../components/AsyncSelect.vue';
import DashboardHome from './DashboardHome.vue';

const user = ref(null);
const router = useRouter();
const route = useRoute();
const openGroups = ref({});
const sidebarCollapsed = ref(false);
const mobileMenuOpen = ref(false);
const isMobile = ref(false);
const menuSearch = ref('');
const hoveredGroup = ref(null);
const flyoutStyle = ref({ top: '0px', left: '64px' });
const collapsedSearchOpen = ref(false);
const collapsedSearchInput = ref(null);
const profileOpen = ref(false);
const profileRef = ref(null);
const notifRef = ref(null);
const notifOpen = ref(false);
const notificaciones = ref([]);
const unreadCount = ref(0);
const loadingNotifs = ref(false);

let flyoutHideTimer = null;
let notifPollTimer = null;

const menu = computed(() => user.value?.menu || []);
const themeHint = computed(() => themePreferenceDescription(themePreference.value));

const userInitials = computed(() => {
  const name = user.value?.username || user.value?.email || '?';
  const parts = name.replace(/[@.]/g, ' ').trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.slice(0, 2).toUpperCase();
});

const filteredMenu = computed(() => {
  const q = menuSearch.value.trim().toLowerCase();
  if (!q) {
    return menu.value;
  }
  return menu.value
    .map((group) => ({
      ...group,
      options: group.options.filter(
        (opt) =>
          opt.name.toLowerCase().includes(q) ||
          group.group.toLowerCase().includes(q),
      ),
    }))
    .filter((group) => group.options.length > 0);
});

const activeFlyoutGroup = computed(() =>
  filteredMenu.value.find((g) => g.group === hoveredGroup.value) ?? null,
);

const syncViewport = () => {
  isMobile.value = window.matchMedia('(max-width: 1023px)').matches;
  if (!isMobile.value) {
    mobileMenuOpen.value = false;
  } else {
    hoveredGroup.value = null;
    collapsedSearchOpen.value = false;
  }
};

watch(() => route.path, () => {
  mobileMenuOpen.value = false;
  hoveredGroup.value = null;
  profileOpen.value = false;
});

watch(mobileMenuOpen, (open) => {
  if (isMobile.value) {
    document.body.classList.toggle('modal-open', open);
  }
});

watch(sidebarCollapsed, (collapsed) => {
  if (!collapsed) {
    hoveredGroup.value = null;
    collapsedSearchOpen.value = false;
  }
});

watch(menuSearch, (q) => {
  if (!q.trim()) {
    return;
  }
  filteredMenu.value.forEach((group) => {
    openGroups.value[group.group] = true;
  });
});

const onThemeChange = async () => {
  await setThemePreference(themePreference.value);
};

const toggleGroup = (groupName) => {
  openGroups.value[groupName] = !openGroups.value[groupName];
  localStorage.setItem('menuOpenGroups', JSON.stringify(openGroups.value));
};

const onGroupClick = (groupName, event) => {
  if (sidebarCollapsed.value && !isMobile.value) {
    showGroupFlyout(groupName, event);
    return;
  }
  toggleGroup(groupName);
};

const showGroupFlyout = (groupName, event) => {
  if (!sidebarCollapsed.value || isMobile.value) {
    return;
  }
  cancelHideFlyout();
  hoveredGroup.value = groupName;
  const rect = event.currentTarget.getBoundingClientRect();
  const maxTop = window.innerHeight - 280;
  flyoutStyle.value = {
    top: `${Math.min(rect.top, maxTop)}px`,
    left: `${rect.right + 4}px`,
  };
};

const scheduleHideFlyout = () => {
  cancelHideFlyout();
  flyoutHideTimer = setTimeout(() => {
    hoveredGroup.value = null;
  }, 180);
};

const cancelHideFlyout = () => {
  if (flyoutHideTimer) {
    clearTimeout(flyoutHideTimer);
    flyoutHideTimer = null;
  }
};

const openCollapsedSearch = async () => {
  collapsedSearchOpen.value = true;
  await nextTick();
  collapsedSearchInput.value?.focus();
};

const toggleSidebar = () => {
  sidebarCollapsed.value = !sidebarCollapsed.value;
  localStorage.setItem('sidebarCollapsed', sidebarCollapsed.value ? 'true' : 'false');
  hoveredGroup.value = null;
};

const onDocumentClick = (event) => {
  if (profileRef.value && !profileRef.value.contains(event.target)) {
    profileOpen.value = false;
  }
  if (notifRef.value && !notifRef.value.contains(event.target)) {
    notifOpen.value = false;
  }
};

const loadUnreadCount = async () => {
  try {
    const res = await api.get('/notificaciones/no-leidas');
    unreadCount.value = res.data?.count ?? 0;
  } catch {
    /* silencioso: no interrumpir la sesión por fallos de notificaciones */
  }
};

const loadNotificaciones = async () => {
  loadingNotifs.value = true;
  try {
    const res = await api.get('/notificaciones', { params: { per_page: 10 } });
    const payload = res.data;
    notificaciones.value = Array.isArray(payload) ? payload : (payload?.data ?? []);
  } catch {
    notificaciones.value = [];
  } finally {
    loadingNotifs.value = false;
  }
};

const toggleNotifications = () => {
  notifOpen.value = !notifOpen.value;
  if (notifOpen.value) {
    loadNotificaciones();
  }
};

const onNotificationClick = async (n) => {
  if (!n.LEIDA) {
    try {
      await api.post(`/notificaciones/${n.ID_NOTIFICACION}/leer`);
      n.LEIDA = true;
      unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch {
      /* ignore */
    }
  }
  notifOpen.value = false;
  if (n.LINK) {
    router.push(n.LINK);
  }
};

const markAllNotificationsRead = async () => {
  try {
    await api.post('/notificaciones/leer-todas');
    notificaciones.value = notificaciones.value.map((n) => ({ ...n, LEIDA: true }));
    unreadCount.value = 0;
  } catch {
    /* ignore */
  }
};

const formatNotifDate = (fecha) => (fecha ? new Date(fecha).toLocaleString('es-SV') : '');

onMounted(async () => {
  syncViewport();
  window.addEventListener('resize', syncViewport);
  document.addEventListener('click', onDocumentClick);

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

  loadUnreadCount();
  notifPollTimer = setInterval(loadUnreadCount, 60000);
});

onUnmounted(() => {
  window.removeEventListener('resize', syncViewport);
  document.removeEventListener('click', onDocumentClick);
  document.body.classList.remove('modal-open');
  cancelHideFlyout();
  if (notifPollTimer) {
    clearInterval(notifPollTimer);
  }
});

const handleLogout = async () => {
  profileOpen.value = false;
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
