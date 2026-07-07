@echo off
chcp 65001 >nul
title Corrigir campo Andamento
echo.
echo Corrige textos do Andamento com _x000D_ e quebras de linha mistas.
echo Nao apaga cadastros — apenas normaliza o texto existente.
echo.

set "PHP="
where php >nul 2>&1
if %errorlevel% equ 0 set "PHP=php"
if not defined PHP if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"

if not defined PHP (
    echo [ERRO] PHP nao encontrado. Instale o XAMPP.
    pause
    exit /b 1
)

cd /d "%~dp0"
"%PHP%" scripts\corrigir_andamento_multilinha.php
echo.
pause
