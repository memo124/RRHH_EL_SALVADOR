<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="flex flex-wrap justify-between items-center gap-4">
        <div>
          <h1 class="page-title">Horarios de Trabajo</h1>
          <p class="page-subtitle">Configure horarios semanales por empresa.</p>
        </div>
        <button @click="openModal" class="btn-primary">+ Nuevo Horario</button>
      </div>

      <SkeletonTable v-if="loading" :cols="5" :rows="3" />

      <template v-else>
      <div v-for="h in horarios" :key="field(h, 'ID_HORARIO', 'id_horario')" class="card-panel">
        <div class="flex justify-between mb-3">
          <div>
            <h3 class="font-bold text-slate-900 dark:text-white">{{ fieldStr(h, 'NOMBRE_HORARIO', 'nombre_horario') }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              {{ fieldStr(h, 'NOMBREEMPRESA', 'nombreempresa') }} · Tolerancia: {{ field(h, 'TOLERANCIA_ENTRADA_MINUTOS', 'tolerancia_entrada_minutos') }} min
            </p>
          </div>
          <span :class="field(h, 'ESACTIVO', 'esactivo') ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'" class="text-xs font-semibold">
            {{ field(h, 'ESACTIVO', 'esactivo') ? 'Activo' : 'Inactivo' }}
          </span>
        </div>
        <div class="table-shell">
          <table class="table-base text-xs">
            <thead>
              <tr class="table-head-row">
                <th class="table-head-cell">Día</th>
                <th class="table-head-cell">Entrada</th>
                <th class="table-head-cell">Salida</th>
                <th class="table-head-cell">Almuerzo</th>
                <th class="table-head-cell">Descanso</th>
              </tr>
            </thead>
            <tbody class="table-body">
              <tr v-for="d in h.detalle" :key="field(d, 'ID_HORARIODETALLE', 'id_horariodetalle')" class="table-row-even">
                <td class="table-body-cell">{{ dias[field(d, 'DIA_SEMANA', 'dia_semana')] }}</td>
                <td class="table-body-cell">{{ String(field(d, 'HORA_ENTRADA', 'hora_entrada') || '').slice(0, 5) }}</td>
                <td class="table-body-cell">{{ String(field(d, 'HORA_SALIDA', 'hora_salida') || '').slice(0, 5) }}</td>
                <td class="table-body-cell">{{ field(d, 'TIEMPO_ALMUERZO_MINUTOS', 'tiempo_almuerzo_minutos') }} min</td>
                <td class="table-body-cell">{{ field(d, 'ES_DIA_DESCANSO', 'es_dia_descanso') ? 'Sí' : 'No' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      </template>

      <div v-if="showModal" class="modal-overlay">
        <form v-submit-lock="save" class="modal-panel w-full max-w-2xl modal-body">
          <h3 class="modal-title">Nuevo Horario</h3>
          <AsyncSelect
            v-model="form.ID_EMPRESA"
            catalog="empresas"
            placeholder="Seleccionar empresa"
          />
          <input v-model="form.NOMBRE_HORARIO" placeholder="Nombre del horario" required class="input-base" />
          <div class="space-y-2 max-h-64 overflow-y-auto">
            <div v-for="(d, i) in form.detalle" :key="i" class="grid grid-cols-6 gap-2 items-center text-xs text-slate-700 dark:text-slate-200">
              <span class="font-semibold">{{ dias[d.DIA_SEMANA] }}</span>
              <input v-model="d.HORA_ENTRADA" type="time" class="input-inline" />
              <input v-model="d.HORA_SALIDA" type="time" class="input-inline" />
              <input v-model.number="d.TIEMPO_ALMUERZO_MINUTOS" type="number" class="input-inline" />
              <label class="flex items-center gap-1 col-span-2">
                <input type="checkbox" v-model="d.ES_DIA_DESCANSO" class="rounded text-indigo-600" /> Descanso
              </label>
            </div>
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
import { field, fieldStr } from '../../utils/fields';

const dias = { 1: 'Lun', 2: 'Mar', 3: 'Mié', 4: 'Jue', 5: 'Vie', 6: 'Sáb', 7: 'Dom' };
const horarios = ref([]);
const showModal = ref(false);
const loading = ref(false);

const defaultDetalle = () => [1, 2, 3, 4, 5, 6, 7].map((d) => ({
  DIA_SEMANA: d,
  HORA_ENTRADA: d <= 5 ? '08:00' : '08:00',
  HORA_SALIDA: d <= 5 ? '17:00' : '12:00',
  TIEMPO_ALMUERZO_MINUTOS: d <= 5 ? 60 : 0,
  ES_DIA_DESCANSO: d >= 6,
}));

const defaultForm = () => ({
  ID_EMPRESA: null,
  NOMBRE_HORARIO: '',
  detalle: defaultDetalle(),
});

const form = ref(defaultForm());

const load = async () => {
  loading.value = true;
  try {
    horarios.value = (await api.get('/horarios')).data;
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
  await api.post('/horarios', form.value);
  closeModal();
  load();
};
</script>
