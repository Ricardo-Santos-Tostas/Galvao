@echo off
chcp 65001 >nul
echo ============================================
echo  INSTALAR BACKUP AUTOMATICO DO BANCO
echo ============================================
echo.
echo Agenda backup diario do MySQL as 23:00.
echo Os arquivos ficam em: sql\backups\
echo Mantem os ultimos 30 dias.
echo.

set "SCRIPT=%~dp0backup_banco_automatico.bat"
set "TAREFA=Advocacia Backup MySQL Diario"

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

echo.
echo ============================================
echo  BACKUP AUTOMATICO INSTALADO
echo ============================================
echo  Tarefa: %TAREFA%
echo  Horario: todos os dias as 23:00
echo  Script: %SCRIPT%
echo.
echo Para testar agora, execute:
echo   backup_banco_automatico.bat
echo.
echo Para remover a tarefa:
echo   schtasks /delete /tn "%TAREFA%" /f
echo ============================================
echo.
pause
