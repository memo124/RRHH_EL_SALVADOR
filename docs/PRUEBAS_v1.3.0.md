# Plan de pruebas — v1.3.0

Fecha de referencia: 2026-08-10  
Entorno: Laravel 11 + Vue 3 + PostgreSQL  
Usuario demo: `admin@rrhh.sv` / `Admin123!`

**Post-migración:** `php artisan migrate` y re-login para permisos `GESTION_HUMANA_*`, `PORTAL_*` y auditoría.

---

## 1. Instalación y setup

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 1.1 | Migraciones nuevas | `php artisan migrate` | Tablas GH, vacaciones, reclutamiento, portal, auditoría, ISSS_MOVIMIENTO, contabilidad |
| 1.2 | Bootstrap limpio | `db:seed --class=BootstrapSeeder` | Catálogos + RBAC sin demo |
| 1.3 | Wizard setup | Abrir `/setup` | Registra empresa y administrador |
| 1.4 | Demo | `migrate --seed` | Login `admin@rrhh.sv` / `Admin123!` |

---

## 2. Gestión Humana — Fase 1

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 2.1 | Calendario | `/calendario` → crear evento | Evento visible; CRUD OK |
| 2.2 | Encuesta | Crear, publicar, responder | Resultados; job de recordatorio no rompe publicación |
| 2.3 | Encuesta confidencial | `ANONIMA=true` + reportes | Agregados sin nombre; un solo respuesta por empleado |
| 2.4 | Formulario público | Campaña → invitación → `/actualizar-datos/{token}` | Empleado envía; RRHH aprueba y aplica expediente |
| 2.5 | Adjuntos | Documentos empleado → subir PDF | Descarga autenticada; máx. 10 MB |

---

## 3. Vacaciones, permisos y capacitaciones

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 3.1 | Solicitud vacaciones | Crear → aprobar | Descuenta saldo; evento en calendario; planilla Vacaciones / `INTEGRADO_PLANILLA` |
| 3.2 | Rechazo / cancelación | Rechazar pendiente | Estado y motivo correctos |
| 3.3 | Capacitación | Publicar → inscribir → asistencia → completar | Certificado opcional; evento calendario |

---

## 4. Reclutamiento y evaluación

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 4.1 | Vacante + candidato | Crear vacante, registrar candidato con CV | Pipeline y descarga CV |
| 4.2 | Entrevista | Programar entrevista | Evento en calendario |
| 4.3 | Contratar | `contratar` candidato | Crea empleado / onboarding |
| 4.4 | Evaluación | Periodo → asignar → metas → completar | Puntuación global calculada |

---

## 5. Cumplimiento SV

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 5.1 | ISSS / AFP / INSAFORP | Preview + export CSV de planilla cerrada | Archivo UTF-8 BOM; techos cotizables coherentes |
| 5.2 | Renta F-14 | Preview + CSV + PDF resumen | Acumulado anual por empleado |
| 5.3 | Aguinaldo | Preview → crear planilla → calcular | Encabezado tipo Aguinaldo + cálculo estándar |
| 5.4 | Retención 10% | Preview/export | Consolida lo ya retenido en planilla |
| 5.5 | Altas/bajas ISSS | Crear empleado / liquidar | Movimiento pendiente; export + marcar enviado |

---

## 6. Portal del empleado

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 6.1 | Acceso portal | Usuario con `PORTAL_*` | Layout `/portal` sin menú admin completo |
| 6.2 | Boletas | Listar boletas | Paginación OK |
| 6.3 | Permisos propios | Crear solicitud | Visible en bandeja RRHH |
| 6.4 | Encuestas / evaluaciones | Responder / ver propias | Solo datos del empleado autenticado |

---

## 7. Auditoría, API y asistencia

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 7.1 | Auditoría | Editar empleado/descuento | Registro en Seguridad → Auditoría |
| 7.2 | Scramble | `/docs/api` (local) | Spec OpenAPI navegable |
| 7.3 | Import CSV marcaciones | Asistencia → importar CSV | Filas válidas importadas; errores reportados |
| 7.4 | Bearer token | `POST /api/login` + header | Endpoints autenticados responden 200 |

---

## Regresión rápida v1.2.0

Verificar que siguen OK: dashboard KPIs, bitácora errores API, toasts CRUD, PDF/Excel planilla y boletas, recálculo ISR (si aplica). Detalle en [PRUEBAS_v1.2.0.md](PRUEBAS_v1.2.0.md).
