# RRHH El Salvador

Sistema de Recursos Humanos y nómina para empresas en El Salvador. Incluye expedientes de empleados, cálculo de planilla (ISSS, AFP, renta, recálculo semestral), asistencia, incapacidades, liquidaciones, reportes PDF/Excel, exportación bancaria y panel analítico de inicio.

**Versión:** `1.2.0`  
**Stack:** Laravel 11 · Vue 3 · Vite 6 · Tailwind CSS 3 · Chart.js 4 · PostgreSQL 14+

---

## Novedades v1.2.0

- **Dashboard de inicio:** 8 KPIs, 5 gráficas (empleados, nómina, planillas, incapacidades), alertas y accesos rápidos
- **Errores API centralizados:** respuestas JSON amigables, referencia de soporte y bitácora rotativa (`Seguridad → Errores del sistema`)
- **UX mejorada:** toasts automáticos al guardar/eliminar; cancelación de peticiones al navegar o cambiar pestañas
- **Recálculo ISR quincenal:** ejercicio demo Ene–Jun 2026 con vacaciones incluidas en acumulado semestral
- **Correcciones:** subsidios ISSS, permisos en Seguridad, recálculo renta junio, race condition en tabs

Ver detalle completo en [CHANGELOG.md](CHANGELOG.md) y plan de pruebas en [docs/PRUEBAS_v1.2.0.md](docs/PRUEBAS_v1.2.0.md).

---

## Requisitos

| Componente | Versión mínima | Recomendada |
|------------|----------------|-------------|
| PHP | 8.2 | 8.2+ |
| Composer | 2.x | 2.7+ |
| Node.js | 18.x | 20 LTS |
| npm | 9.x | 10+ |
| Base de datos | PostgreSQL 14+ | PostgreSQL 16 |

### Extensiones PHP requeridas

`openssl`, `pdo`, `pdo_pgsql`, `pgsql`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`

---

## Inicio rápido (desarrollo)

```bash
git clone <url-del-repositorio> RRHH_EL_SALVADOR
cd RRHH_EL_SALVADOR

cp .env.example .env          # Linux/macOS
# copy .env.example .env      # Windows CMD

# Crear la base de datos en PostgreSQL antes de migrar:
# createdb -U postgres rrhh_el_salvador

composer install
npm install --legacy-peer-deps

php artisan key:generate
php artisan migrate --seed
npm run build

php artisan serve
```

Abrir `http://127.0.0.1:8000`

**Usuario demo (después de `migrate --seed`):**

| Campo | Valor |
|-------|-------|
| Usuario | `admin@rrhh.sv` |
| Contraseña | `Admin123!` |

> Tras agregar permisos nuevos (p. ej. bitácora de errores), **cerrar sesión y volver a entrar** para refrescar permisos en el menú.

---

## Documentación

| Documento | Contenido |
|-----------|-----------|
| [CHANGELOG.md](CHANGELOG.md) | Historial de versiones |
| [docs/PRUEBAS_v1.2.0.md](docs/PRUEBAS_v1.2.0.md) | Plan de pruebas de la versión actual |
| [docs/INSTALACION-WINDOWS.md](docs/INSTALACION-WINDOWS.md) | Instalación en Windows (AppServ / XAMPP) |
| [docs/INSTALACION-LINUX.md](docs/INSTALACION-LINUX.md) | Instalación en Linux |
| [docs/INSTALACION-DOCKER.md](docs/INSTALACION-DOCKER.md) | Instalación con Docker |
| [docs/ARQUITECTURA.md](docs/ARQUITECTURA.md) | Capas, carpetas, APIs paginadas |
| [docs/FRONTEND-UX.md](docs/FRONTEND-UX.md) | Diálogos, modales, toasts, tema oscuro |
| [docs/API-CONCEPTOS-EMPLEADO.md](docs/API-CONCEPTOS-EMPLEADO.md) | Detalle de pagos, abonos, incapacidades |
| [database/seeders/EJERCICIO_QUINCENAL_JUNIO_2026.md](database/seeders/EJERCICIO_QUINCENAL_JUNIO_2026.md) | Ejercicio quincenal + recálculo ISR |

---

## Scripts útiles

| Comando | Descripción |
|---------|-------------|
| `iniciar.bat` | Windows: levanta Laravel + Vite en ventanas separadas |
| `composer dev` | Laravel serve + queue + logs + Vite (requiere `concurrently`) |
| `npm run dev` | Vite en modo desarrollo (HMR) |
| `npm run build` | Compila assets para producción |
| `php artisan migrate --seed` | Estructura + datos demo |
| `php artisan migrate:fresh --seed` | Reinicia BD y vuelve a sembrar |
| `php artisan db:seed --class=DemoQuincenalJunioRecalculoSeeder` | Ejercicio quincenal Ene–Jun + recálculo ISR |

---

## Módulos principales

- **Inicio (Dashboard):** KPIs, gráficas, alertas de contratos/marcaciones/planillas
- **Corporativo:** empresas, áreas, departamentos, cargos, sucursales
- **Empleados:** expedientes laborales
- **Planilla:** cálculo mensual/quincenal, recálculo ISR junio/diciembre, boletas, PDF, Excel, archivo banco
- **Asistencia:** marcaciones y procesamiento
- **Incapacidades / ISSS:** certificados y subsidios
- **Conceptos por empleado:** préstamos, descuentos, otros ingresos
- **Seguridad:** usuarios, roles, permisos y bitácora de errores del sistema

---

## Rendimiento y UX

- Tablas paginadas server-side en **todas** las pantallas de listado (`usePaginatedList` + `PaginationBar`)
- Cancelación automática de peticiones HTTP al cambiar de ruta o pestaña (evita race conditions)
- Selects async con `@tanstack/vue-virtual` (`AsyncSelect`)
- Toasts de éxito/error centralizados en el cliente API
- API de catálogos: `GET /api/catalogs/{tipo}/select`
- Empleados: `GET /api/empleados/select`
- Dashboard: `GET /api/dashboard/stats` (requiere permiso `SALARIAL_VIEW`)

Parámetros comunes en listados: `page`, `per_page` (10–100), `search` (búsqueda ILIKE en backend).

---

## Versiones

| Versión | Fecha | Notas |
|---------|-------|-------|
| [1.2.0](https://github.com/memo124/RRHH_EL_SALVADOR/releases/tag/v1.2.0) | 2026-08-02 | Dashboard, errores API, UX, recálculo ISR |
| [1.1.0](https://github.com/memo124/RRHH_EL_SALVADOR/releases/tag/v1.1.0) | 2026-07 | UX móvil, iconos SVG, paginación global |
| [1.0.0](https://github.com/memo124/RRHH_EL_SALVADOR/releases/tag/v1.0.0) | 2026-07-27 | Planilla completa, reportes PDF/Excel |

---

## Licencia

MIT (framework Laravel). Código de aplicación: uso interno del proyecto RRHH El Salvador.
