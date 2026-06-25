param(
    [string]$RepoUrl = "https://github.com/Ricardo-Santos-Tostas/Galvao.git",
    [string]$WorkspacePath = "C:\Servio\Galvao",
    [string]$XamppPath = "C:\xampp",
    [switch]$TryInstallXampp = $true
)

$ErrorActionPreference = "Stop"

function Write-Step {
    param([string]$Message)
    Write-Host ""
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Assert-Admin {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
        throw "Execute este script como Administrador (PowerShell -> Executar como administrador)."
    }
}

function Ensure-Command {
    param(
        [string]$CommandName,
        [string]$WingetId
    )

    if (Get-Command $CommandName -ErrorAction SilentlyContinue) {
        return
    }

    if (-not (Get-Command winget -ErrorAction SilentlyContinue)) {
        throw "Comando '$CommandName' nao encontrado e winget nao esta disponivel para instalar automaticamente."
    }

    Write-Step "Instalando dependência: $CommandName"
    & winget install --id $WingetId --accept-source-agreements --accept-package-agreements --silent

    if (-not (Get-Command $CommandName -ErrorAction SilentlyContinue)) {
        throw "Falha ao instalar '$CommandName'. Instale manualmente e rode o script novamente."
    }
}

function Ensure-Xampp {
    $phpExe = Join-Path $XamppPath "php\php.exe"
    $mysqlExe = Join-Path $XamppPath "mysql\bin\mysql.exe"
    $httpdExe = Join-Path $XamppPath "apache\bin\httpd.exe"
    $mysqldExe = Join-Path $XamppPath "mysql\bin\mysqld.exe"

    if ((Test-Path $phpExe) -and (Test-Path $mysqlExe) -and (Test-Path $httpdExe) -and (Test-Path $mysqldExe)) {
        return @{
            PhpExe    = $phpExe
            MysqlExe  = $mysqlExe
            HttpdExe  = $httpdExe
            MysqldExe = $mysqldExe
        }
    }

    if (-not $TryInstallXampp) {
        throw "XAMPP nao encontrado em '$XamppPath'. Instale o XAMPP e rode novamente."
    }

    if (-not (Get-Command winget -ErrorAction SilentlyContinue)) {
        throw "XAMPP nao encontrado e winget indisponivel. Instale o XAMPP manualmente em $XamppPath."
    }

    Write-Step "Tentando instalar XAMPP automaticamente via winget"
    & winget install --id ApacheFriends.Xampp --accept-source-agreements --accept-package-agreements --silent

    if (-not ((Test-Path $phpExe) -and (Test-Path $mysqlExe) -and (Test-Path $httpdExe) -and (Test-Path $mysqldExe))) {
        throw "Nao foi possivel instalar/detectar o XAMPP automaticamente. Instale manualmente em '$XamppPath'."
    }

    return @{
        PhpExe    = $phpExe
        MysqlExe  = $mysqlExe
        HttpdExe  = $httpdExe
        MysqldExe = $mysqldExe
    }
}

function Ensure-ServiceInstalled {
    param(
        [string]$ServiceName,
        [scriptblock]$InstallAction
    )

    $service = Get-Service -Name $ServiceName -ErrorAction SilentlyContinue
    if (-not $service) {
        & $InstallAction
    }

    $service = Get-Service -Name $ServiceName -ErrorAction SilentlyContinue
    if (-not $service) {
        throw "Servico '$ServiceName' nao foi encontrado apos tentativa de instalacao."
    }

    Set-Service -Name $ServiceName -StartupType Automatic
    if ($service.Status -ne "Running") {
        Start-Service -Name $ServiceName
    }
}

function Ensure-FirewallPort {
    param(
        [string]$RuleName,
        [int]$Port
    )

    $existing = Get-NetFirewallRule -DisplayName $RuleName -ErrorAction SilentlyContinue
    if (-not $existing) {
        New-NetFirewallRule -DisplayName $RuleName -Direction Inbound -Protocol TCP -LocalPort $Port -Action Allow | Out-Null
    }
}

Assert-Admin
Write-Step "Validando dependencias"
Ensure-Command -CommandName "git" -WingetId "Git.Git"
$xampp = Ensure-Xampp

Write-Step "Baixando/atualizando projeto do GitHub"
if (-not (Test-Path $WorkspacePath)) {
    New-Item -Path $WorkspacePath -ItemType Directory -Force | Out-Null
}

$repoPath = Join-Path $WorkspacePath "Galvao"
if (-not (Test-Path (Join-Path $repoPath ".git"))) {
    & git clone $RepoUrl $repoPath
} else {
    Push-Location $repoPath
    & git pull --ff-only
    Pop-Location
}

Write-Step "Copiando sistema para htdocs do XAMPP"
$sourceApp = Join-Path $repoPath "advocacia"
if (-not (Test-Path $sourceApp)) {
    throw "Pasta '$sourceApp' nao encontrada no repositorio."
}

$htdocsPath = Join-Path $XamppPath "htdocs"
$targetApp = Join-Path $htdocsPath "advocacia"

if (-not (Test-Path $htdocsPath)) {
    throw "Pasta htdocs nao encontrada em '$htdocsPath'."
}

if (Test-Path $targetApp) {
    Remove-Item -Path $targetApp -Recurse -Force
}
Copy-Item -Path $sourceApp -Destination $targetApp -Recurse -Force

Write-Step "Instalando/configurando servicos Apache e MySQL"
Ensure-ServiceInstalled -ServiceName "Apache2.4" -InstallAction {
    & $xampp.HttpdExe -k install -n Apache2.4
}

Ensure-ServiceInstalled -ServiceName "mysql" -InstallAction {
    $myIni = Join-Path $XamppPath "mysql\bin\my.ini"
    & $xampp.MysqldExe --install mysql "--defaults-file=$myIni"
}

Write-Step "Liberando portas no Firewall (80, 443 e 8080)"
Ensure-FirewallPort -RuleName "Advocacia Apache 80" -Port 80
Ensure-FirewallPort -RuleName "Advocacia Apache 443" -Port 443
Ensure-FirewallPort -RuleName "Advocacia PHP 8080" -Port 8080

Write-Step "Preparando configuracao local"
$configLocal = Join-Path $targetApp "config\config.local.php"
$configExample = Join-Path $targetApp "config\config.local.php.example"
if (-not (Test-Path $configLocal) -and (Test-Path $configExample)) {
    Copy-Item -Path $configExample -Destination $configLocal -Force
}

Write-Step "Importando banco de dados para MySQL"
$backupFile = Join-Path $targetApp "sql\backup_advocacia.sql"
if (-not (Test-Path $backupFile)) {
    throw "Arquivo de backup nao encontrado: $backupFile"
}

cmd /c "`"$($xampp.MysqlExe)`" -u root < `"$backupFile`""
if ($LASTEXITCODE -ne 0) {
    throw "Falha ao importar o banco de dados no MySQL."
}

Write-Step "Verificando sistema"
$verifyScript = Join-Path $targetApp "scripts\verificar_instalacao.php"
& $xampp.PhpExe $verifyScript
if ($LASTEXITCODE -ne 0) {
    throw "Script de verificacao retornou erro."
}

$ipv4 = (Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
    Where-Object { $_.IPAddress -notlike "127.*" -and $_.PrefixOrigin -ne "WellKnown" } |
    Select-Object -First 1 -ExpandProperty IPAddress)

Write-Host ""
Write-Host "===========================================" -ForegroundColor Green
Write-Host "INSTALACAO CONCLUIDA COM SUCESSO" -ForegroundColor Green
Write-Host "===========================================" -ForegroundColor Green
Write-Host "Local: http://localhost/advocacia"
if ($ipv4) {
    Write-Host "Rede : http://$ipv4/advocacia"
}
Write-Host ""
Write-Host "Apache e MySQL estao como servico Automatico."
Write-Host "Apos reiniciar o PC, o sistema sobe sozinho."
