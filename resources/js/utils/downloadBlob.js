/**
 * Descarga la respuesta blob de una petición axios (responseType: 'blob'),
 * usando el nombre de archivo del header Content-Disposition si está presente.
 */
export function downloadBlobResponse(response, fallbackName) {
  const disposition = response.headers?.['content-disposition'] || '';
  const match = disposition.match(/filename="?([^";\n]+)"?/i);
  const filename = match?.[1] || fallbackName;

  const url = window.URL.createObjectURL(new Blob([response.data]));
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  window.URL.revokeObjectURL(url);

  return filename;
}

/** Extrae el mensaje de error de una respuesta blob con JSON de error. */
export async function getBlobErrorMessage(err, fallback) {
  if (err.response?.data instanceof Blob) {
    try {
      const text = await err.response.data.text();
      return JSON.parse(text).error || fallback;
    } catch {
      return fallback;
    }
  }
  return err.response?.data?.error || fallback;
}
