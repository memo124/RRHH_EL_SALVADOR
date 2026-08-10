<template>
  <PortalLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">
            Hola{{ perfil ? ',' : '' }} <span v-if="perfil">{{ perfil.NOMBRES }}</span>
          </h1>
          <p class="page-subtitle mt-1">
            Bienvenido a su portal de autoservicio.
            <span v-if="perfil?.NOMBREDEPARTAMENTO"> · {{ perfil.NOMBREDEPARTAMENTO }}</span>
            <span v-if="perfil?.NOMBRECARGO"> · {{ perfil.NOMBRECARGO }}</span>
          </p>
        </div>
      </div>

      <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <div v-for="i in 6" :key="i" class="card-panel animate-pulse h-24"></div>
      </div>

      <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <router-link
          v-for="link in quickLinks"
          :key="link.to"
          :to="link.to"
          class="card-panel flex flex-col items-start gap-2 hover:border-indigo-300 dark:hover:border-indigo-600 hover:shadow-md transition-all"
        >
          <span class="p-2 rounded-lg" :class="link.iconBg">
            <AppIcon :name="link.icon" size="md" :class="link.iconColor" />
          </span>
          <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ link.label }}</span>
          <span class="text-xs text-slate-500 dark:text-slate-400">{{ link.hint }}</span>
        </router-link>
      </div>

      <div v-if="!loading && perfil" class="card-panel">
        <h2 class="text-sm font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wide mb-3">Mis datos</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
          <div class="flex justify-between gap-2 border-b border-slate-100 dark:border-slate-700 pb-2">
            <dt class="text-slate-500 dark:text-slate-400">Código de empleado</dt>
            <dd class="font-medium text-slate-800 dark:text-slate-100">{{ perfil.CODIGOEMPLEADO }}</dd>
          </div>
          <div class="flex justify-between gap-2 border-b border-slate-100 dark:border-slate-700 pb-2">
            <dt class="text-slate-500 dark:text-slate-400">Empresa</dt>
            <dd class="font-medium text-slate-800 dark:text-slate-100 truncate">{{ perfil.NOMBREEMPRESA || '—' }}</dd>
          </div>
          <div class="flex justify-between gap-2 border-b border-slate-100 dark:border-slate-700 pb-2">
            <dt class="text-slate-500 dark:text-slate-400">Fecha de ingreso</dt>
            <dd class="font-medium text-slate-800 dark:text-slate-100">{{ fmtDate(perfil.FECHAINGRESO) }}</dd>
          </div>
          <div class="flex justify-between gap-2 border-b border-slate-100 dark:border-slate-700 pb-2">
            <dt class="text-slate-500 dark:text-slate-400">Correo empresarial</dt>
            <dd class="font-medium text-slate-800 dark:text-slate-100 truncate">{{ perfil.CORREOELECTRONICOEMPRESARIAL || '—' }}</dd>
          </div>
        </dl>
      </div>
    </div>
  </PortalLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import PortalLayout from './PortalLayout.vue';
import AppIcon from '../../components/AppIcon.vue';
import api from '../../services/api';

const perfil = ref(null);
const loading = ref(true);

const permissions = computed(() => {
  try {
    return JSON.parse(localStorage.getItem('user'))?.permissions || [];
  } catch {
    return [];
  }
});

const ALL_LINKS = [
  { to: '/portal/boletas', label: 'Boletas de pago', hint: 'Consulte y descargue sus boletas', icon: 'banknote', perm: 'PORTAL_BOLETAS', iconBg: 'bg-emerald-50 dark:bg-emerald-950/50', iconColor: 'text-emerald-600 dark:text-emerald-400' },
  { to: '/portal/permisos', label: 'Permisos y vacaciones', hint: 'Solicite y revise su historial', icon: 'calendar', perm: 'PORTAL_PERMISOS', iconBg: 'bg-violet-50 dark:bg-violet-950/50', iconColor: 'text-violet-600 dark:text-violet-400' },
  { to: '/portal/encuestas', label: 'Encuestas', hint: 'Encuestas pendientes de responder', icon: 'clipboard-list', perm: 'PORTAL_ENCUESTAS', iconBg: 'bg-amber-50 dark:bg-amber-950/50', iconColor: 'text-amber-600 dark:text-amber-400' },
  { to: '/portal/evaluaciones', label: 'Evaluaciones', hint: 'Su desempeño evaluado', icon: 'check-circle', perm: 'PORTAL_EVALUACIONES', iconBg: 'bg-indigo-50 dark:bg-indigo-950/50', iconColor: 'text-indigo-600 dark:text-indigo-400' },
  { to: '/portal/perfil', label: 'Mi perfil', hint: 'Sus datos personales y laborales', icon: 'user', perm: 'PORTAL_PERFIL', iconBg: 'bg-sky-50 dark:bg-sky-950/50', iconColor: 'text-sky-600 dark:text-sky-400' },
];

const quickLinks = computed(() => ALL_LINKS.filter((l) => permissions.value.includes(l.perm)));

function fmtDate(d) {
  return d ? new Date(d).toLocaleDateString('es-SV') : '—';
}

async function loadPerfil() {
  try {
    loading.value = true;
    const { data } = await api.get('/portal/me');
    perfil.value = data;
  } catch {
    perfil.value = null;
  } finally {
    loading.value = false;
  }
}

onMounted(loadPerfil);
</script>
