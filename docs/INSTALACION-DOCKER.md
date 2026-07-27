# Instalación con Docker — RRHH El Salvador v1.0.0

Entorno containerizado con **PHP 8.2**, **Nginx**, **PostgreSQL 16** y **Node** (solo build).

---

## Requisitos

- Docker Engine 24+
- Docker Compose v2+

---

## Estructura

```
docker/
  nginx/default.conf    # Virtual host
  php/Dockerfile        # Imagen PHP-FPM + pdo_pgsql
  postgres/init.sql     # Extensiones iniciales
docker-compose.yml
```

---

## 1. Clonar y configurar

```bash
git clone <url-del-repositorio> RRHH_EL_SALVADOR
cd RRHH_EL_SALVADOR
cp .env.example .env
```

Ajustar `.env` para Docker:

```ini
APP_NAME="RRHH El Salvador"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080
APP_TIMEZONE=America/El_Salvador

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
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
| `postgres` | 5432 | PostgreSQL 16 |
| `node` | — | Ejecuta `npm run build` (perfil `build`) |

---

## 3. Inicializar base de datos (primera vez)

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Si `node` no compiló assets:

```bash
docker compose --profile build run --rm node
```

---

## 4. Acceso

| | |
|-|-|
| URL | http://localhost:8080 |
| Usuario | `admin@rrhh.sv` |
| Contraseña | `Admin123!` |
| PostgreSQL (desde host) | `127.0.0.1:5432` user `rrhh` / `rrhh_secret` db `rrhh_el_salvador` |

---

## 5. Comandos útiles

```bash
# Ver logs
docker compose logs -f app nginx postgres

# Artisan
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker

# Consola PostgreSQL
docker compose exec postgres psql -U rrhh -d rrhh_el_salvador

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
3. Montar volumen persistente para `storage/` y backups de PostgreSQL.
4. Colocar un reverse proxy (Traefik / Caddy) con TLS delante de nginx.

```bash
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

Backup de la base:

```bash
docker compose exec postgres pg_dump -U rrhh rrhh_el_salvador > backup_rrhh.sql
```
