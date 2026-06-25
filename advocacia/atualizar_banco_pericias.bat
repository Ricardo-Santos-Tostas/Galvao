@echo off
chcp 65001 >nul
echo ============================================
echo  ATUALIZAR BANCO - Tabela Pericias
echo ============================================
echo.

set "PHP="
if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"
if not defined PHP if exist "D:\xampp\php\php.exe" set "PHP=D:\xampp\php\php.exe"

if not defined PHP (
    echo [ERRO] PHP nao encontrado.
    pause
    exit /b 1
)

cd /d "%~dp0"
"%PHP%" scripts\atualizar_banco_pericias.php
echo.
pause
