# Instalación con Docker — RRHH El Salvador v1.0.0

Entorno containerizado con **PHP 8.2**, **Nginx**, **MySQL 8** y **Node** (solo build).

---

## Requisitos

- Docker Engine 24+
- Docker Compose v2+

---

## Estructura

```
docker/
  nginx/default.conf    # Virtual host
  php/Dockerfile        # Imagen PHP-FPM
  mysql/init.sql        # Creación de BD
docker-compose.yml
```

---

## 1. Clonar y configurar

```bash
git clone <url-del-repositorio> RRHH_EL_SALVADOR
cd RRHH_EL_SALVADOR
cp .env.example .env
```

Ajustar `.env` para Docker (valores ya compatibles con `docker-compose.yml`):

```ini
APP_NAME="RRHH El Salvador"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080
APP_TIMEZONE=America/El_Salvador

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=rrhh_el_salvador
DB_USERNAME=rrhh
DB_PASSWORD=rrhh_secret
```

---

## 2. Levantar contenedores

```bash
docker compose up -d --build
```

Servicios:

| Servicio | Puerto | Descripción |
|----------|--------|-------------|
| `app` | — | PHP 8.2-FPM |
| `nginx` | **8080** | Servidor web |
| `mysql` | 3306 | MySQL 8.0 |
| `node` | — | Ejecuta `npm run build` al iniciar |

---

## 3. Inicializar base de datos (primera vez)

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Si `node` no compiló assets:

```bash
docker compose run --rm node npm install --legacy-peer-deps
docker compose run --rm node npm run build
```

---

## 4. Acceso

| | |
|-|-|
| URL | http://localhost:8080 |
| Usuario | `admin@rrhh.sv` |
| Contraseña | `Admin123!` |
| MySQL (desde host) | `127.0.0.1:3306` user `rrhh` / `rrhh_secret` |

---

## 5. Comandos útiles

```bash
# Ver logs
docker compose logs -f app nginx

# Artisan
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker

# Reiniciar
docker compose restart

# Detener y eliminar volúmenes
docker compose down -v
```

---

## 6. Desarrollo con Vite (HMR)

Para hot-reload del frontend, ejecute Vite en el host:

```bash
npm install --legacy-peer-deps
npm run dev
```

Y en `.env` agregue (opcional):

```ini
VITE_DEV_SERVER_URL=http://localhost:5173
```

---

## 7. Producción con Docker

1. Cambiar `.env`: `APP_ENV=production`, `APP_DEBUG=false`.
2. Usar secretos seguros para `DB_PASSWORD` y `APP_KEY`.
3. Montar volumen persistente para `storage/` y backups de MySQL.
4. Colocar un reverse proxy (Traefik / Caddy) con TLS delante de nginx.

```bash
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

---

## Notas SQL Server

La imagen incluida usa **MySQL**. Para SQL Server en Docker, reemplace el servicio `mysql` por `mcr.microsoft.com/mssql/server:2022-latest`, instale `pdo_sqlsrv` en la imagen PHP y ajuste `DB_CONNECTION=sqlsrv` en `.env`.
