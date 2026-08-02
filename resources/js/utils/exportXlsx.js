import * as XLSX from 'xlsx';
import {
  buildPlanillaSubtotalRow,
  collectConceptosDescuento,
  collectConceptosIngreso,
  collectConceptosPatronal,
  getDescuentoMonto,
  getPatronalMonto,
  groupDetallesByAreaDepartamento,
  totalConceptoDescuento,
  totalConceptoIngreso,
  totalConceptoPatronal,
} from './planillaColumns';

/**
 * Exporta filas a archivo Excel (.xlsx) en el navegador.
 */
export function downloadXlsx(sheetRows, filename, sheetName = 'Planilla') {
  const ws = XLSX.utils.aoa_to_sheet(sheetRows);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, sheetName.substring(0, 31));
  XLSX.writeFile(wb, filename.endsWith('.xlsx') ? filename : `${filename}.xlsx`);
}

function buildEmployeeRow(det, rowNum, ingresos, descuentos, patronal) {
  return [
    rowNum,
    det.NOM_EMPLEADO,
    det.TIPO_CONTRATACION_NOM || '',
    det.CARGO || '',
    Number(det.DIASLABORADOS ?? 0),
    ...ingresos.map((ing) => Number(det[ing.key] ?? 0)),
    Number(det.TOTAL_DEVENGADO ?? 0),
    ...descuentos.map((c) => getDescuentoMonto(det, c)),
    Number(det.TOTAL_DEDUCCIONES ?? 0),
    Number(det.LIQUIDO_A_RECIBIR ?? 0),
    ...patronal.map((p) => getPatronalMonto(det, p)),
  ];
}

/**
 * Construye filas de planilla para Excel agrupadas por área y departamento.
 */
export function buildPlanillaSheetRows(detalles, conceptosDescuento = [], totales = null, conceptosIngreso = [], conceptosPatronal = []) {
  const ingresos = collectConceptosIngreso(detalles, conceptosIngreso);
  const descuentos = collectConceptosDescuento(detalles, conceptosDescuento);
  const patronal = collectConceptosPatronal(detalles, conceptosPatronal);
  const grupos = groupDetallesByAreaDepartamento(detalles);

  const headers = [
    '#',
    'Empleado',
    'Contrato',
    'Cargo',
    'Días',
    ...ingresos.map((i) => i.label),
    'Total devengado',
    ...descuentos.map((c) => c.CONCEPTO),
    'Total descuentos',
    'Líquido',
    ...patronal.map((p) => p.label),
  ];

  const rows = [headers];
  let rowNum = 0;

  for (const grupoArea of grupos) {
    rows.push([`ÁREA: ${grupoArea.area}`]);
    for (const grupoDepto of grupoArea.departamentos) {
      rows.push([`Departamento: ${grupoDepto.departamento}`]);
      for (const det of grupoDepto.detalles) {
        rowNum += 1;
        rows.push(buildEmployeeRow(det, rowNum, ingresos, descuentos, patronal));
      }
      rows.push(buildPlanillaSubtotalRow(
        `Subtotal ${grupoDepto.departamento} (${grupoDepto.detalles.length} empleados)`,
        grupoDepto.detalles,
        ingresos,
        descuentos,
        patronal
      ));
    }
    rows.push(buildPlanillaSubtotalRow(
      `Subtotal área ${grupoArea.area} (${grupoArea.detalles.length} empleados)`,
      grupoArea.detalles,
      ingresos,
      descuentos,
      patronal
    ));
    rows.push([]);
  }

  if (totales) {
    rows.push(buildPlanillaSubtotalRow(
      `TOTALES (${totales.COUNT ?? detalles.length} empleados)`,
      detalles,
      ingresos,
      descuentos,
      patronal
    ));
  }

  return rows;
}

/**
 * Construye filas desde vista previa bancaria.
 */
export function buildBankPreviewSheetRows(previewData) {
  const headers = previewData.columns.map((c) => c.label);
  const rows = [headers];
  for (const row of previewData.preview || []) {
    rows.push(previewData.columns.map((c) => row[c.key] ?? ''));
  }
  return rows;
}
