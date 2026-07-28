<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="page-header">
        <div>
          <h1 class="page-title">Conceptos por Empleado</h1>
          <p class="page-subtitle">
            Administre préstamos, descuentos e ingresos adicionales que se aplican en la planilla.
          </p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex border-b border-slate-200 dark:border-slate-700 overflow-x-auto">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="activeTab === tab.id
            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 font-bold'
            : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
          class="py-3 px-6 border-b-2 text-sm font-medium transition-all whitespace-nowrap"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Filtro común -->
      <div class="flex flex-wrap gap-4 items-center bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
        <div class="flex-1 min-w-[200px]">
          <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Filtrar por empleado</label>
          <AsyncSelect
            v-model="filtroEmpleado"
            endpoint="/empleados/select"
            nullable
            placeholder="Todos los empleados"
            search-placeholder="Buscar empleado…"
            @change="reload"
          />
        </div>
        <label class="flex items-center gap-2 text-sm mt-5 text-slate-700 dark:text-slate-200">
          <input type="checkbox" v-model="soloActivos" @change="reload" class="rounded border-slate-300 dark:border-slate-600" />
          <span>Solo activos</span>
        </label>
      </div>

      <!-- Préstamos -->
      <div v-if="activeTab === 'prestamos'" class="space-y-4">
        <div class="flex justify-end">
          <button @click="openPrestamoModal()" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold">
            + Nuevo Préstamo
          </button>
        </div>
        <SkeletonTable v-if="loading" />
        <div v-else class="table-shell">
          <table v-table-cards class="table-cards table-base">
            <thead>
              <tr class="table-head-row">
                <th class="table-head-cell">Empleado</th>
                <th class="table-head-cell">Tipo</th>
                <th class="table-head-cell text-right">Monto</th>
                <th class="table-head-cell text-right">Cuota</th>
                <th class="table-head-cell text-right">Saldo</th>
                <th class="table-head-cell text-center">Cuotas</th>
                <th class="table-head-cell">Estado</th>
                <th class="table-head-cell text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="table-body">
              <tr v-for="(p, idx) in items" :key="p.ID_PRESTAMO" :class="idx % 2 === 0 ? 'table-row-even' : 'table-row-odd'">
                <td class="table-body-cell">
                  <div class="font-semibold text-slate-900 dark:text-white">{{ p.NOMBRE_EMPLEADO }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">{{ p.CODIGOEMPLEADO }}</div>
                </td>
                <td class="table-body-cell">{{ p.NOMBREPRESTAMO }}</td>
                <td class="table-body-cell text-right font-mono">${{ fmt(p.MONTOPRESTAMO) }}</td>
                <td class="table-body-cell text-right font-mono">${{ fmt(p.CUOTA) }}</td>
                <td class="table-body-cell text-right font-mono">${{ fmt(p.SALDO_ACTUAL) }}</td>
                <td class="table-body-cell text-center">{{ p.NUMCUOTAS }}</td>
                <td class="table-body-cell">
                  <span :class="p.PRESTAMOESTADO ? 'badge-success' : 'badge-danger'">
                    {{ p.PRESTAMOESTADO ? 'Activo' : 'Cancelado' }}
                  </span>
                </td>
                <td class="table-body-cell text-right space-x-2 whitespace-nowrap">
                  <IconActionButton variant="view" @click="openDetallePrestamo(p)" />
                  <IconActionButton variant="edit" @click="openPrestamoModal(p)" />
                  <IconActionButton v-if="p.PRESTAMOESTADO" variant="cancel" @click="cancelarPrestamo(p)" />
                </td>
              </tr>
              <tr v-if="items.length === 0">
                <td colspan="8" class="table-body-cell py-8 text-center text-slate-500 dark:text-slate-400">No hay préstamos registrados.</td>
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
      </div>

      <!-- Descuentos -->
      <div v-if="activeTab === 'descuentos'" class="space-y-4">
        <div class="flex justify-end">
          <button @click="openDescuentoModal()" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold">
            + Nuevo Descuento
          </button>
        </div>
        <SkeletonTable v-if="loading" />
        <div v-else class="table-shell">
          <table v-table-cards class="table-cards table-base">
            <thead>
              <tr class="table-head-row">
                <th class="table-head-cell">Empleado</th>
                <th class="table-head-cell">Tipo Descuento</th>
                <th class="table-head-cell text-right">Monto / %</th>
                <th class="table-head-cell">Vigencia</th>
                <th class="table-head-cell">Recurrente</th>
                <th class="table-head-cell">Estado</th>
                <th class="table-head-cell text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="table-body">
              <tr v-for="(d, idx) in items" :key="d.ID_DESCUENTOEMPLEADO" :class="idx % 2 === 0 ? 'table-row-even' : 'table-row-odd'">
                <td class="table-body-cell">
                  <div class="font-semibold text-slate-900 dark:text-white">{{ d.NOMBRE_EMPLEADO }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">{{ d.CODIGOEMPLEADO }}</div>
                </td>
                <td class="table-body-cell">{{ d.NOMBRETIPODESC }}</td>
                <td class="table-body-cell text-right font-mono">
                  {{ d.ES_PORCENTAJE ? d.PORCENTAJE + '%' : '$' + fmt(d.MONTO) }}
                </td>
                <td class="table-body-cell text-xs">
                  {{ fmtDate(d.FECHAINICIO) }} — {{ d.FECHAFIN ? fmtDate(d.FECHAFIN) : 'Indefinido' }}
                </td>
                <td class="table-body-cell">{{ d.ES_RECURRENTE ? 'Sí' : 'No' }}</td>
                <td class="table-body-cell">
                  <span :class="d.ESACTIVO ? 'badge-success' : 'badge-danger'">
                    {{ d.ESACTIVO ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="table-body-cell text-right space-x-2 whitespace-nowrap">
                  <IconActionButton variant="view" @click="openDetalleDescuento(d)" />
                  <IconActionButton variant="edit" @click="openDescuentoModal(d)" />
                  <IconActionButton v-if="d.ESACTIVO" variant="inactivate" @click="inactivarDescuento(d)" />
                </td>
              </tr>
              <tr v-if="items.length === 0">
                <td colspan="7" class="table-body-cell py-8 text-center text-slate-500 dark:text-slate-400">No hay descuentos registrados.</td>
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
      </div>

      <!-- Ingresos adicionales -->
      <div v-if="activeTab === 'ingresos'" class="space-y-4">
        <div class="flex justify-end">
          <button @click="openIngresoModal()" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold">
            + Nuevo Ingreso
          </button>
        </div>
        <SkeletonTable v-if="loading" />
        <div v-else class="table-shell">
          <table v-table-cards class="table-cards table-base">
            <thead>
              <tr class="table-head-row">
                <th class="table-head-cell">Empleado</th>
                <th class="table-head-cell">Tipo Ingreso</th>
                <th class="table-head-cell text-right">Monto</th>
                <th class="table-head-cell">Vigencia</th>
                <th class="table-head-cell">Estado</th>
                <th class="table-head-cell text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="table-body">
              <tr v-for="(i, idx) in items" :key="i.ID_OTROINGRESO" :class="idx % 2 === 0 ? 'table-row-even' : 'table-row-odd'">
                <td class="table-body-cell">
                  <div class="font-semibold text-slate-900 dark:text-white">{{ i.NOMBRE_EMPLEADO }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">{{ i.CODIGOEMPLEADO }}</div>
                </td>
                <td class="table-body-cell">{{ i.TIPOINGRESO }}</td>
                <td class="table-body-cell text-right font-mono">${{ fmt(i.MONTOINGRESO) }}</td>
                <td class="table-body-cell text-xs">
                  {{ fmtDate(i.FECHAINICIO) }} — {{ i.FECHAFIN ? fmtDate(i.FECHAFIN) : 'Indefinido' }}
                </td>
                <td class="table-body-cell">
                  <span :class="i.ESACTIVO ? 'badge-success' : 'badge-danger'">
                    {{ i.ESACTIVO ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="table-body-cell text-right space-x-2 whitespace-nowrap">
                  <IconActionButton variant="view" @click="openDetalleIngreso(i)" />
                  <IconActionButton variant="edit" @click="openIngresoModal(i)" />
                  <IconActionButton v-if="i.ESACTIVO" variant="inactivate" @click="inactivarIngreso(i)" />
                </td>
              </tr>
              <tr v-if="items.length === 0">
                <td colspan="6" class="table-body-cell py-8 text-center text-slate-500 dark:text-slate-400">No hay ingresos adicionales registrados.</td>
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
      </div>

      <!-- Modal Préstamo -->
      <AppModalShell :open="showPrestamoModal" @close="showPrestamoModal = false">
        <div class="modal-panel max-w-lg w-full mx-auto">
          <div class="modal-header">
            <h3 class="modal-title">{{ editingPrestamo ? 'Editar Préstamo' : 'Nuevo Préstamo' }}</h3>
            <button @click="showPrestamoModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white font-semibold" aria-label="Cerrar"><AppIcon name="x" size="md" /></button>
          </div>
          <form v-submit-lock="savePrestamo" class="flex flex-col flex-1 min-h-0 overflow-hidden">
            <div class="modal-body">
            <div v-if="!editingPrestamo">
              <label class="label-base">Empleado *</label>
              <AsyncSelect
                v-model="prestamoForm.ID_EMPLEADO"
                endpoint="/empleados/select"
                placeholder="Seleccionar empleado"
                search-placeholder="Buscar empleado…"
              />
            </div>
            <div v-if="!editingPrestamo" class="grid grid-cols-2 gap-4">
              <div>
                <label class="label-base">Tipo Préstamo *</label>
                <AsyncSelect
                  v-model="prestamoForm.ID_TIPOPRESTAMO"
                  catalog="tipos-prestamo"
                  placeholder="Seleccionar tipo"
                />
              </div>
              <div>
                <label class="label-base">Tipo Descuento *</label>
                <AsyncSelect
                  v-model="prestamoForm.ID_TIPODESCUENTO"
                  catalog="tipos-descuento-prestamo"
                  placeholder="Seleccionar tipo"
                />
              </div>
            </div>
            <div v-if="!editingPrestamo" class="grid grid-cols-2 gap-4">
              <div>
                <label class="label-base">Monto Total *</label>
                <input v-model.number="prestamoForm.MONTOPRESTAMO" type="number" step="0.01" min="0.01" required class="input-base" />
              </div>
              <div>
                <label class="label-base">N° Cuotas *</label>
                <input v-model.number="prestamoForm.NUMCUOTAS" type="number" min="1" required class="input-base" />
              </div>
            </div>
            <div v-if="!editingPrestamo">
              <label class="label-base">Fecha Inicio *</label>
              <input v-model="prestamoForm.FECHAINICIO" type="date" required class="input-base" />
            </div>
            <div v-if="editingPrestamo">
              <label class="label-base">Cuota Mensual</label>
              <input v-model.number="prestamoForm.CUOTA" type="number" step="0.01" min="0.01" class="input-base" />
            </div>
            <div>
              <label class="label-base">Observaciones</label>
              <textarea v-model="prestamoForm.OBSERVACIONES" rows="2" class="input-base"></textarea>
            </div>
            <p v-if="!editingPrestamo && prestamoForm.MONTOPRESTAMO && prestamoForm.NUMCUOTAS" class="text-sm text-indigo-600 dark:text-indigo-400">
              Cuota calculada: ${{ fmt(prestamoForm.MONTOPRESTAMO / prestamoForm.NUMCUOTAS) }}
            </p>
            <div v-if="modalError" class="text-xs text-red-500 dark:text-red-400 bg-red-50 dark:bg-red-900/20 p-2.5 rounded-lg border border-red-200 dark:border-red-800">{{ modalError }}</div>
            </div>
            <div class="modal-footer">
              <button data-no-lock type="button" @click="showPrestamoModal = false" class="btn-secondary">Cancelar</button>
              <button type="submit" class="btn-primary">Guardar</button>
            </div>
          </form>
        </div>
      </AppModalShell>

      <AppModalShell :open="showDescuentoModal" @close="showDescuentoModal = false">
        <div class="modal-panel max-w-lg w-full mx-auto">
          <div class="modal-header">
            <h3 class="modal-title">{{ editingDescuento ? 'Editar Descuento' : 'Nuevo Descuento' }}</h3>
            <button @click="showDescuentoModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white font-semibold" aria-label="Cerrar"><AppIcon name="x" size="md" /></button>
          </div>
          <form v-submit-lock="saveDescuento" class="flex flex-col flex-1 min-h-0 overflow-hidden">
            <div class="modal-body">
            <div v-if="!editingDescuento">
              <label class="label-base">Empleado *</label>
              <AsyncSelect
                v-model="descuentoForm.ID_EMPLEADO"
                endpoint="/empleados/select"
                placeholder="Seleccionar empleado"
                search-placeholder="Buscar empleado…"
              />
            </div>
            <div>
              <label class="label-base">Tipo Descuento *</label>
              <AsyncSelect
                v-model="descuentoForm.ID_TIPODESCUENTO"
                catalog="tipos-descuento"
                placeholder="Seleccionar tipo"
              />
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
              <input type="checkbox" v-model="descuentoForm.ES_PORCENTAJE" class="rounded border-slate-300 dark:border-slate-600" />
              <span>Descuento por porcentaje del salario</span>
            </label>
            <div v-if="descuentoForm.ES_PORCENTAJE">
              <label class="label-base">Porcentaje (%)</label>
              <input v-model.number="descuentoForm.PORCENTAJE" type="number" step="0.01" min="0" max="100" required class="input-base" />
            </div>
            <div v-else>
              <label class="label-base">Monto Fijo ($)</label>
              <input v-model.number="descuentoForm.MONTO" type="number" step="0.01" min="0" required class="input-base" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="label-base">Fecha Inicio *</label>
                <input v-model="descuentoForm.FECHAINICIO" type="date" required class="input-base" />
              </div>
              <div>
                <label class="label-base">Fecha Fin</label>
                <input v-model="descuentoForm.FECHAFIN" type="date" class="input-base" />
              </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
              <input type="checkbox" v-model="descuentoForm.ES_RECURRENTE" class="rounded border-slate-300 dark:border-slate-600" />
              <span>Aplicar en cada planilla del periodo</span>
            </label>
            <div v-if="editingDescuento">
              <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                <input type="checkbox" v-model="descuentoForm.ESACTIVO" class="rounded border-slate-300 dark:border-slate-600" />
                <span>Activo</span>
              </label>
            </div>
            <div>
              <label class="label-base">Observaciones</label>
              <input v-model="descuentoForm.OBSERVACIONES" type="text" class="input-base" />
            </div>
            <div v-if="modalError" class="text-xs text-red-500 dark:text-red-400 bg-red-50 dark:bg-red-900/20 p-2.5 rounded-lg border border-red-200 dark:border-red-800">{{ modalError }}</div>
            </div>
            <div class="modal-footer">
              <button data-no-lock type="button" @click="showDescuentoModal = false" class="btn-secondary">Cancelar</button>
              <button type="submit" class="btn-primary">Guardar</button>
            </div>
          </form>
        </div>
      </AppModalShell>

      <AppModalShell :open="showIngresoModal" @close="showIngresoModal = false">
        <div class="modal-panel max-w-lg w-full mx-auto">
          <div class="modal-header">
            <h3 class="modal-title">{{ editingIngreso ? 'Editar Ingreso' : 'Nuevo Ingreso Adicional' }}</h3>
            <button @click="showIngresoModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white font-semibold" aria-label="Cerrar"><AppIcon name="x" size="md" /></button>
          </div>
          <form v-submit-lock="saveIngreso" class="flex flex-col flex-1 min-h-0 overflow-hidden">
            <div class="modal-body">
            <div v-if="!editingIngreso">
              <label class="label-base">Empleado *</label>
              <AsyncSelect
                v-model="ingresoForm.ID_EMPLEADO"
                endpoint="/empleados/select"
                placeholder="Seleccionar empleado"
                search-placeholder="Buscar empleado…"
              />
            </div>
            <div>
              <label class="label-base">Tipo Ingreso *</label>
              <AsyncSelect
                v-model="ingresoForm.ID_TIPOINGRESO"
                catalog="tipos-ingreso"
                placeholder="Seleccionar tipo"
              />
            </div>
            <div>
              <label class="label-base">Monto ($) *</label>
              <input v-model.number="ingresoForm.MONTOINGRESO" type="number" step="0.01" min="0.01" required class="input-base" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="label-base">Fecha Inicio *</label>
                <input v-model="ingresoForm.FECHAINICIO" type="date" required class="input-base" />
              </div>
              <div>
                <label class="label-base">Fecha Fin</label>
                <input v-model="ingresoForm.FECHAFIN" type="date" class="input-base" />
              </div>
            </div>
            <div v-if="editingIngreso">
              <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                <input type="checkbox" v-model="ingresoForm.ESACTIVO" class="rounded border-slate-300 dark:border-slate-600" />
                <span>Activo</span>
              </label>
            </div>
            <div v-if="modalError" class="text-xs text-red-500 dark:text-red-400 bg-red-50 dark:bg-red-900/20 p-2.5 rounded-lg border border-red-200 dark:border-red-800">{{ modalError }}</div>
            </div>
            <div class="modal-footer">
              <button data-no-lock type="button" @click="showIngresoModal = false" class="btn-secondary">Cancelar</button>
              <button type="submit" class="btn-primary">Guardar</button>
            </div>
          </form>
        </div>
      </AppModalShell>

      <AppModalShell :open="showDetalleModal" @close="closeDetalleModal">
        <div class="modal-panel max-w-3xl w-full mx-auto">
          <div class="modal-header">
            <h3 class="modal-title">{{ detalleTitulo }}</h3>
            <button @click="closeDetalleModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-white font-semibold" aria-label="Cerrar"><AppIcon name="x" size="md" /></button>
          </div>
          <div class="modal-body space-y-4">
            <div v-if="detalleLoading" class="py-8 text-center text-slate-500 dark:text-slate-400">Cargando historial…</div>
            <template v-else>
              <div class="preview-box grid grid-cols-2 md:grid-cols-4 gap-3">
                <div v-for="item in detalleResumenItems" :key="item.label">
                  <p class="text-[10px] uppercase font-semibold text-slate-500 dark:text-slate-400">{{ item.label }}</p>
                  <p class="font-semibold text-slate-900 dark:text-white">{{ item.value }}</p>
                </div>
              </div>

              <div class="table-shell">
                <table v-table-cards class="table-cards table-base">
                  <thead>
                    <tr class="table-head-row">
                      <template v-if="detalleTipo === 'prestamo'">
                        <th class="table-head-cell">Fecha</th>
                        <th class="table-head-cell text-right">Monto</th>
                        <th class="table-head-cell">Planilla</th>
                        <th class="table-head-cell">Concepto</th>
                        <th class="table-head-cell">Origen</th>
                        <th class="table-head-cell text-right">Acciones</th>
                      </template>
                      <template v-else>
                        <th v-for="col in detalleColumnas" :key="col.key" class="table-head-cell" :class="col.align">{{ col.label }}</th>
                      </template>
                    </tr>
                  </thead>
                  <tbody class="table-body">
                    <template v-if="detalleTipo === 'prestamo'">
                      <tr v-for="(abono, idx) in detalleAbonos" :key="abono.ID_PRESTAMOABONO" :class="idx % 2 === 0 ? 'table-row-even' : 'table-row-odd'">
                        <td class="table-body-cell">{{ fmtDate(abono.FECHAABONO) }}</td>
                        <td class="table-body-cell text-right font-mono">${{ fmt(abono.MONTOABONADO) }}</td>
                        <td class="table-body-cell">{{ abono.TITULO_PLANILLA ? `#${abono.ID_PLANILLA} — ${abono.TITULO_PLANILLA}` : '—' }}</td>
                        <td class="table-body-cell">{{ abono.CONCEPTO || 'Abono' }}</td>
                        <td class="table-body-cell">{{ abono.FUERA_PLANILLA ? 'Fuera de planilla' : 'Planilla' }}</td>
                        <td class="table-body-cell text-right">
                          <IconActionButton variant="delete" @click="eliminarAbono(abono)" />
                        </td>
                      </tr>
                      <tr v-if="detalleAbonos.length === 0">
                        <td colspan="6" class="table-body-cell py-8 text-center text-slate-500 dark:text-slate-400">
                          No hay cuotas pagadas registradas aún.
                        </td>
                      </tr>
                    </template>
                    <template v-else>
                      <tr v-for="(row, idx) in detalleFilas" :key="idx" :class="idx % 2 === 0 ? 'table-row-even' : 'table-row-odd'">
                        <td v-for="col in detalleColumnas" :key="col.key" class="table-body-cell" :class="col.align">
                          {{ row[col.key] }}
                        </td>
                      </tr>
                      <tr v-if="detalleFilas.length === 0">
                        <td :colspan="detalleColumnas.length" class="table-body-cell py-8 text-center text-slate-500 dark:text-slate-400">
                          No hay registros de pago o aplicación en planilla aún.
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>
            </template>
          </div>
          <div class="modal-footer">
            <button type="button" @click="closeDetalleModal" class="btn-secondary">Cerrar</button>
          </div>
        </div>
      </AppModalShell>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import PaginationBar from '../../components/PaginationBar.vue';
import AppModalShell from '../../components/AppModalShell.vue';
import { usePaginatedList } from '../../composables/usePaginatedList';
import api from '../../services/api';
import { dialog } from '../../composables/useDialog';
import { useToast } from '../../composables/useToast';

const toast = useToast();

const tabs = [
  { id: 'prestamos', label: 'Préstamos' },
  { id: 'descuentos', label: 'Descuentos' },
  { id: 'ingresos', label: 'Ingresos Adicionales' },
];

const activeTab = ref('prestamos');
const filtroEmpleado = ref(null);
const soloActivos = ref(true);
const modalError = ref('');

const tabEndpoints = {
  prestamos: '/prestamos',
  descuentos: '/descuentos-empleado',
  ingresos: '/otros-ingresos',
};
const endpoint = computed(() => tabEndpoints[activeTab.value]);
const listParams = computed(() => {
  const p = {};
  if (filtroEmpleado.value) p.ID_EMPLEADO = filtroEmpleado.value;
  if (soloActivos.value) p.solo_activos = 1;
  return p;
});

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
  reset,
} = usePaginatedList(endpoint, { perPage: 25, params: listParams });

const showPrestamoModal = ref(false);
const showDescuentoModal = ref(false);
const showIngresoModal = ref(false);
const editingPrestamo = ref(false);
const editingDescuento = ref(false);
const editingIngreso = ref(false);

const prestamoForm = ref({});
const descuentoForm = ref({});
const ingresoForm = ref({});

const showDetalleModal = ref(false);
const detalleLoading = ref(false);
const detalleTitulo = ref('');
const detalleTipo = ref('');
const detallePrestamoId = ref(null);
const detalleResumenItems = ref([]);
const detalleColumnas = ref([]);
const detalleFilas = ref([]);
const detalleAbonos = ref([]);
const detallePrestamoMeta = ref(null);

const fmt = (v) => Number(v || 0).toFixed(2);
const fmtDate = (d) => d ? new Date(d).toLocaleDateString('es-SV') : '';

watch(activeTab, () => { reset(); reload(); });
watch([filtroEmpleado, soloActivos], () => { reset(); reload(); });
onMounted(reload);

// Préstamos
const openPrestamoModal = (p = null) => {
  editingPrestamo.value = !!p;
  modalError.value = '';
  prestamoForm.value = p
    ? { ID_PRESTAMO: p.ID_PRESTAMO, CUOTA: p.CUOTA, OBSERVACIONES: p.OBSERVACIONES || '' }
    : { ID_EMPLEADO: filtroEmpleado.value, ID_TIPOPRESTAMO: null, ID_TIPODESCUENTO: null, MONTOPRESTAMO: null, NUMCUOTAS: 12, FECHAINICIO: new Date().toISOString().slice(0, 10), OBSERVACIONES: '' };
  showPrestamoModal.value = true;
};

const savePrestamo = async () => {
  try {
    if (editingPrestamo.value) {
      await api.put(`/prestamos/${prestamoForm.value.ID_PRESTAMO}`, prestamoForm.value);
    } else {
      await api.post('/prestamos', prestamoForm.value);
    }
    showPrestamoModal.value = false;
    reload();
  } catch (err) {
    modalError.value = err.response?.data?.message || 'Error al guardar el préstamo.';
  }
};

const cancelarPrestamo = async (p) => {
  if (!await dialog.confirm({
    title: 'Cancelar préstamo',
    message: '¿Confirma cancelar este préstamo? No se podrán registrar más abonos.',
    variant: 'danger',
    confirmText: 'Sí, cancelar',
  })) return;
  await api.delete(`/prestamos/${p.ID_PRESTAMO}`);
  reload();
};

// Descuentos
const openDescuentoModal = (d = null) => {
  editingDescuento.value = !!d;
  modalError.value = '';
  descuentoForm.value = d
    ? { ...d, FECHAINICIO: d.FECHAINICIO?.slice(0, 10), FECHAFIN: d.FECHAFIN?.slice(0, 10) || '' }
    : { ID_EMPLEADO: filtroEmpleado.value, ID_TIPODESCUENTO: null, MONTO: null, PORCENTAJE: null, ES_PORCENTAJE: false, FECHAINICIO: new Date().toISOString().slice(0, 10), FECHAFIN: '', ES_RECURRENTE: true, OBSERVACIONES: '' };
  showDescuentoModal.value = true;
};

const saveDescuento = async () => {
  try {
    const payload = { ...descuentoForm.value, FECHAFIN: descuentoForm.value.FECHAFIN || null };
    if (editingDescuento.value) {
      await api.put(`/descuentos-empleado/${payload.ID_DESCUENTOEMPLEADO}`, payload);
    } else {
      await api.post('/descuentos-empleado', payload);
    }
    showDescuentoModal.value = false;
    reload();
  } catch (err) {
    modalError.value = err.response?.data?.message || 'Error al guardar el descuento.';
  }
};

const inactivarDescuento = async (d) => {
  if (!await dialog.confirm({
    title: 'Inactivar descuento',
    message: '¿Inactivar este descuento del empleado?',
    variant: 'warning',
    confirmText: 'Sí, inactivar',
  })) return;
  await api.delete(`/descuentos-empleado/${d.ID_DESCUENTOEMPLEADO}`);
  reload();
};

// Ingresos
const openIngresoModal = (i = null) => {
  editingIngreso.value = !!i;
  modalError.value = '';
  ingresoForm.value = i
    ? { ...i, FECHAINICIO: i.FECHAINICIO?.slice(0, 10), FECHAFIN: i.FECHAFIN?.slice(0, 10) || '' }
    : { ID_EMPLEADO: filtroEmpleado.value, ID_TIPOINGRESO: null, MONTOINGRESO: null, FECHAINICIO: new Date().toISOString().slice(0, 10), FECHAFIN: '' };
  showIngresoModal.value = true;
};

const saveIngreso = async () => {
  try {
    const payload = { ...ingresoForm.value, FECHAFIN: ingresoForm.value.FECHAFIN || null };
    if (editingIngreso.value) {
      await api.put(`/otros-ingresos/${payload.ID_OTROINGRESO}`, payload);
    } else {
      await api.post('/otros-ingresos', payload);
    }
    showIngresoModal.value = false;
    reload();
  } catch (err) {
    modalError.value = err.response?.data?.message || 'Error al guardar el ingreso.';
  }
};

const inactivarIngreso = async (i) => {
  if (!await dialog.confirm({
    title: 'Inactivar ingreso',
    message: '¿Inactivar este ingreso adicional del empleado?',
    variant: 'warning',
    confirmText: 'Sí, inactivar',
  })) return;
  await api.delete(`/otros-ingresos/${i.ID_OTROINGRESO}`);
  reload();
};

const closeDetalleModal = () => {
  showDetalleModal.value = false;
  detalleFilas.value = [];
  detalleAbonos.value = [];
  detalleResumenItems.value = [];
  detalleColumnas.value = [];
  detalleTipo.value = '';
  detallePrestamoId.value = null;
  detallePrestamoMeta.value = null;
};

const setPrestamoResumen = (prestamo, resumen) => {
  detalleResumenItems.value = [
    { label: 'Monto préstamo', value: `$${fmt(prestamo.MONTOPRESTAMO)}` },
    { label: 'Total abonado', value: `$${fmt(resumen.total_abonado)}` },
    { label: 'Saldo actual', value: `$${fmt(resumen.saldo_actual)}` },
    { label: 'Cuotas pagadas', value: `${resumen.cuotas_pagadas} / ${prestamo.NUMCUOTAS}` },
  ];
};

const cargarDetallePrestamo = async (prestamoId) => {
  const res = await api.get(`/prestamos/${prestamoId}`);
  const { prestamo, abonos, resumen } = res.data;
  detallePrestamoMeta.value = prestamo;
  detalleAbonos.value = abonos;
  setPrestamoResumen(prestamo, resumen);
  return res.data;
};

const openDetallePrestamo = async (p) => {
  detalleTitulo.value = `Detalle de pagos — ${p.NOMBREPRESTAMO}`;
  detalleTipo.value = 'prestamo';
  detallePrestamoId.value = p.ID_PRESTAMO;
  detalleColumnas.value = [];
  detalleFilas.value = [];
  showDetalleModal.value = true;
  detalleLoading.value = true;
  try {
    await cargarDetallePrestamo(p.ID_PRESTAMO);
  } catch (err) {
    console.error(err);
    detalleAbonos.value = [];
  } finally {
    detalleLoading.value = false;
  }
};

const eliminarAbono = async (abono) => {
  const msg = abono.ID_PLANILLA
    ? `¿Eliminar esta cuota de $${fmt(abono.MONTOABONADO)}? Se revertirá el saldo del préstamo. La planilla #${abono.ID_PLANILLA} no se modifica automáticamente.`
    : `¿Eliminar esta cuota de $${fmt(abono.MONTOABONADO)}? Se revertirá el saldo del préstamo.`;
  if (!await dialog.confirm({
    title: 'Eliminar cuota',
    message: msg,
    variant: 'danger',
    confirmText: 'Sí, eliminar',
  })) return;

  try {
    await api.delete(`/prestamos/${detallePrestamoId.value}/abonos/${abono.ID_PRESTAMOABONO}`);
    await cargarDetallePrestamo(detallePrestamoId.value);
    reload();
  } catch (err) {
    toast.error('Error', err.response?.data?.message || err.response?.data?.error || 'No se pudo eliminar la cuota.');
  }
};

const openDetalleDescuento = async (d) => {
  detalleTitulo.value = `Detalle de aplicaciones — ${d.NOMBRETIPODESC}`;
  detalleTipo.value = 'descuento';
  detallePrestamoId.value = null;
  detalleAbonos.value = [];
  detalleColumnas.value = [
    { key: 'fecha', label: 'Fecha pago', align: '' },
    { key: 'planilla', label: 'Planilla', align: '' },
    { key: 'concepto', label: 'Concepto', align: '' },
    { key: 'monto', label: 'Monto', align: 'text-right font-mono' },
  ];
  showDetalleModal.value = true;
  detalleLoading.value = true;
  try {
    const res = await api.get(`/descuentos-empleado/${d.ID_DESCUENTOEMPLEADO}/historial`);
    const { resumen, aplicaciones } = res.data;
    detalleResumenItems.value = [
      { label: 'Configurado', value: d.ES_PORCENTAJE ? `${d.PORCENTAJE}%` : `$${fmt(d.MONTO)}` },
      { label: 'Total aplicado', value: `$${fmt(resumen.total_aplicado)}` },
      { label: 'Veces aplicado', value: String(resumen.veces_aplicado) },
      { label: 'Estado', value: d.ESACTIVO ? 'Activo' : 'Inactivo' },
    ];
    detalleFilas.value = aplicaciones.map((a) => ({
      fecha: fmtDate(a.FECHAPAGO),
      planilla: `#${a.ID_PLANILLA} — ${a.TITULO}`,
      concepto: a.CONCEPTO,
      monto: `$${fmt(a.MONTO)}`,
    }));
  } catch (err) {
    console.error(err);
    detalleFilas.value = [];
  } finally {
    detalleLoading.value = false;
  }
};

const openDetalleIngreso = async (i) => {
  detalleTitulo.value = `Detalle de aplicaciones — ${i.TIPOINGRESO}`;
  detalleTipo.value = 'ingreso';
  detallePrestamoId.value = null;
  detalleAbonos.value = [];
  detalleColumnas.value = [
    { key: 'fecha', label: 'Fecha pago', align: '' },
    { key: 'planilla', label: 'Planilla', align: '' },
    { key: 'monto', label: 'Monto en planilla', align: 'text-right font-mono' },
    { key: 'configurado', label: 'Monto configurado', align: 'text-right font-mono' },
  ];
  showDetalleModal.value = true;
  detalleLoading.value = true;
  try {
    const res = await api.get(`/otros-ingresos/${i.ID_OTROINGRESO}/historial`);
    const { resumen, aplicaciones } = res.data;
    detalleResumenItems.value = [
      { label: 'Monto configurado', value: `$${fmt(i.MONTOINGRESO)}` },
      { label: 'Total en planillas', value: `$${fmt(resumen.total_aplicado)}` },
      { label: 'Planillas con ingreso', value: String(resumen.veces_aplicado) },
      { label: 'Estado', value: i.ESACTIVO ? 'Activo' : 'Inactivo' },
    ];
    detalleFilas.value = aplicaciones.map((a) => ({
      fecha: fmtDate(a.FECHAPAGO),
      planilla: `#${a.ID_PLANILLA} — ${a.TITULO}`,
      monto: `$${fmt(a.MONTO)}`,
      configurado: `$${fmt(a.MONTO_CONFIGURADO)}`,
    }));
  } catch (err) {
    console.error(err);
    detalleFilas.value = [];
  } finally {
    detalleLoading.value = false;
  }
};
</script>
