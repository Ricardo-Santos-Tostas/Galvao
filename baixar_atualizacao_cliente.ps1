param(
    [string]$WorkspacePath = "C:\Servio\Galvao",
    [string]$XamppPath = "C:\xampp"
)

$ErrorActionPreference = "Stop"

function Write-Step {
    param([string]$Message)
    Write-Host ""
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Restart-ApacheSafe {
    param([string]$XamppPath)

    $reiniciado = $false

    try {
        $apache = Get-Service -Name "Apache2.4" -ErrorAction SilentlyContinue
        if ($apache) {
            if ($apache.Status -eq "Running") {
                Restart-Service "Apache2.4" -Force -ErrorAction Stop
            } else {
                Start-Service "Apache2.4" -ErrorAction Stop
            }
            $reiniciado = $true
            Write-Host "  Apache reiniciado (servico Windows)."
        }
    } catch {
        Write-Host "  [AVISO] Servico Apache2.4: $($_.Exception.Message)" -ForegroundColor Yellow
    }

    if (-not $reiniciado) {
        $stop = Join-Path $XamppPath "apache_stop.bat"
        $start = Join-Path $XamppPath "apache_start.bat"
        if ((Test-Path $stop) -and (Test-Path $start)) {
            try {
                & cmd /c "`"$stop`""
                Start-Sleep -Seconds 2
                & cmd /c "`"$start`""
                $reiniciado = $true
                Write-Host "  Apache reiniciado (painel XAMPP)."
            } catch {
                Write-Host "  [AVISO] Falha ao reiniciar via scripts do XAMPP." -ForegroundColor Yellow
            }
        }
    }

    if (-not $reiniciado) {
        Write-Host ""
        Write-Host "  [AVISO] Nao foi possivel reiniciar o Apache automaticamente." -ForegroundColor Yellow
        Write-Host "  Abra o XAMPP e clique Stop/Start no Apache." -ForegroundColor Yellow
        Write-Host "  Codigo e banco ja foram atualizados." -ForegroundColor Yellow
    }

    return $reiniciado
}

$repoPath = Join-Path $WorkspacePath "Galvao"
$sourceApp = Join-Path $repoPath "advocacia"
$targetApp = Join-Path $XamppPath "htdocs\advocacia"
$configBackup = $null
$phpExe = Join-Path $XamppPath "php\php.exe"
$mysqlExe = Join-Path $XamppPath "mysql\bin\mysql.exe"

Write-Host ""
Write-Host "============================================" -ForegroundColor Yellow
Write-Host " ATUALIZAR SISTEMA - Moura Galvao" -ForegroundColor Yellow
Write-Host "============================================" -ForegroundColor Yellow
Write-Host "Atualiza codigo e banco de dados do GitHub."
Write-Host "O MySQL local sera substituido pelo backup atualizado."
Write-Host ""

if (-not (Test-Path (Join-Path $repoPath ".git"))) {
    throw "Repositorio nao encontrado em '$repoPath'."
}

if (-not (Test-Path $phpExe)) {
    throw "PHP nao encontrado em '$phpExe'. Verifique o XAMPP."
}

if (-not (Test-Path $mysqlExe)) {
    throw "MySQL nao encontrado em '$mysqlExe'. Verifique o XAMPP."
}

Write-Step "1/5 Baixando codigo do GitHub"
Push-Location $repoPath
& git pull --ff-only
if ($LASTEXITCODE -ne 0) { throw "Falha no git pull. Verifique internet e login no GitHub." }
Pop-Location

if (-not (Test-Path $sourceApp)) {
    throw "Pasta '$sourceApp' nao encontrada apos o download."
}

Write-Step "2/5 Copiando codigo para o XAMPP"
if (Test-Path (Join-Path $targetApp "config\config.local.php")) {
    $configBackup = Join-Path $env:TEMP "advocacia_config.local.php.bak"
    Copy-Item (Join-Path $targetApp "config\config.local.php") $configBackup -Force
    Write-Host "Configuracao local preservada."
}

if (Test-Path $targetApp) {
    Remove-Item $targetApp -Recurse -Force
}

Copy-Item $sourceApp $targetApp -Recurse -Force

if ($configBackup -and (Test-Path $configBackup)) {
    Copy-Item $configBackup (Join-Path $targetApp "config\config.local.php") -Force
    Remove-Item $configBackup -Force
}

Write-Step "3/5 Importando banco de dados atualizado"
& $mysqlExe -u root --connect-timeout=10 -e "SELECT 1" | Out-Null
if ($LASTEXITCODE -ne 0) {
    throw "MySQL nao esta rodando. Ligue o MySQL no painel do XAMPP."
}

$backupFile = Join-Path $targetApp "sql\backup_advocacia.sql"
if (-not (Test-Path $backupFile)) {
    throw "Backup nao encontrado: $backupFile"
}

$backupSize = (Get-Item $backupFile).Length
if ($backupSize -lt 1000) {
    throw "Arquivo de backup invalido ou incompleto ($backupSize bytes)."
}

Write-Host "  Arquivo: $backupFile"
Write-Host "  Aguarde 1-3 minutos (substitui todos os cadastros locais)..."

$importCmd = "`"$mysqlExe`" -u root < `"$backupFile`""
cmd /c $importCmd
if ($LASTEXITCODE -ne 0) {
    throw "Falha ao importar backup_advocacia.sql"
}

Write-Host "  Banco importado com sucesso."

Write-Step "4/5 Aplicando migracoes complementares"
$migrations = @(
    "corrigir_telefones.php",
    "atualizar_banco_anexos.php",
    "atualizar_banco_pericias.php",
    "atualizar_banco_usuarios.php",
    "atualizar_banco_log.php"
)

foreach ($script in $migrations) {
    $scriptPath = Join-Path $targetApp "scripts\$script"
    if (Test-Path $scriptPath) {
        Write-Host "  $script"
        & $phpExe $scriptPath
        if ($LASTEXITCODE -ne 0) {
            throw "Falha ao executar $script"
        }
    }
}

Write-Step "5/5 Reiniciando Apache e verificando"
Restart-ApacheSafe -XamppPath $XamppPath | Out-Null

$verifyScript = Join-Path $targetApp "scripts\verificar_instalacao.php"
if (Test-Path $verifyScript) {
    & $phpExe $verifyScript
    if ($LASTEXITCODE -ne 0) {
        throw "Verificacao falhou apos atualizacao. Verifique se o MySQL esta ligado."
    }
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host " ATUALIZACAO CONCLUIDA" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green
Write-Host "Acesse: http://localhost/advocacia"
Write-Host "Banco de dados atualizado com o backup do GitHub."
Write-Host ""
