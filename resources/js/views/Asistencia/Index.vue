<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div>
        <h1 class="page-title">Asistencia y Marcaciones</h1>
        <p class="page-subtitle">Registre marcaciones y procese la asistencia diaria.</p>
      </div>

      <div class="flex border-b border-slate-200 dark:border-slate-700">
        <button
          v-for="t in ['marcaciones', 'asistencia', 'procesar']"
          :key="t"
          @click="tab = t"
          :class="tab === t ? 'tab-btn tab-btn-active' : 'tab-btn tab-btn-inactive'"
        >
          {{ t }}
        </button>
      </div>

      <div v-if="tab === 'marcaciones'" class="space-y-4">
        <button @click="openMarcModal" class="btn-primary">+ Registrar Marcación</button>
        <SkeletonTable v-if="loadingMarc" :cols="4" :no-header="true" />
        <div v-else class="table-shell">
          <table class="table-base">
            <thead>
              <tr class="table-head-row">
                <th class="table-head-cell">Empleado</th>
                <th class="table-head-cell">Fecha/Hora</th>
                <th class="table-head-cell">Tipo</th>
                <th class="table-head-cell">Origen</th>
              </tr>
            </thead>
            <tbody class="table-body">
              <tr v-for="(m, i) in marcaciones" :key="field(m, 'ID_MARCACION', 'id_marcacion')" :class="i % 2 === 0 ? 'table-row-even' : 'table-row-odd'">
                <td class="table-body-cell font-semibold text-slate-900 dark:text-white">{{ fieldStr(m, 'NOMBRE_EMPLEADO', 'nombre_empleado') || '—' }}</td>
                <td class="table-body-cell">{{ fmtDateTime(field(m, 'FECHA_HORA_MARCACION', 'fecha_hora_marcacion')) }}</td>
                <td class="table-body-cell">{{ fieldStr(m, 'TIPO_MARCACION', 'tipo_marcacion') }}</td>
                <td class="table-body-cell">{{ fieldStr(m, 'ORIGEN', 'origen') }}</td>
              </tr>
              <tr v-if="!marcaciones.length">
                <td colspan="4" class="table-body-cell text-center text-slate-500 dark:text-slate-400 py-8">No hay marcaciones pendientes.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="tab === 'asistencia'" class="space-y-4">
        <div class="flex flex-wrap gap-4">
          <input v-model="filtro.fecha_inicio" type="date" class="input-base max-w-xs" />
          <input v-model="filtro.fecha_fin" type="date" class="input-base max-w-xs" />
          <button @click="loadAsistencia" class="btn-primary">Consultar</button>
        </div>
        <SkeletonTable v-if="loadingAsist" :cols="7" :no-header="true" />
        <div v-else class="table-shell">
          <table class="table-base">
            <thead>
              <tr class="table-head-row">
                <th class="table-head-cell">Empleado</th>
                <th class="table-head-cell">Fecha</th>
                <th class="table-head-cell">Horas</th>
                <th class="table-head-cell">HE Diurnas</th>
                <th class="table-head-cell">HE Nocturnas</th>
                <th class="table-head-cell">Descanso</th>
                <th class="table-head-cell">Estado</th>
              </tr>
            </thead>
            <tbody class="table-body">
              <tr v-for="(a, i) in asistencias" :key="field(a, 'ID_ASISTENCIA', 'id_asistencia')" :class="i % 2 === 0 ? 'table-row-even' : 'table-row-odd'">
                <td class="table-body-cell font-semibold text-slate-900 dark:text-white">{{ fieldStr(a, 'NOMBRE_EMPLEADO', 'nombre_empleado') || '—' }}</td>
                <td class="table-body-cell">{{ fmtDate(field(a, 'FECHA', 'fecha')) }}</td>
                <td class="table-body-cell">{{ field(a, 'HORAS_TRABAJADAS', 'horas_trabajadas') }}</td>
                <td class="table-body-cell">{{ field(a, 'HORAS_EXTRAS_DIURNAS', 'horas_extras_diurnas') }}</td>
                <td class="table-body-cell">{{ field(a, 'HORAS_EXTRAS_NOCTURNAS', 'horas_extras_nocturnas') }}</td>
                <td class="table-body-cell">{{ field(a, 'ES_DIA_DESCANSO', 'es_dia_descanso') ? 'Sí' : 'No' }}</td>
                <td class="table-body-cell text-xs">
                  {{ field(a, 'ES_INASISTENCIA', 'es_inasistencia') ? 'Ausencia' : field(a, 'ES_INCAPACIDAD', 'es_incapacidad') ? 'Incapacidad' : 'Normal' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="tab === 'procesar'" class="card-panel space-y-4 max-w-lg">
        <h3 class="modal-title">Procesar Marcaciones → Asistencia</h3>
        <div class="grid grid-cols-2 gap-4">
          <input v-model="procesar.fecha_inicio" type="date" class="input-base" />
          <input v-model="procesar.fecha_fin" type="date" class="input-base" />
        </div>
        <button @click="procesarAsistencia" class="btn-primary w-full bg-emerald-600 hover:bg-emerald-700">Procesar Asistencia</button>
        <p v-if="msg" class="text-sm text-emerald-600 dark:text-emerald-400">{{ msg }}</p>
      </div>

      <div v-if="showMarc" class="modal-overlay">
        <form v-submit-lock="saveMarc" class="modal-panel w-full max-w-md modal-body">
          <h3 class="modal-title">Nueva Marcación</h3>
          <AsyncSelect
            v-model="marcForm.ID_EMPLEADO"
            endpoint="/empleados/select"
            placeholder="Seleccionar empleado"
            search-placeholder="Buscar empleado…"
          />
          <input v-model="marcForm.FECHA_HORA_MARCACION" type="datetime-local" required class="input-base" />
          <AsyncSelect
            v-model="marcForm.TIPO_MARCACION"
            :options="TIPO_MARCACION_OPTIONS"
            :searchable="false"
            placeholder="Tipo de marcación"
          />
          <div class="flex justify-end gap-2">
            <button data-no-lock type="button" @click="closeMarcModal" class="btn-secondary">Cancelar</button>
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
import { TIPO_MARCACION_OPTIONS } from '../../utils/staticSelectOptions';
import { field, fieldStr } from '../../utils/fields';

const tab = ref('marcaciones');
const marcaciones = ref([]);
const asistencias = ref([]);
const showMarc = ref(false);
const msg = ref('');
const loadingMarc = ref(false);
const loadingAsist = ref(false);

const hoy = new Date().toISOString().slice(0, 10);
const filtro = ref({ fecha_inicio: hoy, fecha_fin: hoy });
const procesar = ref({ fecha_inicio: hoy, fecha_fin: hoy });

const defaultMarcForm = () => ({
  ID_EMPLEADO: null,
  FECHA_HORA_MARCACION: '',
  TIPO_MARCACION: 'ENTRADA',
});

const marcForm = ref(defaultMarcForm());

const fmtDate = (d) => (d ? new Date(d).toLocaleDateString('es-SV') : '');
const fmtDateTime = (d) => (d ? new Date(d).toLocaleString('es-SV') : '');

const loadMarcaciones = async () => {
  loadingMarc.value = true;
  try {
    marcaciones.value = (await api.get('/marcaciones/pendientes')).data;
  } finally {
    loadingMarc.value = false;
  }
};

onMounted(async () => {
  await loadMarcaciones();
});

const openMarcModal = () => {
  marcForm.value = defaultMarcForm();
  showMarc.value = true;
};

const closeMarcModal = () => {
  showMarc.value = false;
  marcForm.value = defaultMarcForm();
};

const loadAsistencia = async () => {
  loadingAsist.value = true;
  try {
    asistencias.value = (await api.get('/asistencia', { params: filtro.value })).data;
  } finally {
    loadingAsist.value = false;
  }
};

const saveMarc = async () => {
  if (!marcForm.value.ID_EMPLEADO) {
    await dialog.alert({
      title: 'Empleado requerido',
      message: 'Seleccione un empleado antes de guardar la marcación.',
      variant: 'warning',
    });
    return;
  }
  await api.post('/marcaciones', marcForm.value);
  closeMarcModal();
  await loadMarcaciones();
};

const procesarAsistencia = async () => {
  const r = await api.post('/asistencia/procesar', procesar.value);
  msg.value = r.data.message;
  await loadMarcaciones();
};
</script>
