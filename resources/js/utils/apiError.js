/**
 * Mensaje amigable desde respuestas de error de la API.
 */
export function getApiErrorMessage(err, fallback = 'Ocurrió un error inesperado.') {
  const data = err?.response?.data;
  if (!data) {
    return err?.message || fallback;
  }
  if (typeof data.message === 'string' && data.message.trim()) {
    return data.message;
  }
  if (typeof data.error === 'string' && data.error.trim()) {
    return data.error;
  }
  if (data.errors && typeof data.errors === 'object') {
    const first = Object.values(data.errors).flat().find(Boolean);
    if (first) return String(first);
  }
  return fallback;
}

export function getApiErrorReference(err) {
  return err?.response?.data?.reference || null;
}

export function isServerError(err) {
  const status = err?.response?.status;
  return typeof status === 'number' && status >= 500;
}

/** Petición abortada (navegación, desmontaje de componente, etc.). */
export function isRequestCancelled(err) {
  return err?.code === 'ERR_CANCELED'
    || err?.name === 'CanceledError'
    || err?.message === 'canceled';
}
