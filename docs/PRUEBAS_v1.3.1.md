# Plan de pruebas — v1.3.1

Fecha de referencia: 2026-08-18  
Entorno: Laravel 11 + Vue 3 + PostgreSQL  
Usuario demo: `admin@rrhh.sv` / `Admin123!`

**Post-migración:** `php artisan migrate` y re-login para permisos `GESTION_HUMANA_*`, `PORTAL_*` y auditoría.

---

## 1. Instalación y setup

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 1.1 | Migraciones nuevas | `php artisan migrate` | Tablas GH, vacaciones, reclutamiento, portal, auditoría, ISSS_MOVIMIENTO, contabilidad |
| 1.2 | Bootstrap limpio | `db:seed --class=BootstrapSeeder` | Catálogos + RBAC sin demo |
| 1.2b | Limpiar ejercicios | `php artisan db:clean-ejercicio` | Vacía datos operativos; conserva catálogos (ver [README](../README.md#limpiar-base-de-datos-para-ejercicios)) |
| 1.2c | Post-limpieza demo | Tras 1.2b → `db:seed --class=DemoSeeder` | Login `admin@rrhh.sv` / `Admin123!` con catálogos intactos |
| 1.3 | Wizard setup | Abrir `/setup` | Registra empresa y administrador |
| 1.4 | Demo | `migrate --seed` | Login `admin@rrhh.sv` / `Admin123!` |

---

## 2. UX — menú lateral

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 2.1 | Textos largos en grupos | Sidebar expandido con nombres largos de módulo | Título del grupo hace wrap (`break-words`); chevron alineado arriba |
| 2.2 | Opciones del menú | Abrir grupo con opciones de nombre largo | Enlaces en varias líneas; sin truncado con ellipsis |
| 2.3 | Sidebar colapsado | Colapsar menú en escritorio | Solo iconos centrados; tooltip/hover de grupo OK |

---

## 3. Gestión Humana — Fase 1

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 3.1 | Calendario | `/calendario` → crear evento | Evento visible; CRUD OK |
| 3.2 | Encuesta | Crear, publicar, responder | Resultados; job de recordatorio no rompe publicación |
| 3.3 | Encuesta confidencial | `ANONIMA=true` + reportes | Agregados sin nombre; un solo respuesta por empleado |
| 3.4 | Formulario público | Campaña → invitación → `/actualizar-datos/{token}` | Empleado envía; RRHH aprueba y aplica expediente |
| 3.5 | Adjuntos | Documentos empleado → subir PDF | Descarga autenticada; máx. 10 MB |

---

## 4. Vacaciones, permisos y capacitaciones

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 4.1 | Solicitud vacaciones | Crear → aprobar | Descuenta saldo; evento en calendario; planilla Vacaciones / `INTEGRADO_PLANILLA` |
| 4.2 | Rechazo / cancelación | Rechazar pendiente | Estado y motivo correctos |
| 4.3 | Capacitación | Publicar → inscribir → asistencia → completar | Certificado opcional; evento calendario |

---

## 5. Reclutamiento y evaluación

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 5.1 | Vacante + candidato | Crear vacante, registrar candidato con CV | Pipeline y descarga CV |
| 5.2 | Entrevista | Programar entrevista | Evento en calendario |
| 5.3 | Contratar | `contratar` candidato | Crea empleado / onboarding |
| 5.4 | Evaluación | Periodo → asignar → metas → completar | Puntuación global calculada |

---

## 6. Cumplimiento SV

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 6.1 | ISSS / AFP / INSAFORP | Preview + export CSV de planilla cerrada | Archivo UTF-8 BOM; techos cotizables coherentes |
| 6.2 | Renta F-14 | Preview + CSV + PDF resumen | Acumulado anual por empleado |
| 6.3 | Aguinaldo | Preview → crear planilla → calcular | Encabezado tipo Aguinaldo + cálculo estándar |
| 6.4 | Retención 10% | Preview/export | Consolida lo ya retenido en planilla |
| 6.5 | Altas/bajas ISSS | Crear empleado / liquidar | Movimiento pendiente; export + marcar enviado |

---

## 7. Portal del empleado

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 7.1 | Acceso portal | Usuario con `PORTAL_*` | Layout `/portal` sin menú admin completo |
| 7.2 | Boletas | Listar boletas | Paginación OK |
| 7.3 | Permisos propios | Crear solicitud | Visible en bandeja RRHH |
| 7.4 | Encuestas / evaluaciones | Responder / ver propias | Solo datos del empleado autenticado |

---

## 8. Auditoría, API y asistencia

| # | Caso | Pasos | Resultado esperado |
|---|------|-------|-------------------|
| 8.1 | Auditoría | Editar empleado/descuento | Registro en Seguridad → Auditoría |
| 8.2 | Scramble | `/docs/api` (local) | Spec OpenAPI navegable |
| 8.3 | Import CSV marcaciones | Asistencia → importar CSV | Filas válidas importadas; errores reportados |
| 8.4 | Bearer token | `POST /api/login` + header | Endpoints autenticados responden 200 |

---

## Regresión rápida v1.3.0 / v1.2.0

Verificar que siguen OK: módulos GH, cumplimiento SV, portal, dashboard KPIs, bitácora errores API, toasts CRUD, PDF/Excel planilla y boletas, recálculo ISR (si aplica). Detalle en [PRUEBAS_v1.3.0.md](PRUEBAS_v1.3.0.md) y [PRUEBAS_v1.2.0.md](PRUEBAS_v1.2.0.md).
