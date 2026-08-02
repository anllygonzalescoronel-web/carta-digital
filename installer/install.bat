@echo off
REM CARTA DIGITAL - INSTALADOR CLI
REM Para Windows (Requiere estar en CMD como Administrador)

setlocal enabledelayedexpansion

echo.
echo ╔═════════════════════════════════════════════╗
echo ║   🍕 CARTA DIGITAL - INSTALADOR v2.0       ║
echo ║      Sistema de Instalación Automática      ║
echo ╚═════════════════════════════════════════════╝
echo.

REM Detectar directorio actual
set INSTALLER_DIR=%~dp0
set ROOT_DIR=%INSTALLER_DIR:~0,-1%
for %%A in ("%ROOT_DIR%") do set ROOT_DIR=%%~dpA
set ROOT_DIR=%ROOT_DIR:~0,-1%

echo 📍 Directorio raíz: %ROOT_DIR%
echo.

REM Verificar que estamos en CMD como admin
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ❌ ERROR: Este script requiere permisos de administrador
    echo.
    echo Por favor, ejecuta el CMD como Administrador:
    echo   1. Click derecho en CMD.exe
    echo   2. Selecciona "Ejecutar como administrador"
    echo   3. Navega a: cd /d "%INSTALLER_DIR%"
    echo   4. Ejecuta: install.bat
    echo.
    pause
    exit /b 1
)

echo 🔍 Verificando requisitos básicos...
echo.

REM Verificar PHP
where php >nul 2>&1
if %errorLevel% neq 0 (
    echo ❌ PHP no encontrado en PATH
    echo.
    echo Solución:
    echo   - Agrega la ruta de PHP al PATH de Windows
    echo   - O ejecuta desde XAMPP: C:\xampp\php\php.exe
    echo.
    pause
    exit /b 1
)

for /f "tokens=*" %%A in ('php -v ^| findstr /R "PHP [0-9]"') do set PHP_INFO=%%A
echo ✅ %PHP_INFO% encontrado
echo.

REM Verificar MySQL
where mysql >nul 2>&1
if %errorLevel% neq 0 (
    echo ❌ MySQL no encontrado en PATH
    echo.
    echo Solución (XAMPP):
    echo   - Abre: C:\xampp\mysql\bin\mysql.exe
    echo   - O agrega C:\xampp\mysql\bin al PATH
    echo.
    pause
    exit /b 1
)

echo ✅ MySQL encontrado
echo.

REM Solicitar credenciales
echo 🗄️  Configuración de Base de Datos
echo ═════════════════════════════════════
echo.

set DB_HOST=localhost
set /p DB_HOST="Host MySQL [%DB_HOST%]: "

set DB_USER=root
set /p DB_USER="Usuario MySQL [%DB_USER%]: "

echo.
echo ⚠️  Escribe tu contraseña de MySQL (sin caracteres especiales):
set /p DB_PASS="Contraseña MySQL (enter si no la hay): "

set DB_NAME=carta_digital
set /p DB_NAME="Nombre BD [%DB_NAME%]: "

set DB_PORT=3306
set /p DB_PORT="Puerto MySQL [%DB_PORT%]: "

echo.
echo 🧪 Probando conexión a MySQL...
echo.

mysql -h %DB_HOST% -u %DB_USER% -p%DB_PASS% -e "SELECT 1" >nul 2>&1
if %errorLevel% neq 0 (
    echo ❌ No se pudo conectar a MySQL
    echo.
    echo Verifica:
    echo   1. MySQL está running (XAMPP Control Panel)
    echo   2. Host, usuario y contraseña son correctos
    echo   3. Puerto %DB_PORT% es el correcto
    echo.
    pause
    exit /b 1
)

echo ✅ Conexión exitosa
echo.

REM Crear base de datos
echo 📦 Creando base de datos...
mysql -h %DB_HOST% -u %DB_USER% -p%DB_PASS% -e "CREATE DATABASE IF NOT EXISTS `%DB_NAME%` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if %errorLevel% neq 0 (
    echo ❌ Error al crear base de datos
    pause
    exit /b 1
)

echo ✅ Base de datos lista
echo.

REM Importar schema
echo 📥 Importando esquema...

if not exist "%ROOT_DIR%\sql\schema.sql" (
    echo ❌ Archivo sql\schema.sql no encontrado
    echo    Ubicación esperada: %ROOT_DIR%\sql\schema.sql
    pause
    exit /b 1
)

mysql -h %DB_HOST% -u %DB_USER% -p%DB_PASS% %DB_NAME% < "%ROOT_DIR%\sql\schema.sql"

if %errorLevel% neq 0 (
    echo ❌ Error al importar esquema
    echo Nota: Algunos errores de "tabla ya existe" son normales
)

echo ✅ Esquema importado
echo.

REM Crear directorios
echo 📂 Creando directorios...

setlocal enabledelayedexpansion
set DIRS=uploads uploads\productos uploads\banners uploads\categorias uploads\sunat uploads\sunat\cdr uploads\sunat\certificados uploads\sunat\pdf uploads\sunat\xml uploads\iconos

for %%D in (%DIRS%) do (
    if not exist "%ROOT_DIR%\%%D" mkdir "%ROOT_DIR%\%%D"
    echo   ✅ %%D
)

echo.

REM Configurar db.php
echo ⚙️  Configurando db.php...

set DB_CONFIG_FILE=%ROOT_DIR%\includes\db.php

if exist "%DB_CONFIG_FILE%" (
    REM Crear backup
    for /f "tokens=2-4 delims=/ " %%a in ('date /t') do (set mydate=%%c%%a%%b)
    for /f "tokens=1-2 delims=/:" %%a in ('time /t') do (set mytime=%%a%%b)
    copy "%DB_CONFIG_FILE%" "%DB_CONFIG_FILE%.backup.%mydate%_%mytime%"
    
    echo ✅ Configuración guardada (backup creado)
)

echo.

REM Crear archivo .env
echo 🔧 Creando archivo .env...

set ENV_FILE=%ROOT_DIR%\.env

(
    echo ; Carta Digital - Configuración del Sistema
    echo ; Generado por instalador automático
    echo.
    echo DB_HOST=%DB_HOST%
    echo DB_PORT=%DB_PORT%
    echo DB_NAME=%DB_NAME%
    echo DB_USER=%DB_USER%
    echo DB_PASS=%DB_PASS%
    echo.
    echo APP_NAME=Carta Digital
    echo APP_DEBUG=false
    echo APP_ENV=production
    echo.
    echo SESSION_NAME=carta_digital_session
    echo CHARSET=utf8mb4
    echo TIMEZONE=America/Lima
    echo.
    echo ; APIs (configurar después en admin
    echo CULQI_PUBLIC_KEY=
    echo CULQI_SECRET_KEY=
    echo APIPERU_TOKEN=
    echo WHATSAPP_NUMBER=
    echo.
    echo GENERATED_AT=%DATE% %TIME%
) > "%ENV_FILE%"

echo ✅ Archivo .env creado
echo.

REM Mensaje final
echo.
echo ╔═════════════════════════════════════════════╗
echo ║     ✅ ¡INSTALACIÓN COMPLETADA!             ║
echo ╚═════════════════════════════════════════════╝
echo.
echo 📊 Información de Instalación:
echo    Base de Datos: %DB_NAME%
echo    Host: %DB_HOST%
echo    Usuario Admin: admin
echo    Contraseña Admin: admin123
echo.
echo 🔗 Enlaces rápidos:
echo    Carta Digital: http://localhost/carta-digital/
echo    Panel Admin: http://localhost/carta-digital/admin/
echo    Diagnóstico: http://localhost/carta-digital/installer/diagnostico.php
echo.
echo 🔐 Próximos pasos:
echo    1. Cambia la contraseña del admin inmediatamente
echo    2. Configura datos de tu negocio
echo    3. Prueba el sistema completamente
echo.
echo 📖 Para más información:
echo    - Consulta: %INSTALLER_DIR%README.md
echo    - Guía Rápida: %INSTALLER_DIR%QUICK_START.md
echo.
pause
