param(
    [string]$RepoUrl = "https://github.com/Ricardo-Santos-Tostas/Galvao.git",
    [string]$WorkspacePath = "C:\Servio\Galvao",
    [string]$XamppPath = "C:\xampp"
)

$ErrorActionPreference = "Stop"

function Write-Step {
    param([string]$Message)
    Write-Host ""
    Write-Host "==> $Message" -ForegroundColor Cyan
}

Write-Step "Atualizando codigo do sistema (sem alterar banco de dados)"

$repoPath = Join-Path $WorkspacePath "Galvao"
$sourceApp = Join-Path $repoPath "advocacia"
$htdocsPath = Join-Path $XamppPath "htdocs"
$targetApp = Join-Path $htdocsPath "advocacia"
$configBackup = $null

if (-not (Test-Path $repoPath)) {
    throw "Repositorio nao encontrado em '$repoPath'. Rode instalar_pc_novo.ps1 primeiro."
}

Push-Location $repoPath
& git pull --ff-only
Pop-Location

if (-not (Test-Path $sourceApp)) {
    throw "Pasta '$sourceApp' nao encontrada apos git pull."
}

if (-not (Test-Path $htdocsPath)) {
    throw "Pasta htdocs nao encontrada em '$htdocsPath'."
}

if (Test-Path (Join-Path $targetApp "config\config.local.php")) {
    $configBackup = Join-Path $env:TEMP "advocacia_config.local.php.bak"
    Copy-Item -Path (Join-Path $targetApp "config\config.local.php") -Destination $configBackup -Force
    Write-Host "Config local preservada."
}

if (Test-Path $targetApp) {
    Remove-Item -Path $targetApp -Recurse -Force
}

Copy-Item -Path $sourceApp -Destination $targetApp -Recurse -Force

if ($configBackup -and (Test-Path $configBackup)) {
    Copy-Item -Path $configBackup -Destination (Join-Path $targetApp "config\config.local.php") -Force
    Remove-Item -Path $configBackup -Force
}

$apacheService = Get-Service -Name "Apache2.4" -ErrorAction SilentlyContinue
if ($apacheService) {
    if ($apacheService.Status -eq "Running") {
        Restart-Service -Name "Apache2.4" -Force
    } else {
        Start-Service -Name "Apache2.4"
    }
}

$phpExe = Join-Path $XamppPath "php\php.exe"
$verifyScript = Join-Path $targetApp "scripts\verificar_instalacao.php"
if ((Test-Path $phpExe) -and (Test-Path $verifyScript)) {
    & $phpExe $verifyScript
    if ($LASTEXITCODE -ne 0) {
        throw "Verificacao falhou apos atualizacao."
    }
}

$ipv4 = (Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
    Where-Object { $_.IPAddress -notlike "127.*" -and $_.PrefixOrigin -ne "WellKnown" } |
    Select-Object -First 1 -ExpandProperty IPAddress)

Write-Host ""
Write-Host "===========================================" -ForegroundColor Green
Write-Host "ATUALIZACAO CONCLUIDA" -ForegroundColor Green
Write-Host "===========================================" -ForegroundColor Green
Write-Host "Local: http://localhost/advocacia"
if ($ipv4) {
    Write-Host "Rede : http://$ipv4/advocacia"
}
Write-Host ""
Write-Host "O banco de dados do cliente foi preservado."
