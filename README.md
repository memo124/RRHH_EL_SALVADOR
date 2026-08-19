# RRHH El Salvador

Sistema de Recursos Humanos y nómina para empresas en El Salvador. Incluye expedientes de empleados, cálculo de planilla (ISSS, AFP, renta, recálculo semestral), asistencia, incapacidades, liquidaciones, reportes PDF/Excel, exportación bancaria, cumplimiento legal, gestión humana integral y portal del empleado.

**Versión:** `1.3.1`  
**Stack:** Laravel 11 · Vue 3 · Vite 6 · Tailwind CSS 3 · Chart.js 4 · FullCalendar 6 · PostgreSQL 14+ · Scramble (OpenAPI)

---

## Novedades v1.3.1

- **Limpieza de ejercicios:** comando `php artisan db:clean-ejercicio` y seeder `CleanTransactionalSeeder` — vacía datos operativos y conserva catálogos (geografía, AFP, ISR, RBAC, tipos de planilla, etc.)
- **Menú lateral:** textos largos de grupos y opciones se muestran en varias líneas sin truncar

Ver detalle completo en [CHANGELOG.md](CHANGELOG.md) y plan de pruebas en [docs/PRUEBAS_v1.3.1.md](docs/PRUEBAS_v1.3.1.md).

### Versión anterior (v1.3.0)

Gestión Humana integral, cumplimiento SV, portal del empleado, auditoría, Scramble OpenAPI, importación CSV de asistencia y wizard `/setup`. Ver [docs/MODULO-GESTION-HUMANA.md](docs/MODULO-GESTION-HUMANA.md) y [docs/PRUEBAS_v1.3.0.md](docs/PRUEBAS_v1.3.0.md).

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
php artisan migrate
php artisan db:seed --class=BootstrapSeeder
npm run build

php artisan serve
```

Abrir `http://127.0.0.1:8000/setup` para registrar empresa y administrador.

**Instalación con datos demo (desarrollo):**

```bash
php artisan migrate --seed
```

Abrir `http://127.0.0.1:8000`

**Usuario demo (después de `migrate --seed` o `DemoSeeder`):**

| Campo | Valor |
|-------|-------|
| Usuario | `admin@rrhh.sv` |
| Contraseña | `Admin123!` |

> Tras agregar permisos nuevos (Gestión Humana, portal, auditoría), **cerrar sesión y volver a entrar** para refrescar permisos en el menú.

---

## Documentación

| Documento | Contenido |
|-----------|-----------|
| [CHANGELOG.md](CHANGELOG.md) | Historial de versiones |
| [docs/PRUEBAS_v1.3.1.md](docs/PRUEBAS_v1.3.1.md) | Plan de pruebas de la versión actual |
| [docs/PRUEBAS_v1.3.0.md](docs/PRUEBAS_v1.3.0.md) | Plan de pruebas v1.3.0 (histórico) |
| [docs/MODULO-GESTION-HUMANA.md](docs/MODULO-GESTION-HUMANA.md) | Gestión Humana, cumplimiento SV y portal |
| [docs/INSTALACION-WINDOWS.md](docs/INSTALACION-WINDOWS.md) | Instalación en Windows (AppServ / XAMPP) |
| [docs/INSTALACION-LINUX.md](docs/INSTALACION-LINUX.md) | Instalación en Linux |
| [docs/INSTALACION-DOCKER.md](docs/INSTALACION-DOCKER.md) | Instalación con Docker |
| [docs/ARQUITECTURA.md](docs/ARQUITECTURA.md) | Capas, carpetas, APIs paginadas |
| [docs/FRONTEND-UX.md](docs/FRONTEND-UX.md) | Diálogos, modales, toasts, tema oscuro |
| [docs/API-CONCEPTOS-EMPLEADO.md](docs/API-CONCEPTOS-EMPLEADO.md) | Detalle de pagos, abonos, incapacidades |
| [docs/PRUEBAS_v1.2.0.md](docs/PRUEBAS_v1.2.0.md) | Plan de pruebas v1.2.0 (histórico) |
| [database/seeders/EJERCICIO_QUINCENAL_JUNIO_2026.md](database/seeders/EJERCICIO_QUINCENAL_JUNIO_2026.md) | Ejercicio quincenal + recálculo ISR |
| [mobile/README.md](mobile/README.md) | Notas de consumo API para clientes móviles |

### Documentación API (Swagger / OpenAPI)

La API se documenta automáticamente con [Scramble](https://scramble.dedoc.co) a partir de las rutas Laravel.

| URL | Descripción |
|-----|-------------|
| `/docs/api` | Interfaz interactiva (Try it) |
| `/docs/api.json` | Especificación OpenAPI 3.1 (JSON) |

**Autenticación para clientes móviles:**

```http
POST /api/login
Content-Type: application/json

{"usuario": "admin@rrhh.sv", "contrasena": "Admin123!"}
```

Respuesta: `{ "token": "...", "user": { ... } }`. En peticiones siguientes:

```http
Authorization: Bearer {token}
```

En **local** la documentación está disponible sin configuración extra. En **producción**, defina `SCRAMBLE_ENABLED=true` en `.env` solo en entornos internos o staging.

```bash
# Regenerar caché de la spec (opcional, tras despliegue)
php artisan scramble:cache
```

---

## Scripts útiles

| Comando | Descripción |
|---------|-------------|
| `iniciar.bat` | Windows: levanta Laravel + Vite en ventanas separadas |
| `composer dev` | Laravel serve + queue + logs + Vite (requiere `concurrently`) |
| `npm run dev` | Vite en modo desarrollo (HMR) |
| `npm run build` | Compila assets para producción |
| `php artisan migrate --seed` | Estructura + catálogos + demo completo |
| `php artisan db:seed --class=BootstrapSeeder` | Solo catálogos legales y RBAC (instalación limpia) |
| `php artisan db:seed --class=DemoSeeder` | Datos demo + usuario `admin@rrhh.sv` |
| `php artisan db:seed --class=RbacSeeder` | Roles y permisos (incl. portal / gestión humana) |
| `php artisan migrate:fresh --seed` | Reinicia BD y vuelve a sembrar |
| `php artisan db:seed --class=DemoQuincenalJunioRecalculoSeeder` | Ejercicio quincenal Ene–Jun + recálculo ISR |
| `php artisan db:clean-ejercicio` | Vacía datos operativos y conserva catálogos (ver abajo) |

### Limpiar base de datos para ejercicios

Para repetir pruebas o ejercicios **sin perder catálogos** (geografía, AFP, ISR, RBAC, tipos de planilla, etc.):

```bash
php artisan db:clean-ejercicio
```

Equivalente directo:

```bash
php artisan db:seed --class=CleanTransactionalSeeder --force
```

**Qué elimina:** empresas, empleados, planillas, usuarios, asistencia, contratos, gestión humana, reclutamiento, auditoría y demás tablas transaccionales.

**Qué conserva:** catálogos legales y de referencia. Restaura la plantilla global de contrato.

**Después de limpiar:**

1. Registrar de nuevo empresa y admin en `/setup`, **o**
2. Cargar demo: `php artisan db:seed --class=DemoSeeder` (usuario `admin@rrhh.sv` / `Admin123!`)

> En Windows con XAMPP, use la ruta de PHP 8.2 si `php` en PATH es antiguo, por ejemplo:  
> `C:\xampp\php\php.exe artisan db:clean-ejercicio`

---

## Módulos principales

- **Inicio (Dashboard):** KPIs, gráficas, alertas de contratos/marcaciones/planillas
- **Corporativo:** empresas, áreas, departamentos, cargos, sucursales
- **Empleados:** expedientes laborales (educación, certificaciones, dependientes)
- **Planilla:** cálculo mensual/quincenal, recálculo ISR junio/diciembre, boletas, PDF, Excel, archivo banco
- **Asistencia:** marcaciones, procesamiento e importación CSV
- **Incapacidades / ISSS:** certificados y subsidios
- **Conceptos por empleado:** préstamos, descuentos, otros ingresos
- **Gestión Humana:** calendario, encuestas, formularios, documentos, vacaciones/permisos, capacitaciones
- **Reclutamiento / Evaluación:** vacantes, candidatos, entrevistas, desempeño
- **Cumplimiento SV:** ISSS, AFP, INSAFORP, renta, aguinaldo, retención 10%, altas/bajas
- **Portal del empleado:** autoservicio (boletas, permisos, encuestas, evaluaciones)
- **Seguridad:** usuarios, roles, permisos, bitácora de errores y auditoría

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
| [1.3.1](https://github.com/memo124/RRHH_EL_SALVADOR/releases/tag/v1.3.1) | 2026-08-18 | Limpieza de ejercicios (`db:clean-ejercicio`), UX menú lateral |
| [1.3.0](https://github.com/memo124/RRHH_EL_SALVADOR/releases/tag/v1.3.0) | 2026-08-10 | Gestión Humana, cumplimiento SV, portal, auditoría, Scramble |
| [1.2.0](https://github.com/memo124/RRHH_EL_SALVADOR/releases/tag/v1.2.0) | 2026-08-02 | Dashboard, errores API, UX, recálculo ISR |
| [1.1.0](https://github.com/memo124/RRHH_EL_SALVADOR/releases/tag/v1.1.0) | 2026-07 | UX móvil, iconos SVG, paginación global |
| [1.0.0](https://github.com/memo124/RRHH_EL_SALVADOR/releases/tag/v1.0.0) | 2026-07-27 | Planilla completa, reportes PDF/Excel |

---

## Licencia

MIT (framework Laravel). Código de aplicación: uso interno del proyecto RRHH El Salvador.
