<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Calendario RRHH</h1>
          <p class="page-subtitle mt-1">Eventos manuales y automáticos de encuestas, formularios y actividades.</p>
        </div>
        <div class="page-header-actions">
          <button @click="openModal()" class="btn-primary">+ Nuevo evento</button>
        </div>
      </div>

      <div class="flex flex-wrap gap-3 items-end">
        <div>
          <label class="form-label">Tipo</label>
          <select v-model="filtroTipo" @change="reload" class="form-input">
            <option value="">Todos</option>
            <option v-for="t in tipos" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
        </div>
        <div class="min-w-[200px]">
          <label class="form-label">Empresa</label>
          <AsyncSelect v-model="filtroEmpresa" catalog="empresas" nullable placeholder="Todas" @change="reload" />
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-xl border dark:border-slate-700 p-4">
        <FullCalendar ref="calRef" :options="calendarOptions" />
      </div>

      <AppModalShell :open="showModal" @close="closeModal">
        <div class="modal-panel w-full max-w-lg mx-auto">
          <div class="modal-header">
            <h3 class="modal-title">{{ editingId ? 'Editar evento' : 'Nuevo evento' }}</h3>
          </div>
          <form v-submit-lock="save" class="modal-body space-y-4">
            <div>
              <label class="form-label">Tipo</label>
              <select v-model="form.TIPO" class="form-input" required>
                <option v-for="t in tipos" :key="t.value" :value="t.value">{{ t.label }}</option>
              </select>
            </div>
            <div>
              <label class="form-label">Título</label>
              <input v-model="form.TITULO" class="form-input" required />
            </div>
            <div>
              <label class="form-label">Descripción</label>
              <textarea v-model="form.DESCRIPCION" class="form-input" rows="2" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="form-label">Inicio</label>
                <input v-model="form.FECHA_INICIO" type="datetime-local" class="form-input" required />
              </div>
              <div>
                <label class="form-label">Fin</label>
                <input v-model="form.FECHA_FIN" type="datetime-local" class="form-input" />
              </div>
            </div>
            <label class="flex items-center gap-2 text-sm">
              <input v-model="form.TODO_DIA" type="checkbox" /> Todo el día
            </label>
            <p v-if="modalError" class="text-sm text-red-600">{{ modalError }}</p>
            <div class="modal-footer">
              <button type="button" data-no-lock class="btn-secondary" @click="closeModal">Cancelar</button>
              <LoadingButton type="submit">Guardar</LoadingButton>
            </div>
          </form>
        </div>
      </AppModalShell>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import DashboardLayout from '../Dashboard.vue';
import AppModalShell from '../../components/AppModalShell.vue';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';
import { getApiErrorMessage } from '../../utils/apiError';

const router = useRouter();
const calRef = ref(null);
const tipos = ref([]);
const events = ref([]);
const filtroTipo = ref('');
const filtroEmpresa = ref(null);
const showModal = ref(false);
const editingId = ref(null);
const modalError = ref('');
const form = ref(emptyForm());

function emptyForm() {
  return {
    TIPO: 'manual',
    TITULO: '',
    DESCRIPCION: '',
    FECHA_INICIO: '',
    FECHA_FIN: '',
    TODO_DIA: false,
  };
}

const calendarOptions = computed(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin],
  initialView: 'dayGridMonth',
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,listWeek',
  },
  locale: 'es',
  events: events.value,
  eventClick: async (info) => {
    const props = info.event.extendedProps || {};
    const route = props.route;
    const isManual = !props.origenTipo && (props.tipo === 'manual' || props.tipo === 'reunion_rrhh' || props.tipo === 'feriado');

    if (isManual) {
      const ok = await dialog.confirm({
        title: info.event.title,
        message: (props.descripcion || 'Sin descripción.') + '\n\n¿Desea eliminar este evento?',
        confirmText: 'Eliminar',
        cancelText: 'Cerrar',
        variant: 'danger',
      });
      if (ok) {
        try {
          await api.delete(`/calendario/eventos/${info.event.id}`);
          await reload();
        } catch (err) {
          await dialog.alert({ title: 'Error', message: getApiErrorMessage(err), variant: 'danger' });
        }
      }
      return;
    }

    if (route) {
      router.push(route);
      return;
    }
    dialog.alert({
      title: info.event.title,
      message: props.descripcion || 'Sin descripción.',
      variant: 'info',
    });
  },
  datesSet: () => reload(),
}));

async function loadTipos() {
  const { data } = await api.get('/calendario/tipos');
  tipos.value = data;
}

async function reload() {
  const cal = calRef.value?.getApi?.();
  const params = {};
  if (cal) {
    params.start = cal.view.activeStart.toISOString();
    params.end = cal.view.activeEnd.toISOString();
  }
  if (filtroTipo.value) params.tipo = filtroTipo.value;
  if (filtroEmpresa.value) params.ID_EMPRESA = filtroEmpresa.value;
  const { data } = await api.get('/calendario/eventos', { params });
  events.value = data;
}

function openModal() {
  editingId.value = null;
  form.value = emptyForm();
  modalError.value = '';
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}

async function save() {
  modalError.value = '';
  try {
    const payload = { ...form.value };
    if (editingId.value) {
      await api.put(`/calendario/eventos/${editingId.value}`, payload);
    } else {
      await api.post('/calendario/eventos', payload);
    }
    closeModal();
    await reload();
  } catch (err) {
    modalError.value = getApiErrorMessage(err);
  }
}

onMounted(async () => {
  await loadTipos();
  await reload();
});
</script>
