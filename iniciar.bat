@echo off
title PageLink - Link-in-bio System
cd /d "%~dp0"

color 0A

echo ========================================
echo   PageLink v2.0 - Link-in-bio System
echo ========================================
echo.

if not exist "database.sqlite" (
    echo   [*] Base de datos no encontrada.
    echo   [*] Ejecutando setup.php...
    echo.
    php setup.php
    if errorlevel 1 (
        echo.
        echo   [!] Error al inicializar la base de datos.
        echo   [!] Asegurate de tener PHP 8+ instalado.
        pause
        exit /b 1
    )
    echo.
) else (
    echo   [i] Base de datos encontrada.
)

echo   [i] Iniciando servidor en localhost:8000
echo.
echo   Presiona Ctrl+C para detener el servidor.
echo.

start http://localhost:8000
start http://localhost:8000/admin/
php -S localhost:8000 -t "%~dp0"

echo.
echo   [x] Servidor detenido.
pause
