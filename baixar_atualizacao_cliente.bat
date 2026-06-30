@echo off
chcp 65001 >nul
title Atualizar Sistema - Moura Galvao
echo.
echo ============================================
echo  ATUALIZAR SISTEMA - Moura Galvao
echo ============================================
echo.
echo Atualiza codigo (GitHub) e banco de dados.
echo Os dados existentes NAO serao apagados.
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0baixar_atualizacao_cliente.ps1"
if errorlevel 1 (
    echo.
    echo [ERRO] A atualizacao falhou. Leia a mensagem acima.
    echo.
    pause
    exit /b 1
)

echo.
pause
