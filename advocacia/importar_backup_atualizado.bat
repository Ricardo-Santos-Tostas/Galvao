@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

echo ============================================
echo  IMPORTAR BACKUP ATUALIZADO DO GIT
echo ============================================
echo.
echo ATENCAO: O baixar_atualizacao_cliente.bat ja importa
echo o backup automaticamente. Use este script apenas
echo se quiser importar de novo sem baixar do GitHub.
echo.
set /p CONF="Continuar com importacao completa? (S/N): "
if /i not "!CONF!"=="S" (
    echo Cancelado.
    pause
    exit /b 0
)

set "MYSQL="
if exist "C:\xampp\mysql\bin\mysql.exe" set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
if not defined MYSQL if exist "D:\xampp\mysql\bin\mysql.exe" set "MYSQL=D:\xampp\mysql\bin\mysql.exe"

if not defined MYSQL (
    echo [ERRO] MySQL do XAMPP nao encontrado.
    pause
    exit /b 1
)

cd /d "%~dp0"

if not exist "sql\backup_advocacia.sql" (
    echo [ERRO] sql\backup_advocacia.sql nao encontrado.
    echo Rode baixar_atualizacao_cliente.bat para baixar do GitHub.
    pause
    exit /b 1
)

"%MYSQL%" -u root --connect-timeout=5 -e "SELECT 1" >nul 2>&1
if errorlevel 1 (
    echo [ERRO] MySQL nao esta rodando. Ligue no XAMPP.
    pause
    exit /b 1
)

echo.
echo Importando backup... aguarde (1-3 minutos).
"%MYSQL%" -u root < "sql\backup_advocacia.sql"
if errorlevel 1 (
    echo [ERRO] Falha ao importar o backup.
    pause
    exit /b 1
)

set "PHP="
where php >nul 2>&1
if %errorlevel% equ 0 set "PHP=php"
if not defined PHP if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"

if defined PHP (
    echo.
    echo Aplicando migracoes complementares...
    "%PHP%" scripts\atualizar_banco_anexos.php
    "%PHP%" scripts\atualizar_banco_pericias.php
    "%PHP%" scripts\atualizar_banco_usuarios.php
    "%PHP%" scripts\atualizar_banco_log.php
)

echo.
echo ============================================
echo  BANCO IMPORTADO COM SUCESSO
echo ============================================
echo  Registros sincronizados com o Access.
echo  Acesse: http://localhost/advocacia
echo ============================================
echo.
pause
