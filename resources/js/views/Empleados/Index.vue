<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="page-header">
        <div>
          <h1 class="page-title">Expedientes de Empleados</h1>
          <p class="page-subtitle">Gestione la información laboral de los empleados de la organización.</p>
        </div>
        <div class="page-header-actions">
        <button
          @click="openCreateModal"
          class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-sm transition-colors shadow-sm w-full sm:w-auto"
        >
          + Nuevo Empleado
        </button>
        </div>
      </div>

      <!-- Quick Search -->
      <div class="flex items-center justify-between bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Buscar por nombre, DUI o código..."
          class="w-full max-w-md px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
      </div>

      <!-- Loader State -->
      <SkeletonTable v-if="loading && !empleados.length" />

      <!-- Data Table -->
      <div v-else class="table-shell table-scroll">
        <div ref="empleadosScrollRef" class="max-h-[560px] overflow-y-auto">
          <table v-table-cards class="table-cards w-full text-left border-collapse">
            <thead class="sticky top-0 z-10">
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200 dark:border-slate-700">
                <th class="px-6 py-4">Código</th>
                <th class="px-6 py-4">Nombre Completo</th>
                <th class="px-6 py-4">DUI</th>
                <th class="px-6 py-4">Empresa</th>
                <th class="px-6 py-4">Cargo / Depto</th>
                <th class="px-6 py-4">Salario</th>
                <th class="px-6 py-4">Estado</th>
                <th class="px-6 py-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm text-slate-700 dark:text-slate-200">
              <tr v-if="empleadoPaddingTop > 0" aria-hidden="true">
                <td colspan="8" :style="{ height: empleadoPaddingTop + 'px', padding: 0, border: 'none' }"></td>
              </tr>
              <tr v-for="virtualRow in empleadoVirtualRows" :key="empleados[virtualRow.index].ID_EMPLEADO" :class="virtualRow.index % 2 === 0 ? 'table-row-even' : 'table-row-odd'" class="hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors">
                <td class="px-6 py-4 font-mono text-xs">{{ empleados[virtualRow.index].CODIGOEMPLEADO }}</td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">
                  {{ empleados[virtualRow.index].NOMBRES }} {{ empleados[virtualRow.index].APELLIDO_1 }} {{ empleados[virtualRow.index].APELLIDO_2 || '' }}
                </td>
                <td class="px-6 py-4">{{ empleados[virtualRow.index].DUI }}</td>
                <td class="px-6 py-4">{{ empleados[virtualRow.index].EMPRESA_NOMBRE || 'N/A' }}</td>
                <td class="px-6 py-4">
                  <div class="font-semibold">{{ empleados[virtualRow.index].CARGO_NOMBRE || 'N/A' }}</div>
                  <div class="text-xs text-slate-500">{{ empleados[virtualRow.index].DEPARTAMENTO_NOMBRE || 'N/A' }}</div>
                </td>
                <td class="px-6 py-4 font-semibold">${{ Number(empleados[virtualRow.index].SALARIOMENSUAL).toFixed(2) }}</td>
                <td class="px-6 py-4">
                  <span :class="empleados[virtualRow.index].ESACTIVO ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200' : 'bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 border border-rose-200'" class="px-2.5 py-1 rounded-full text-xs font-semibold inline-block">
                    {{ empleados[virtualRow.index].ESACTIVO ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                  <IconActionButton variant="edit" @click="editEmpleado(empleados[virtualRow.index])" />
                  <IconActionButton v-if="empleados[virtualRow.index].ESACTIVO"
                    variant="inactivate" @click="inactivateEmpleado(empleados[virtualRow.index])" />
                </td>
              </tr>
              <tr v-if="empleadoPaddingBottom > 0" aria-hidden="true">
                <td colspan="8" :style="{ height: empleadoPaddingBottom + 'px', padding: 0, border: 'none' }"></td>
              </tr>
              <tr v-if="!empleados.length && !loading">
                <td colspan="8" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">No se encontraron registros.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <PaginationBar
          :page="page"
          :last-page="lastPage"
          :per-page="perPage"
          :total="total"
          :loading="loading"
          @update:page="onPageChange"
          @update:per-page="onPerPageChange"
        />
      </div>

      <!-- Modal CRUD -->
      <AppModalShell :open="showModal" @close="closeModal">
        <div class="modal-panel modal-panel-lg w-full max-w-4xl mx-auto">
          <div class="modal-header">
            <h3 class="modal-title">{{ isEditing ? 'Editar Expediente de Empleado' : 'Registrar Nuevo Empleado' }}</h3>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white font-semibold" aria-label="Cerrar"><AppIcon name="x" size="md" /></button>
          </div>

          <div v-if="isEditing" class="flex border-b border-slate-200 dark:border-slate-700 overflow-x-auto px-4">
            <button
              v-for="t in expedienteTabs"
              :key="t.key"
              type="button"
              @click="expedienteTab = t.key"
              :class="expedienteTab === t.key ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
              class="py-2.5 px-4 border-b-2 text-sm font-medium transition-all whitespace-nowrap"
            >
              {{ t.label }}
            </button>
          </div>

          <form v-if="expedienteTab === 'datos'" v-submit-lock="saveForm" class="flex flex-col flex-1 min-h-0 overflow-hidden">
            <div class="modal-body space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <!-- Datos Personales -->
              <div class="md:col-span-3 border-b pb-2">
                <h4 class="font-bold text-indigo-600 dark:text-indigo-400 text-sm">Información Personal</h4>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Nombres *</label>
                <input v-model="form.NOMBRES" type="text" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Primer Apellido *</label>
                <input v-model="form.APELLIDO_1" type="text" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Segundo Apellido</label>
                <input v-model="form.APELLIDO_2" type="text" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Género *</label>
                <AsyncSelect v-model="form.GENERO" :options="GENERO_OPTIONS" :searchable="false" placeholder="Seleccionar género" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Fecha Nacimiento *</label>
                <input v-model="form.FECHANACIMIENTO" type="date" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">DUI *</label>
                <input v-model="form.DUI" type="text" required placeholder="00000000-0" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>

              <!-- Contacto e Identificaciones -->
              <div class="md:col-span-3 border-b pb-2 pt-2">
                <h4 class="font-bold text-indigo-600 dark:text-indigo-400 text-sm">Identificaciones y Contacto</h4>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">NIT</label>
                <input v-model="form.NIT" type="text" placeholder="0000-000000-000-0" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">ISSS (Seguro)</label>
                <input v-model="form.ISSS" type="text" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">NUP (AFP)</label>
                <input v-model="form.NUP" type="text" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Correo Electrónico</label>
                <input v-model="form.CORREOELECTRONICO" type="email" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Celular</label>
                <input v-model="form.TELEFONOCELULAR" type="text" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Dirección</label>
                <input v-model="form.DIRECCION" type="text" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>

              <!-- Estructura y Relación Laboral -->
              <div class="md:col-span-3 border-b pb-2 pt-2">
                <h4 class="font-bold text-indigo-600 dark:text-indigo-400 text-sm">Información Laboral y Contratación</h4>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Código Empleado *</label>
                <input v-model="form.CODIGOEMPLEADO" type="text" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Empresa *</label>
                <AsyncSelect v-model="form.ID_EMPRESA" catalog="empresas" placeholder="Seleccionar empresa" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Departamento *</label>
                <AsyncSelect v-model="form.ID_DEPARTAMENTO" catalog="departamentos" :params="deptoParams" placeholder="Seleccionar departamento" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Cargo *</label>
                <AsyncSelect v-model="form.ID_CARGO" catalog="cargos" :params="cargoParams" :disabled="!form.ID_DEPARTAMENTO" placeholder="Seleccionar cargo" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Tipo Contratación *</label>
                <AsyncSelect v-model="form.ID_TIPOCONTRATACION" catalog="tipos-contratacion" placeholder="Seleccionar tipo" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Fecha Ingreso *</label>
                <input v-model="form.FECHAINGRESO" type="date" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Salario Mensual *</label>
                <input v-model="form.SALARIOMENSUAL" type="number" step="0.01" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>

              <!-- Horas extras autorizadas -->
              <div class="md:col-span-3 border-b pb-2 pt-2">
                <h4 class="font-bold text-indigo-600 dark:text-indigo-400 text-sm">Horas Extras Autorizadas (por periodo de planilla)</h4>
                <p class="text-xs text-slate-500 mt-1">
                  Indique cuántas horas fijas tiene autorizadas el empleado. Lo que exceda ese cupo se clasificará como hora extra adicional al sincronizar desde asistencia.
                </p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">HE Fijas Diurnas</label>
                <input v-model.number="form.HORAS_EXTRAS_FIJAS_DIURAS" type="number" step="0.5" min="0" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
                <p class="text-[11px] text-slate-400 mt-1">Jornada diurna: 06:00 a 19:00</p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">HE Fijas Nocturnas</label>
                <input v-model.number="form.HORAS_EXTRAS_FIJAS_NOCTURNAS" type="number" step="0.5" min="0" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
                <p class="text-[11px] text-slate-400 mt-1">Jornada nocturna: 19:00 a 06:00</p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Departamento (Geográfico) *</label>
                <AsyncSelect v-model="selectedGeoDepto" catalog="departamentos-pais" placeholder="Seleccionar departamento" @change="onDeptoChange" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Municipio *</label>
                <AsyncSelect v-model="selectedGeoMuni" catalog="municipios" :params="muniParams" :disabled="!selectedGeoDepto" placeholder="Seleccionar municipio" @change="onMuniChange" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Distrito (Dirección MH) *</label>
                <AsyncSelect v-model="form.ID_DISTRITO" catalog="distritos" :params="distritoParams" :disabled="!selectedGeoMuni" placeholder="Seleccionar distrito" />
              </div>

              <!-- AFP y Banco -->
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Institución AFP</label>
                <AsyncSelect v-model="form.ID_AFP" catalog="afps" nullable placeholder="Ninguna" />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Banco Cuenta</label>
                <AsyncSelect v-model="form.ID_BANCO" catalog="bancos" nullable placeholder="Ninguno / Efectivo" />
              </div>
              <div v-if="form.ID_BANCO">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Número de Cuenta Bancaria *</label>
                <input v-model="form.NUMEROCUENTA" type="text" required placeholder="Digite número de cuenta" class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none" />
              </div>
              <div v-if="isEditing">
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Estado</label>
                <AsyncSelect v-model="form.ESACTIVO" :options="ACTIVO_BOOL_OPTIONS" :searchable="false" placeholder="Estado" />
              </div>
            </div>

            <div v-if="modalError" class="text-xs text-red-500 dark:text-red-400 font-semibold">{{ modalError }}</div>
            </div>
            <div class="modal-footer">
              <button data-no-lock type="button" @click="closeModal" class="btn-secondary">Cancelar</button>
              <button type="submit" class="btn-primary">Guardar</button>
            </div>
          </form>

          <!-- Educación -->
          <div v-else-if="expedienteTab === 'educacion'" class="flex flex-col flex-1 min-h-0 overflow-hidden">
            <div class="modal-body space-y-4">
              <div class="flex justify-end">
                <button type="button" class="btn-primary text-sm" @click="addEducacion">+ Agregar educación</button>
              </div>
              <table class="w-full text-sm">
                <thead>
                  <tr class="text-xs uppercase text-slate-500 border-b dark:border-slate-600">
                    <th class="py-2 text-left">Nivel</th>
                    <th class="py-2 text-left">Título Obtenido</th>
                    <th class="py-2 text-left">Institución</th>
                    <th class="py-2 text-left">Graduación</th>
                    <th class="py-2 text-right">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="e in educacionList" :key="e.ID_EMPLEADO_EDUCACION" class="border-b dark:border-slate-700">
                    <td class="py-2">{{ e.EDUCACION_NOMBRE || '—' }}</td>
                    <td class="py-2">{{ e.TITULO_OBTENIDO || '—' }}</td>
                    <td class="py-2">{{ e.INSTITUCION || '—' }}</td>
                    <td class="py-2">{{ fmtDate(e.FECHA_GRADUACION) }}</td>
                    <td class="py-2 text-right space-x-1">
                      <IconActionButton variant="edit" @click="editEducacion(e)" />
                      <IconActionButton variant="delete" @click="deleteEducacion(e)" />
                    </td>
                  </tr>
                  <tr v-if="!educacionList.length"><td colspan="5" class="py-4 text-center text-slate-400">Sin registros.</td></tr>
                </tbody>
              </table>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="closeModal">Cerrar</button>
            </div>
          </div>

          <!-- Certificaciones -->
          <div v-else-if="expedienteTab === 'certificaciones'" class="flex flex-col flex-1 min-h-0 overflow-hidden">
            <div class="modal-body space-y-4">
              <div class="flex justify-end">
                <button type="button" class="btn-primary text-sm" @click="addCertificacion">+ Agregar certificación</button>
              </div>
              <table class="w-full text-sm">
                <thead>
                  <tr class="text-xs uppercase text-slate-500 border-b dark:border-slate-600">
                    <th class="py-2 text-left">Nombre</th>
                    <th class="py-2 text-left">Institución</th>
                    <th class="py-2 text-left">Emisión</th>
                    <th class="py-2 text-left">Vencimiento</th>
                    <th class="py-2 text-right">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="c in certificacionList" :key="c.ID_CERTIFICACION" class="border-b dark:border-slate-700">
                    <td class="py-2">{{ c.NOMBRE }}</td>
                    <td class="py-2">{{ c.INSTITUCION || '—' }}</td>
                    <td class="py-2">{{ fmtDate(c.FECHA_EMISION) }}</td>
                    <td class="py-2">{{ fmtDate(c.FECHA_VENCIMIENTO) }}</td>
                    <td class="py-2 text-right space-x-1">
                      <IconActionButton variant="edit" @click="editCertificacion(c)" />
                      <IconActionButton variant="delete" @click="deleteCertificacion(c)" />
                    </td>
                  </tr>
                  <tr v-if="!certificacionList.length"><td colspan="5" class="py-4 text-center text-slate-400">Sin registros.</td></tr>
                </tbody>
              </table>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="closeModal">Cerrar</button>
            </div>
          </div>

          <!-- Dependientes -->
          <div v-else-if="expedienteTab === 'dependientes'" class="flex flex-col flex-1 min-h-0 overflow-hidden">
            <div class="modal-body space-y-4">
              <div class="flex justify-end">
                <button type="button" class="btn-primary text-sm" @click="addDependiente">+ Agregar dependiente</button>
              </div>
              <table class="w-full text-sm">
                <thead>
                  <tr class="text-xs uppercase text-slate-500 border-b dark:border-slate-600">
                    <th class="py-2 text-left">Nombres</th>
                    <th class="py-2 text-left">Apellidos</th>
                    <th class="py-2 text-left">Parentesco</th>
                    <th class="py-2 text-left">Nacimiento</th>
                    <th class="py-2 text-left">Documento</th>
                    <th class="py-2 text-right">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="d in dependienteList" :key="d.ID_DEPENDIENTE" class="border-b dark:border-slate-700">
                    <td class="py-2">{{ d.NOMBRES }}</td>
                    <td class="py-2">{{ d.APELLIDOS || '—' }}</td>
                    <td class="py-2">{{ d.PARENTESCO }}</td>
                    <td class="py-2">{{ fmtDate(d.FECHA_NACIMIENTO) }}</td>
                    <td class="py-2">{{ d.DOCUMENTO_IDENTIDAD || '—' }}</td>
                    <td class="py-2 text-right space-x-1">
                      <IconActionButton variant="edit" @click="editDependiente(d)" />
                      <IconActionButton variant="delete" @click="deleteDependiente(d)" />
                    </td>
                  </tr>
                  <tr v-if="!dependienteList.length"><td colspan="6" class="py-4 text-center text-slate-400">Sin registros.</td></tr>
                </tbody>
              </table>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn-secondary" @click="closeModal">Cerrar</button>
            </div>
          </div>
        </div>
      </AppModalShell>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useVirtualizer } from '@tanstack/vue-virtual';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import PaginationBar from '../../components/PaginationBar.vue';
import AppModalShell from '../../components/AppModalShell.vue';
import { usePaginatedList } from '../../composables/usePaginatedList';
import { GENERO_OPTIONS, ACTIVO_BOOL_OPTIONS } from '../../utils/staticSelectOptions';
import { getApiErrorMessage } from '../../utils/apiError';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';
import { useToast } from '../../composables/useToast';

const toast = useToast();

const {
  items: empleados,
  loading,
  search: searchQuery,
  page,
  perPage,
  total,
  lastPage,
  fetch: loadEmpleados,
  setPage,
  setSearch,
  setPerPage,
} = usePaginatedList('/empleados', { perPage: 25 });

const empleadosScrollRef = ref(null);
const empleadoVirtualizer = useVirtualizer(computed(() => ({
  count: empleados.value.length,
  getScrollElement: () => empleadosScrollRef.value,
  estimateSize: () => 56,
  overscan: 5,
})));
const empleadoVirtualRows = computed(() => empleadoVirtualizer.value.getVirtualItems());
const empleadoPaddingTop = computed(() => empleadoVirtualRows.value[0]?.start ?? 0);
const empleadoPaddingBottom = computed(() => {
  const items = empleadoVirtualRows.value;
  if (!items.length) return 0;
  return empleadoVirtualizer.value.getTotalSize() - items[items.length - 1].end;
});

const onPageChange = (p) => setPage(p);
const onPerPageChange = (n) => setPerPage(n);

const deptoParams = computed(() => (form.value.ID_EMPRESA ? { ID_EMPRESA: form.value.ID_EMPRESA } : {}));
const cargoParams = computed(() => (form.value.ID_DEPARTAMENTO ? { ID_DEPARTAMENTO: form.value.ID_DEPARTAMENTO } : {}));
const muniParams = computed(() => (selectedGeoDepto.value ? { ID_DEPARTAMENTOPAIS: selectedGeoDepto.value } : {}));
const distritoParams = computed(() => (selectedGeoMuni.value ? { ID_MUNICIPIO: selectedGeoMuni.value } : {}));

watch(searchQuery, (q) => setSearch(q));

const showModal = ref(false);
const isEditing = ref(false);
const modalError = ref('');
const selectedGeoDepto = ref('');
const selectedGeoMuni = ref('');

// ── Expediente ampliado (tabs del modal) ────────────────────────────────────
const expedienteTabs = [
  { key: 'datos', label: 'Datos' },
  { key: 'educacion', label: 'Educación' },
  { key: 'certificaciones', label: 'Certificaciones' },
  { key: 'dependientes', label: 'Dependientes' },
];
const expedienteTab = ref('datos');
const educacionList = ref([]);
const certificacionList = ref([]);
const dependienteList = ref([]);
const educacionOptions = ref([]);

const loadEducacionOptions = async () => {
  try {
    const { data } = await api.get('/catalogs/educaciones/select', { params: { per_page: 100 } });
    educacionOptions.value = data.data ?? data;
  } catch (err) {
    console.error(err);
  }
};

function fmtDate(d) {
  return d ? new Date(d).toLocaleDateString('es-SV') : '—';
}

const loadEducacion = async () => {
  if (!form.value.ID_EMPLEADO) return;
  const { data } = await api.get(`/empleados/${form.value.ID_EMPLEADO}/educacion`, { params: { per_page: 100 } });
  educacionList.value = data.data ?? data;
};

const addEducacion = async () => {
  const values = await dialog.form({
    title: 'Agregar educación',
    confirmText: 'Guardar',
    fields: [
      { name: 'ID_EDUCACIONACADEMICA', type: 'select', label: 'Nivel Educativo', options: educacionOptions.value },
      { name: 'TITULO_OBTENIDO', type: 'text', label: 'Título Obtenido', placeholder: 'Ej. Licenciatura en...' },
      { name: 'INSTITUCION', type: 'text', label: 'Institución' },
      { name: 'FECHA_GRADUACION', type: 'text', label: 'Fecha de Graduación (YYYY-MM-DD)' },
    ],
  });
  if (!values) return;
  try {
    await api.post(`/empleados/${form.value.ID_EMPLEADO}/educacion`, values);
    loadEducacion();
  } catch (err) {
    await dialog.alert({ title: 'Error', message: getApiErrorMessage(err, 'No se pudo guardar el registro.'), variant: 'danger' });
  }
};

const editEducacion = async (item) => {
  const values = await dialog.form({
    title: 'Editar educación',
    confirmText: 'Guardar',
    fields: [
      { name: 'ID_EDUCACIONACADEMICA', type: 'select', label: 'Nivel Educativo', options: educacionOptions.value, defaultValue: item.ID_EDUCACIONACADEMICA ?? '' },
      { name: 'TITULO_OBTENIDO', type: 'text', label: 'Título Obtenido', defaultValue: item.TITULO_OBTENIDO ?? '' },
      { name: 'INSTITUCION', type: 'text', label: 'Institución', defaultValue: item.INSTITUCION ?? '' },
      { name: 'FECHA_GRADUACION', type: 'text', label: 'Fecha de Graduación (YYYY-MM-DD)', defaultValue: item.FECHA_GRADUACION ? item.FECHA_GRADUACION.split('T')[0] : '' },
    ],
  });
  if (!values) return;
  try {
    await api.put(`/empleados/${form.value.ID_EMPLEADO}/educacion/${item.ID_EMPLEADO_EDUCACION}`, values);
    loadEducacion();
  } catch (err) {
    await dialog.alert({ title: 'Error', message: getApiErrorMessage(err, 'No se pudo actualizar el registro.'), variant: 'danger' });
  }
};

const deleteEducacion = async (item) => {
  if (!await dialog.confirm({
    title: 'Eliminar educación',
    message: `¿Eliminar el registro "${item.TITULO_OBTENIDO || item.EDUCACION_NOMBRE || 'educación'}"?`,
    variant: 'danger',
    confirmText: 'Sí, eliminar',
  })) return;
  try {
    await api.delete(`/empleados/${form.value.ID_EMPLEADO}/educacion/${item.ID_EMPLEADO_EDUCACION}`);
    loadEducacion();
  } catch (err) {
    await dialog.alert({ title: 'Error', message: getApiErrorMessage(err, 'No se pudo eliminar el registro.'), variant: 'danger' });
  }
};

const loadCertificaciones = async () => {
  if (!form.value.ID_EMPLEADO) return;
  const { data } = await api.get(`/empleados/${form.value.ID_EMPLEADO}/certificaciones`, { params: { per_page: 100 } });
  certificacionList.value = data.data ?? data;
};

const addCertificacion = async () => {
  const values = await dialog.form({
    title: 'Agregar certificación',
    confirmText: 'Guardar',
    fields: [
      { name: 'NOMBRE', type: 'text', label: 'Nombre', required: true },
      { name: 'INSTITUCION', type: 'text', label: 'Institución' },
      { name: 'FECHA_EMISION', type: 'text', label: 'Fecha de Emisión (YYYY-MM-DD)' },
      { name: 'FECHA_VENCIMIENTO', type: 'text', label: 'Fecha de Vencimiento (YYYY-MM-DD)' },
    ],
  });
  if (!values) return;
  try {
    await api.post(`/empleados/${form.value.ID_EMPLEADO}/certificaciones`, values);
    loadCertificaciones();
  } catch (err) {
    await dialog.alert({ title: 'Error', message: getApiErrorMessage(err, 'No se pudo guardar el registro.'), variant: 'danger' });
  }
};

const editCertificacion = async (item) => {
  const values = await dialog.form({
    title: 'Editar certificación',
    confirmText: 'Guardar',
    fields: [
      { name: 'NOMBRE', type: 'text', label: 'Nombre', required: true, defaultValue: item.NOMBRE ?? '' },
      { name: 'INSTITUCION', type: 'text', label: 'Institución', defaultValue: item.INSTITUCION ?? '' },
      { name: 'FECHA_EMISION', type: 'text', label: 'Fecha de Emisión (YYYY-MM-DD)', defaultValue: item.FECHA_EMISION ? item.FECHA_EMISION.split('T')[0] : '' },
      { name: 'FECHA_VENCIMIENTO', type: 'text', label: 'Fecha de Vencimiento (YYYY-MM-DD)', defaultValue: item.FECHA_VENCIMIENTO ? item.FECHA_VENCIMIENTO.split('T')[0] : '' },
    ],
  });
  if (!values) return;
  try {
    await api.put(`/empleados/${form.value.ID_EMPLEADO}/certificaciones/${item.ID_CERTIFICACION}`, values);
    loadCertificaciones();
  } catch (err) {
    await dialog.alert({ title: 'Error', message: getApiErrorMessage(err, 'No se pudo actualizar el registro.'), variant: 'danger' });
  }
};

const deleteCertificacion = async (item) => {
  if (!await dialog.confirm({
    title: 'Eliminar certificación',
    message: `¿Eliminar "${item.NOMBRE}"?`,
    variant: 'danger',
    confirmText: 'Sí, eliminar',
  })) return;
  try {
    await api.delete(`/empleados/${form.value.ID_EMPLEADO}/certificaciones/${item.ID_CERTIFICACION}`);
    loadCertificaciones();
  } catch (err) {
    await dialog.alert({ title: 'Error', message: getApiErrorMessage(err, 'No se pudo eliminar el registro.'), variant: 'danger' });
  }
};

const loadDependientes = async () => {
  if (!form.value.ID_EMPLEADO) return;
  const { data } = await api.get(`/empleados/${form.value.ID_EMPLEADO}/dependientes`, { params: { per_page: 100 } });
  dependienteList.value = data.data ?? data;
};

const addDependiente = async () => {
  const values = await dialog.form({
    title: 'Agregar dependiente',
    confirmText: 'Guardar',
    fields: [
      { name: 'NOMBRES', type: 'text', label: 'Nombres', required: true },
      { name: 'APELLIDOS', type: 'text', label: 'Apellidos' },
      { name: 'PARENTESCO', type: 'text', label: 'Parentesco', required: true, placeholder: 'Ej. Hijo/a, Cónyuge, Padre, Madre' },
      { name: 'FECHA_NACIMIENTO', type: 'text', label: 'Fecha de Nacimiento (YYYY-MM-DD)' },
      { name: 'DOCUMENTO_IDENTIDAD', type: 'text', label: 'Documento de Identidad' },
    ],
  });
  if (!values) return;
  try {
    await api.post(`/empleados/${form.value.ID_EMPLEADO}/dependientes`, values);
    loadDependientes();
  } catch (err) {
    await dialog.alert({ title: 'Error', message: getApiErrorMessage(err, 'No se pudo guardar el registro.'), variant: 'danger' });
  }
};

const editDependiente = async (item) => {
  const values = await dialog.form({
    title: 'Editar dependiente',
    confirmText: 'Guardar',
    fields: [
      { name: 'NOMBRES', type: 'text', label: 'Nombres', required: true, defaultValue: item.NOMBRES ?? '' },
      { name: 'APELLIDOS', type: 'text', label: 'Apellidos', defaultValue: item.APELLIDOS ?? '' },
      { name: 'PARENTESCO', type: 'text', label: 'Parentesco', required: true, defaultValue: item.PARENTESCO ?? '' },
      { name: 'FECHA_NACIMIENTO', type: 'text', label: 'Fecha de Nacimiento (YYYY-MM-DD)', defaultValue: item.FECHA_NACIMIENTO ? item.FECHA_NACIMIENTO.split('T')[0] : '' },
      { name: 'DOCUMENTO_IDENTIDAD', type: 'text', label: 'Documento de Identidad', defaultValue: item.DOCUMENTO_IDENTIDAD ?? '' },
    ],
  });
  if (!values) return;
  try {
    await api.put(`/empleados/${form.value.ID_EMPLEADO}/dependientes/${item.ID_DEPENDIENTE}`, values);
    loadDependientes();
  } catch (err) {
    await dialog.alert({ title: 'Error', message: getApiErrorMessage(err, 'No se pudo actualizar el registro.'), variant: 'danger' });
  }
};

const deleteDependiente = async (item) => {
  if (!await dialog.confirm({
    title: 'Eliminar dependiente',
    message: `¿Eliminar a "${item.NOMBRES}"?`,
    variant: 'danger',
    confirmText: 'Sí, eliminar',
  })) return;
  try {
    await api.delete(`/empleados/${form.value.ID_EMPLEADO}/dependientes/${item.ID_DEPENDIENTE}`);
    loadDependientes();
  } catch (err) {
    await dialog.alert({ title: 'Error', message: getApiErrorMessage(err, 'No se pudo eliminar el registro.'), variant: 'danger' });
  }
};

watch(expedienteTab, (tab) => {
  if (tab === 'educacion') loadEducacion();
  else if (tab === 'certificaciones') loadCertificaciones();
  else if (tab === 'dependientes') loadDependientes();
});

const catalogs = ref({
  empresas: [],
  departamentos: [],
  cargos: [],
  tipos_contratacion: [],
  afps: [],
  bancos: [],
  departamentos_geograficos: [],
  municipios: [],
  distritos: []
});

const form = ref({
  ID_EMPLEADO: null,
  ID_EMPRESA: '',
  ID_DEPARTAMENTO: '',
  ID_CARGO: '',
  ID_TIPOCONTRATACION: '',
  ID_DISTRITO: '',
  ID_AFP: null,
  ID_BANCO: null,
  CODIGOEMPLEADO: '',
  NOMBRES: '',
  APELLIDO_1: '',
  APELLIDO_2: '',
  DUI: '',
  NIT: '',
  ISSS: '',
  NUP: '',
  GENERO: 'M',
  FECHANACIMIENTO: '',
  FECHAINGRESO: '',
  SALARIOMENSUAL: 0,
  HORAS_EXTRAS_FIJAS_DIURAS: 0,
  HORAS_EXTRAS_FIJAS_NOCTURNAS: 0,
  CORREOELECTRONICO: '',
  TELEFONOCELULAR: '',
  DIRECCION: '',
  NUMEROCUENTA: '',
  ESACTIVO: true
});

const loadCatalogs = async () => {
  try {
    const res = await api.get('/empleados/catalogs');
    catalogs.value = res.data;
  } catch (err) {
    console.error(err);
  }
};

onMounted(() => {
  loadEmpleados();
  loadCatalogs();
  loadEducacionOptions();
});

const filteredGeoMunis = computed(() => {
  if (!selectedGeoDepto.value) return [];
  return catalogs.value.municipios.filter(m => m.ID_DEPARTAMENTOPAIS === selectedGeoDepto.value);
});

const filteredGeoDistritos = computed(() => {
  if (!selectedGeoMuni.value) return [];
  return catalogs.value.distritos.filter(d => d.ID_MUNICIPIO === selectedGeoMuni.value);
});

const onDeptoChange = () => {
  selectedGeoMuni.value = '';
  form.value.ID_DISTRITO = '';
};

const onMuniChange = () => {
  form.value.ID_DISTRITO = '';
};

// Auto generate employee code based on selected company, surnames, and DUI
const autoGenerateEmployeeCode = () => {
  if (isEditing.value) return;

  const selectedEmp = catalogs.value.empresas.find(e => e.ID_EMPRESA === form.value.ID_EMPRESA);
  const companyPart = selectedEmp ? (selectedEmp.ABREVIATURA || selectedEmp.NOMBREEMPRESA.substring(0, 3)).toUpperCase() : 'EMP';

  const ap1 = form.value.APELLIDO_1 ? form.value.APELLIDO_1.trim().charAt(0).toUpperCase() : '';
  const ap2 = form.value.APELLIDO_2 ? form.value.APELLIDO_2.trim().charAt(0).toUpperCase() : '';
  const duiPart = form.value.DUI ? form.value.DUI.replace(/[^0-9]/g, '') : '';

  if (duiPart) {
    form.value.CODIGOEMPLEADO = `${companyPart}-${ap1}${ap2}-${duiPart}`;
  } else {
    form.value.CODIGOEMPLEADO = '';
  }
};

watch(
  () => [form.value.ID_EMPRESA, form.value.APELLIDO_1, form.value.APELLIDO_2, form.value.DUI],
  () => {
    autoGenerateEmployeeCode();
  },
  { deep: true }
);

const openCreateModal = () => {
  isEditing.value = false;
  modalError.value = '';
  selectedGeoDepto.value = '';
  selectedGeoMuni.value = '';
  expedienteTab.value = 'datos';
  educacionList.value = [];
  certificacionList.value = [];
  dependienteList.value = [];
  form.value = {
    ID_EMPLEADO: null,
    ID_EMPRESA: catalogs.value.empresas[0]?.ID_EMPRESA || '',
    ID_DEPARTAMENTO: catalogs.value.departamentos[0]?.ID_DEPARTAMENTO || '',
    ID_CARGO: catalogs.value.cargos[0]?.ID_CARGO || '',
    ID_TIPOCONTRATACION: catalogs.value.tipos_contratacion[0]?.ID_TIPOCONTRATACION || '',
    ID_DISTRITO: '',
    ID_AFP: null,
    ID_BANCO: null,
    CODIGOEMPLEADO: '',
    NOMBRES: '',
    APELLIDO_1: '',
    APELLIDO_2: '',
    DUI: '',
    NIT: '',
    ISSS: '',
    NUP: '',
    GENERO: 'M',
    FECHANACIMIENTO: '',
    FECHAINGRESO: new Date().toISOString().split('T')[0],
    SALARIOMENSUAL: 365.00,
    HORAS_EXTRAS_FIJAS_DIURAS: 0,
    HORAS_EXTRAS_FIJAS_NOCTURNAS: 0,
    CORREOELECTRONICO: '',
    TELEFONOCELULAR: '',
    DIRECCION: '',
    NUMEROCUENTA: '',
    ESACTIVO: true
  };
  showModal.value = true;
};

const resolveGeoHierarchy = (distritoId) => {
  const dist = catalogs.value.distritos.find(d => d.ID_DISTRITO === distritoId);
  if (dist) {
    selectedGeoMuni.value = dist.ID_MUNICIPIO;
    const muni = catalogs.value.municipios.find(m => m.ID_MUNICIPIO === dist.ID_MUNICIPIO);
    if (muni) {
      selectedGeoDepto.value = muni.ID_DEPARTAMENTOPAIS;
    }
  }
};

const editEmpleado = (emp) => {
  isEditing.value = false; // set false temporarily to not trigger code auto-generation during hydration
  modalError.value = '';
  expedienteTab.value = 'datos';
  educacionList.value = [];
  certificacionList.value = [];
  dependienteList.value = [];
  form.value = {
    ...emp,
    FECHANACIMIENTO: emp.FECHANACIMIENTO ? emp.FECHANACIMIENTO.split('T')[0] : '',
    FECHAINGRESO: emp.FECHAINGRESO ? emp.FECHAINGRESO.split('T')[0] : ''
  };
  resolveGeoHierarchy(emp.ID_DISTRITO);
  isEditing.value = true;
  showModal.value = true;
};

const saveForm = async () => {
  try {
    if (isEditing.value) {
      await api.put(`/empleados/${form.value.ID_EMPLEADO}`, form.value);
    } else {
      await api.post('/empleados', form.value);
    }
    showModal.value = false;
    loadEmpleados();
  } catch (err) {
    if (err.response?.data?.errors) {
      modalError.value = Object.values(err.response.data.errors).flat().join(' ');
    } else {
      modalError.value = 'Ocurrió un error al guardar el registro.';
    }
  }
};

const inactivateEmpleado = async (emp) => {
  if (!await dialog.confirm({
    title: 'Inactivar empleado',
    message: `¿Está seguro de inactivar a ${emp.NOMBRES}?`,
    variant: 'danger',
    confirmText: 'Sí, inactivar',
  })) return;
  try {
    await api.delete(`/empleados/${emp.ID_EMPLEADO}`);
    loadEmpleados();
  } catch {
    toast.error('Error', 'No se pudo inactivar el empleado.');
  }
};

const closeModal = () => {
  showModal.value = false;
};
</script>
