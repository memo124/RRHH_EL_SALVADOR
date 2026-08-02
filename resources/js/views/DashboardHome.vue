<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
      <div>
        <h1 class="page-title">Panel de Administración</h1>
        <p class="page-subtitle mt-1">
          Resumen de nómina y recursos humanos
          <span v-if="fechaHoy" class="text-slate-400 dark:text-slate-500"> · {{ fechaHoy }}</span>
        </p>
      </div>
      <p v-if="username" class="text-sm text-slate-500 dark:text-slate-400">
        Bienvenido, <span class="font-semibold text-slate-700 dark:text-slate-200">{{ username }}</span>
      </p>
    </div>

    <!-- KPIs principales -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4">
      <template v-if="loading">
        <div v-for="i in 8" :key="i" class="dashboard-kpi animate-pulse">
          <div class="h-4 bg-slate-200 dark:bg-slate-700 rounded w-2/3" />
          <div class="h-8 bg-slate-200 dark:bg-slate-700 rounded w-1/2 mt-3" />
        </div>
      </template>
      <template v-else>
        <div v-for="kpi in kpis" :key="kpi.key" class="dashboard-kpi group">
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <p class="text-xs sm:text-sm font-semibold text-slate-500 dark:text-slate-400 truncate">{{ kpi.label }}</p>
              <p class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white mt-1 tabular-nums">{{ kpi.value }}</p>
              <p v-if="kpi.hint" class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 truncate">{{ kpi.hint }}</p>
            </div>
            <div
              class="shrink-0 p-2 rounded-lg transition-colors"
              :class="kpi.iconBg"
            >
              <AppIcon :name="kpi.icon" size="md" :class="kpi.iconColor" />
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Gráficas -->
    <div v-if="!loading && charts" class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
      <section class="dashboard-card">
        <h2 class="dashboard-card-title">Colaboradores por departamento</h2>
        <p class="dashboard-card-sub">Top 8 áreas con más personal activo</p>
        <DashboardChart
          type="bar"
          :labels="charts.empleados_por_departamento.labels"
          :datasets="[{ label: 'Empleados', data: charts.empleados_por_departamento.values }]"
        />
      </section>

      <section class="dashboard-card">
        <h2 class="dashboard-card-title">Evolución de nómina líquida</h2>
        <p class="dashboard-card-sub">Últimas 6 planillas calculadas</p>
        <DashboardChart
          type="line"
          currency-y
          :labels="charts.nomina_ultimas_planillas.labels"
          :datasets="[{
            label: 'Líquido a pagar',
            data: charts.nomina_ultimas_planillas.liquido,
            fill: true,
            backgroundColor: 'rgba(99, 102, 241, 0.12)',
            borderColor: 'rgba(99, 102, 241, 0.9)',
          }]"
        />
      </section>

      <section class="dashboard-card">
        <h2 class="dashboard-card-title">Tipo de contratación</h2>
        <p class="dashboard-card-sub">Distribución del personal activo</p>
        <DashboardChart
          type="doughnut"
          :labels="charts.empleados_por_contratacion.labels"
          :datasets="[{ label: 'Empleados', data: charts.empleados_por_contratacion.values }]"
          :height="260"
        />
      </section>

      <section class="dashboard-card">
        <h2 class="dashboard-card-title">Estado de planillas</h2>
        <p class="dashboard-card-sub">Pipeline del ciclo de nómina</p>
        <DashboardChart
          type="doughnut"
          :labels="charts.planillas_por_estado.labels"
          :datasets="[{ label: 'Planillas', data: charts.planillas_por_estado.values }]"
          :height="260"
        />
      </section>
    </div>

    <!-- Fila inferior: incapacidades + alertas -->
    <div v-if="!loading && charts" class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
      <section class="dashboard-card lg:col-span-2">
        <h2 class="dashboard-card-title">Incapacidades registradas</h2>
        <p class="dashboard-card-sub">Últimos 6 meses (no canceladas)</p>
        <DashboardChart
          type="bar"
          :labels="charts.incapacidades_por_mes.labels"
          :datasets="[{
            label: 'Casos',
            data: charts.incapacidades_por_mes.values,
            backgroundColor: 'rgba(245, 158, 11, 0.75)',
            borderColor: 'rgba(245, 158, 11, 1)',
          }]"
          :height="240"
        />
      </section>

      <section class="dashboard-card flex flex-col">
        <h2 class="dashboard-card-title">Alertas y seguimiento</h2>
        <p class="dashboard-card-sub">Requieren atención próxima</p>

        <ul class="mt-4 space-y-3 flex-1">
          <li class="flex items-center justify-between gap-2 text-sm">
            <span class="text-slate-600 dark:text-slate-300 flex items-center gap-2">
              <AppIcon name="clock" size="sm" class="text-amber-500 shrink-0" />
              Marcaciones sin procesar
            </span>
            <span class="font-bold tabular-nums" :class="stats.marcaciones_pendientes > 0 ? 'text-amber-600' : 'text-slate-400'">
              {{ stats.marcaciones_pendientes ?? 0 }}
            </span>
          </li>
          <li class="flex items-center justify-between gap-2 text-sm">
            <span class="text-slate-600 dark:text-slate-300 flex items-center gap-2">
              <AppIcon name="file-text" size="sm" class="text-rose-500 shrink-0" />
              Contratos por vencer (30 días)
            </span>
            <span class="font-bold tabular-nums" :class="stats.contratos_por_vencer > 0 ? 'text-rose-600' : 'text-slate-400'">
              {{ stats.contratos_por_vencer ?? 0 }}
            </span>
          </li>
          <li class="flex items-center justify-between gap-2 text-sm">
            <span class="text-slate-600 dark:text-slate-300 flex items-center gap-2">
              <AppIcon name="calculator" size="sm" class="text-indigo-500 shrink-0" />
              Planillas sin calcular
            </span>
            <span class="font-bold tabular-nums" :class="stats.planillas_pendientes > 0 ? 'text-indigo-600' : 'text-slate-400'">
              {{ stats.planillas_pendientes ?? 0 }}
            </span>
          </li>
        </ul>

        <div v-if="alertasContratos.length" class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
          <p class="text-xs font-semibold uppercase text-slate-400 mb-2">Contratos próximos a vencer</p>
          <ul class="space-y-2 max-h-36 overflow-y-auto">
            <li
              v-for="c in alertasContratos"
              :key="c.id"
              class="text-xs text-slate-600 dark:text-slate-300 flex justify-between gap-2"
            >
              <span class="truncate">{{ c.empleado }}</span>
              <span class="shrink-0 font-mono text-rose-600 dark:text-rose-400">{{ fmtFecha(c.fecha_fin) }}</span>
            </li>
          </ul>
        </div>
        <p v-else class="mt-4 text-xs text-slate-400 dark:text-slate-500">Sin contratos por vencer en los próximos 30 días.</p>

        <div v-if="stats.ultima_planilla" class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
          <p class="text-xs font-semibold uppercase text-slate-400 mb-1">Última planilla</p>
          <p class="text-sm font-semibold text-slate-800 dark:text-white truncate">{{ stats.ultima_planilla.TITULO }}</p>
          <p class="text-xs text-slate-500 mt-0.5">
            {{ stats.ultima_planilla.CALPERIODO }}
            · {{ stats.ultima_planilla.RECALCULADA ? (stats.ultima_planilla.CERRADA ? 'Cerrada' : 'Calculada') : 'Pendiente' }}
          </p>
        </div>
      </section>
    </div>

    <!-- Accesos rápidos -->
    <div v-if="!loading" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <router-link
        v-for="link in quickLinks"
        :key="link.to"
        :to="link.to"
        class="dashboard-quick-link"
      >
        <AppIcon :name="link.icon" size="md" class="text-indigo-500" />
        <span>{{ link.label }}</span>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../services/api';
import DashboardChart from '../components/dashboard/DashboardChart.vue';

const props = defineProps({
  username: { type: String, default: '' },
});

const stats = ref({});
const loading = ref(true);

const charts = computed(() => stats.value.charts ?? null);
const alertasContratos = computed(() => stats.value.alertas?.contratos_por_vencer ?? []);

const fechaHoy = computed(() => {
  try {
    return new Date().toLocaleDateString('es-SV', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  } catch {
    return '';
  }
});

const fmtMoney = (n) => {
  if (n == null || Number.isNaN(Number(n))) return '—';
  return '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const fmtFecha = (d) => {
  if (!d) return '—';
  try {
    return new Date(d + 'T12:00:00').toLocaleDateString('es-SV');
  } catch {
    return d;
  }
};

const kpis = computed(() => {
  const s = stats.value;
  return [
    {
      key: 'empleados',
      label: 'Colaboradores activos',
      value: s.empleados_activos ?? '—',
      hint: s.empleados_nuevos_mes ? `+${s.empleados_nuevos_mes} nuevos este mes` : null,
      icon: 'users',
      iconBg: 'bg-indigo-50 dark:bg-indigo-950/50 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40',
      iconColor: 'text-indigo-600 dark:text-indigo-400',
    },
    {
      key: 'nomina',
      label: 'Nómina última planilla',
      value: fmtMoney(s.nomina_ultima_planilla),
      hint: s.ultima_planilla?.TIPOPLANILLA ?? null,
      icon: 'banknote',
      iconBg: 'bg-emerald-50 dark:bg-emerald-950/50 group-hover:bg-emerald-100',
      iconColor: 'text-emerald-600 dark:text-emerald-400',
    },
    {
      key: 'planillas',
      label: 'Planillas pendientes',
      value: s.planillas_pendientes ?? '—',
      hint: s.planillas_abiertas ? `${s.planillas_abiertas} calculadas abiertas` : null,
      icon: 'calculator',
      iconBg: 'bg-violet-50 dark:bg-violet-950/50',
      iconColor: 'text-violet-600 dark:text-violet-400',
    },
    {
      key: 'incapacidades',
      label: 'Incapacidades activas',
      value: s.incapacidades_activas ?? '—',
      hint: 'Vigentes hoy',
      icon: 'clipboard-list',
      iconBg: 'bg-amber-50 dark:bg-amber-950/50',
      iconColor: 'text-amber-600 dark:text-amber-400',
    },
    {
      key: 'prestamos',
      label: 'Préstamos activos',
      value: s.prestamos_activos ?? '—',
      hint: 'Con saldo pendiente',
      icon: 'banknote',
      iconBg: 'bg-sky-50 dark:bg-sky-950/50',
      iconColor: 'text-sky-600 dark:text-sky-400',
    },
    {
      key: 'marcaciones',
      label: 'Marcaciones pendientes',
      value: s.marcaciones_pendientes ?? '—',
      hint: 'Sin procesar a asistencia',
      icon: 'clock',
      iconBg: 'bg-orange-50 dark:bg-orange-950/50',
      iconColor: 'text-orange-600 dark:text-orange-400',
    },
    {
      key: 'contratos',
      label: 'Contratos por vencer',
      value: s.contratos_por_vencer ?? '—',
      hint: 'Próximos 30 días',
      icon: 'file-text',
      iconBg: 'bg-rose-50 dark:bg-rose-950/50',
      iconColor: 'text-rose-600 dark:text-rose-400',
    },
    {
      key: 'nuevos',
      label: 'Ingresos del mes',
      value: s.empleados_nuevos_mes ?? '—',
      hint: 'Altas en el mes actual',
      icon: 'plus',
      iconBg: 'bg-teal-50 dark:bg-teal-950/50',
      iconColor: 'text-teal-600 dark:text-teal-400',
    },
  ];
});

const quickLinks = [
  { to: '/empleados', label: 'Empleados', icon: 'users' },
  { to: '/planilla', label: 'Planilla', icon: 'calculator' },
  { to: '/asistencia', label: 'Asistencia', icon: 'clock' },
  { to: '/incapacidades', label: 'Incapacidades', icon: 'clipboard-list' },
];

onMounted(async () => {
  try {
    loading.value = true;
    const res = await api.get('/dashboard/stats');
    stats.value = res.data;
  } catch {
    stats.value = {};
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.dashboard-kpi {
  @apply bg-white dark:bg-slate-800 p-4 sm:p-5 rounded-xl border border-slate-200 dark:border-slate-700 transition-shadow hover:shadow-md;
}
.dashboard-card {
  @apply bg-white dark:bg-slate-800 p-4 sm:p-5 rounded-xl border border-slate-200 dark:border-slate-700;
}
.dashboard-card-title {
  @apply text-sm sm:text-base font-bold text-slate-900 dark:text-white;
}
.dashboard-card-sub {
  @apply text-xs text-slate-500 dark:text-slate-400 mt-0.5 mb-4;
}
.dashboard-quick-link {
  @apply flex items-center gap-2 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:border-indigo-300 dark:hover:border-indigo-600 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/30 transition-colors;
}
</style>
