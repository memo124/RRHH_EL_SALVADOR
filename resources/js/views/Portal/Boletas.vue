<template>
  <PortalLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Mis boletas de pago</h1>
          <p class="page-subtitle mt-1">Boletas de planillas ya cerradas. Puede verlas o descargarlas en PDF.</p>
        </div>
      </div>

      <SkeletonTable v-if="loading" :cols="5" />

      <div v-else-if="items.length === 0" class="card-panel text-center text-sm text-slate-500 dark:text-slate-400 py-10">
        Todavía no tiene boletas de pago disponibles.
      </div>

      <div v-else class="table-shell table-scroll">
        <table v-table-cards class="table-cards w-full text-sm">
          <thead>
            <tr class="text-xs uppercase bg-slate-50 dark:bg-slate-700/50">
              <th class="px-4 py-3">Planilla</th>
              <th class="px-4 py-3">Tipo</th>
              <th class="px-4 py-3">Fecha de pago</th>
              <th class="px-4 py-3 text-right">Líquido a recibir</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y dark:divide-slate-700">
            <tr v-for="b in items" :key="b.ID_DETALLEPLANILLA">
              <td class="px-4 py-3 font-medium">{{ b.TITULO }}</td>
              <td class="px-4 py-3">{{ b.TIPOPLANILLA }}</td>
              <td class="px-4 py-3 text-xs whitespace-nowrap">{{ fmtDate(b.FECHAPAGO) }}</td>
              <td class="px-4 py-3 text-right font-bold text-emerald-600 dark:text-emerald-400">{{ fmtMoney(b.LIQUIDO_A_RECIBIR) }}</td>
              <td class="px-4 py-3 text-right space-x-1">
                <button
                  type="button"
                  data-no-lock
                  :disabled="isBusy()"
                  class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 text-xs font-semibold disabled:opacity-40 inline-flex items-center gap-1"
                  title="Ver PDF"
                  @click="verBoleta(b)"
                >
                  <AppIcon name="eye" size="xs" /> Ver
                </button>
                <button
                  type="button"
                  data-no-lock
                  :disabled="isBusy()"
                  class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 text-xs font-semibold disabled:opacity-40 inline-flex items-center gap-1"
                  title="Descargar PDF"
                  @click="descargarBoleta(b)"
                >
                  <AppIcon name="printer" size="xs" /> PDF
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <PaginationBar :page="page" :last-page="lastPage" :per-page="perPage" :total="total" :loading="loading"
          @update:page="setPage" @update:per-page="setPerPage" />
      </div>
    </div>
  </PortalLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import PortalLayout from './PortalLayout.vue';
import AppIcon from '../../components/AppIcon.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import PaginationBar from '../../components/PaginationBar.vue';
import { usePaginatedList } from '../../composables/usePaginatedList';
import { usePlanillaReports } from '../../composables/usePlanillaReports';

const { items, loading, page, perPage, total, lastPage, fetch: reload, setPage, setPerPage } =
  usePaginatedList('/portal/boletas', { perPage: 12 });

const { openPdfViewWhenReady, downloadFileWhenReady, isBusy } = usePlanillaReports();

function fmtDate(d) {
  return d ? new Date(d).toLocaleDateString('es-SV') : '—';
}

function fmtMoney(n) {
  if (n == null || Number.isNaN(Number(n))) return '—';
  return '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function verBoleta(b) {
  openPdfViewWhenReady(`/reportes/portal/boletas/${b.ID_DETALLEPLANILLA}/pdf`, {
    key: `view-boleta-${b.ID_DETALLEPLANILLA}`,
    label: 'boleta de pago',
  });
}

function descargarBoleta(b) {
  downloadFileWhenReady(`/reportes/portal/boletas/${b.ID_DETALLEPLANILLA}/pdf`, {
    key: `download-boleta-${b.ID_DETALLEPLANILLA}`,
    label: 'boleta de pago',
    fallbackName: `boleta_${b.ID_DETALLEPLANILLA}.pdf`,
    params: { download: '1' },
  });
}

onMounted(reload);
</script>
