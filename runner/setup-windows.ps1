param(
    [string[]]$Runtimes = @('python', 'php', 'go', 'gcc')
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Require-Command([string]$Name, [string]$Message) {
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw $Message
    }
}

function Wait-Runner([string]$Url, [int]$Attempts = 40) {
    for ($attempt = 1; $attempt -le $Attempts; $attempt++) {
        try {
            return Invoke-RestMethod -Uri "$Url/api/v2/runtimes" -Method Get -TimeoutSec 3
        } catch {
            Start-Sleep -Seconds 2
        }
    }
    throw "The Piston API did not become ready at $Url. Open Docker Desktop and inspect: docker logs codemwana-piston"
}

function Set-EnvValue([string]$Path, [string]$Key, [string]$Value) {
    if (Test-Path $Path) {
        $content = Get-Content -Raw -Path $Path
    } else {
        $content = ''
    }
    $line = "$Key=$Value"
    $pattern = "(?m)^" + [regex]::Escape($Key) + "=.*$"
    if ([regex]::IsMatch($content, $pattern)) {
        $content = [regex]::Replace($content, $pattern, $line, 1)
    } else {
        if ($content.Length -gt 0 -and -not $content.EndsWith("`n")) { $content += "`r`n" }
        $content += "$line`r`n"
    }
    Set-Content -Path $Path -Value $content -Encoding UTF8
}

Require-Command docker 'Docker Desktop is required. Install it, use Linux containers, then run this script again.'
Require-Command git 'Git is required to obtain the official Piston CLI.'
Require-Command node 'Node.js 15 or newer is required by the official Piston CLI.'
Require-Command npm 'npm is required by the official Piston CLI.'

docker info | Out-Null

$ProjectRoot = Split-Path -Parent $PSScriptRoot
$RuntimeRoot = Join-Path $PSScriptRoot '.runtime'
$DataRoot = Join-Path $RuntimeRoot 'piston-data'
$SourceRoot = Join-Path $RuntimeRoot 'piston-source'
$RunnerUrl = 'http://127.0.0.1:2000'
$ContainerName = 'codemwana-piston'

New-Item -ItemType Directory -Force -Path $RuntimeRoot, $DataRoot | Out-Null

$containerExists = docker ps -a --format '{{.Names}}' | Where-Object { $_ -eq $ContainerName }
if ($containerExists) {
    docker start $ContainerName | Out-Null
} else {
    docker run --privileged --restart unless-stopped --mount "type=bind,source=$DataRoot,target=/piston" -dit -p 127.0.0.1:2000:2000 --name $ContainerName ghcr.io/engineer-man/piston | Out-Null
}

Wait-Runner $RunnerUrl | Out-Null

if (-not (Test-Path (Join-Path $SourceRoot '.git'))) {
    if (Test-Path $SourceRoot) { Remove-Item -Recurse -Force $SourceRoot }
    git clone --depth 1 https://github.com/engineer-man/piston.git $SourceRoot
} else {
    git -C $SourceRoot pull --ff-only
}

Push-Location (Join-Path $SourceRoot 'cli')
try {
    npm install --no-audit --no-fund
    foreach ($runtime in $Runtimes) {
        Write-Host "Installing Piston package: $runtime" -ForegroundColor Cyan
        node index.js -u $RunnerUrl ppman install $runtime
    }
} finally {
    Pop-Location
}

$installed = @(Wait-Runner $RunnerUrl)
$availableNames = @()
foreach ($runtime in $installed) {
    $availableNames += [string]$runtime.language
    if ($runtime.aliases) { $availableNames += @($runtime.aliases | ForEach-Object { [string]$_ }) }
}

$expected = @('python', 'php', 'go', 'c', 'c++')
$missing = @($expected | Where-Object { $_ -notin $availableNames })
if ($missing.Count -gt 0) {
    throw "Runner started, but these language aliases were not detected: $($missing -join ', '). Run the script again or inspect the Piston CLI output."
}

$envPath = Join-Path $ProjectRoot '.env'
if (-not (Test-Path $envPath)) {
    $examplePath = Join-Path $ProjectRoot '.env.example'
    if (-not (Test-Path $examplePath)) { throw '.env.example was not found in the CodeMwana project.' }
    Copy-Item $examplePath $envPath
}
Set-EnvValue $envPath 'CODE_RUNNER_URL' $RunnerUrl
Set-EnvValue $envPath 'CODE_RUNNER_TIMEOUT' '15'

$testPayload = @{
    language = 'python'
    version = '*'
    files = @(@{ name = 'main.py'; content = 'print("CodeMwana sandbox ready")' })
    stdin = ''
    run_timeout = 3000
} | ConvertTo-Json -Depth 6

$testResult = Invoke-RestMethod -Uri "$RunnerUrl/api/v2/execute" -Method Post -ContentType 'application/json' -Body $testPayload -TimeoutSec 15
$testOutput = ''
if ($testResult.run -and $testResult.run.stdout) { $testOutput = [string]$testResult.run.stdout }
if ($testOutput -notmatch 'CodeMwana sandbox ready') {
    throw 'The runner responded, but the verification program did not produce the expected output.'
}

Write-Host ''
Write-Host 'CodeMwana local sandbox is ready.' -ForegroundColor Green
Write-Host "Runner URL: $RunnerUrl"
Write-Host 'Configured runtimes: Python, PHP, Go, C and C++'
Write-Host "Updated: $envPath"
Write-Host 'Open CodeMwana system-status.php, then run a project in Code Lab.'
