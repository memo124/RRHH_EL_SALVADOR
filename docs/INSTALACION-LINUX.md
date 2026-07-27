# Instalación en Linux — RRHH El Salvador v1.0.0

Guía para **Ubuntu 22.04 / 24.04** o **Debian 12**. Adaptable a otras distribuciones.

---

## 1. Dependencias del sistema

```bash
sudo apt update
sudo apt install -y git curl unzip \
  php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring \
  php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd \
  nginx mariadb-server
```

### Node.js 20 LTS

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

## 2. Base de datos MariaDB/MySQL

```bash
sudo mysql -e "CREATE DATABASE rrhh_el_salvador CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'rrhh'@'localhost' IDENTIFIED BY 'password_seguro';"
sudo mysql -e "GRANT ALL PRIVILEGES ON rrhh_el_salvador.* TO 'rrhh'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

---

## 3. Clonar e instalar

```bash
cd /var/www
sudo git clone <url-del-repositorio> rrhh
sudo chown -R $USER:www-data rrhh
cd rrhh

cp .env.example .env
```

Editar `.env`:

```ini
APP_NAME="RRHH El Salvador"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
APP_TIMEZONE=America/El_Salvador

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rrhh_el_salvador
DB_USERNAME=rrhh
DB_PASSWORD=password_seguro
```

```bash
composer install
npm install --legacy-peer-deps
php artisan key:generate
php artisan migrate --seed
npm run build
```

Permisos:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 4. Nginx (producción)

Crear `/etc/nginx/sites-available/rrhh`:

```nginx
server {
    listen 80;
    server_name rrhh.local;
    root /var/www/rrhh/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/rrhh /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

Agregar a `/etc/hosts`:

```
127.0.0.1  rrhh.local
```

---

## 5. Desarrollo local

```bash
# Terminal 1
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 2
npm run dev
```

O con un solo comando (si `concurrently` está disponible):

```bash
composer dev
```

---

## 6. Acceso

| | |
|-|-|
| URL dev | http://127.0.0.1:8000 |
| URL nginx | http://rrhh.local |
| Usuario | `admin@rrhh.sv` |
| Contraseña | `Admin123!` |

---

## 7. SQL Server en Linux (opcional)

Si la empresa usa SQL Server:

```bash
# Instalar ODBC y extensiones PHP (Microsoft docs)
# https://learn.microsoft.com/en-us/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server
```

`.env`:

```ini
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=RRHH_EL_SALVADOR
DB_USERNAME=sa
DB_PASSWORD=YourStrong@Passw0rd
```

---

## 8. Optimización producción

```bash
composer install --no-dev --optimize-autoloader
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Configurar cron para colas (si se usan):

```cron
* * * * * cd /var/www/rrhh && php artisan schedule:run >> /dev/null 2>&1
```
