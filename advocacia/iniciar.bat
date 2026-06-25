@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

echo ============================================
echo  ADVOCACIA TRABALHISTA - Rede Local
echo ============================================
echo.

set "PHP="
where php >nul 2>&1
if %errorlevel% equ 0 set "PHP=php"
if not defined PHP if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"
if not defined PHP if exist "D:\xampp\php\php.exe" set "PHP=D:\xampp\php\php.exe"

if not defined PHP (
    echo [ERRO] PHP nao encontrado. Instale o XAMPP.
    echo Download: https://www.apachefriends.org/
    pause
    exit /b 1
)

if exist "C:\xampp\mysql\bin\mysql.exe" (
    "C:\xampp\mysql\bin\mysql.exe" -u root --connect-timeout=3 -e "SELECT 1" >nul 2>&1
    if errorlevel 1 (
        echo [AVISO] MySQL nao esta rodando.
        echo Abra o XAMPP e clique em Start no MySQL.
        echo.
    ) else (
        echo MySQL: OK
    )
) else (
    echo [AVISO] MySQL do XAMPP nao encontrado.
    echo.
)

netstat -ano | findstr ":8080" | findstr "LISTENING" >nul 2>&1
if %errorlevel% equ 0 (
    echo.
    echo [AVISO] A porta 8080 ja esta em uso.
    echo O sistema pode ja estar rodando.
    echo Abrindo o navegador em http://localhost:8080
    echo.
    echo Se a pagina nao abrir, feche outras janelas do iniciar.bat
    echo ou reinicie o computador e tente novamente.
    echo.
    start "" http://localhost:8080/login.php
    pause
    exit /b 0
)

REM Descobrir IP local
set "MEU_IP="
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4"') do (
    set "IP=%%a"
    setlocal enabledelayedexpansion
    set "IP=!IP:~1!"
    if not "!IP!"=="127.0.0.1" set "MEU_IP=!IP!"
    endlocal
)

echo --------------------------------------------
echo  Neste computador:
echo    http://localhost:8080
echo.
if defined MEU_IP (
    echo  Outros PCs na mesma rede Wi-Fi:
    echo    http://!MEU_IP!:8080
) else (
    echo  Outros PCs: use o IP deste PC ^(comando: ipconfig^)
    echo    http://SEU_IP:8080
)
echo --------------------------------------------
echo.
echo Abrindo o navegador...
echo Pressione Ctrl+C para parar o servidor.
echo NAO FECHE esta janela enquanto outros usarem o sistema.
echo.

cd /d "%~dp0"
start "" http://localhost:8080/login.php
"%PHP%" -S 0.0.0.0:8080
echo.
echo Servidor encerrado.
pause
