# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/).

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
