import { ref } from 'vue';
import { useToast } from './useToast';

function parseFilename(response) {
  const disposition = response.headers.get('content-disposition') || '';
  const match = disposition.match(/filename=\"?([^\";\n]+)\"?/i);
  return match?.[1] || null;
}

function buildReportUrl(path, extraParams = {}) {
  const url = new URL(path, window.location.origin);
  const token = localStorage.getItem('token');
  if (token) {
    url.searchParams.set('token', token);
  }
  Object.entries(extraParams).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      url.searchParams.set(key, String(value));
    }
  });
  return url.toString();
}

async function fetchReport(path, { asText = false, params = {} } = {}) {
  const token = localStorage.getItem('token');
  if (!token) {
    throw new Error('Debe iniciar sesión para generar reportes.');
  }

  const response = await fetch(buildReportUrl(path, params), {
    headers: { Accept: asText ? 'text/html' : '*/*' },
  });

  if (!response.ok) {
    let message = 'No se pudo generar el reporte.';
    try {
      const data = await response.json();
      message = data.error || data.message || message;
    } catch {
      message = response.statusText || message;
    }
    throw new Error(message);
  }

  if (asText) {
    return { text: await response.text() };
  }

  return {
    blob: await response.blob(),
    filename: parseFilename(response),
  };
}

export function usePlanillaReports() {
  const toast = useToast();
  const reportLoading = ref(null);
  const reportMessage = ref('');

  const start = (key, message) => {
    reportLoading.value = key;
    reportMessage.value = message;
  };

  const stop = () => {
    reportLoading.value = null;
    reportMessage.value = '';
  };

  const openPrintWhenReady = async (path, { key, label, params = {} } = {}) => {
    if (reportLoading.value) return;
    start(key, `Generando ${label}…`);
    try {
      const { text } = await fetchReport(path, { asText: true, params });
      const win = window.open('', '_blank');
      if (!win) {
        throw new Error('Permita ventanas emergentes para abrir el reporte.');
      }
      win.document.open();
      win.document.write(text);
      win.document.close();
      toast.success(`${label} listo`, 'Se abrió en una nueva pestaña. Use el botón Imprimir o Ctrl+P.');
    } catch (err) {
      toast.error('Error al generar reporte', err.message);
    } finally {
      stop();
    }
  };

  const openPdfViewWhenReady = async (path, { key, label, params = {} } = {}) => {
    if (reportLoading.value) return;
    start(key, `Generando ${label}…`);
    try {
      const { blob } = await fetchReport(path, { params });
      const pdfBlob = blob.type === 'application/pdf'
        ? blob
        : new Blob([blob], { type: 'application/pdf' });
      const url = window.URL.createObjectURL(pdfBlob);
      const win = window.open(url, '_blank');
      if (!win) {
        window.URL.revokeObjectURL(url);
        throw new Error('Permita ventanas emergentes para ver el PDF.');
      }
      setTimeout(() => window.URL.revokeObjectURL(url), 120000);
      toast.success(`${label}`, 'PDF listo en una nueva pestaña.');
    } catch (err) {
      toast.error('Error al generar PDF', err.message);
    } finally {
      stop();
    }
  };

  const downloadFileWhenReady = async (path, { key, label, fallbackName, params = {} } = {}) => {
    if (reportLoading.value) return;
    start(key, `Generando ${label}…`);
    try {
      const { blob, filename } = await fetchReport(path, { params });
      const name = filename || fallbackName;
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = name;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      toast.success('Archivo generado', `${name} se descargó correctamente.`);
    } catch (err) {
      toast.error('Error al descargar', err.message);
    } finally {
      stop();
    }
  };

  const isLoading = (key) => reportLoading.value === key;
  const isBusy = () => !!reportLoading.value;

  return {
    reportLoading,
    reportMessage,
    openPrintWhenReady,
    openPdfViewWhenReady,
    downloadFileWhenReady,
    buildReportUrl,
    isLoading,
    isBusy,
    toast,
  };
}
