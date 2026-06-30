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

$repoPath = Join-Path $WorkspacePath "Galvao"
$sourceApp = Join-Path $repoPath "advocacia"
$targetApp = Join-Path $XamppPath "htdocs\advocacia"
$configBackup = $null
$phpExe = Join-Path $XamppPath "php\php.exe"

Write-Host ""
Write-Host "============================================" -ForegroundColor Yellow
Write-Host " ATUALIZAR SISTEMA - Moura Galvao" -ForegroundColor Yellow
Write-Host "============================================" -ForegroundColor Yellow
Write-Host "Codigo + banco de dados. Dados existentes preservados."
Write-Host ""

if (-not (Test-Path (Join-Path $repoPath ".git"))) {
    throw "Repositorio nao encontrado em '$repoPath'."
}

if (-not (Test-Path $phpExe)) {
    throw "PHP nao encontrado em '$phpExe'. Verifique o XAMPP."
}

Write-Step "1/4 Baixando codigo do GitHub"
Push-Location $repoPath
& git pull --ff-only
if ($LASTEXITCODE -ne 0) { throw "Falha no git pull. Verifique internet e login no GitHub." }
Pop-Location

if (-not (Test-Path $sourceApp)) {
    throw "Pasta '$sourceApp' nao encontrada apos o download."
}

Write-Step "2/4 Copiando codigo para o XAMPP"
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

Write-Step "3/4 Atualizando banco de dados"
$migrations = @(
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

Write-Step "4/4 Reiniciando Apache e verificando"
$apache = Get-Service -Name "Apache2.4" -ErrorAction SilentlyContinue
if ($apache) {
    if ($apache.Status -eq "Running") { Restart-Service "Apache2.4" -Force }
    else { Start-Service "Apache2.4" }
}

$verifyScript = Join-Path $targetApp "scripts\verificar_instalacao.php"
if (Test-Path $verifyScript) {
    & $phpExe $verifyScript
    if ($LASTEXITCODE -ne 0) {
        throw "Verificacao falhou apos atualizacao."
    }
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Green
Write-Host " ATUALIZACAO CONCLUIDA" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Green
Write-Host "Acesse: http://localhost/advocacia"
Write-Host ""
Write-Host "Banco de dados (dados do Access):" -ForegroundColor Yellow
Write-Host "  Opcao A - Mesclar planilha (preserva fotos/PDFs locais):"
Write-Host "    sincronizar_access.bat + planilha .xlsx"
Write-Host "  Opcao B - Importar backup completo do Git:"
Write-Host "    importar_backup_atualizado.bat"
Write-Host ""
