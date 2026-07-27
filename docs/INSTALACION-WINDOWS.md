# Instalación en Windows — RRHH El Salvador v1.0.0

Guía para entornos **AppServ**, **XAMPP** o PHP/Node instalados manualmente.

---

## 1. Requisitos

| Software | Versión |
|----------|---------|
| PHP | 8.2 o superior |
| Composer | 2.x |
| Node.js | 20 LTS |
| Git | 2.x |
| Base de datos | MySQL 8 / MariaDB 10 / SQL Server 2019+ |

Habilitar en `php.ini`: `extension=openssl`, `pdo_mysql` o `pdo_sqlsrv`, `mbstring`, `fileinfo`, `gd`.

---

## 2. Clonar el proyecto

```powershell
cd C:\AppServ\www
git clone <url-del-repositorio> RRHH_EL_SALVADOR
cd RRHH_EL_SALVADOR
```

---

## 3. Configurar `.env`

```powershell
copy .env.example .env
```

### Opción A — MySQL (AppServ / XAMPP)

```ini
APP_NAME="RRHH El Salvador"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=America/El_Salvador

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rrhh_el_salvador
DB_USERNAME=root
DB_PASSWORD=tu_password
```

Crear la base de datos en phpMyAdmin o:

```sql
CREATE DATABASE rrhh_el_salvador CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Opción B — SQL Server

```ini
DB_CONNECTION=sqlsrv
DB_HOST=localhost
DB_PORT=1433
DB_DATABASE=RRHH_EL_SALVADOR
DB_USERNAME=sa
DB_PASSWORD=tu_password
```

Requiere [Microsoft ODBC Driver for SQL Server](https://learn.microsoft.com/en-us/sql/connect/odbc/download-odbc-driver-for-sql-server) y extensiones `sqlsrv` / `pdo_sqlsrv` para PHP 8.2.

### Opción C — SQLite (solo pruebas rápidas)

```ini
DB_CONNECTION=sqlite
# DB_DATABASE se resuelve a database/database.sqlite
```

---

## 4. Instalar dependencias

```powershell
composer install
npm install --legacy-peer-deps
```

> `npm install --legacy-peer-deps` evita conflictos de peer dependencies entre Vite 6 y vue-router 5.

---

## 5. Inicializar aplicación

```powershell
php artisan key:generate
php artisan migrate --seed
npm run build
```

El seeder carga catálogos legales, empresas demo, nómina masiva (~200 empleados) y usuario administrador.

---

## 6. Ejecutar en desarrollo

### Método automático (recomendado)

Doble clic en `iniciar.bat` o desde PowerShell:

```powershell
.\iniciar.bat
```

Abre Laravel (`http://127.0.0.1:8000`) y Vite en ventanas separadas.

**Nota:** `iniciar.bat` asume PHP en `C:\xampp\php\php.exe`. Si usa AppServ, edite la variable `PHP` al inicio del archivo, por ejemplo:

```bat
set "PHP=C:\AppServ\php8.2\php.exe"
```

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

## 7. Acceso

| | |
|-|-|
| URL | http://127.0.0.1:8000 |
| Usuario | `admin@rrhh.sv` |
| Contraseña | `Admin123!` |

---

## 8. Producción en Windows (IIS)

1. Instalar [URL Rewrite](https://www.iis.net/downloads/microsoft/url-rewrite) y PHP Manager para IIS.
2. Apuntar el sitio a la carpeta `public/`.
3. Ejecutar `composer install --no-dev --optimize-autoloader`.
4. Ejecutar `npm run build`.
5. Configurar `.env` con `APP_ENV=production`, `APP_DEBUG=false`.
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
| Error `pdo_sqlsrv` | Instalar drivers Microsoft para PHP 8.2 |
| Pantalla en blanco | Revisar `storage/logs/laravel.log` |
| Assets sin estilo | Ejecutar `npm run build` o `npm run dev` |
| Timeout al calcular planilla | Aumentar `max_execution_time` en `php.ini` |
