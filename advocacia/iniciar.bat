@echo off
chcp 65001 >nul
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
    pause
    exit /b 1
)

echo IMPORTANTE: Ligue o MySQL no painel do XAMPP antes de usar o sistema.
echo.

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
    echo    http://%MEU_IP%:8080
) else (
    echo  Outros PCs: use o IP deste PC ^(comando: ipconfig^)
    echo    http://SEU_IP:8080
)
echo --------------------------------------------
echo.
echo Pressione Ctrl+C para parar o servidor.
echo NAO FECHE esta janela enquanto outros usarem o sistema.
echo.

cd /d "%~dp0"
"%PHP%" -S 0.0.0.0:8080
pause
