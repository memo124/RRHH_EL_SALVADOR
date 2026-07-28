# RRHH El Salvador

Sistema de Recursos Humanos y nómina para empresas en El Salvador. Incluye expedientes de empleados, cálculo de planilla (ISSS, AFP, renta), asistencia, incapacidades, liquidaciones, reportes PDF/Excel y exportación bancaria.

**Versión:** `1.0.0`  
**Stack:** Laravel 11 · Vue 3 · Vite 6 · Tailwind CSS 3

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

---

## Documentación de instalación

| Entorno | Guía |
|---------|------|
| Windows (AppServ / XAMPP) | [docs/INSTALACION-WINDOWS.md](docs/INSTALACION-WINDOWS.md) |
| Linux (Ubuntu/Debian) | [docs/INSTALACION-LINUX.md](docs/INSTALACION-LINUX.md) |
| Docker | [docs/INSTALACION-DOCKER.md](docs/INSTALACION-DOCKER.md) |

Documentación técnica adicional:

| Documento | Contenido |
|-----------|-----------|
| [docs/ARQUITECTURA.md](docs/ARQUITECTURA.md) | Capas, carpetas, APIs paginadas |
| [docs/FRONTEND-UX.md](docs/FRONTEND-UX.md) | Diálogos, modales, toasts, tema oscuro |
| [docs/API-CONCEPTOS-EMPLEADO.md](docs/API-CONCEPTOS-EMPLEADO.md) | Detalle de pagos, abonos, incapacidades |

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

---

## Módulos principales

- **Corporativo:** empresas, áreas, departamentos, cargos, sucursales
- **Empleados:** expedientes laborales
- **Planilla:** cálculo mensual/quincenal, boletas, PDF, Excel, archivo banco
- **Asistencia:** marcaciones y procesamiento
- **Incapacidades / ISSS:** certificados y subsidios
- **Conceptos por empleado:** préstamos, descuentos, otros ingresos
- **Seguridad:** usuarios, roles y permisos por módulo

---

## Rendimiento (listados y selects)

- Tablas paginadas server-side en **todas** las pantallas de listado (`usePaginatedList` + `PaginationBar`)
- Selects async con `@tanstack/vue-virtual` (`AsyncSelect`)
- API de catálogos: `GET /api/catalogs/{tipo}/select`
- Empleados: `GET /api/empleados/select`

Parámetros comunes en listados: `page`, `per_page` (10–100), `search` (búsqueda ILIKE en backend).

## UX (diálogos y modales)

- Diálogos interactivos (`dialog.confirm`, `dialog.form`, etc.) — ver [docs/FRONTEND-UX.md](docs/FRONTEND-UX.md)
- Modales de formulario con overlay global (`AppModalShell`)
- Notificaciones toast (`useToast`) para resultados de API
- Detalle de pagos en conceptos por empleado — ver [docs/API-CONCEPTOS-EMPLEADO.md](docs/API-CONCEPTOS-EMPLEADO.md)

---

## Licencia

MIT (framework Laravel). Código de aplicación: uso interno del proyecto RRHH El Salvador.
