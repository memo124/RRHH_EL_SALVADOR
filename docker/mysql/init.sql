CREATE DATABASE IF NOT EXISTS rrhh_el_salvador CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'rrhh'@'%' IDENTIFIED BY 'rrhh_secret';
GRANT ALL PRIVILEGES ON rrhh_el_salvador.* TO 'rrhh'@'%';
FLUSH PRIVILEGES;
