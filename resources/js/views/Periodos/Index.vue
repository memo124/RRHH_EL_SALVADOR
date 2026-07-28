<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="flex flex-wrap justify-between items-center gap-4">
        <div>
          <h1 class="page-title">Periodos Laborales</h1>
          <p class="page-subtitle">Administre los periodos de pago del sistema.</p>
        </div>
        <div class="flex gap-2">
          <button @click="generarAnio" class="btn-secondary">Generar Año</button>
          <button @click="openModal" class="btn-primary">+ Nuevo Periodo</button>
        </div>
      </div>

      <SkeletonTable v-if="loading" :cols="5" />

      <div v-else class="table-shell">
        <table class="table-base">
          <thead>
            <tr class="table-head-row">
              <th class="table-head-cell">Periodo</th>
              <th class="table-head-cell">Inicio</th>
              <th class="table-head-cell">Fin</th>
              <th class="table-head-cell">Días</th>
              <th class="table-head-cell">Estado</th>
            </tr>
          </thead>
          <tbody class="table-body">
            <tr v-for="(p, i) in periodos" :key="field(p, 'ID_PERIODO', 'id_periodo')" :class="i % 2 === 0 ? 'table-row-even' : 'table-row-odd'">
              <td class="table-body-cell font-semibold text-slate-900 dark:text-white">{{ fieldStr(p, 'CALPERIODO', 'calperiodo') }}</td>
              <td class="table-body-cell">{{ fmtDate(field(p, 'FECHAINICIO', 'fechainicio')) }}</td>
              <td class="table-body-cell">{{ fmtDate(field(p, 'FECHAFIN', 'fechafin')) }}</td>
              <td class="table-body-cell">{{ field(p, 'DIAS', 'dias') }}</td>
              <td class="table-body-cell">
                <span :class="field(p, 'ESACTIVO', 'esactivo') ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400'" class="text-xs font-semibold">
                  {{ field(p, 'ESACTIVO', 'esactivo') ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="showModal" class="modal-overlay">
        <form v-submit-lock="save" class="modal-panel w-full max-w-md modal-body">
          <h3 class="modal-title">Nuevo Periodo</h3>
          <input v-model="form.CALPERIODO" placeholder="Nombre (ej. Agosto 2026)" required class="input-base" />
          <div class="grid grid-cols-2 gap-4">
            <input v-model="form.FECHAINICIO" type="date" required class="input-base" />
            <input v-model="form.FECHAFIN" type="date" required class="input-base" />
          </div>
          <div class="flex justify-end gap-2">
            <button data-no-lock type="button" @click="closeModal" class="btn-secondary">Cancelar</button>
            <button type="submit" class="btn-primary">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';
import { field, fieldStr } from '../../utils/fields';

const periodos = ref([]);
const showModal = ref(false);
const loading = ref(false);
const defaultForm = () => ({ CALPERIODO: '', FECHAINICIO: '', FECHAFIN: '' });
const form = ref(defaultForm());

const fmtDate = (d) => (d ? new Date(d).toLocaleDateString('es-SV') : '');

const load = async () => {
  loading.value = true;
  try {
    periodos.value = (await api.get('/periodos-laborales')).data;
  } finally {
    loading.value = false;
  }
};

onMounted(load);

const openModal = () => {
  form.value = defaultForm();
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  form.value = defaultForm();
};

const save = async () => {
  await api.post('/periodos-laborales', form.value);
  closeModal();
  load();
};

const generarAnio = async () => {
  const anio = await dialog.prompt({
    title: 'Generar períodos',
    message: 'Ingrese el año para generar los períodos laborales:',
    inputLabel: 'Año',
    inputType: 'number',
    inputDefault: String(new Date().getFullYear()),
    inputRequired: true,
    confirmText: 'Generar',
  });
  if (anio) {
    await api.post('/periodos-laborales/generar', { anio });
    load();
  }
};
</script>
