@echo off
title RRHH El Salvador - Iniciando...
cd /d "%~dp0"

REM Usar PHP 8.2 (ajuste la ruta segun su instalacion)
REM AppServ:  set "PHP=C:\AppServ\php8.2\php.exe"
REM XAMPP:    set "PHP=C:\xampp\php\php.exe"
set "PHP=C:\AppServ\php8.2\php.exe"

if not exist "%PHP%" (
    echo [ERROR] No se encontro PHP 8.2 en %PHP%
    echo Edite la variable PHP al inicio de iniciar.bat
    pause
    exit /b 1
)

where npm >nul 2>&1
if errorlevel 1 (
    echo [ERROR] npm no esta en el PATH. Instale Node.js primero.
    pause
    exit /b 1
)

echo.
echo  RRHH El Salvador - Modo desarrollo
echo  ==================================
echo  Backend:  http://127.0.0.1:8000
echo  Vite:     http://localhost:5173 (solo assets, no abrir directo)
echo.

start "RRHH - Laravel" cmd /k ""%PHP%" artisan serve"
start "RRHH - Vite" cmd /k "npm run dev"

echo Esperando que los servidores arranquen...
timeout /t 4 /nobreak >nul

start "" "http://127.0.0.1:8000"

echo.
echo  Servidores iniciados. El navegador abrira la app automaticamente.
echo  Cierre las ventanas "RRHH - Laravel" y "RRHH - Vite" para detener todo.
echo.
pause
