@echo off
chcp 65001 >nul
title Instalar Backup Diario - Moura Galvao
echo.
echo Instala backup diario na pasta backup-banco da Area de Trabalho.
echo.

set "INSTALADOR="
if exist "C:\xampp\htdocs\advocacia\instalar_backup_area_trabalho.bat" (
    set "INSTALADOR=C:\xampp\htdocs\advocacia\instalar_backup_area_trabalho.bat"
)
if not defined INSTALADOR if exist "%~dp0advocacia\instalar_backup_area_trabalho.bat" (
    set "INSTALADOR=%~dp0advocacia\instalar_backup_area_trabalho.bat"
)

if not defined INSTALADOR (
    echo [ERRO] Nao encontrei instalar_backup_area_trabalho.bat
    echo Atualize o sistema antes ou copie os arquivos do Git.
    pause
    exit /b 1
)

call "%INSTALADOR%"
