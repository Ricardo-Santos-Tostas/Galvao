@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

set "SILENT=0"
if "%1"=="silent" set "SILENT=1"

echo ============================================
echo  EXPORTAR BANCO MySQL - advocacia
echo ============================================
echo.

set "MYSQL="
set "MYSQLDUMP="
if exist "C:\xampp\mysql\bin\mysql.exe" (
    set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
    set "MYSQLDUMP=C:\xampp\mysql\bin\mysqldump.exe"
)
if not defined MYSQL if exist "D:\xampp\mysql\bin\mysql.exe" (
    set "MYSQL=D:\xampp\mysql\bin\mysql.exe"
    set "MYSQLDUMP=D:\xampp\mysql\bin\mysqldump.exe"
)

if not defined MYSQL (
    echo [ERRO] MySQL do XAMPP nao encontrado.
    echo Instale o XAMPP e ligue o MySQL no painel de controle.
    if "%SILENT%"=="0" pause
    exit /b 1
)

echo Verificando MySQL...
"%MYSQL%" -u root --connect-timeout=5 -e "SELECT 1" >nul 2>&1
if errorlevel 1 (
    echo [ERRO] MySQL nao esta rodando.
    echo Abra o XAMPP e clique em Start no MySQL.
    if "%SILENT%"=="0" pause
    exit /b 1
)

cd /d "%~dp0"
if not exist "sql" mkdir sql

set "ARQUIVO=sql\backup_advocacia.sql"
echo Exportando banco para: %ARQUIVO%
echo Aguarde...

"%MYSQLDUMP%" -u root --connect-timeout=5 --databases advocacia --result-file="%ARQUIVO%" --default-character-set=utf8mb4

if errorlevel 1 (
    echo [ERRO] Falha ao exportar o banco.
    if "%SILENT%"=="0" pause
    exit /b 1
)

if not exist "%ARQUIVO%" (
    echo [ERRO] Arquivo de backup nao foi criado.
    if "%SILENT%"=="0" pause
    exit /b 1
)

for %%A in ("%ARQUIVO%") do set TAM=%%~zA
if !TAM! LSS 1000 (
    echo [ERRO] Backup muito pequeno ^(!TAM! bytes^). Exportacao incompleta.
    if "%SILENT%"=="0" pause
    exit /b 1
)

echo.
echo ============================================
echo  EXPORTACAO CONCLUIDA
echo ============================================
echo  Arquivo: %ARQUIVO%
echo  Tamanho: !TAM! bytes
echo.
echo  Leve este arquivo junto com a pasta advocacia
echo  para o outro computador.
echo ============================================
echo.
if "%SILENT%"=="0" pause
