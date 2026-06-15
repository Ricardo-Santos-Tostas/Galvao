@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

echo ============================================
echo  INSTALAR SISTEMA - NOVO COMPUTADOR
echo ============================================
echo.

set "PHP="
set "MYSQL="
if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"
if exist "C:\xampp\mysql\bin\mysql.exe" set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
if not defined PHP if exist "D:\xampp\php\php.exe" set "PHP=D:\xampp\php\php.exe"
if not defined MYSQL if exist "D:\xampp\mysql\bin\mysql.exe" set "MYSQL=D:\xampp\mysql\bin\mysql.exe"

if not defined PHP (
    echo [ERRO] XAMPP / PHP nao encontrado.
    echo Baixe e instale: https://www.apachefriends.org/
    pause
    exit /b 1
)

if not defined MYSQL (
    echo [ERRO] MySQL do XAMPP nao encontrado.
    pause
    exit /b 1
)

echo Antes de continuar:
echo   1. Abra o XAMPP
echo   2. Clique em Start no MySQL
echo.
pause

cd /d "%~dp0"

echo Verificando MySQL...
"%MYSQL%" -u root --connect-timeout=5 -e "SELECT 1" >nul 2>&1
if errorlevel 1 (
    echo [ERRO] MySQL nao esta rodando. Ligue o MySQL no XAMPP.
    pause
    exit /b 1
)

if not exist "config\config.local.php" (
    echo Criando config.local.php...
    copy /Y "config\config.local.php.example" "config\config.local.php" >nul
)

set "BACKUP=sql\backup_advocacia.sql"

if exist "%BACKUP%" (
    echo.
    echo Importando banco de dados...
    echo Arquivo: %BACKUP%
    echo Aguarde, pode demorar alguns minutos...
    echo.

    "%MYSQL%" -u root --connect-timeout=5 < "%BACKUP%"

    if errorlevel 1 (
        echo [ERRO] Falha ao importar o banco.
        pause
        exit /b 1
    )
    echo Banco importado com sucesso!
) else (
    echo.
    echo [AVISO] Arquivo %BACKUP% nao encontrado.
    echo.

    if exist "..\sistema.db" (
        echo Encontrado sistema.db na pasta pai.
        echo Tentando migrar SQLite -^> MySQL...
        "%PHP%" scripts\migrar_sqlite_para_mysql.php
        if errorlevel 1 (
            echo [ERRO] Migracao falhou.
            pause
            exit /b 1
        )
    ) else (
        echo Copie o arquivo sql\backup_advocacia.sql do PC antigo
        echo e execute este instalador novamente.
        pause
        exit /b 1
    )
)

echo.
echo Verificando instalacao...
"%PHP%" scripts\verificar_instalacao.php
if errorlevel 1 (
    echo.
    echo [ERRO] Verificacao falhou. Confira MySQL e config.local.php
    pause
    exit /b 1
)

echo.
echo ============================================
echo  INSTALACAO CONCLUIDA COM SUCESSO
echo ============================================
echo.
echo Proximo passo: execute iniciar.bat
echo Depois abra: http://localhost:8080
echo.
echo Para outros PCs na rede, use o IP que aparecer no iniciar.bat
echo ============================================
echo.
pause
