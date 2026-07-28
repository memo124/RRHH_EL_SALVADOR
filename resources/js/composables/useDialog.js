import { ref } from 'vue';

/** @typedef {'confirm'|'alert'|'prompt'|'form'} DialogTemplate */
/** @typedef {'danger'|'warning'|'info'|'success'} DialogVariant */

/**
 * @typedef {Object} DialogField
 * @property {string} name
 * @property {'text'|'textarea'|'number'|'select'|'checkbox'} [type]
 * @property {string} [label]
 * @property {string} [placeholder]
 * @property {boolean} [required]
 * @property {number} [rows]
 * @property {string|number|boolean} [defaultValue]
 * @property {{ value: string|number|boolean, label: string }[]} [options]
 */

const dialogState = ref(null);
let resolver = null;

const defaults = {
  template: 'confirm',
  title: '',
  message: '',
  variant: 'warning',
  confirmText: 'Confirmar',
  cancelText: 'Cancelar',
  inputType: 'text',
  inputLabel: '',
  inputPlaceholder: '',
  inputRequired: false,
  inputDefault: '',
  fields: [],
};

function finish(result) {
  const resolve = resolver;
  resolver = null;
  dialogState.value = null;
  if (resolve) resolve(result);
}

export function resolveDialog(confirmed, values = null) {
  finish({ confirmed, values });
}

export function cancelDialog() {
  finish({ confirmed: false, values: null });
}

/**
 * @param {Partial<typeof defaults> & { template?: DialogTemplate }} options
 * @returns {Promise<{ confirmed: boolean, values: Record<string, unknown>|null }>}
 */
function inferTemplate(options) {
  if (options.template) return options.template;
  if (options.fields?.length) return 'form';
  if (options.inputLabel || options.inputType) return 'prompt';
  return 'confirm';
}

export function openDialog(options) {
  return new Promise((resolve) => {
    resolver = resolve;
    const template = inferTemplate(options);
    dialogState.value = {
      ...defaults,
      ...options,
      template,
      fieldValues: buildInitialValues({ ...options, template }),
      fieldErrors: {},
    };
  });
}

function buildInitialValues(options) {
  if (options.fields?.length) {
    const values = {};
    for (const field of options.fields) {
      values[field.name] = field.defaultValue ?? (field.type === 'checkbox' ? false : '');
    }
    return values;
  }
  if (options.template === 'prompt' || options.inputLabel || options.inputType) {
    return { input: options.inputDefault ?? '' };
  }
  return {};
}

/** @returns {Promise<boolean>} */
export async function dialogConfirm(options) {
  if (typeof options === 'string') {
    options = { message: options };
  }
  const result = await openDialog({ template: 'confirm', ...options });
  return result.confirmed === true;
}

/** @returns {Promise<void>} */
export async function dialogAlert(options) {
  if (typeof options === 'string') {
    options = { message: options };
  }
  await openDialog({
    template: 'alert',
    title: 'Aviso',
    confirmText: 'Entendido',
    variant: 'info',
    ...options,
  });
}

/** @returns {Promise<string|null>} */
export async function dialogPrompt(options) {
  if (typeof options === 'string') {
    options = { message: options };
  }
  const result = await openDialog({ template: 'prompt', ...options });
  if (!result.confirmed) return null;
  return result.values?.input ?? null;
}

/** @returns {Promise<Record<string, unknown>|null>} */
export async function dialogForm(options) {
  const result = await openDialog({ template: 'form', ...options });
  if (!result.confirmed) return null;
  return result.values;
}

export function useDialog() {
  return {
    dialogState,
    open: openDialog,
    confirm: dialogConfirm,
    alert: dialogAlert,
    prompt: dialogPrompt,
    form: dialogForm,
    resolve: resolveDialog,
    cancel: cancelDialog,
  };
}

/** Atajo para import directo: `import { dialog } from '...'` */
export const dialog = {
  open: openDialog,
  confirm: dialogConfirm,
  alert: dialogAlert,
  prompt: dialogPrompt,
  form: dialogForm,
};

/** Nombre legible de empleado desde filas de API */
export function dialogEmpleadoLabel(row) {
  if (!row) return 'registro';
  return (
    row.NOMBRE_EMPLEADO
    || [row.NOMBRES, row.APELLIDO_1, row.APELLIDO_2].filter(Boolean).join(' ').trim()
    || row.CODIGOEMPLEADO
    || 'registro'
  );
}
