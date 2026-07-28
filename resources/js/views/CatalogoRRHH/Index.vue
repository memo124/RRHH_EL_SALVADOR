<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Catálogos RRHH</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400">Catálogos base del sistema de recursos humanos.</p>
      </div>

      <!-- Tabs -->
      <div class="flex border-b border-slate-200 dark:border-slate-700 overflow-x-auto">
        <button v-for="t in tabs" :key="t.key" @click="activeTab = t.key"
          :class="activeTab === t.key ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
          class="py-3 px-5 border-b-2 text-sm font-medium transition-all whitespace-nowrap">
          {{ t.label }}
        </button>
      </div>

      <!-- Actions bar -->
      <div class="flex justify-between items-center">
        <input v-model="search" type="text" :placeholder="`Buscar ${currentTab?.label?.toLowerCase()}...`"
          class="w-full max-w-xs px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
        <button @click="openCreate" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors">
          + {{ currentTab?.addLabel }}
        </button>
      </div>

      <SkeletonTable v-if="loading" />

      <!-- Generic table -->
      <div v-else class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200">
              <th v-for="col in currentTab?.columns" :key="col.key" class="px-6 py-4">{{ col.label }}</th>
              <th class="px-6 py-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
            <tr v-for="r in filtered" :key="r[currentTab?.idKey]" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
              <td v-for="col in currentTab?.columns" :key="col.key" class="px-6 py-4">
                <template v-if="col.type === 'id'">
                  <span class="text-slate-500">{{ r[col.key] }}</span>
                </template>
                <template v-else-if="col.type === 'badge'">
                  <span :class="r[col.key] ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'" class="px-2 py-0.5 rounded text-xs font-semibold">
                    {{ r[col.key] ? 'Activo' : 'Inactivo' }}
                  </span>
                </template>
                <template v-else-if="col.type === 'bool'">
                  {{ r[col.key] ? 'Sí' : 'No' }}
                </template>
                <template v-else-if="col.type === 'name'">
                  <span class="font-semibold text-slate-900 dark:text-white">{{ r[col.key] }}</span>
                </template>
                <template v-else>
                  {{ r[col.key] ?? '—' }}
                </template>
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                <button @click="openEdit(r)" class="text-indigo-600 font-semibold text-xs hover:underline">Editar</button>
                <button @click="deleteOrInactivate(r)" class="text-rose-600 font-semibold text-xs hover:underline">
                  {{ currentTab?.deletable ? 'Eliminar' : 'Inactivar' }}
                </button>
              </td>
            </tr>
            <tr v-if="!filtered.length">
              <td :colspan="(currentTab?.columns?.length || 2) + 1" class="px-6 py-8 text-center text-slate-400 text-sm">Sin registros</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ══ DYNAMIC MODAL ════════════════════════════════════════════════════ -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-lg w-full overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-950 dark:text-white">{{ isEditing ? 'Editar' : 'Nuevo' }} {{ currentTab?.label }}</h3>
            <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">✕</button>
          </div>
          <form v-submit-lock="save" class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">

            <!-- AFP -->
            <template v-if="activeTab === 'afp'">
              <FieldInput label="Nombre AFP *" v-model="form.NOMBREAFP" :required="true" />
              <FieldInput label="Código Previsional" v-model="form.CODIGOPREVISIONAL" />
              <div class="grid grid-cols-2 gap-4">
                <FieldInput label="% Patronal *" v-model.number="form.PORCENTAJEPATRONAL" type="number" :required="true" />
                <FieldInput label="% Empleado *" v-model.number="form.PORCENTAJEEMPLEADOR" type="number" :required="true" />
              </div>
              <div class="grid grid-cols-2 gap-4">
                <FieldInput label="Devengado Máximo" v-model.number="form.DEVENGADOMAXIMO" type="number" />
                <FieldInput label="Devengado Mínimo" v-model.number="form.DEVENGADOMINIMO" type="number" />
              </div>
              <FieldCheck label="Activo" v-model="form.ESACTIVO" />
            </template>

            <!-- BANCO -->
            <template v-else-if="activeTab === 'bancos'">
              <FieldInput label="Nombre Banco *" v-model="form.NOMBREBANCO" :required="true" />
              <FieldInput label="Alias" v-model="form.ALIAS" />
              <FieldCheck label="Banco Activo" v-model="form.BANCOACTIVO" />
            </template>

            <!-- ESTADO CIVIL -->
            <template v-else-if="activeTab === 'estado-civil'">
              <FieldInput label="Nombre Estado Civil *" v-model="form.NOMBREESTADOCIVIL" :required="true" />
            </template>

            <!-- EDUCACIÓN -->
            <template v-else-if="activeTab === 'educacion'">
              <FieldInput label="Descripción *" v-model="form.DESCRIPCION" :required="true" />
              <FieldCheck label="Activo" v-model="form.ACTIVO" />
            </template>

            <!-- PROFESIONES -->
            <template v-else-if="activeTab === 'profesiones'">
              <FieldInput label="Profesión / Oficio *" v-model="form.PROFESION_OFICIO" :required="true" />
            </template>

            <!-- PERFIL PAGO -->
            <template v-else-if="activeTab === 'perfil-pago'">
              <FieldInput label="Nombre Perfil *" v-model="form.PEFILPAGO" :required="true" />
              <div class="flex space-x-4">
                <FieldCheck label="Gratificaciones" v-model="form.GRATIFICACIONES" />
                <FieldCheck label="Extra Gratificaciones" v-model="form.EXTRA_GRATIFICACIONES" />
              </div>
            </template>

            <!-- FRECUENCIA PAGO -->
            <template v-else-if="activeTab === 'frecuencia-pago'">
              <FieldInput label="Nombre Frecuencia *" v-model="form.NOMBREFRECUENCIA" :required="true" />
              <FieldInput label="Número de Días *" v-model.number="form.NUMERODIAS" type="number" :required="true" />
            </template>

            <!-- TIPO PLANILLA -->
            <template v-else-if="activeTab === 'tipo-planilla'">
              <FieldInput label="Tipo Planilla *" v-model="form.TIPOPLANILLA" :required="true" />
              <FieldInput label="Descripción" v-model="form.DESCRIPCION" />
              <div class="grid grid-cols-2 gap-3">
                <FieldCheck label="Aplica ISSS" v-model="form.APLICA_ISSS" />
                <FieldCheck label="Aplica AFP" v-model="form.APLICA_AFP" />
                <FieldCheck label="Aplica Renta" v-model="form.APLICA_RENTA" />
                <FieldCheck label="Aplica INSAFORP" v-model="form.APLICA_INSAFORP" />
                <FieldCheck label="Es Eventual" v-model="form.ES_EVENTUAL" />
                <FieldCheck label="Activo" v-model="form.ESACTIVO" />
              </div>
            </template>

            <!-- TIPO PRÉSTAMO -->
            <template v-else-if="activeTab === 'tipo-prestamo'">
              <FieldInput label="Nombre Préstamo *" v-model="form.NOMBREPRESTAMO" :required="true" />
              <FieldInput label="Observaciones" v-model="form.OBSERVACIONES" />
            </template>

            <!-- HORAS EXTRAS -->
            <template v-else-if="activeTab === 'horas-extras'">
              <FieldInput label="Tipo de Hora Extra *" v-model="form.TIPOHORAEXTRA" :required="true" />
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-semibold uppercase mb-1">Modalidad *</label>
                  <AsyncSelect
                    v-model="form.MODALIDAD"
                    :options="MODALIDAD_HE_OPTIONS"
                    :searchable="false"
                    placeholder="Modalidad"
                  />
                </div>
                <div>
                  <label class="block text-xs font-semibold uppercase mb-1">Jornada *</label>
                  <AsyncSelect
                    v-model="form.JORNADA"
                    :options="JORNADA_HE_OPTIONS"
                    :searchable="false"
                    placeholder="Jornada"
                  />
                </div>
              </div>
              <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="form.ES_DOMINICAL" />
                <span>Aplica en día de descanso / dominical</span>
              </label>
              <div class="grid grid-cols-2 gap-4">
                <FieldInput label="% Recargo *" v-model.number="form.PORCENTAJEEXTRA" type="number" :required="true" />
                <FieldInput label="Factor *" v-model.number="form.FACTOR" type="number" step="0.0001" :required="true" />
              </div>
              <p class="text-xs text-slate-500">Diurna: 100% recargo (factor 2.00) · Nocturna: 125% recargo (factor 2.25)</p>
            </template>

            <div v-if="modalError" class="text-xs text-red-500 bg-red-50 p-2 rounded font-semibold">{{ modalError }}</div>
            <div class="flex justify-end space-x-3 pt-4 border-t">
              <button data-no-lock type="button" @click="showModal = false" class="px-4 py-2 border rounded-lg text-sm hover:bg-slate-50">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Guardar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';
import { MODALIDAD_HE_OPTIONS, JORNADA_HE_OPTIONS } from '../../utils/staticSelectOptions';

// Inline form helpers
const FieldInput = {
  props: ['label', 'modelValue', 'required', 'type', 'step'],
  emits: ['update:modelValue'],
  template: `
    <div>
      <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">{{ label }}</label>
      <input :type="type || 'text'" :value="modelValue" :step="step" @input="$emit('update:modelValue', $event.target.value)" :required="required"
        class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
    </div>
  `
};

const FieldCheck = {
  props: ['label', 'modelValue'],
  emits: ['update:modelValue'],
  template: `
    <label class="flex items-center space-x-2 text-sm cursor-pointer">
      <input type="checkbox" :checked="modelValue" @change="$emit('update:modelValue', $event.target.checked)" class="rounded text-indigo-600 focus:ring-indigo-500" />
      <span class="text-slate-700 dark:text-slate-300">{{ label }}</span>
    </label>
  `
};

const tabs = [
  { key: 'afp',           label: 'AFP',              addLabel: 'Nueva AFP',              api: 'afp',           idKey: 'ID_AFP',                 deletable: false,
    columns: [{key:'ID_AFP',type:'id',label:'ID'},{key:'NOMBREAFP',type:'name',label:'AFP'},{key:'CODIGOPREVISIONAL',type:'text',label:'Código'},{key:'PORCENTAJEPATRONAL',type:'text',label:'% Patronal'},{key:'PORCENTAJEEMPLEADOR',type:'text',label:'% Empleado'},{key:'ESACTIVO',type:'badge',label:'Estado'}],
    search: 'NOMBREAFP' },
  { key: 'bancos',        label: 'Bancos',           addLabel: 'Nuevo Banco',            api: 'bancos',        idKey: 'ID_BANCO',               deletable: false,
    columns: [{key:'ID_BANCO',type:'id',label:'ID'},{key:'NOMBREBANCO',type:'name',label:'Banco'},{key:'ALIAS',type:'text',label:'Alias'},{key:'BANCOACTIVO',type:'badge',label:'Estado'}],
    search: 'NOMBREBANCO' },
  { key: 'estado-civil',  label: 'Estado Civil',     addLabel: 'Nuevo Estado Civil',     api: 'estado-civil',  idKey: 'ID_ESTADOCIVIL',         deletable: true,
    columns: [{key:'ID_ESTADOCIVIL',type:'id',label:'ID'},{key:'NOMBREESTADOCIVIL',type:'name',label:'Estado Civil'}],
    search: 'NOMBREESTADOCIVIL' },
  { key: 'educacion',     label: 'Educación',        addLabel: 'Nuevo Nivel Educativo',  api: 'educacion',     idKey: 'ID_EDUCACIONACADEMICA',  deletable: false,
    columns: [{key:'ID_EDUCACIONACADEMICA',type:'id',label:'ID'},{key:'DESCRIPCION',type:'name',label:'Nivel Educativo'},{key:'ACTIVO',type:'badge',label:'Estado'}],
    search: 'DESCRIPCION' },
  { key: 'profesiones',   label: 'Profesiones',      addLabel: 'Nueva Profesión',        api: 'profesiones',   idKey: 'ID_PROFESIONES_OFICIOS', deletable: true,
    columns: [{key:'ID_PROFESIONES_OFICIOS',type:'id',label:'ID'},{key:'PROFESION_OFICIO',type:'name',label:'Profesión / Oficio'}],
    search: 'PROFESION_OFICIO' },
  { key: 'perfil-pago',   label: 'Perfil de Pago',   addLabel: 'Nuevo Perfil',           api: 'perfil-pago',   idKey: 'ID_PERFILPAGO',          deletable: true,
    columns: [{key:'ID_PERFILPAGO',type:'id',label:'ID'},{key:'PEFILPAGO',type:'name',label:'Perfil'},{key:'GRATIFICACIONES',type:'bool',label:'Gratific.'},{key:'EXTRA_GRATIFICACIONES',type:'bool',label:'Extra Gratific.'}],
    search: 'PEFILPAGO' },
  { key: 'frecuencia-pago',label:'Frecuencia de Pago',addLabel:'Nueva Frecuencia',      api: 'frecuencia-pago',idKey:'ID_FRECUENCIAPAGO',       deletable: true,
    columns: [{key:'ID_FRECUENCIAPAGO',type:'id',label:'ID'},{key:'NOMBREFRECUENCIA',type:'name',label:'Frecuencia'},{key:'NUMERODIAS',type:'text',label:'Días'}],
    search: 'NOMBREFRECUENCIA' },
  { key: 'tipo-planilla', label: 'Tipo Planilla',    addLabel: 'Nuevo Tipo',             api: 'tipo-planilla', idKey: 'ID_TIPOPLANILLA',        deletable: false,
    columns: [{key:'ID_TIPOPLANILLA',type:'id',label:'ID'},{key:'TIPOPLANILLA',type:'name',label:'Tipo'},{key:'APLICA_ISSS',type:'bool',label:'ISSS'},{key:'APLICA_AFP',type:'bool',label:'AFP'},{key:'APLICA_RENTA',type:'bool',label:'Renta'},{key:'ESACTIVO',type:'badge',label:'Estado'}],
    search: 'TIPOPLANILLA' },
  { key: 'tipo-prestamo', label: 'Tipo Préstamo',    addLabel: 'Nuevo Tipo',             api: 'tipo-prestamo', idKey: 'ID_TIPOPRESTAMO',        deletable: true,
    columns: [{key:'ID_TIPOPRESTAMO',type:'id',label:'ID'},{key:'NOMBREPRESTAMO',type:'name',label:'Préstamo'},{key:'OBSERVACIONES',type:'text',label:'Observaciones'}],
    search: 'NOMBREPRESTAMO' },
  { key: 'horas-extras',  label: 'Horas Extras',     addLabel: 'Nuevo Tipo HE',          api: 'horas-extras',  idKey: 'ID_HORASEXTRAS',         deletable: true,
    columns: [{key:'ID_HORASEXTRAS',type:'id',label:'ID'},{key:'TIPOHORAEXTRA',type:'name',label:'Tipo'},{key:'MODALIDAD',type:'text',label:'Modalidad'},{key:'JORNADA',type:'text',label:'Jornada'},{key:'PORCENTAJEEXTRA',type:'text',label:'% Recargo'},{key:'FACTOR',type:'text',label:'Factor'}],
    search: 'TIPOHORAEXTRA' },
];

const activeTab   = ref('afp');
const currentTab  = computed(() => tabs.find(t => t.key === activeTab.value));
const loading     = ref(false);
const search      = ref('');
const showModal   = ref(false);
const isEditing   = ref(false);
const modalError  = ref('');
const form        = ref({});
const records     = ref([]);

const filtered = computed(() => {
  const key = currentTab.value?.search;
  if (!search.value || !key) return records.value;
  return records.value.filter(r => (r[key] || '').toLowerCase().includes(search.value.toLowerCase()));
});

const loadTab = async () => {
  loading.value = true;
  try {
    const res = await api.get(`/${currentTab.value.api}`);
    records.value = res.data;
  } catch (err) { console.error(err); }
  finally { loading.value = false; }
};

onMounted(loadTab);
watch(activeTab, () => { search.value = ''; loadTab(); });

const defaultForms = {
  afp:           { NOMBREAFP: '', CODIGOPREVISIONAL: '', PORCENTAJEPATRONAL: 0, PORCENTAJEEMPLEADOR: 0, DEVENGADOMAXIMO: null, DEVENGADOMINIMO: null, ESACTIVO: true },
  bancos:        { NOMBREBANCO: '', ALIAS: '', BANCOACTIVO: true },
  'estado-civil':{ NOMBREESTADOCIVIL: '' },
  educacion:     { DESCRIPCION: '', ACTIVO: true },
  profesiones:   { PROFESION_OFICIO: '' },
  'perfil-pago': { PEFILPAGO: '', GRATIFICACIONES: true, EXTRA_GRATIFICACIONES: true },
  'frecuencia-pago': { NOMBREFRECUENCIA: '', NUMERODIAS: 0 },
  'tipo-planilla': { TIPOPLANILLA: '', DESCRIPCION: '', APLICA_ISSS: true, APLICA_AFP: true, APLICA_RENTA: true, APLICA_INSAFORP: true, ES_EVENTUAL: false, ESACTIVO: true },
  'tipo-prestamo': { NOMBREPRESTAMO: '', OBSERVACIONES: '' },
  'horas-extras':  { TIPOHORAEXTRA: '', PORCENTAJEEXTRA: 100, FACTOR: 2, MODALIDAD: 'ADICIONAL', JORNADA: 'DIURNA', ES_DOMINICAL: false, CODIGO: '' },
};

const openCreate = () => {
  isEditing.value = false;
  modalError.value = '';
  form.value = { ...defaultForms[activeTab.value] };
  showModal.value = true;
};

const openEdit = (r) => {
  isEditing.value = true;
  modalError.value = '';
  form.value = { ...r };
  showModal.value = true;
};

const save = async () => {
  const tab   = currentTab.value;
  const idKey = tab.idKey;
  try {
    if (isEditing.value) {
      await api.put(`/${tab.api}/${form.value[idKey]}`, form.value);
    } else {
      await api.post(`/${tab.api}`, form.value);
    }
    showModal.value = false;
    loadTab();
  } catch (err) {
    modalError.value = err.response?.data?.errors
      ? Object.values(err.response.data.errors).flat().join(' ')
      : 'Error al guardar.';
  }
};

const deleteOrInactivate = async (r) => {
  const tab = currentTab.value;
  const label = tab.deletable ? 'eliminar' : 'inactivar';
  if (!await dialog.confirm({
    title: `Confirmar ${label}`,
    message: `¿Confirma ${label} este registro?`,
    variant: tab.deletable ? 'danger' : 'warning',
    confirmText: `Sí, ${label}`,
  })) return;
  await api.delete(`/${tab.api}/${r[tab.idKey]}`);
  loadTab();
};
</script>
