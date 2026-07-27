<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Cálculo de Planilla</h1>
          <p class="text-sm text-slate-600 dark:text-slate-400">Procesamiento de planillas mensuales y extraordinarias.</p>
        </div>
        <button @click="openCreateModal"
          class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-sm transition-colors shadow-sm">
          + Nueva Planilla
        </button>
      </div>

      <SkeletonTable v-if="initialLoading" />

      <!-- Planillas List -->
      <div v-else class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold uppercase border-b border-slate-200 dark:border-slate-700">
                <th class="px-6 py-4">ID</th>
                <th class="px-6 py-4">Título</th>
                <th class="px-6 py-4">Tipo</th>
                <th class="px-6 py-4">Periodo</th>
                <th class="px-6 py-4">Fecha Pago</th>
                <th class="px-6 py-4">Estado</th>
                <th class="px-6 py-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm text-slate-700 dark:text-slate-200">
              <tr v-for="plan in planillas" :key="plan.ID_PLANILLA"
                :class="selectedPayroll?.ID_PLANILLA === plan.ID_PLANILLA ? 'bg-indigo-50 dark:bg-indigo-900/20' : 'hover:bg-slate-50 dark:hover:bg-slate-700/30'"
                class="transition-colors">
                <td class="px-6 py-4 font-medium text-slate-500">{{ plan.ID_PLANILLA }}</td>
                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white">{{ plan.TITULO }}</td>
                <td class="px-6 py-4">{{ plan.TIPOPLANILLA }}</td>
                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ plan.CALPERIODO }}</td>
                <td class="px-6 py-4">{{ formatDate(plan.FECHAPAGO) }}</td>
                <td class="px-6 py-4">
                  <span :class="estadoClass(plan)" class="px-2.5 py-1 rounded text-xs font-bold uppercase">{{ estadoLabel(plan) }}</span>
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                  <button v-if="!plan.CERRADA && !plan.ANULADA" @click="calculatePayroll(plan)" :disabled="calculating"
                    class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 rounded text-xs font-semibold disabled:opacity-50 transition-colors">
                    {{ calculating && selectedPayroll?.ID_PLANILLA === plan.ID_PLANILLA ? '...' : 'Calcular' }}
                  </button>
                  <button @click="viewDetails(plan)"
                    class="px-2.5 py-1 border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 rounded text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors">
                    Ver Detalles
                  </button>
                  <button v-if="plan.RECALCULADA && !plan.CERRADA && !plan.ANULADA" @click="cerrarPlanilla(plan)" class="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded text-xs font-semibold">Cerrar</button>
                  <button v-if="plan.CERRADA && !plan.CONTABILIZADA && !plan.ANULADA" @click="contabilizarPlanilla(plan)" class="px-2.5 py-1 bg-blue-50 text-blue-700 rounded text-xs font-semibold">Contabilizar</button>
                  <button v-if="!plan.ANULADA && !plan.CONTABILIZADA" @click="anularPlanilla(plan)" class="px-2.5 py-1 bg-rose-50 text-rose-700 rounded text-xs font-semibold">Anular</button>
                </td>
              </tr>
              <tr v-if="!planillas.length">
                <td colspan="7" class="px-6 py-10 text-center text-slate-400 text-sm">No hay planillas registradas. Cree una nueva.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Details Panel -->
      <div v-if="selectedPayroll" id="detail-panel" class="relative bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <!-- Overlay generación reportes -->
        <div v-if="reportLoading" class="absolute inset-0 z-30 flex items-center justify-center bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm">
          <div class="text-center px-6 py-5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 shadow-lg">
            <span class="btn-spinner !h-8 !w-8 !border-[3px] mx-auto block text-indigo-600"></span>
            <p class="mt-3 text-sm font-semibold text-slate-800 dark:text-white">{{ reportMessage }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Espere un momento…</p>
          </div>
        </div>
        <!-- Detail Header -->
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-700/40">
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Detalle de Planilla: {{ selectedPayroll.TITULO }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{ selectedPayroll.CALPERIODO }} · {{ selectedPayroll.TIPOPLANILLA }}
              <span class="ml-2 text-indigo-600 dark:text-indigo-400 font-semibold">{{ payrollTotales.COUNT }} empleados</span>
            </p>
          </div>
          <div class="flex items-center space-x-3">
            <template v-if="hasDetalles">
              <button data-no-lock @click="imprimirPlanilla" :disabled="isBusy()" class="text-xs bg-slate-800 hover:bg-slate-900 text-white rounded px-3 py-1.5 font-semibold disabled:opacity-50 inline-flex items-center gap-1.5">
                <span v-if="isLoading('print-planilla')" class="btn-spinner !h-3 !w-3"></span>
                {{ isLoading('print-planilla') ? 'Generando…' : 'Imprimir Planilla' }}
              </button>
              <button data-no-lock @click="descargarPdfPlanilla" :disabled="isBusy()" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white rounded px-3 py-1.5 font-semibold disabled:opacity-50 inline-flex items-center gap-1.5">
                <span v-if="isLoading('pdf-planilla')" class="btn-spinner !h-3 !w-3"></span>
                {{ isLoading('pdf-planilla') ? 'Generando…' : 'PDF Planilla' }}
              </button>
              <button data-no-lock @click="descargarXlsxPlanilla" :disabled="isBusy()" class="text-xs bg-green-600 hover:bg-green-700 text-white rounded px-3 py-1.5 font-semibold disabled:opacity-50 inline-flex items-center gap-1.5">
                <span v-if="isLoading('xlsx-planilla')" class="btn-spinner !h-3 !w-3"></span>
                {{ isLoading('xlsx-planilla') ? 'Generando…' : 'Excel Planilla' }}
              </button>
              <button data-no-lock @click="imprimirBoletas" :disabled="isBusy()" class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white rounded px-3 py-1.5 font-semibold disabled:opacity-50 inline-flex items-center gap-1.5">
                <span v-if="isLoading('print-boletas')" class="btn-spinner !h-3 !w-3"></span>
                {{ isLoading('print-boletas') ? 'Generando…' : 'Imprimir Boletas' }}
              </button>
              <button data-no-lock @click="descargarPdfBoletas" :disabled="isBusy()" class="text-xs bg-emerald-700 hover:bg-emerald-800 text-white rounded px-3 py-1.5 font-semibold disabled:opacity-50 inline-flex items-center gap-1.5">
                <span v-if="isLoading('pdf-boletas')" class="btn-spinner !h-3 !w-3"></span>
                {{ isLoading('pdf-boletas') ? 'Generando…' : 'PDF Boletas' }}
              </button>
              <button data-no-lock @click="showBankExport = true" :disabled="isBusy()" class="text-xs bg-blue-600 hover:bg-blue-700 text-white rounded px-3 py-1.5 font-semibold disabled:opacity-50">Archivo Banco</button>
            </template>
            <button v-if="selectedPayroll && !selectedPayroll.CERRADA && !selectedPayroll.ANULADA" @click="toggleHePanel" class="text-xs border border-slate-300 dark:border-slate-600 rounded px-3 py-1.5 font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700">Horas Extras</button>
            <button v-if="loadingDetails" class="text-xs text-slate-400 animate-pulse">Cargando...</button>
            <button @click="closeDetails" class="text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 font-semibold border border-slate-200 dark:border-slate-600 rounded px-3 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
              ✕ Cerrar Detalles
            </button>
          </div>
        </div>

        <!-- Horas Extras Panel -->
        <div v-if="showHePanel && selectedPayroll" class="px-6 py-4 border-b border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 space-y-3">
          <div class="flex justify-between items-center">
            <h4 class="text-sm font-bold text-slate-800 dark:text-amber-100">Horas Extras — Planilla #{{ selectedPayroll.ID_PLANILLA }}</h4>
            <button @click="syncHe" class="text-xs bg-amber-600 hover:bg-amber-700 text-white px-3 py-1 rounded font-semibold">Sincronizar desde Asistencia</button>
          </div>
          <div v-if="loadingHe" class="text-xs text-slate-500 dark:text-slate-400 animate-pulse">Cargando horas extras...</div>
          <form v-else v-submit-lock="addHe" class="flex flex-wrap gap-2 items-end">
            <div class="min-w-[220px]">
              <AsyncSelect
                v-model="heForm.ID_EMPLEADO"
                :endpoint="`/planillas/${selectedPayroll.ID_PLANILLA}/empleados-select`"
                placeholder="Seleccione empleado"
                search-placeholder="Buscar en planilla…"
              />
            </div>
            <AsyncSelect
              v-model="heForm.ID_HORASEXTRAS"
              catalog="horas-extras"
              placeholder="Seleccione tipo de hora extra"
              input-class="min-w-[220px] !py-1.5 !text-xs"
            />
            <input v-model.number="heForm.CANTIDADHORAS" type="number" step="0.5" min="0.5" placeholder="Horas" required class="px-2 py-1.5 border border-slate-300 dark:border-slate-600 rounded text-xs w-20 bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
            <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-semibold">Agregar</button>
          </form>
          <div v-if="!loadingHe && horasExtras.length" class="text-xs space-y-1">
            <div v-for="he in horasExtras" :key="he.ID_DETALLEHORAEXTRA" class="flex justify-between bg-white dark:bg-slate-800 rounded px-3 py-1.5 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200">
              <span>{{ heNombre(he) }} — {{ heTipo(he) }} ({{ he.CANTIDADHORAS ?? he.cantidadhoras }} hrs)</span>
              <span class="font-mono">${{ fmt(he.MONTOAPAGAR) }} <button @click="deleteHe(he)" class="text-rose-600 dark:text-rose-400 ml-2">✕</button></span>
            </div>
          </div>
          <p v-else-if="!loadingHe && !hasDetalles" class="text-xs text-amber-700 dark:text-amber-300">
            Calcule la planilla primero para cargar los empleados de esta corrida.
          </p>
          <p v-else-if="!loadingHe" class="text-xs text-slate-500 dark:text-slate-400">No hay horas extras registradas. Agregue manualmente o sincronice desde asistencia.</p>
        </div>

        <!-- Loading state -->
        <div v-if="loadingDetails" class="p-6">
          <SkeletonTable :cols="6" :rows="4" :no-header="true" />
        </div>

        <!-- Empty state -->
        <div v-else-if="!hasDetalles" class="p-10 text-center">
          <div class="text-4xl mb-3">📋</div>
          <p class="text-slate-600 dark:text-slate-400 font-semibold">Sin detalles calculados</p>
          <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Haz clic en "Calcular" para procesar los empleados de esta planilla.</p>
        </div>

        <!-- Details Table -->
        <div v-else>
          <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex flex-wrap gap-3 items-center justify-between bg-white dark:bg-slate-800">
            <input
              v-model="detalleSearch"
              type="text"
              placeholder="Buscar empleado en planilla…"
              class="w-full max-w-xs px-3 py-1.5 border border-slate-300 dark:border-slate-600 rounded-lg text-xs bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
            <span class="text-xs text-slate-500">{{ detalleTotal }} empleados en planilla</span>
          </div>
          <div ref="detalleScrollRef" class="overflow-x-auto max-h-[520px] overflow-y-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 font-semibold uppercase border-b border-slate-200 dark:border-slate-700">
                <th class="px-4 py-3 sticky left-0 bg-slate-50 dark:bg-slate-700/50 z-10">#</th>
                <th class="px-4 py-3 min-w-[160px]">Empleado</th>
                <th class="px-4 py-3 min-w-[90px]">Contrato</th>
                <th class="px-4 py-3 min-w-[120px]">Cargo</th>
                <th class="px-4 py-3 min-w-[80px]">Días</th>
                <th v-for="ing in ingresosVisibles" :key="ing.key" class="px-4 py-3 text-right min-w-[100px]">{{ ing.label }}</th>
                <th class="px-4 py-3 text-right min-w-[110px]">Total Devengado</th>
                <th v-for="desc in descuentosVisibles" :key="`${desc.CONCEPTO}|${desc.CATEGORIA}`" class="px-4 py-3 text-right min-w-[90px]">{{ desc.CONCEPTO }}</th>
                <th class="px-4 py-3 text-right min-w-[100px]">Total Desc.</th>
                <th class="px-4 py-3 text-right min-w-[110px] text-emerald-600">Líquido</th>
                <th v-for="pat in patronalVisible" :key="pat.key" class="px-4 py-3 text-right min-w-[95px] text-violet-600 dark:text-violet-400">{{ pat.label }}</th>
                <th class="px-4 py-3 text-center min-w-[70px]">Boleta</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-if="detallePaddingTop > 0" aria-hidden="true">
                <td :colspan="detalleColspan" :style="{ height: detallePaddingTop + 'px', padding: 0, border: 'none' }"></td>
              </tr>
              <tr v-for="virtualRow in detalleVirtualRows" :key="payrollDetails[virtualRow.index].ID_DETALLEPLANILLA"
                class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                <td class="px-4 py-3 text-slate-400 sticky left-0 bg-white dark:bg-slate-800">{{ rowNumber(virtualRow.index) }}</td>
                <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white">{{ payrollDetails[virtualRow.index].NOM_EMPLEADO }}</td>
                <td class="px-4 py-3 text-slate-500 dark:text-slate-400 whitespace-nowrap" :title="payrollDetails[virtualRow.index].TIPO_CONTRATACION_NOM">{{ abreviarContrato(payrollDetails[virtualRow.index].TIPO_CONTRATACION_NOM) }}</td>
                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ payrollDetails[virtualRow.index].CARGO || '—' }}</td>
                <td class="px-4 py-3">{{ Number(payrollDetails[virtualRow.index].DIASLABORADOS).toFixed(1) }}</td>
                <td v-for="ing in ingresosVisibles" :key="ing.key" class="px-4 py-3 text-right">${{ fmt(payrollDetails[virtualRow.index][ing.key]) }}</td>
                <td class="px-4 py-3 text-right font-semibold text-indigo-600 dark:text-indigo-400">${{ fmt(payrollDetails[virtualRow.index].TOTAL_DEVENGADO) }}</td>
                <td v-for="desc in descuentosVisibles" :key="`${desc.CONCEPTO}|${desc.CATEGORIA}`" class="px-4 py-3 text-right text-rose-500">${{ fmt(getDescuentoMonto(payrollDetails[virtualRow.index], desc)) }}</td>
                <td class="px-4 py-3 text-right text-rose-600 font-semibold">${{ fmt(payrollDetails[virtualRow.index].TOTAL_DEDUCCIONES) }}</td>
                <td class="px-4 py-3 text-right font-bold text-emerald-600 dark:text-emerald-400">${{ fmt(payrollDetails[virtualRow.index].LIQUIDO_A_RECIBIR) }}</td>
                <td v-for="pat in patronalVisible" :key="pat.key" class="px-4 py-3 text-right text-violet-600 dark:text-violet-400 font-medium">${{ fmt(getPatronalMonto(payrollDetails[virtualRow.index], pat)) }}</td>
                <td class="px-4 py-3 text-center space-x-1 whitespace-nowrap">
                  <button data-no-lock @click="imprimirBoleta(payrollDetails[virtualRow.index])" :disabled="isBusy()" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 text-xs font-semibold disabled:opacity-40" title="Imprimir boleta">🖨</button>
                  <button data-no-lock @click="descargarPdfBoleta(payrollDetails[virtualRow.index])" :disabled="isBusy()" class="text-emerald-600 hover:text-emerald-800 text-xs font-semibold disabled:opacity-40" title="PDF">PDF</button>
                </td>
              </tr>
              <tr v-if="detallePaddingBottom > 0" aria-hidden="true">
                <td :colspan="detalleColspan" :style="{ height: detallePaddingBottom + 'px', padding: 0, border: 'none' }"></td>
              </tr>
            </tbody>
            <!-- Totals Footer -->
            <tfoot>
              <tr class="bg-slate-100 dark:bg-slate-700/60 font-bold text-xs border-t-2 border-slate-300 dark:border-slate-600">
                <td class="px-4 py-3 text-slate-600 dark:text-slate-300" colspan="5">TOTALES ({{ payrollTotales.COUNT }} empleados)</td>
                <td v-for="ing in ingresosVisibles" :key="'t-' + ing.key" class="px-4 py-3 text-right text-slate-700 dark:text-slate-200">${{ fmt(totalConceptoIngresoServer(totalesConceptos, ing.key)) }}</td>
                <td class="px-4 py-3 text-right text-indigo-700 dark:text-indigo-300">${{ fmt(payrollTotales.TOTAL_DEVENGADO) }}</td>
                <td v-for="desc in descuentosVisibles" :key="'t-' + desc.CONCEPTO + desc.CATEGORIA" class="px-4 py-3 text-right text-rose-600">${{ fmt(totalConceptoDescuentoServer(totalesConceptos, desc)) }}</td>
                <td class="px-4 py-3 text-right text-rose-700">${{ fmt(payrollTotales.TOTAL_DEDUCCIONES) }}</td>
                <td class="px-4 py-3 text-right text-emerald-700 dark:text-emerald-300">${{ fmt(payrollTotales.LIQUIDO_A_RECIBIR) }}</td>
                <td v-for="pat in patronalVisible" :key="'tp-' + pat.key" class="px-4 py-3 text-right text-violet-700 dark:text-violet-300">${{ fmt(totalConceptoPatronalServer(totalesConceptos, pat)) }}</td>
                <td></td>
              </tr>
              <!-- Costo Patronal -->
              <tr v-if="!patronalVisible.length" class="bg-slate-50 dark:bg-slate-700/30 text-xs border-t border-slate-200 dark:border-slate-700">
                <td class="px-4 py-2 text-slate-500 font-semibold" colspan="5">Costo Patronal</td>
                <td class="px-4 py-2 text-right text-slate-500" :colspan="Math.max(1, ingresosVisibles.length + descuentosVisibles.length + 2)">
                  AFP Pat: ${{ fmt(payrollTotales.AFP_PATRONAL) }}
                  · ISSS Pat: ${{ fmt(payrollTotales.ISSS_PATRONAL) }}
                  · INSAFORP: ${{ fmt(payrollTotales.INSAFORP_PATRONAL) }}
                </td>
                <td></td>
              </tr>
            </tfoot>
          </table>
          </div>
          <PaginationBar
            :page="detallePage"
            :last-page="detalleLastPage"
            :per-page="detallePerPage"
            :total="detalleTotal"
            :loading="loadingDetails"
            :per-page-options="[25, 50, 100]"
            @update:page="onDetallePageChange"
            @update:per-page="onDetallePerPageChange"
          />
        </div>
      </div>

      <PlanillaBankExportModal
        :open="showBankExport"
        :planilla-id="selectedPayroll?.ID_PLANILLA"
        :planilla-titulo="selectedPayroll?.TITULO || ''"
        @close="showBankExport = false"
      />

      <!-- Create Modal -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full overflow-hidden border border-slate-200 dark:border-slate-700">
          <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-950 dark:text-white">Nueva Corrida de Planilla</h3>
            <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">✕</button>
          </div>
          <form v-submit-lock="saveForm" class="p-6 space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Empresa</label>
              <AsyncSelect v-model="form.ID_EMPRESA" catalog="empresas" placeholder="Seleccionar empresa" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Título de Planilla</label>
              <input v-model="form.TITULO" type="text" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Tipo Planilla</label>
              <AsyncSelect v-model="form.ID_TIPOPLANILLA" catalog="tipos-planilla" placeholder="Seleccionar tipo" />
              <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">Use planillas separadas: Permanente, Honorarios y Comercial. No combine grupos.</p>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Frecuencia de Pago</label>
              <AsyncSelect v-model="form.ID_FRECUENCIAPAGO" catalog="frecuencias-pago" placeholder="Seleccionar frecuencia" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Periodo Laboral</label>
              <AsyncSelect v-model="form.ID_PERIODO" catalog="periodos-laborales" placeholder="Seleccionar periodo" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Cuenta Bancaria</label>
              <AsyncSelect v-model="form.ID_CUENTA" catalog="cuentas" placeholder="Seleccionar cuenta" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Forma de Pago</label>
              <AsyncSelect v-model="form.FORMAPAGO" :options="FORMA_PAGO_OPTIONS" :searchable="false" placeholder="Forma de pago" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-1">Fecha de Pago</label>
              <input v-model="form.FECHAPAGO" type="date" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
            <div v-if="modalError" class="text-xs text-red-500 bg-red-50 p-2 rounded">{{ modalError }}</div>
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
import { useVirtualizer } from '@tanstack/vue-virtual';
import DashboardLayout from '../Dashboard.vue';
import SkeletonTable from '../../components/SkeletonTable.vue';
import PlanillaBankExportModal from '../../components/PlanillaBankExportModal.vue';
import PaginationBar from '../../components/PaginationBar.vue';
import AsyncSelect from '../../components/AsyncSelect.vue';
import { FORMA_PAGO_OPTIONS } from '../../utils/staticSelectOptions';
import api from '../../services/api';
import { usePlanillaReports } from '../../composables/usePlanillaReports';
import { buildPlanillaSheetRows, downloadXlsx } from '../../utils/exportXlsx';
import {
  collectConceptosDescuento,
  collectConceptosIngreso,
  collectConceptosPatronal,
  getDescuentoMonto,
  getPatronalMonto,
  totalConceptoDescuento,
  totalConceptoIngreso,
  totalConceptoPatronal,
  totalConceptoDescuentoServer,
  totalConceptoIngresoServer,
  totalConceptoPatronalServer,
  abreviarContrato,
} from '../../utils/planillaColumns';

const {
  reportLoading,
  reportMessage,
  openPrintWhenReady,
  downloadFileWhenReady,
  isLoading,
  isBusy,
  toast,
} = usePlanillaReports();

const planillas     = ref([]);
const catalogs      = ref({ empresas: [], tiposPlanilla: [], periodos: [], frecuencias: [], cuentas: [] });
const horasExtras   = ref([]);
const showHePanel   = ref(false);
const heForm        = ref({ ID_EMPLEADO: null, ID_HORASEXTRAS: null, CANTIDADHORAS: null });
const initialLoading = ref(true);
const loadingDetails= ref(false);
const loadingHe     = ref(false);
const calculating   = ref(false);
const showModal     = ref(false);
const showBankExport = ref(false);
const modalError    = ref('');
const selectedPayroll = ref(null);
const payrollDetails  = ref([]);
const payrollTotales  = ref({ COUNT: 0, TOTAL_DEVENGADO: 0, AFP_EMPLEADO: 0, ISSS_EMPLEADO: 0, RENTA_EMPLEADO: 0, PRESTAMOS: 0, OTRO_DESCUENTOS: 0, TOTAL_DEDUCCIONES: 0, LIQUIDO_A_RECIBIR: 0, AFP_PATRONAL: 0, ISSS_PATRONAL: 0, INSAFORP_PATRONAL: 0 });
const totalesConceptos = ref({ ingreso: {}, descuento: [], patronal: {} });
const hasDetalles = ref(false);
const detalleSearch = ref('');
const detallePage = ref(1);
const detalleLastPage = ref(1);
const detallePerPage = ref(50);
const detalleTotal = ref(0);
const detalleScrollRef = ref(null);
let detalleSearchTimer = null;
const conceptosIngreso = ref([]);
const conceptosDescuento = ref([]);
const conceptosPatronal = ref([]);

const ingresosVisibles = computed(() => collectConceptosIngreso(payrollDetails.value, conceptosIngreso.value));
const descuentosVisibles = computed(() => collectConceptosDescuento(payrollDetails.value, conceptosDescuento.value));
const patronalVisible = computed(() => collectConceptosPatronal(payrollDetails.value, conceptosPatronal.value));

const detalleColspan = computed(() =>
  5 + ingresosVisibles.value.length + descuentosVisibles.value.length + patronalVisible.value.length + 4
);

const detalleVirtualizer = useVirtualizer(computed(() => ({
  count: payrollDetails.value.length,
  getScrollElement: () => detalleScrollRef.value,
  estimateSize: () => 44,
  overscan: 6,
})));

const detalleVirtualRows = computed(() => detalleVirtualizer.value.getVirtualItems());
const detallePaddingTop = computed(() => detalleVirtualRows.value[0]?.start ?? 0);
const detallePaddingBottom = computed(() => {
  const items = detalleVirtualRows.value;
  if (!items.length) return 0;
  return detalleVirtualizer.value.getTotalSize() - items[items.length - 1].end;
});

const rowNumber = (index) => (detallePage.value - 1) * detallePerPage.value + index + 1;

const form = ref({
  TITULO: '', ID_EMPRESA: '', ID_TIPOPLANILLA: 1, ID_PERIODO: 1,
  ID_FRECUENCIAPAGO: 1, ID_CUENTA: 1, FORMAPAGO: 'Transferencia',
  FECHAPAGO: new Date().toISOString().split('T')[0], OBSERVACION: ''
});

const estadoLabel = (p) => { if (p.ANULADA) return 'Anulada'; if (p.CONTABILIZADA) return 'Contabilizada'; if (p.CERRADA) return 'Cerrada'; if (p.RECALCULADA) return 'Calculada'; return 'Pendiente'; };
const estadoClass = (p) => {
  if (p.ANULADA) return 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300';
  if (p.CONTABILIZADA) return 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300';
  if (p.CERRADA) return 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200';
  if (p.RECALCULADA) return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
  return 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
};

const field = (row, ...keys) => {
  if (!row) return null;
  for (const key of keys) {
    if (row[key] !== undefined && row[key] !== null) return row[key];
  }
  return null;
};

const resetHeForm = () => {
  heForm.value = {
    ID_EMPLEADO: null,
    ID_HORASEXTRAS: null,
    CANTIDADHORAS: null,
  };
};

const heNombre = (he) => field(he, 'NOMBRE_EMPLEADO', 'nombre_empleado') || '—';
const heTipo = (he) => field(he, 'TIPOHORAEXTRA', 'tipohoraextra') || '—';

const fmt = (val) => Number(val ?? 0).toFixed(2);
const formatDate = (d) => {
  try { return new Date(d).toLocaleDateString('es-SV'); } catch { return d; }
};

const loadPlanillas = async () => {
  try {
    const res = await api.get('/planillas');
    planillas.value = res.data;
  } catch (err) { console.error(err); }
  finally { initialLoading.value = false; }
};

const loadCatalogs = async () => {
  try {
    const res = await api.get('/planillas/catalogs');
    catalogs.value = res.data;
    if (res.data.empresas?.length) form.value.ID_EMPRESA = res.data.empresas[0].ID_EMPRESA;
    if (res.data.tiposPlanilla?.length) form.value.ID_TIPOPLANILLA = res.data.tiposPlanilla[0].ID_TIPOPLANILLA;
    if (res.data.periodos?.length) form.value.ID_PERIODO = res.data.periodos[0].ID_PERIODO;
    if (res.data.frecuencias?.length) form.value.ID_FRECUENCIAPAGO = res.data.frecuencias[0].ID_FRECUENCIAPAGO;
    if (res.data.cuentas?.length) form.value.ID_CUENTA = res.data.cuentas[0].ID_CUENTA;
  } catch (err) { console.error(err); }
};

const loadDetalles = async () => {
  if (!selectedPayroll.value) return;
  loadingDetails.value = true;
  try {
    const res = await api.get(`/planillas/${selectedPayroll.value.ID_PLANILLA}/detalles`, {
      params: {
        page: detallePage.value,
        per_page: detallePerPage.value,
        search: detalleSearch.value.trim() || undefined,
      },
    });
    payrollDetails.value = res.data.data ?? [];
    detallePage.value = res.data.current_page ?? 1;
    detalleLastPage.value = res.data.last_page ?? 1;
    detalleTotal.value = res.data.total ?? 0;
  } catch (err) {
    console.error('Error cargando filas de planilla:', err);
    payrollDetails.value = [];
  } finally {
    loadingDetails.value = false;
  }
};

const onDetallePageChange = (page) => {
  detallePage.value = page;
  loadDetalles();
};

const onDetallePerPageChange = (perPage) => {
  detallePerPage.value = perPage;
  detallePage.value = 1;
  loadDetalles();
};

watch(detalleSearch, () => {
  clearTimeout(detalleSearchTimer);
  detalleSearchTimer = setTimeout(() => {
    detallePage.value = 1;
    loadDetalles();
  }, 350);
});

const fetchAllDetallesForExport = async () => {
  const res = await api.get(`/planillas/${selectedPayroll.value.ID_PLANILLA}/detalles`, {
    params: { page: 1, per_page: Math.max(detalleTotal.value, 9999) },
  });
  return res.data.data ?? [];
};

const toggleHePanel = async () => {
  if (!showHePanel.value && !hasDetalles.value) {
    alert('Calcule la planilla primero para ver los empleados de esta corrida.');
    return;
  }
  showHePanel.value = !showHePanel.value;
  if (showHePanel.value) {
    resetHeForm();
  }
};

onMounted(() => { loadPlanillas(); loadCatalogs(); });

// ── CRUD ─────────────────────────────────────────────────────────────────────
const openCreateModal = () => {
  modalError.value = '';
  form.value = { TITULO: '', ID_EMPRESA: catalogs.value.empresas[0]?.ID_EMPRESA || '', ID_TIPOPLANILLA: catalogs.value.tiposPlanilla[0]?.ID_TIPOPLANILLA || 1, ID_PERIODO: catalogs.value.periodos[0]?.ID_PERIODO || 1, ID_FRECUENCIAPAGO: catalogs.value.frecuencias[0]?.ID_FRECUENCIAPAGO || 1, ID_CUENTA: catalogs.value.cuentas[0]?.ID_CUENTA || 1, FORMAPAGO: 'Transferencia', FECHAPAGO: new Date().toISOString().split('T')[0], OBSERVACION: '' };
  showModal.value = true;
};

const saveForm = async () => {
  try {
    await api.post('/planillas', form.value);
    showModal.value = false;
    loadPlanillas();
  } catch (err) { modalError.value = 'Error al crear planilla.'; }
};

const calculatePayroll = async (plan) => {
  calculating.value = true;
  selectedPayroll.value = plan;
  try {
    const res = await api.post(`/planillas/${plan.ID_PLANILLA}/calcular`, {}, { timeout: 600000 });
    await loadPlanillas();
    await viewDetails(plan);
    if (res.data?.message) {
      alert(res.data.message);
    }
  } catch (err) {
    const msg = err.response?.data?.error || err.message || 'Error al calcular planilla.';
    alert(msg);
    console.error(err);
  } finally {
    calculating.value = false;
  }
};

const loadHorasExtras = async (id) => {
  loadingHe.value = true;
  try {
    horasExtras.value = (await api.get(`/planillas/${id}/horas-extras`)).data;
  } catch (err) {
    console.error('Error cargando horas extras:', err);
    horasExtras.value = [];
  } finally {
    loadingHe.value = false;
  }
};
const addHe = async () => {
  try {
    await api.post(`/planillas/${selectedPayroll.value.ID_PLANILLA}/horas-extras`, heForm.value);
    await loadHorasExtras(selectedPayroll.value.ID_PLANILLA);
    heForm.value.CANTIDADHORAS = null;
  } catch (err) {
    alert('Error al agregar hora extra.');
    console.error(err);
  }
};
const deleteHe = async (he) => {
  await api.delete(`/planillas/${selectedPayroll.value.ID_PLANILLA}/horas-extras/${he.ID_DETALLEHORAEXTRA}`);
  await loadHorasExtras(selectedPayroll.value.ID_PLANILLA);
};
const syncHe = async () => {
  try {
    await api.post(`/planillas/${selectedPayroll.value.ID_PLANILLA}/horas-extras/sync`);
    await loadHorasExtras(selectedPayroll.value.ID_PLANILLA);
    alert('Horas extras sincronizadas desde asistencia.');
  } catch (err) {
    alert(err.response?.data?.error || 'Error al sincronizar horas extras.');
  }
};
const cerrarPlanilla = async (plan) => { if (!confirm('¿Cerrar planilla? No podrá recalcularla.')) return; await api.post(`/planillas/${plan.ID_PLANILLA}/cerrar`); loadPlanillas(); };
const anularPlanilla = async (plan) => { if (!confirm('¿Anular planilla? Se revertirán abonos de préstamos.')) return; await api.post(`/planillas/${plan.ID_PLANILLA}/anular`); loadPlanillas(); };
const contabilizarPlanilla = async (plan) => { await api.post(`/planillas/${plan.ID_PLANILLA}/contabilizar`); loadPlanillas(); };

const viewDetails = async (plan) => {
  selectedPayroll.value = plan;
  loadingDetails.value  = true;
  showHePanel.value = false;
  detalleSearch.value = '';
  detallePage.value = 1;
  try {
    const [detailRes] = await Promise.all([
      api.get(`/planillas/${plan.ID_PLANILLA}`),
      loadHorasExtras(plan.ID_PLANILLA),
    ]);
    selectedPayroll.value = detailRes.data.planilla;
    payrollTotales.value  = detailRes.data.totales   || payrollTotales.value;
    totalesConceptos.value = detailRes.data.totales_conceptos || { ingreso: {}, descuento: [], patronal: {} };
    conceptosIngreso.value = detailRes.data.conceptos_ingreso || [];
    conceptosDescuento.value = detailRes.data.conceptos_descuento || [];
    conceptosPatronal.value = detailRes.data.conceptos_patronal || [];
    hasDetalles.value = detailRes.data.has_detalles ?? (payrollTotales.value.COUNT > 0);
    await loadDetalles();
    setTimeout(() => document.getElementById('detail-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
  } catch (err) {
    console.error('Error cargando detalles:', err);
    alert('Error al cargar detalles de planilla.');
  } finally {
    loadingDetails.value = false;
  }
};

const closeDetails = () => {
  selectedPayroll.value = null;
  payrollDetails.value  = [];
  hasDetalles.value = false;
  detalleSearch.value = '';
  detalleTotal.value = 0;
  totalesConceptos.value = { ingreso: {}, descuento: [], patronal: {} };
  conceptosIngreso.value = [];
  conceptosDescuento.value = [];
  conceptosPatronal.value = [];
};

const imprimirPlanilla = () => {
  if (!selectedPayroll.value) return;
  openPrintWhenReady(`/reportes/planillas/${selectedPayroll.value.ID_PLANILLA}/imprimir`, {
    key: 'print-planilla',
    label: 'planilla para imprimir',
  });
};

const imprimirBoletas = () => {
  if (!selectedPayroll.value) return;
  openPrintWhenReady(`/reportes/planillas/${selectedPayroll.value.ID_PLANILLA}/boletas`, {
    key: 'print-boletas',
    label: 'boletas para imprimir',
  });
};

const imprimirBoleta = (det) => {
  if (!selectedPayroll.value || !det?.ID_DETALLEPLANILLA) return;
  openPrintWhenReady(
    `/reportes/planillas/${selectedPayroll.value.ID_PLANILLA}/boletas/${det.ID_DETALLEPLANILLA}`,
    { key: `print-boleta-${det.ID_DETALLEPLANILLA}`, label: 'boleta para imprimir' }
  );
};

const descargarPdfPlanilla = () => {
  if (!selectedPayroll.value) return;
  downloadFileWhenReady(`/reportes/planillas/${selectedPayroll.value.ID_PLANILLA}/pdf`, {
    key: 'pdf-planilla',
    label: 'PDF de planilla',
    fallbackName: 'planilla.pdf',
  });
};

const descargarPdfBoletas = () => {
  if (!selectedPayroll.value) return;
  downloadFileWhenReady(`/reportes/planillas/${selectedPayroll.value.ID_PLANILLA}/boletas/pdf`, {
    key: 'pdf-boletas',
    label: 'PDF de boletas',
    fallbackName: 'boletas.pdf',
  });
};

const descargarPdfBoleta = (det) => {
  if (!selectedPayroll.value || !det?.ID_DETALLEPLANILLA) return;
  downloadFileWhenReady(
    `/reportes/planillas/${selectedPayroll.value.ID_PLANILLA}/boletas/${det.ID_DETALLEPLANILLA}/pdf`,
    {
      key: `pdf-boleta-${det.ID_DETALLEPLANILLA}`,
      label: 'PDF de boleta',
      fallbackName: 'boleta.pdf',
    }
  );
};

const descargarXlsxPlanilla = async () => {
  if (!selectedPayroll.value || isBusy()) return;
  reportLoading.value = 'xlsx-planilla';
  reportMessage.value = 'Generando Excel de planilla…';
  try {
    const slug = (selectedPayroll.value.TITULO || 'planilla').replace(/[^\w\-]+/g, '_').slice(0, 40);
    const filename = `planilla_${selectedPayroll.value.ID_PLANILLA}_${slug}.xlsx`;
    const allDetalles = await fetchAllDetallesForExport();
    const rows = buildPlanillaSheetRows(
      allDetalles,
      descuentosVisibles.value,
      payrollTotales.value,
      ingresosVisibles.value,
      patronalVisible.value
    );
    downloadXlsx(rows, filename, 'Planilla');
    toast.success('Excel generado', `${filename} se descargó correctamente.`);
  } catch (err) {
    toast.error('Error al generar Excel', err.message);
  } finally {
    reportLoading.value = null;
    reportMessage.value = '';
  }
};
</script>
