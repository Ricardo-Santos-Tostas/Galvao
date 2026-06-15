@echo off
chcp 65001 >nul
echo ============================================
echo  Recriar tabela MySQL + Importar SQLite
echo ============================================
echo.

set "PHP="
where php >nul 2>&1
if %errorlevel% equ 0 set "PHP=php"
if not defined PHP if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"

if not defined PHP (
    echo PHP nao encontrado.
    pause
    exit /b 1
)

echo IMPORTANTE: Ligue o MySQL no painel do XAMPP antes de continuar.
echo.
pause

cd /d "%~dp0"
"%PHP%" scripts\migrar_sqlite_para_mysql.php
echo.
pause
