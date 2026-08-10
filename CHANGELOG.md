# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/).

## [1.3.0] - 2026-08-10

### Agregado

- **Gestión Humana (Fase 1–3):** encuestas (incl. confidenciales), calendario FullCalendar, formularios de actualización con token público y aprobación, adjuntos tipados, educación/certificaciones/dependientes
- **Vacaciones y permisos:** saldos anuales, flujo de aprobación, sincronización a calendario e integración a planilla tipo Vacaciones (`VacacionesPlanillaService`)
- **Capacitaciones:** publicación, inscripción, asistencia y certificados adjuntos
- **Reclutamiento:** vacantes, candidatos, CV, entrevistas (calendario) y contratación/onboarding a empleado
- **Evaluación de desempeño:** periodos, asignación evaluado/evaluador, metas ponderadas
- **Cumplimiento SV:** exportaciones CSV ISSS, AFP (por AFP), INSAFORP, renta F-14 (+ PDF resumen), aguinaldo, retención 10%; bitácora altas/bajas ISSS
- **Portal del empleado:** layout y APIs (`/portal/*`) para boletas, permisos, encuestas, evaluaciones y perfil
- **Auditoría:** `AuditService` + tabla `AUDITORIA` y consulta en Seguridad
- **Notificaciones:** tablas y API in-app (`NotificationService`)
- **Contabilidad:** tablas de asientos y `AccountingPostingService`
- **Setup wizard:** `/setup` + `SetupService` / `BootstrapSeeder` / `RbacSeeder`
- **API OpenAPI:** Scramble (`/docs/api`, `/docs/api.json`); config `SCRAMBLE_ENABLED`
- **Asistencia:** importación de marcaciones desde CSV
- **Geografía / catálogos MH:** ampliación de CRUD y actividades económicas
- Documentación: `docs/MODULO-GESTION-HUMANA.md`, `docs/PRUEBAS_v1.3.0.md`, `mobile/README.md`

### Cambiado

- Menú y RBAC ampliados (`GESTION_HUMANA_*`, `PORTAL_*`, permisos de auditoría)
- Dashboard ampliado con métricas de gestión humana / operación
- Seeders: `BootstrapSeeder`, `DemoSeeder`, `RbacSeeder` para instalación limpia vs demo

### Dependencias

- `dedoc/scramble` ^0.13 (documentación OpenAPI)
- `@fullcalendar/*` ^6.x + `fullcalendar` (calendario RRHH)
- `@vueup/vue-quill` (editor enriquecido en formularios/encuestas)

### Pruebas

Ver [docs/PRUEBAS_v1.3.0.md](docs/PRUEBAS_v1.3.0.md).

[1.3.0]: https://github.com/memo124/RRHH_EL_SALVADOR/releases/tag/v1.3.0

## [1.2.0] - 2026-08-02

### Agregado

- **Dashboard analítico:** 8 KPIs, 5 gráficas (Chart.js), alertas y accesos rápidos (`DashboardService`, `DashboardHome.vue`)
- **Bitácora de errores API:** `ErrorJournalService`, rotación de archivos, pestaña en Seguridad (`ERROR_JOURNAL_VIEW`)
- **Respuestas JSON centralizadas:** `ApiExceptionRenderer` sin stack trace; referencia de error para soporte
- **Toasts automáticos:** éxito en POST/PUT/PATCH/DELETE; mensajes del backend o por defecto (`apiFeedback.js`)
- **Cancelación de peticiones:** al cambiar de ruta y al cambiar pestañas en listas paginadas (`AbortController`)
- **Ejercicio quincenal ISR:** seeder Ene–Jun 2026 + vacaciones + doc de verificación (`DemoQuincenalJunioRecalculoSeeder`)
- **Planilla PDF/Excel:** agrupación por área y departamento con subtotales
- **Boletas PDF:** vista dedicada sin toolbar HTML ni encabezado duplicado
- Documentación de pruebas: `docs/PRUEBAS_v1.2.0.md`

### Corregido

- **Recálculo renta junio:** columna `FRECUENCIAISR` (antes `NOMBREFRECUENCIA` inexistente)
- **Vacaciones en recálculo:** ingresos gravados de planilla vacaciones suman al acumulado; ajuste semestral solo en planilla ordinaria
- **Subsidios ISSS:** error PostgreSQL `GROUP BY` al listar certificados pendientes
- **Seguridad permisos:** listado plano (no paginado); búsqueda en columnas correctas; sin `null.ID_PERMISO`
- **Race condition tabs:** loader/datos incorrectos al cambiar pestañas rápido en catálogos multi-tab
- **Interceptor API:** peticiones canceladas no muestran toast de error

### Cambiado

- `usePaginatedList` y `useAsyncSelect` ignoran respuestas obsoletas y abortan fetch anterior
- `PaginatesQueries` soporta expresiones raw en búsqueda ILIKE
- `DashboardController` delega en `DashboardService`
- `.gitignore` excluye `storage/app/error-journal`

### Dependencias

- `chart.js` ^4.x (gráficas del dashboard)

### Pruebas

Ver [docs/PRUEBAS_v1.2.0.md](docs/PRUEBAS_v1.2.0.md).

[1.2.0]: https://github.com/memo124/RRHH_EL_SALVADOR/releases/tag/v1.2.0

## [1.0.0] - 2026-07-27

### Agregado

- Módulo completo de planilla: cálculo, cierre, anulación, contabilización
- Reportes PDF e impresión (planilla y boletas) con DomPDF
- Export Excel de planilla y exportación de archivo banco
- Detalle de descuentos por línea (`DETALLE_DESCUENTO_PLANILLA`)
- Seeders de nómina demo (normal, masiva ~200 empleados, quincenal ISSS)
- Componentes Vue: `AsyncSelect`, `PaginationBar`, toasts, modal export banco
- Paginación server-side en empleados y detalle de planilla
- Virtualización con `@tanstack/vue-virtual` en selects y tablas
- API paginada de catálogos (`/api/catalogs/{tipo}/select`)
- API paginada de empleados (`/api/empleados/select`)
- Documentación de instalación (Windows, Linux, Docker)
- Entorno Docker (`docker-compose.yml`)

### Cambiado

- Todos los `<select>` de formularios migrados a `AsyncSelect`
- `PlanillaController::show()` devuelve resumen; detalles paginados en endpoint separado
- `EmpleadoController::index()` responde con paginación Laravel

### Dependencias principales

- Laravel 11.31, PHP 8.2, PostgreSQL 14+
- Vue 3.5, Vite 6, Tailwind 3.4
- @tanstack/vue-virtual 3.13
- barryvdh/laravel-dompdf 3.1

[1.0.0]: https://github.com/your-org/RRHH_EL_SALVADOR/releases/tag/v1.0.0
