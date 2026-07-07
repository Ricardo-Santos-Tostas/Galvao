@echo off
chcp 65001 >nul
title Atualizar Sistema - Moura Galvao
echo.
echo ============================================
echo  ATUALIZAR SISTEMA - Moura Galvao
echo ============================================
echo.
echo Atualiza codigo e estrutura do banco (tabelas/colunas).
echo Dados do cliente NAO sao alterados.
echo.

set "PS1="
if exist "C:\Servio\Galvao\Galvao\baixar_atualizacao_cliente.ps1" (
    set "PS1=C:\Servio\Galvao\Galvao\baixar_atualizacao_cliente.ps1"
)
if not defined PS1 if exist "%~dp0baixar_atualizacao_cliente.ps1" (
    set "PS1=%~dp0baixar_atualizacao_cliente.ps1"
)

if not defined PS1 (
    echo [ERRO] Nao encontrei baixar_atualizacao_cliente.ps1
    echo Procure em C:\Servio\Galvao\Galvao\
    pause
    exit /b 1
)

echo Usando: %PS1%
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%PS1%"
if errorlevel 1 (
    echo.
    echo [ERRO] A atualizacao falhou. Leia a mensagem acima.
    echo.
    pause
    exit /b 1
)

echo.
pause
