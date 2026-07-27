import * as XLSX from 'xlsx';
import {
  collectConceptosDescuento,
  collectConceptosIngreso,
  collectConceptosPatronal,
  getDescuentoMonto,
  getPatronalMonto,
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

/**
 * Construye filas de planilla para Excel con ingresos y descuentos dinámicos.
 */
export function buildPlanillaSheetRows(detalles, conceptosDescuento = [], totales = null, conceptosIngreso = [], conceptosPatronal = []) {
  const ingresos = collectConceptosIngreso(detalles, conceptosIngreso);
  const descuentos = collectConceptosDescuento(detalles, conceptosDescuento);
  const patronal = collectConceptosPatronal(detalles, conceptosPatronal);

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

  detalles.forEach((det, i) => {
    rows.push([
      i + 1,
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
    ]);
  });

  if (totales) {
    rows.push([]);
    rows.push([
      'TOTALES',
      '',
      '',
      '',
      '',
      ...ingresos.map((ing) => totalConceptoIngreso(detalles, ing.key)),
      Number(totales.TOTAL_DEVENGADO ?? 0),
      ...descuentos.map((c) => totalConceptoDescuento(detalles, c)),
      Number(totales.TOTAL_DEDUCCIONES ?? 0),
      Number(totales.LIQUIDO_A_RECIBIR ?? 0),
      ...patronal.map((p) => totalConceptoPatronal(detalles, p)),
    ]);
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
