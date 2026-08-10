<template>
  <div class="min-h-screen flex flex-col bg-slate-50 dark:bg-slate-900 transition-colors duration-300">
    <header class="sticky top-0 z-30 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
      <div class="h-14 sm:h-16 flex items-center gap-3 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto w-full">
        <router-link to="/portal" class="flex items-center gap-2 shrink-0">
          <span class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shrink-0">RS</span>
          <span class="text-sm sm:text-base font-bold text-indigo-600 dark:text-indigo-400 truncate">Portal Empleado</span>
        </router-link>

        <div class="flex-1"></div>

        <div ref="profileRef" class="relative shrink-0" data-no-lock>
          <button
            type="button"
            class="flex items-center gap-2 pl-1 pr-2 py-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
            :aria-expanded="profileOpen"
            aria-haspopup="true"
            @click="profileOpen = !profileOpen"
          >
            <span class="w-8 h-8 rounded-full bg-indigo-600 dark:bg-indigo-500 text-white flex items-center justify-center text-xs font-bold shrink-0">
              {{ userInitials }}
            </span>
            <span class="hidden md:block text-sm font-medium text-slate-700 dark:text-slate-200 max-w-[10rem] truncate">
              {{ displayName }}
            </span>
            <AppIcon name="chevron-down" size="xs" class="hidden md:block text-slate-400 transition-transform" :class="{ 'rotate-180': profileOpen }" />
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
              class="absolute right-0 top-full mt-2 w-64 rounded-xl shadow-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 overflow-hidden z-50"
            >
              <div class="px-4 py-3 bg-indigo-50 dark:bg-indigo-950/40 border-b border-slate-200 dark:border-slate-700">
                <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ displayName }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ user?.email || user?.username }}</p>
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

      <nav class="border-t border-slate-100 dark:border-slate-700 overflow-x-auto">
        <div class="flex max-w-5xl mx-auto px-2 sm:px-6 lg:px-8">
          <router-link
            v-for="link in navLinks"
            :key="link.to"
            :to="link.to"
            class="tab-btn tab-btn-inactive whitespace-nowrap"
            active-class="tab-btn-active"
          >
            <span class="inline-flex items-center gap-1.5">
              <AppIcon :name="link.icon" size="sm" />
              {{ link.label }}
            </span>
          </router-link>
        </div>
      </nav>
    </header>

    <main class="flex-1 max-w-5xl mx-auto w-full p-3 sm:p-5 lg:p-8">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../services/api';
import AppIcon from '../../components/AppIcon.vue';

const user = ref(null);
const router = useRouter();
const profileOpen = ref(false);
const profileRef = ref(null);

const ALL_LINKS = [
  { to: '/portal', label: 'Inicio', icon: 'home', perm: null },
  { to: '/portal/boletas', label: 'Boletas de pago', icon: 'banknote', perm: 'PORTAL_BOLETAS' },
  { to: '/portal/permisos', label: 'Permisos y vacaciones', icon: 'calendar', perm: 'PORTAL_PERMISOS' },
  { to: '/portal/encuestas', label: 'Encuestas', icon: 'clipboard-list', perm: 'PORTAL_ENCUESTAS' },
  { to: '/portal/evaluaciones', label: 'Evaluaciones', icon: 'check-circle', perm: 'PORTAL_EVALUACIONES' },
  { to: '/portal/perfil', label: 'Mi perfil', icon: 'user', perm: 'PORTAL_PERFIL' },
];

const permissions = computed(() => user.value?.permissions || []);
const navLinks = computed(() => ALL_LINKS.filter((l) => !l.perm || permissions.value.includes(l.perm)));

const displayName = computed(() => user.value?.username || user.value?.email || 'Empleado');

const userInitials = computed(() => {
  const name = displayName.value || '?';
  const parts = name.replace(/[@.]/g, ' ').trim().split(/\s+/);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.slice(0, 2).toUpperCase();
});

const onDocumentClick = (event) => {
  if (profileRef.value && !profileRef.value.contains(event.target)) {
    profileOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', onDocumentClick);
  try {
    user.value = JSON.parse(localStorage.getItem('user'));
  } catch {
    user.value = null;
  }
});

onUnmounted(() => {
  document.removeEventListener('click', onDocumentClick);
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
