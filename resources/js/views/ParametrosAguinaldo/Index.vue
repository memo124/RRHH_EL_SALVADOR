<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="flex flex-wrap justify-between items-center gap-4">
        <div>
          <h1 class="page-title">Parámetros de Aguinaldo</h1>
          <p class="page-subtitle">Días de aguinaldo según antigüedad (Ley de El Salvador).</p>
        </div>
        <div class="flex gap-2">
          <button @click="seedDefault" class="btn-secondary">Cargar Tabla Legal</button>
          <button @click="openModal" class="btn-primary">+ Agregar</button>
        </div>
      </div>

      <AsyncSelect
        v-model="filtroEmpresa"
        catalog="empresas"
        nullable
        placeholder="Todas las empresas"
        wrapper-class="max-w-xs"
        @change="reload"
      />

      <SkeletonTable v-if="loading" :cols="4" />

      <div v-else class="table-shell">
        <table v-table-cards class="table-cards table-base">
          <thead>
            <tr class="table-head-row">
              <th class="table-head-cell">Empresa</th>
              <th class="table-head-cell">Desde (años)</th>
              <th class="table-head-cell">Hasta (años)</th>
              <th class="table-head-cell">Días</th>
            </tr>
          </thead>
          <tbody class="table-body">
            <tr v-for="(p, i) in items" :key="field(p, 'ID_PARAMETRO_AGUINALDO', 'id_parametro_aguinaldo')" :class="i % 2 === 0 ? 'table-row-even' : 'table-row-odd'">
              <td class="table-body-cell">{{ fieldStr(p, 'NOMBREEMPRESA', 'nombreempresa') }}</td>
              <td class="table-body-cell">{{ field(p, 'DESDE_ANOS', 'desde_anos') }}</td>
              <td class="table-body-cell">{{ field(p, 'HASTA_ANOS', 'hasta_anos') }}</td>
              <td class="table-body-cell font-bold text-slate-900 dark:text-white">{{ field(p, 'NUMERO_DIAS', 'numero_dias') }}</td>
            </tr>
          </tbody>
        </table>
        <PaginationBar
          :page="page"
          :last-page="lastPage"
          :per-page="perPage"
          :total="total"
          :loading="loading"
          @update:page="setPage"
          @update:per-page="setPerPage"
        />
      </div>

      <AppModalShell :open="showModal" @close="closeModal">
        <form v-submit-lock="save" class="modal-panel w-full max-w-md mx-auto modal-body">
          <h3 class="modal-title">Nuevo parámetro</h3>
          <AsyncSelect
            v-model="form.ID_EMPRESA"
            catalog="empresas"
            placeholder="Seleccionar empresa"
          />
          <div class="grid grid-cols-3 gap-4">
            <input v-model.number="form.DESDE_ANOS" type="number" min="0" placeholder="Desde" required class="input-base" />
            <input v-model.number="form.HASTA_ANOS" type="number" min="0" placeholder="Hasta" required class="input-base" />
            <input v-model.number="form.NUMERO_DIAS" type="number" min="1" placeholder="Días" required class="input-base" />
          </div>
          <div class="flex justify-end gap-2">
            <button data-no-lock type="button" @click="closeModal" class="btn-secondary">Cancelar</button>
            <button type="submit" class="btn-primary">Guardar</button>
          </div>
        </form>
      </AppModalShell>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import AppModalShell from '../../components/AppModalShell.vue';
import PaginationBar from '../../components/PaginationBar.vue';
import { usePaginatedList } from '../../composables/usePaginatedList';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';
import { field, fieldStr } from '../../utils/fields';

const showModal = ref(false);
const filtroEmpresa = ref(null);

const listParams = computed(() => (filtroEmpresa.value ? { ID_EMPRESA: filtroEmpresa.value } : {}));

const {
  items,
  loading,
  page,
  perPage,
  total,
  lastPage,
  fetch: reload,
  setPage,
  setPerPage,
} = usePaginatedList('/parametros-aguinaldo', { perPage: 25, params: listParams });

const defaultForm = () => ({ ID_EMPRESA: null, DESDE_ANOS: 0, HASTA_ANOS: 0, NUMERO_DIAS: 15 });
const form = ref(defaultForm());

watch(filtroEmpresa, () => { reload(); });
onMounted(reload);

const openModal = () => {
  form.value = defaultForm();
  if (filtroEmpresa.value) form.value.ID_EMPRESA = filtroEmpresa.value;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  form.value = defaultForm();
};

const save = async () => {
  await api.post('/parametros-aguinaldo', form.value);
  closeModal();
  reload();
};

const seedDefault = async () => {
  const id = filtroEmpresa.value;
  if (!id) {
    await dialog.alert({
      title: 'Empresa requerida',
      message: 'Seleccione una empresa para cargar la tabla legal.',
      variant: 'warning',
    });
    return;
  }
  await api.post(`/parametros-aguinaldo/seed/${id}`);
  reload();
};
</script>
