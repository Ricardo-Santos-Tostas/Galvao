@echo off
chcp 65001 >nul
title Backup do Banco - Moura Galvao
echo.
echo Gera backup agora na pasta backup-banco da Area de Trabalho.
echo.

set "BACKUP="
if exist "C:\xampp\htdocs\advocacia\backup_banco_area_trabalho.bat" (
    set "BACKUP=C:\xampp\htdocs\advocacia\backup_banco_area_trabalho.bat"
)
if not defined BACKUP if exist "%~dp0advocacia\backup_banco_area_trabalho.bat" (
    set "BACKUP=%~dp0advocacia\backup_banco_area_trabalho.bat"
)

if not defined BACKUP (
    echo [ERRO] Nao encontrei backup_banco_area_trabalho.bat
    pause
    exit /b 1
)

call "%BACKUP%"
