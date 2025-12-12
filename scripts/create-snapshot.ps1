# ============================================================================
# CRIAR SNAPSHOT COMPLETO DO SISTEMA
# ============================================================================
# Cria um snapshot de ABSOLUTAMENTE TUDO em um único momento
# Execute: .\scripts\create-snapshot.ps1
# ============================================================================

$ErrorActionPreference = "Continue"
$pluginPath = "C:\Users\Adm-Sup\Documents\Github\vemcomer-core"
$outputDir = Join-Path $pluginPath "debug-reports\snapshots"
$timestamp = Get-Date -Format 'yyyy-MM-dd-HHmmss'

if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "CRIANDO SNAPSHOT COMPLETO" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Coletar via scripts PowerShell
Write-Host "[1/3] Executando scripts de coleta..." -ForegroundColor Yellow
& "$pluginPath\scripts\collect-full-debug.ps1" -IncludeDatabase -IncludeCache -LogLines 1000

# 2. Coletar via REST API
Write-Host ""
Write-Host "[2/3] Coletando via REST API..." -ForegroundColor Yellow
& "$pluginPath\scripts\collect-everything-via-api.ps1" -SiteUrl "http://pedevem-local.local"

# 3. Criar índice consolidado
Write-Host ""
Write-Host "[3/3] Criando índice consolidado..." -ForegroundColor Yellow

$indexFile = Join-Path $outputDir "snapshot-index-$timestamp.txt"
$index = @()
$index += "========================================"
$index += "SNAPSHOT COMPLETO - $timestamp"
$index += "========================================"
$index += ""
$index += "Arquivos gerados:"
$index += ""

# Listar todos os arquivos gerados
$reportsDir = Join-Path $pluginPath "debug-reports"
$files = Get-ChildItem -Path $reportsDir -File | Where-Object { $_.LastWriteTime -gt (Get-Date).AddMinutes(-5) } | Sort-Object LastWriteTime -Descending

foreach ($file in $files) {
    $index += "  - $($file.Name) ($(Get-Date $file.LastWriteTime -Format 'HH:mm:ss'))"
}

$index | Out-File -FilePath $indexFile -Encoding UTF8

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "✅ SNAPSHOT COMPLETO CRIADO!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Índice: $indexFile" -ForegroundColor Cyan
Write-Host ""

