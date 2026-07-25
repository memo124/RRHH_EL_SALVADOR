/** Lee campos con nombre en mayúsculas o minúsculas (PostgreSQL). */
export function field(row, ...keys) {
  if (!row) return null;
  for (const key of keys) {
    if (row[key] !== undefined && row[key] !== null) return row[key];
  }
  return null;
}

export function fieldStr(row, ...keys) {
  const v = field(row, ...keys);
  return v === null || v === undefined ? '' : String(v);
}
