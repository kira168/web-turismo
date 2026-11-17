@echo off
title Turismo Inkaiko - Servidor Local
echo ============================================
echo   Iniciando Turismo Inkaiko en Localhost
echo ============================================

:: Verificar si MySQL ya esta corriendo
netstat -ano | findstr :3306 >nul
if %errorlevel% neq 0 (
    echo Iniciando MySQL de XAMPP...
    start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --console
    timeout /t 2 /nobreak >nul
) else (
    echo MySQL ya esta en ejecucion.
)

:: Abrir navegador automaticamente
start http://localhost:8080

:: Iniciar servidor PHP
echo Iniciando servidor PHP en http://localhost:8080 ...
echo Presiona Ctrl + C para detener el servidor.
"C:\xampp\php\php.exe" -S 0.0.0.0:8080
pause
