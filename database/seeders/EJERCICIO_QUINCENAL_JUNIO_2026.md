# Ejercicio: Planillas quincenales Ene–Jun 2026 (1ra quincena) + Recálculo ISR

## Objetivo

Validar que el **recálculo semestral de renta (junio)** funciona correctamente comparando:

1. Las planillas **enero–mayo** (retención quincenal normal, **sin ajuste**).
2. La planilla de **junio 1ra quincena** (retención quincenal normal **+ ajuste semestral**).

> **Importante:** `PLANILLA.RECALCULADA = true` significa “planilla calculada”, **no** “ajuste de junio aplicado”. El ajuste de junio solo afecta `RENTA_EMPLEADO` y la tabla `ACUMULADO_RECALCULO`.

---

## Cómo cargar el ejercicio

```bash
php artisan db:seed --class=DemoQuincenalJunioRecalculoSeeder
```

El seeder crea periodos, empleados, planillas **20–25** y las calcula en orden cronológico. Al final imprime el reporte de verificación en consola.

---

## Datos del ejercicio

| Concepto | Valor |
|----------|-------|
| Empresa | GICA (ID 3) |
| Tipo planilla ordinaria | Ordinaria (ID 1) |
| Planilla vacaciones | Vacaciones (ID 2), planilla **26**, marzo 2026 |
| Frecuencia | Quincenal (ID 2, 15 días) |
| Periodos | ID 10–15 (ordinarias) + **16 (vacaciones mar)** |
| Planillas | ID **20–25** ordinarias + **26 vacaciones** |
| Empleados | 401–404 (contrato permanente, tabla ISR) |

### Vacaciones en el recálculo

| Regla | Comportamiento |
|-------|----------------|
| ¿Suma al acumulado ene–may? | **Sí** — `TIPO_PLANILLA.APLICA_RENTA = true` |
| ¿Retiene ISR al pagarse? | **Sí** — tabla quincenal normal del periodo |
| ¿Recibe ajuste de junio? | **No** — el ajuste semestral va en la **planilla ordinaria** de junio |
| ¿Aguinaldo entra al acumulado? | **No** — `APLICA_RENTA = false` |

En el ejercicio, **María López (402)** cobra vacaciones en marzo (planilla 26) además de su quincena ordinaria. Ese ingreso extra aumenta el ajuste de renta en junio.

### Empleados demo

| ID | Código | Salario mensual | Devengado/quincena | Notas |
|----|--------|-----------------|-------------------|-------|
| 401 | GICA-EJ-401 | $880 | $440 | Renta baja ene–may |
| 402 | GICA-EJ-402 | $1,200 | $600 | **Vacaciones en mar (planilla 26)** |
| 403 | GICA-EJ-403 | $2,000 | $1,000 | Techo ISSS $1,000 |
| 404 | GICA-EJ-404 | $3,000 | $1,500 | Tramo IV quincenal |

Sin horas extras, préstamos ni incapacidades — salario puro para facilitar la verificación manual.

---

## Fórmulas (ene–mayo, meses normales)

Por cada quincena:

```
salarioDias       = (SALARIOMENSUAL / 30) × 15
AFP empleado      = devengado × 7.25%
ISSS empleado     = min(devengado, $1,000) × 3%   ← 1ra quincena: acumulado mes = 0
base ISR          = devengado - AFP - ISSS
renta             = tabla quincenal (ID_FRECUENCIAISR = 2)
líquido           = devengado - AFP - ISSS - renta
```

**AFP e ISSS no cambian en junio.** Solo cambia la **renta**.

---

## Recálculo de junio (lo que NO aplica ene–mayo)

Cuando `PERIODO_LABORAL.FECHAFIN` cae en **junio** y el empleado tiene `APLICA_RENTA_TABLA = true`:

### Paso 1 — Acumular enero–mayo (excluye planilla actual)

Incluye **ordinarias, vacaciones y extraordinarias** (`TIPO_PLANILLA.APLICA_RENTA = true`).
Excluye aguinaldo y tipos sin renta.

```
baseAcumulada   = Σ (DEVENGADO_GRAVADO - AFP - ISSS)  ← ordinarias + vacaciones ene–may
rentaAcumulada  = Σ RENTA_EMPLEADO                   ← incluye renta retenida en planilla vacaciones
```

Solo cuenta planillas con `RECALCULADA = true` y `ANULADA = false`.

### Paso 2 — Periodo junio

```
baseIsrJunio        = devengado - AFP - ISSS  (quincena actual)
rentaNormalJunio    = ISR con tabla QUINCENAL sobre baseIsrJunio
baseTotalSemestre   = baseAcumulada + baseIsrJunio
```

### Paso 3 — Ajuste semestral (solo planilla **ordinaria** en junio/diciembre)

```
rentaDebidaSemestre = ISR con tabla MENSUAL sobre baseTotalSemestre
ajuste              = rentaDebidaSemestre - rentaAcumulada - rentaNormalJunio
rentaFinalJunio     = max(0, rentaNormalJunio + ajuste)   ← solo en planilla tipo 1
```

La planilla de **vacaciones** pagada en junio retendría ISR normal del periodo, sin ajuste.

Se registra en `ACUMULADO_RECALCULO` (MSR, RENTA, RENTA_PENDIENTE_APLICAR).

---

## Resultados esperados — Ene–May (planillas 20–24)

Todos los meses son iguales (mismo salario, 15 días, sin variaciones).

### Empleado 401 — $880/mes

| Campo | Valor |
|-------|-------|
| Devengado | $440.00 |
| AFP | $31.90 |
| ISSS | $13.20 |
| Renta | **$20.82** |
| Líquido | $374.08 |

### Empleado 402 — con vacaciones en marzo (planilla 26)

**Marzo — quincena ordinaria (22):** igual que antes ($48.18 renta).

**Marzo — vacaciones (26):** 15 días, $600 devengado adicional.

| Campo | Vacaciones (26) |
|-------|-------------------|
| Devengado | $600.00 |
| AFP | $43.50 |
| ISSS | **$12.00** (techo mensual: ya se cotizó $18 en quincena del mismo mes) |
| Renta | **$49.38** (ISR normal, **sin** ajuste de junio) |
| Líquido | $495.12 |

**Junio ordinaria (25) — con vacaciones en acumulado:**

| Concepto | Sin vacaciones | **Con vacaciones (26)** |
|----------|----------------|-------------------------|
| Base acum. ene–may | $2,692.50 | **$3,237.00** |
| Renta retenida ene–may | $240.90 | **$290.28** |
| **Renta final junio** | $405.54 | **$519.51** |

La diferencia ($113.97 más de renta en junio) es el efecto de incluir las vacaciones en el recálculo semestral.

### Empleado 402 — $1,200/mes (solo ordinarias, referencia)

| Campo | Valor |
|-------|-------|
| Devengado | $600.00 |
| AFP | $43.50 |
| ISSS | $18.00 |
| Renta | **$48.18** |
| Líquido | $490.32 |

### Empleado 403 — $2,000/mes

| Campo | Valor |
|-------|-------|
| Devengado | $1,000.00 |
| AFP | $72.50 |
| ISSS | $30.00 |
| Renta | **$119.98** |
| Líquido | $777.52 |

### Empleado 404 — $3,000/mes

| Campo | Valor |
|-------|-------|
| Devengado | $1,500.00 |
| AFP | $108.75 |
| ISSS | $30.00 |
| Renta | **$246.94** |
| Líquido | $1,114.31 |

---

## Resultados esperados — Junio con recálculo (planilla 25)

### Empleado 401

| Concepto | Valor |
|----------|-------|
| Devengado / AFP / ISSS | $440 / $31.90 / $13.20 (igual que antes) |
| Renta normal quincenal | $20.82 |
| Base acum. ene–may | $1,974.50 |
| Renta retenida ene–may | $104.10 |
| Base total semestre (MSR) | $2,369.40 |
| Renta debida semestre (tabla mensual) | $387.96 |
| **Ajuste junio** | **$263.04** |
| **Renta final junio** | **$283.86** |
| **Líquido junio** | **$111.04** |

### Empleado 402 (incluye vacaciones mar en acumulado)

| Concepto | Valor |
|----------|-------|
| Renta normal quincenal jun | $48.18 |
| Base acum. ene–may (+ vac.) | $3,237.00 |
| Renta retenida ene–may (+ vac.) | $290.28 |
| Base total semestre | $3,831.00 |
| Renta debida semestre | $857.97 |
| **Ajuste junio** | **$471.33** |
| **Renta final junio (ord.)** | **$519.51** |
| **Líquido junio** | **$18.99** |

### Empleado 403

| Concepto | Valor |
|----------|-------|
| Renta normal quincenal | $119.98 |
| Base acum. ene–may | $4,487.50 |
| Renta retenida ene–may | $599.90 |
| Base total semestre | $5,385.00 |
| Renta debida semestre | $1,292.64 |
| **Ajuste junio** | **$572.76** |
| **Renta final junio** | **$692.74** |
| **Líquido junio** | **$204.76** |

### Empleado 404

| Concepto | Valor |
|----------|-------|
| Renta normal quincenal | $246.94 |
| Base acum. ene–may | $6,806.25 |
| Renta retenida ene–may | $1,234.70 |
| Base total semestre | $8,167.50 |
| Renta debida semestre | $2,127.39 |
| **Ajuste junio** | **$645.75** |
| **Renta final junio** | **$892.69** |
| **Líquido junio** | **$468.56** |

---

## Cómo corroborar en la aplicación

1. Ir a **Planilla** → abrir planillas 20–25 (GICA, quincenal).
2. **Ene–May:** renta debe ser la de la tabla quincenal (columna Renta arriba).
3. **Junio (planilla 25):** renta debe ser **mucho mayor** (ajuste semestral).
4. Comparar detalle de empleado 402 en junio:
   - Renta esperada: **$405.54** (no $48.18).
5. Consultar auditoría (SQL):

```sql
SELECT e."CODIGOEMPLEADO", a."MSR", a."RENTA", a."RENTA_PENDIENTE_APLICAR", a."MES", a."ANIO"
FROM "ACUMULADO_RECALCULO" a
JOIN "EMPLEADO" e ON e."ID_EMPLEADO" = a."ID_EMPLEADO"
WHERE a."ID_PLANILLA" = 25
ORDER BY e."ID_EMPLEADO";
```

---

## Qué demuestra este ejercicio

| Mes | ¿Recálculo ISR? | Renta |
|-----|-----------------|-------|
| Ene–May | No | Tabla quincenal sobre base del periodo |
| Jun | **Sí** | Tabla quincenal + ajuste para cuadrar semestre con tabla **mensual** |

La diferencia entre **renta normal** ($48.18 para emp. 402) y **renta final junio** ($405.54) es exactamente el ajuste que el sistema retiene de más (o de menos) porque en ene–may se aplicó tabla quincenal periodo a periodo, y en junio se “true-up” contra el impuesto debido sobre el acumulado del semestre.

---

## Bug corregido durante el ejercicio

`PayrollRentRecalculationService` buscaba la columna inexistente `NOMBREFRECUENCIA`; se corrigió a `FRECUENCIAISR` para resolver la tabla mensual en el recálculo.

---

## Orden obligatorio de cálculo

```
Planilla 20 (Ene) → 21 (Feb) → 22 (Mar) → 23 (Abr) → 24 (May) → 25 (Jun)
```

Si junio se calcula antes que ene–may, el acumulado estará vacío y **no habrá ajuste** — ese es el error más común al probar.
