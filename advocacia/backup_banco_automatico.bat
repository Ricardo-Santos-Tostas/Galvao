@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

set "SILENT=0"
if /I "%1"=="silent" set "SILENT=1"

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
    if "%SILENT%"=="0" pause
    exit /b 1
)

cd /d "%~dp0"
if not exist "sql\backups" mkdir "sql\backups"

for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd"') do set "DATA=%%i"
for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HH-mm"') do set "STAMP=%%i"

set "ARQUIVO=sql\backups\backup_advocacia_%STAMP%.sql"
set "ULTIMO=sql\backup_advocacia.sql"
set "LOG=sql\backups\backup.log"

echo [%STAMP%] Iniciando backup...>>"%LOG%"

"%MYSQL%" -u root --connect-timeout=10 -e "SELECT 1" >nul 2>&1
if errorlevel 1 (
    echo [ERRO] MySQL nao esta rodando.
    echo [%STAMP%] ERRO: MySQL parado>>"%LOG%"
    if "%SILENT%"=="0" pause
    exit /b 1
)

"%MYSQLDUMP%" -u root --databases advocacia --result-file="%ARQUIVO%" --default-character-set=utf8mb4
if errorlevel 1 (
    echo [ERRO] Falha ao exportar o banco.
    echo [%STAMP%] ERRO: mysqldump falhou>>"%LOG%"
    if exist "%ARQUIVO%" del /f /q "%ARQUIVO%"
    if "%SILENT%"=="0" pause
    exit /b 1
)

if not exist "%ARQUIVO%" (
    echo [ERRO] Arquivo de backup nao foi criado.
    echo [%STAMP%] ERRO: arquivo ausente>>"%LOG%"
    if "%SILENT%"=="0" pause
    exit /b 1
)

for %%A in ("%ARQUIVO%") do set TAM=%%~zA
if !TAM! LSS 1000 (
    echo [ERRO] Backup muito pequeno ^(!TAM! bytes^).
    echo [%STAMP%] ERRO: backup pequeno demais>>"%LOG%"
    del /f /q "%ARQUIVO%"
    if "%SILENT%"=="0" pause
    exit /b 1
)

copy /y "%ARQUIVO%" "%ULTIMO%" >nul

powershell -NoProfile -Command ^
  "$dir = Join-Path (Get-Location) 'sql\backups';" ^
  "$limite = (Get-Date).AddDays(-30);" ^
  "Get-ChildItem $dir -Filter 'backup_advocacia_*.sql' | Where-Object { $_.LastWriteTime -lt $limite } | Remove-Item -Force"

echo [%STAMP%] OK: %ARQUIVO% ^(!TAM! bytes^)>>"%LOG%"

if "%SILENT%"=="0" (
    echo ============================================
    echo  BACKUP CONCLUIDO
    echo ============================================
    echo  Arquivo: %ARQUIVO%
    echo  Copia recente: %ULTIMO%
    echo  Log: %LOG%
    echo  Backups com mais de 30 dias sao removidos.
    echo ============================================
    pause
)

exit /b 0
