@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

echo ============================================
echo  SINCRONIZAR ACCESS .MDB -^> MySQL
echo ============================================
echo.

set "PHP="
if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"
if not defined PHP if exist "D:\xampp\php\php.exe" set "PHP=D:\xampp\php\php.exe"
if not defined PHP where php >nul 2>&1 && set "PHP=php"

if not defined PHP (
    echo [ERRO] PHP nao encontrado.
    pause
    exit /b 1
)

set "CSCRIPT=%WINDIR%\SysWOW64\cscript.exe"
if not exist "%CSCRIPT%" (
    echo [ERRO] cscript 32 bits nao encontrado.
    pause
    exit /b 1
)

cd /d "%~dp0"
if not exist "import" mkdir import

set "MDB=%~1"
if "%MDB%"=="" (
    set "MDB=%~dp0..\Banco de dados Certo.2006.novo.mdb"
)

if not exist "%MDB%" (
    echo [ERRO] Arquivo .mdb nao encontrado:
    echo   %MDB%
    echo.
    echo Uso: sincronizar_mdb.bat [caminho\arquivo.mdb]
    pause
    exit /b 1
)

echo Arquivo Access: %MDB%
echo.

echo PASSO 1 - Backup do MySQL...
call "%~dp0exportar_banco.bat" silent
if errorlevel 1 (
    echo [ERRO] Falha no backup.
    pause
    exit /b 1
)

echo.
echo PASSO 2 - Exportar tabela PRINCIPAL para CSV...
"%CSCRIPT%" //nologo scripts\exportar_mdb_dao.vbs "%MDB%" PRINCIPAL import\planilha_access.csv
if errorlevel 1 (
    echo [ERRO] Falha ao exportar o .mdb
    pause
    exit /b 1
)

echo.
echo PASSO 3 - Simulacao...
"%PHP%" scripts\sincronizar_planilha.php --fonte=csv --arquivo=import/planilha_access.csv --dry-run
if errorlevel 1 (
    echo [ERRO] Falha na simulacao.
    pause
    exit /b 1
)

echo.
set /p CONF="Aplicar sincronizacao no MySQL? (S/N): "
if /i not "!CONF!"=="S" (
    echo Cancelado.
    pause
    exit /b 0
)

echo.
echo PASSO 4 - Sincronizando...
"%PHP%" scripts\sincronizar_planilha.php --fonte=csv --arquivo=import/planilha_access.csv --confirmar
if errorlevel 1 (
    echo [ERRO] Falha na sincronizacao.
    pause
    exit /b 1
)

echo.
echo PASSO 5 - Corrigir telefones...
"%PHP%" scripts\corrigir_telefones.php

echo.
echo ============================================
echo  CONCLUIDO
echo ============================================
pause
