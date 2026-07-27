# Instalación en Windows — RRHH El Salvador v1.0.0

Guía para entornos **AppServ**, **XAMPP** o PHP/Node instalados manualmente, con **PostgreSQL** como base de datos.

---

## 1. Requisitos

| Software | Versión |
|----------|---------|
| PHP | 8.2 o superior |
| Composer | 2.x |
| Node.js | 20 LTS |
| Git | 2.x |
| PostgreSQL | 14+ (recomendado 16) |

Habilitar en `php.ini`: `extension=openssl`, `extension=pdo_pgsql`, `extension=pgsql`, `mbstring`, `fileinfo`, `gd`.

Instalar PostgreSQL desde [postgresql.org/download/windows](https://www.postgresql.org/download/windows/) o con el instalador incluido en su stack (AppServ, etc.).

---

## 2. Clonar el proyecto

```powershell
cd C:\AppServ\www
git clone <url-del-repositorio> RRHH_EL_SALVADOR
cd RRHH_EL_SALVADOR
```

---

## 3. Configurar PostgreSQL

Crear la base de datos con **pgAdmin** o desde `psql`:

```sql
CREATE DATABASE rrhh_el_salvador
  WITH ENCODING 'UTF8'
       LC_COLLATE 'Spanish_El Salvador.1252'
       LC_CTYPE 'Spanish_El Salvador.1252'
       TEMPLATE template0;
```

Si prefiere un usuario dedicado:

```sql
CREATE USER rrhh WITH PASSWORD 'password_seguro';
CREATE DATABASE rrhh_el_salvador OWNER rrhh ENCODING 'UTF8';
GRANT ALL PRIVILEGES ON DATABASE rrhh_el_salvador TO rrhh;
```

---

## 4. Configurar `.env`

```powershell
copy .env.example .env
```

```ini
APP_NAME="RRHH El Salvador"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=America/El_Salvador

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=rrhh_el_salvador
DB_USERNAME=postgres
DB_PASSWORD=tu_password
```

---

## 5. Instalar dependencias

```powershell
composer install
npm install --legacy-peer-deps
```

> `npm install --legacy-peer-deps` evita conflictos de peer dependencies entre Vite 6 y vue-router 5.

---

## 6. Inicializar aplicación

```powershell
php artisan key:generate
php artisan migrate --seed
npm run build
```

El seeder carga catálogos legales, empresas demo, nómina masiva (~200 empleados) y usuario administrador.

---

## 7. Ejecutar en desarrollo

### Método automático (recomendado)

Doble clic en `iniciar.bat` o desde PowerShell:

```powershell
.\iniciar.bat
```

Abre Laravel (`http://127.0.0.1:8000`) y Vite en ventanas separadas.

**Nota:** `iniciar.bat` asume PHP en `C:\AppServ\php8.2\php.exe`. Si usa otra ruta, edite la variable `PHP` al inicio del archivo.

### Método manual (dos terminales)

Terminal 1:

```powershell
php artisan serve
```

Terminal 2:

```powershell
npm run dev
```

---

## 8. Acceso

| | |
|-|-|
| URL | http://127.0.0.1:8000 |
| Usuario | `admin@rrhh.sv` |
| Contraseña | `Admin123!` |

---

## 9. Producción en Windows (IIS)

1. Instalar [URL Rewrite](https://www.iis.net/downloads/microsoft/url-rewrite) y PHP Manager para IIS.
2. Apuntar el sitio a la carpeta `public/`.
3. Ejecutar `composer install --no-dev --optimize-autoloader`.
4. Ejecutar `npm run build`.
5. Configurar `.env` con `APP_ENV=production`, `APP_DEBUG=false` y credenciales PostgreSQL de producción.
6. Ejecutar:

```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

7. Permisos de escritura en `storage/` y `bootstrap/cache/`.

---

## Solución de problemas

| Problema | Solución |
|----------|----------|
| `php` no reconocido | Agregar PHP al PATH o usar ruta completa |
| Error `could not find driver` (pgsql) | Habilitar `pdo_pgsql` y `pgsql` en `php.ini` |
| `connection refused` a PostgreSQL | Verificar que el servicio `postgresql-x64-16` esté activo |
| Pantalla en blanco | Revisar `storage/logs/laravel.log` |
| Assets sin estilo | Ejecutar `npm run build` o `npm run dev` |
| Timeout al calcular planilla | Aumentar `max_execution_time` en `php.ini` |
