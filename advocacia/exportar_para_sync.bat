@echo off
chcp 65001 >nul
echo ============================================
echo  EXPORTAR DADOS PARA SINCRONIZAR NO CLIENTE
echo ============================================
echo.

set "PHP="
if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"
if not defined PHP where php >nul 2>&1 && set "PHP=php"

if not defined PHP (
    echo [ERRO] PHP nao encontrado.
    pause
    exit /b 1
)

cd /d "%~dp0"
"%PHP%" scripts\exportar_para_sync.php
if errorlevel 1 (
    echo [ERRO] Falha na exportacao.
    pause
    exit /b 1
)

echo.
echo Proximo passo: commit e push do arquivo import\dados_servidor.csv
echo.
pause
