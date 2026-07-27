export const INGRESO_CATALOG = [
  { key: 'SALARIO_DIAS', label: 'Salario' },
  { key: 'HORAEXTRAS', label: 'Horas extras' },
  { key: 'PRODUCTIVIDAD', label: 'Productividad' },
  { key: 'COMISION', label: 'Comisión' },
  { key: 'OTROS_INGRESOS', label: 'Otros ingresos' },
  { key: 'DEVENGADO_EXENTO', label: 'Devengado exento' },
];

const DESCUENTO_FALLBACK = [
  { field: 'AFP_EMPLEADO', concepto: 'AFP', categoria: 'LEY' },
  { field: 'ISSS_EMPLEADO', concepto: 'ISSS', categoria: 'LEY' },
  { field: 'RENTA_EMPLEADO', concepto: 'Renta (ISR)', categoria: 'LEY' },
  { field: 'PRESTAMOS', concepto: 'Préstamos', categoria: 'PRESTAMO' },
  { field: 'ANTICIPO', concepto: 'Anticipo', categoria: 'PRESTAMO' },
  { field: 'OTRO_DESCUENTOS', concepto: 'Otros descuentos', categoria: 'DESCUENTO' },
];

const CATEGORIA_ORDEN = { LEY: 1, PRESTAMO: 2, DESCUENTO: 3 };

export function descuentoKey(concepto, categoria = '') {
  return `${concepto}|${categoria || ''}`;
}

export function buildDescuentosFromColumns(det) {
  const lineas = [];
  for (const item of DESCUENTO_FALLBACK) {
    const monto = Number(det[item.field] ?? 0);
    if (monto > 0) {
      lineas.push({
        CONCEPTO: item.concepto,
        CATEGORIA: item.categoria,
        MONTO: monto,
      });
    }
  }
  return lineas;
}

export function resolveDescuentosDetalle(det) {
  const list = det?.descuentos_detalle;
  if (Array.isArray(list) && list.length) return list;
  return buildDescuentosFromColumns(det || {});
}

export function collectConceptosDescuento(detalles, serverConceptos = null) {
  if (serverConceptos?.length) return serverConceptos;

  const conceptos = new Map();
  for (const det of detalles || []) {
    for (const d of resolveDescuentosDetalle(det)) {
      const concepto = d.CONCEPTO || d.concepto;
      const categoria = d.CATEGORIA || d.categoria || '';
      if (!concepto) continue;
      const key = descuentoKey(concepto, categoria);
      if (!conceptos.has(key)) {
        conceptos.set(key, { CONCEPTO: concepto, CATEGORIA: categoria });
      }
    }
  }

  return [...conceptos.values()].sort((a, b) => {
    const oa = CATEGORIA_ORDEN[a.CATEGORIA] ?? 9;
    const ob = CATEGORIA_ORDEN[b.CATEGORIA] ?? 9;
    if (oa !== ob) return oa - ob;
    return String(a.CONCEPTO).localeCompare(String(b.CONCEPTO), 'es');
  });
}

export function collectConceptosIngreso(detalles, serverConceptos = null) {
  if (serverConceptos?.length) return serverConceptos;

  return INGRESO_CATALOG.filter((item) => {
    if (['SALARIO_DIAS', 'HORAEXTRAS'].includes(item.key)) return true;
    return (detalles || []).some((det) => Number(det[item.key] ?? 0) > 0);
  });
}

export function getDescuentoMonto(det, concepto) {
  const key = descuentoKey(concepto.CONCEPTO, concepto.CATEGORIA);
  for (const d of resolveDescuentosDetalle(det)) {
    if (descuentoKey(d.CONCEPTO || d.concepto, d.CATEGORIA || d.categoria) === key) {
      return Number(d.MONTO ?? d.monto ?? 0);
    }
  }
  return 0;
}

export function totalConceptoDescuento(detalles, concepto) {
  return (detalles || []).reduce((sum, det) => sum + getDescuentoMonto(det, concepto), 0);
}

export function totalConceptoIngreso(detalles, key) {
  return (detalles || []).reduce((sum, det) => sum + Number(det[key] ?? 0), 0);
}

export const PATRONAL_CATALOG = [
  { key: 'AFP_PATRONAL', label: 'AFP Patronal' },
  { key: 'ISSS_PATRONAL', label: 'ISSS Patronal' },
  { key: 'INSAFORP_PATRONAL', label: 'INSAFORP Patronal' },
];

export function getPatronalMonto(det, concepto) {
  if (concepto.key === 'TOTAL_PATRONAL' || concepto.computed) {
    return Number(det.AFP_PATRONAL ?? 0) + Number(det.ISSS_PATRONAL ?? 0) + Number(det.INSAFORP_PATRONAL ?? 0);
  }
  return Number(det[concepto.key] ?? 0);
}

export function collectConceptosPatronal(detalles, serverConceptos = null) {
  if (serverConceptos?.length) return serverConceptos;

  const visible = PATRONAL_CATALOG.filter((item) =>
    (detalles || []).some((det) => Number(det[item.key] ?? 0) > 0)
  );

  if (!visible.length) return [];

  return [...visible, { key: 'TOTAL_PATRONAL', label: 'Total Patronal', computed: true }];
}

export function totalConceptoPatronal(detalles, concepto) {
  return (detalles || []).reduce((sum, det) => sum + getPatronalMonto(det, concepto), 0);
}

/** Totales desde servidor (grilla paginada). */
export function totalConceptoIngresoServer(totalesConceptos, key) {
  return Number(totalesConceptos?.ingreso?.[key] ?? 0);
}

export function totalConceptoDescuentoServer(totalesConceptos, concepto) {
  const key = descuentoKey(concepto.CONCEPTO, concepto.CATEGORIA);
  const row = (totalesConceptos?.descuento ?? []).find(
    (d) => descuentoKey(d.CONCEPTO, d.CATEGORIA) === key
  );
  return Number(row?.MONTO ?? 0);
}

export function totalConceptoPatronalServer(totalesConceptos, concepto) {
  return Number(totalesConceptos?.patronal?.[concepto.key] ?? 0);
}

export function abreviarContrato(nombre) {
  if (!nombre) return '—';
  if (nombre.includes('Honorarios')) return 'Honorarios';
  if (nombre.includes('Comercial')) return 'Comercial';
  if (nombre.includes('Permanente')) return 'Permanente';
  return nombre.length > 18 ? `${nombre.slice(0, 16)}…` : nombre;
}
