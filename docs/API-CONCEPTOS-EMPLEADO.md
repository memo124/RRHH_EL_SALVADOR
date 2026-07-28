# API — Conceptos por empleado (detalle de pagos)

Endpoints añadidos o ampliados para consultar historial de aplicaciones en planilla y gestionar abonos de préstamos.

Permiso requerido en todas las rutas: `DEDUCCIONES_VIEW` (lectura) / `DEDUCCIONES_UPDATE` / `DEDUCCIONES_DELETE` según operación.

---

## Préstamos

### Detalle con abonos

```
GET /api/prestamos/{id}
```

**Respuesta**

```json
{
  "prestamo": { "ID_PRESTAMO": 1, "MONTOPRESTAMO": 500, "SALDO_ACTUAL": 200, ... },
  "abonos": [
    {
      "ID_PRESTAMOABONO": 10,
      "MONTOABONADO": 50,
      "FECHAABONO": "2026-01-15",
      "ID_PLANILLA": 3,
      "NUMERO_PLANILLA": "ENE-2026-01"
    }
  ],
  "resumen": {
    "total_abonado": 300,
    "saldo_actual": 200,
    "cuotas_pagadas": 6
  }
}
```

**Uso en frontend:** botón **Detalle** en la pestaña Préstamos (`ConceptosEmpleado/Index.vue`).

---

### Eliminar cuota / abono

```
DELETE /api/prestamos/{prestamoId}/abonos/{abonoId}
```

Elimina el registro en `PRESTAMO_ABONO` y recalcula:

- `SALDO_ACTUAL` del préstamo
- `CUOTAS_PAGADAS` (conteo de abonos)
- Reactiva el préstamo si el saldo vuelve a ser mayor que cero y estaba cancelado

**Nota:** no modifica la planilla ya cerrada; el diálogo de confirmación lo advierte al usuario.

**Respuesta**

```json
{
  "message": "Abono eliminado.",
  "resumen": { "total_abonado": 250, "saldo_actual": 250, "cuotas_pagadas": 5 }
}
```

---

## Descuentos por empleado

### Historial de aplicaciones

```
GET /api/descuentos-empleado/{id}/historial
```

Devuelve filas de descuentos aplicados en planillas (fecha, planilla, concepto, monto).

**Uso en frontend:** botón **Detalle** en la pestaña Descuentos.

---

## Otros ingresos

### Historial de aplicaciones

```
GET /api/otros-ingresos/{id}/historial
```

Devuelve ingresos adicionales aplicados en planillas.

**Uso en frontend:** botón **Detalle** en la pestaña Ingresos.

---

## Incapacidades — cancelar con motivo

```
POST /api/incapacidades/{id}/cancelar
Content-Type: application/json

{ "motivo": "Certificado duplicado" }
```

| Campo | Tipo | Reglas |
|-------|------|--------|
| `motivo` | string | Opcional, máx. 500 caracteres |

Actualiza `ESTADO_INCAPACIDAD = 'CANCELADA'`. Si se envía `motivo`, se guarda en `OBSERVACIONES`.

**Uso en frontend:** `dialog.form()` con textarea obligatorio antes de llamar a la API; toast de éxito/error al terminar.

---

## Flujo UI recomendado

```
Usuario → dialog.confirm / dialog.form
       → API (POST/DELETE/GET)
       → toast.success / toast.error
       → recargar listado o modal de detalle
```

Ver también: [FRONTEND-UX.md](FRONTEND-UX.md)
