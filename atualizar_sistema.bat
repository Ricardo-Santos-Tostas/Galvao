@echo off
chcp 65001 >nul
echo ============================================
echo  ATUALIZAR SISTEMA - Moura Galvao
echo ============================================
echo.
echo Este script baixa a versao nova do GitHub
echo e atualiza o codigo SEM apagar o banco.
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0atualizar_sistema.ps1"
echo.
pause