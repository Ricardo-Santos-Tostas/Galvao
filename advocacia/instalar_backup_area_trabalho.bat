@echo off
chcp 65001 >nul
echo ============================================
echo  BACKUP DIARIO - Area de Trabalho
echo ============================================
echo.
echo Salva o banco em: Area de Trabalho\backup-banco
echo Horario: todos os dias as 23:00
echo Mantem os ultimos 30 dias.
echo.
echo IMPORTANTE: o MySQL do XAMPP precisa estar ligado
echo no horario do backup.
echo.

set "SCRIPT=%~dp0backup_banco_area_trabalho.bat"
set "TAREFA=Advocacia Backup Area de Trabalho"

if not exist "%SCRIPT%" (
    echo [ERRO] Script nao encontrado: %SCRIPT%
    pause
    exit /b 1
)

schtasks /query /tn "%TAREFA%" >nul 2>&1
if not errorlevel 1 (
    echo Removendo tarefa antiga...
    schtasks /delete /tn "%TAREFA%" /f >nul
)

schtasks /create ^
    /tn "%TAREFA%" ^
    /tr "\"%SCRIPT%\" silent" ^
    /sc daily ^
    /st 23:00 ^
    /rl LIMITED ^
    /f

if errorlevel 1 (
    echo.
    echo [ERRO] Nao foi possivel criar a tarefa agendada.
    echo Execute este arquivo como Administrador.
    pause
    exit /b 1
)

for /f "delims=" %%D in ('powershell -NoProfile -Command "[Environment]::GetFolderPath('Desktop')"') do set "DESKTOP=%%D"
if not exist "%DESKTOP%\backup-banco" mkdir "%DESKTOP%\backup-banco"

echo.
echo ============================================
echo  BACKUP AUTOMATICO INSTALADO
echo ============================================
echo  Tarefa: %TAREFA%
echo  Horario: todos os dias as 23:00
echo  Pasta: %DESKTOP%\backup-banco
echo.
echo Para testar agora:
echo   backup_banco_area_trabalho.bat
echo.
echo Para remover a tarefa:
echo   schtasks /delete /tn "%TAREFA%" /f
echo ============================================
echo.
pause
