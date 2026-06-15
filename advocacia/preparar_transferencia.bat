@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

echo ============================================
echo  PREPARAR PACOTE PARA OUTRO COMPUTADOR
echo ============================================
echo.

cd /d "%~dp0"

echo [1/3] Exportando banco de dados MySQL...
call "%~dp0exportar_banco.bat" silent
if errorlevel 1 exit /b 1

echo.
echo [2/3] Verificando arquivos necessarios...

set "OK=1"
if not exist "config\config.local.php" (
    echo  Copiando config.local.php a partir do exemplo...
    copy /Y "config\config.local.php.example" "config\config.local.php" >nul
)

if not exist "sql\backup_advocacia.sql" (
    echo [ERRO] backup_advocacia.sql nao foi criado.
    set "OK=0"
)

if not exist "assets\img\logo.png" (
    echo [AVISO] Logo nao encontrada em assets\img\logo.png
)

echo.
echo [3/3] Gerando lista de transferencia...

(
echo PACOTE PARA TRANSFERENCIA - Moura Galvao Advogados
echo ====================================================
echo.
echo COPIE A PASTA INTEIRA "advocacia" para o outro PC:
echo.
echo   %CD%
echo.
echo Arquivos essenciais:
echo   - sql\backup_advocacia.sql  ^(banco de dados^)
echo   - config\config.local.php
echo   - instalar_novo_pc.bat
echo   - iniciar.bat
echo.
echo NO OUTRO COMPUTADOR:
echo   1. Instalar XAMPP
echo   2. Colar a pasta advocacia ^(ex: C:\Servio\advocacia^)
echo   3. Ligar MySQL no XAMPP
echo   4. Executar: instalar_novo_pc.bat
echo   5. Executar: iniciar.bat
echo   6. Abrir: http://localhost:8080
echo.
echo Data: %date% %time%
) > "LEIA-ME-TRANSFERENCIA.txt"

echo.
echo ============================================
echo  PACOTE PRONTO PARA TRANSFERIR
echo ============================================
echo.
echo Copie esta pasta inteira para pen drive ou rede:
echo.
echo   %CD%
echo.
echo Arquivo gerado: LEIA-ME-TRANSFERENCIA.txt
echo Banco exportado: sql\backup_advocacia.sql
echo.
echo No outro PC, execute: instalar_novo_pc.bat
echo ============================================
echo.
pause
