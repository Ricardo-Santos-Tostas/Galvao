@echo off
chcp 65001 >nul
echo ============================================
echo  CORRIGIR TELEFONES NO BANCO DE DADOS
echo ============================================
echo.
echo Remove o zero extra do sistema antigo
echo Exemplo: 71(098)501-9440 -^> 71(98)501-9440
echo.

set "PHP="
if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"
if not defined PHP if exist "D:\xampp\php\php.exe" set "PHP=D:\xampp\php\php.exe"

if not defined PHP (
    echo [ERRO] PHP nao encontrado. Instale o XAMPP.
    pause
    exit /b 1
)

cd /d "%~dp0"
"%PHP%" scripts\corrigir_telefones.php
echo.
pause
