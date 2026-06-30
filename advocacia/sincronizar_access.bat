@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

echo ============================================
echo  SINCRONIZAR DADOS DO ACCESS
echo  Atualiza o MySQL sem apagar fotos/documentos
echo ============================================
echo.

set "PHP="
where php >nul 2>&1
if %errorlevel% equ 0 set "PHP=php"
if not defined PHP if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"
if not defined PHP if exist "D:\xampp\php\php.exe" set "PHP=D:\xampp\php\php.exe"

if not defined PHP (
    echo [ERRO] PHP nao encontrado. Instale o XAMPP.
    pause
    exit /b 1
)

if exist "C:\xampp\mysql\bin\mysql.exe" (
    "C:\xampp\mysql\bin\mysql.exe" -u root --connect-timeout=3 -e "SELECT 1" >nul 2>&1
    if errorlevel 1 (
        echo [ERRO] MySQL nao esta rodando. Ligue no XAMPP.
        pause
        exit /b 1
    )
)

cd /d "%~dp0"

if not exist "import" mkdir import

echo PASSO 1 - Exportar backup de seguranca do MySQL atual...
echo.
call "%~dp0exportar_banco.bat" silent
if errorlevel 1 (
    echo [ERRO] Nao foi possivel fazer backup antes da sincronizacao.
    pause
    exit /b 1
)

echo.
echo PASSO 2 - Escolha a origem dos dados do Access:
echo.
echo   [1] Arquivo CSV  (recomendado - exportar do Access)
echo   [2] Arquivo SQLite (sistema.db)
echo   [3] Sair
echo.
set /p OPCAO="Opcao (1/2/3): "

if "%OPCAO%"=="3" exit /b 0
if not "%OPCAO%"=="1" if not "%OPCAO%"=="2" (
    echo Opcao invalida.
    pause
    exit /b 1
)

set "ARQUIVO="
set "FONTE="

if "%OPCAO%"=="1" (
    set "FONTE=csv"
    echo.
    echo Coloque o CSV exportado do Access em:
    echo   %CD%\import\planilha_access.csv
    echo.
    echo No Access: selecione a tabela ^> Exportar ^> Arquivo de texto CSV
    echo Use a tabela principal ^(Planilha1^).
    echo.
    set "ARQUIVO=import\planilha_access.csv"
    if not exist "!ARQUIVO!" (
        echo [AVISO] Arquivo ainda nao encontrado: import\planilha_access.csv
        echo Copie o arquivo e pressione uma tecla para continuar...
        pause >nul
    )
)

if "%OPCAO%"=="2" (
    set "FONTE=sqlite"
    echo.
    set /p ARQUIVO="Caminho do sistema.db (Enter = ..\sistema.db): "
    if "!ARQUIVO!"=="" set "ARQUIVO=..\sistema.db"
)

if not exist "!ARQUIVO!" (
    echo [ERRO] Arquivo nao encontrado: !ARQUIVO!
    pause
    exit /b 1
)

echo.
echo PASSO 3 - Simulacao (nao grava ainda)...
echo.
"%PHP%" scripts\sincronizar_planilha.php --fonte=!FONTE! --arquivo="!ARQUIVO!" --dry-run
if errorlevel 1 (
    echo.
    echo [ERRO] Falha na simulacao.
    pause
    exit /b 1
)

echo.
echo ============================================
echo  CONFIRMACAO
echo ============================================
echo.
echo Isso vai ATUALIZAR cadastros existentes e INSERIR novos.
echo Fotos, PDFs e area juridica do sistema novo NAO serao apagados.
echo Backup salvo em: sql\backup_advocacia.sql
echo.
set /p CONF="Deseja aplicar a sincronizacao? (S/N): "
if /i not "!CONF!"=="S" (
    echo Cancelado.
    pause
    exit /b 0
)

echo.
echo Aplicando sincronizacao...
"%PHP%" scripts\sincronizar_planilha.php --fonte=!FONTE! --arquivo="!ARQUIVO!" --confirmar

echo.
pause
