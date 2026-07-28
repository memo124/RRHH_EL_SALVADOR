# Arquitectura — RRHH El Salvador v1.0.0

## Capas

```
┌─────────────────────────────────────────────────────────┐
│  Vue 3 SPA (resources/js)                               │
│  Router · Axios · Tailwind · AsyncSelect · Virtual DOM  │
└───────────────────────────┬─────────────────────────────┘
                            │ REST /api  +  Blade reportes
┌───────────────────────────▼─────────────────────────────┐
│  Laravel 11                                             │
│  Controllers · Services · Sanctum · DomPDF              │
└───────────────────────────┬─────────────────────────────┘
                            │ Eloquent / Query Builder
┌───────────────────────────▼─────────────────────────────┐
│  PostgreSQL                                             │
└─────────────────────────────────────────────────────────┘
```

## Backend

| Carpeta | Rol |
|---------|-----|
| `app/Http/Controllers` | API REST y rutas web de reportes |
| `app/Services` | Lógica de negocio (planilla, reportes, export banco) |
| `app/Models` | Modelos Eloquent |
| `database/migrations` | Esquema relacional |
| `database/seeders` | Catálogos legales SV + demo nómina masiva |
| `resources/views/reportes` | Plantillas Blade → PDF / impresión |

## Frontend

| Carpeta | Rol |
|---------|-----|
| `resources/js/views` | Pantallas por módulo |
| `resources/js/components` | `AsyncSelect`, `PaginationBar`, `AppDialog`, `AppModalShell`, `AppToast` |
| `resources/js/composables` | Paginación, toasts, diálogos (`useDialog`), reportes planilla |
| `resources/js/services/api.js` | Cliente Axios + token Sanctum |

## Autenticación

- Login: `POST /api/login` → token Bearer en `localStorage`
- Rutas API protegidas con `auth:sanctum`
- Permisos por módulo (`permission:MODULO_ACCION`)

## APIs de datos paginados

| Endpoint | Uso |
|----------|-----|
| `GET /api/empleados?page&search` | Listado empleados |
| `GET /api/empleados/select?q&page` | Select async empleados |
| `GET /api/planillas/{id}/detalles?page&search` | Detalle planilla |
| `GET /api/catalogs/{tipo}/select` | Catálogos (empresas, cargos, etc.) |

Tipos de catálogo: `empresas`, `departamentos`, `cargos`, `tipos-planilla`, `periodos-laborales`, `cuentas`, `afps`, `bancos`, `municipios`, `distritos`, `tipos-prestamo`, `tipos-descuento`, `tipos-ingreso`, entre otros.

## APIs de conceptos por empleado

| Endpoint | Uso |
|----------|-----|
| `GET /api/prestamos/{id}` | Préstamo + abonos + resumen |
| `DELETE /api/prestamos/{id}/abonos/{abonoId}` | Eliminar cuota y recalcular saldo |
| `GET /api/descuentos-empleado/{id}/historial` | Aplicaciones en planilla |
| `GET /api/otros-ingresos/{id}/historial` | Ingresos aplicados en planilla |
| `POST /api/incapacidades/{id}/cancelar` | Cancelar certificado (body: `{ motivo? }`) |

Detalle: [API-CONCEPTOS-EMPLEADO.md](API-CONCEPTOS-EMPLEADO.md)

## UX frontend

- **AppDialog** + **useDialog**: reemplazo de `alert`/`confirm`/`prompt` nativos
- **AppModalShell**: modales CRUD con overlay a pantalla completa
- **useToast**: feedback no bloqueante

Detalle: [FRONTEND-UX.md](FRONTEND-UX.md)

## Reportes

Rutas web (sesión o token según configuración):

- `/reportes/planillas/{id}/imprimir`
- `/reportes/planillas/{id}/pdf`
- `/reportes/planillas/{id}/boletas`
- Export Excel generado en cliente (`xlsx`) o vía servicios server-side

## Dependencias clave

| Paquete | Versión | Uso |
|---------|---------|-----|
| laravel/framework | ^11.31 | Backend |
| laravel/sanctum | ^4.3 | API tokens |
| barryvdh/laravel-dompdf | ^3.1 | PDF |
| vue | ^3.5 | Frontend |
| @tanstack/vue-virtual | ^3.13 | Virtualización selects/tablas |
| xlsx | ^0.18 | Export Excel |
