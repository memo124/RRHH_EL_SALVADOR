# Frontend UX — Diálogos, modales y notificaciones

Guía de los componentes reutilizables que reemplazan `alert()`, `confirm()` y `prompt()` nativos del navegador, y del shell de modales de pantalla completa.

---

## Resumen

| Componente | Archivo | Propósito |
|------------|---------|-----------|
| `AppDialog` | `resources/js/components/AppDialog.vue` | Confirmaciones, avisos, prompts y formularios dinámicos |
| `useDialog` | `resources/js/composables/useDialog.js` | API programática (`dialog.confirm`, `dialog.form`, etc.) |
| `AppModalShell` | `resources/js/components/AppModalShell.vue` | Modales de formulario con overlay a pantalla completa |
| `AppToast` | `resources/js/components/AppToast.vue` | Notificaciones no bloqueantes (éxito / error) |
| `useToast` | `resources/js/composables/useToast.js` | API de toasts |

`AppDialog` y `AppToast` se montan una sola vez en `resources/js/App.vue`.

---

## Sistema de diálogos (`useDialog`)

### Importación

```javascript
import { dialog, dialogEmpleadoLabel } from '../../composables/useDialog';
import { useToast } from '../../composables/useToast';

const toast = useToast();
```

### Plantillas disponibles

| Método | Retorno | Uso |
|--------|---------|-----|
| `dialog.confirm(options)` | `Promise<boolean>` | Sí / No |
| `dialog.alert(options)` | `Promise<void>` | Aviso con botón "Entendido" |
| `dialog.prompt(options)` | `Promise<string \| null>` | Un campo (texto, número o textarea) |
| `dialog.form(options)` | `Promise<object \| null>` | Formulario con varios campos |
| `dialog.open(options)` | `Promise<{ confirmed, values }>` | Control total; infiere plantilla |

### Variantes visuales

`variant`: `danger` | `warning` | `info` | `success`

Afecta icono, borde del panel y color del botón principal.

### Ejemplos

**Confirmación simple**

```javascript
if (!await dialog.confirm({
  title: 'Inactivar empleado',
  message: `¿Está seguro de inactivar a ${emp.NOMBRES}?`,
  variant: 'danger',
  confirmText: 'Sí, inactivar',
  cancelText: 'No',
})) return;
```

**Aviso informativo**

```javascript
await dialog.alert({
  title: 'Planilla sin calcular',
  message: 'Calcule la planilla primero para ver los empleados de esta corrida.',
  variant: 'info',
});
```

**Prompt (un campo)**

```javascript
const anio = await dialog.prompt({
  title: 'Generar períodos',
  message: 'Ingrese el año para generar los períodos laborales:',
  inputLabel: 'Año',
  inputType: 'number',
  inputDefault: String(new Date().getFullYear()),
  inputRequired: true,
  confirmText: 'Generar',
});
if (!anio) return;
```

**Formulario dinámico (textarea, select, checkbox)**

```javascript
const values = await dialog.form({
  title: 'Cancelar incapacidad',
  message: `¿Confirma cancelar el certificado médico de ${dialogEmpleadoLabel(row)}?`,
  variant: 'danger',
  confirmText: 'Sí, cancelar',
  fields: [
    {
      name: 'motivo',
      type: 'textarea',
      label: 'Motivo de cancelación',
      required: true,
      rows: 3,
      placeholder: 'Ej: certificado duplicado…',
    },
    {
      name: 'notificar',
      type: 'checkbox',
      label: 'Notificar al empleado',
    },
    {
      name: 'prioridad',
      type: 'select',
      label: 'Prioridad',
      options: [
        { value: 'baja', label: 'Baja' },
        { value: 'alta', label: 'Alta' },
      ],
    },
  ],
});
if (!values) return;
// values.motivo, values.notificar, values.prioridad
```

### Tipos de campo en `dialog.form`

| `type` | Control |
|--------|---------|
| `text` | `<input type="text">` (por defecto) |
| `number` | `<input type="number">` |
| `textarea` | `<textarea>` |
| `select` | `AsyncSelect` (requiere `options: [{ value, label }]`) |
| `checkbox` | Casilla; el `label` va junto al control |

Campos con `required: true` se validan antes de confirmar; los errores se muestran bajo cada campo.

### Helper `dialogEmpleadoLabel(row)`

Evita mostrar `undefined` cuando la API no envía `NOMBRE_EMPLEADO`. Resuelve en este orden:

1. `NOMBRE_EMPLEADO`
2. `NOMBRES` + `APELLIDO_1` + `APELLIDO_2`
3. `CODIGOEMPLEADO`
4. `"registro"`

### Comportamiento UX

- **Teleport** a `<body>` — cubre toda la ventana, incluido el header.
- **Backdrop** con blur; clic fuera cancela (excepto en plantilla `alert`).
- **Scroll bloqueado** en `body` (`class="modal-open"`) mientras el diálogo está abierto.
- **z-index** `10050` (por encima de modales de formulario en `9999`).
- **Tema oscuro** heredado de clases globales (`input-base`, `btn-primary`, etc.).

### Extender con nuevas plantillas

1. Añadir el tipo en `DialogTemplate` en `useDialog.js`.
2. Agregar bloque en `AppDialog.vue` (template + validación en `validate()`).
3. Opcional: exportar un atajo en el objeto `dialog`.

---

## Modales de formulario (`AppModalShell`)

Para formularios grandes (CRUD), usar `AppModalShell` en lugar de `fixed inset-0` dentro de `<main>`.

```vue
<AppModalShell :open="showModal" @close="showModal = false">
  <div class="modal-panel modal-panel-lg">
    <div class="modal-header">...</div>
    <div class="modal-body">...</div>
    <div class="modal-footer">...</div>
  </div>
</AppModalShell>
```

Ventajas:

- Overlay a pantalla completa vía `Teleport`.
- Backdrop no interactuable con la página de fondo.
- Misma clase `modal-open` para bloqueo de scroll.

Vistas que ya lo usan: **Empleados**, **Conceptos por Empleado**.

Clases CSS en `resources/css/app.css`: `.modal-root`, `.modal-backdrop`, `.modal-scroll`, `.modal-panel`, `.modal-header`, `.modal-footer`.

---

## Toasts (`useToast`)

Para feedback **no bloqueante** después de una acción exitosa o fallida:

```javascript
toast.success('Incapacidad cancelada');
toast.error('Error', 'No se pudo cancelar la incapacidad.');
```

Combinación recomendada:

- **Diálogo** → confirmar o capturar datos antes de actuar.
- **Toast** → informar resultado después de la petición API.

---

## Vistas migradas (sin `alert` / `confirm` / `prompt` nativos)

Todas las pantallas listadas usan `dialog.*` o `toast.*`:

- Asistencia, Catálogo RRHH, Catálogos MH, Conceptos por Empleado
- Corporativo, Deducciones, Empleados, Geografía
- Incapacidades, Liquidaciones, Parámetros aguinaldo, Períodos
- Planilla, Seguridad, Tipo contratación

---

## Tema oscuro en formularios

Reglas globales en `resources/css/app.css`:

- `.input-base`, `.label-base` — inputs y labels en light/dark.
- `.modal-panel`, `.modal-body` — fondos y bordes de modales.
- Loader opaco en `App.vue` (`.app-loader-track` / `.app-loader-bar`).
