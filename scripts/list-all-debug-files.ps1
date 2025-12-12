# ============================================================================
# LISTAR TODOS OS ARQUIVOS DE DEBUG DISPONÍVEIS
# ============================================================================
# Lista todos os arquivos que devem ser analisados
# Execute: .\scripts\list-all-debug-files.ps1
# ============================================================================

$ErrorActionPreference = "Continue"
$pluginPath = "C:\Users\Adm-Sup\Documents\Github\vemcomer-core"
$wpPath = "C:\Users\Adm-Sup\Local Sites\pedevem-local\app\public"
$wpContentPath = Join-Path $wpPath "wp-content"
$outputDir = Join-Path $pluginPath "debug-reports"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "ARQUIVOS DE DEBUG DISPONÍVEIS" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$files = @()

# 1. Análise Consolidada (MAIS IMPORTANTE)
Write-Host "1. ANÁLISE CONSOLIDADA (⭐ MAIS IMPORTANTE):" -ForegroundColor Yellow
$consolidated = Get-ChildItem -Path $outputDir -Filter "CONSOLIDATED-ANALYSIS-*.txt" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
if ($consolidated) {
    Write-Host "   ✅ $($consolidated.Name)" -ForegroundColor Green
    Write-Host "      Modificado: $(Get-Date $consolidated.LastWriteTime -Format 'dd/MM/yyyy HH:mm:ss')" -ForegroundColor Gray
    $files += @{
        Type = "Consolidated Analysis"
        Path = $consolidated.FullName
        Priority = "HIGHEST"
    }
} else {
    Write-Host "   ❌ Nenhum arquivo encontrado" -ForegroundColor Red
    Write-Host "      Execute: .\scripts\auto-analyze-all-debug.ps1" -ForegroundColor Yellow
}
Write-Host ""

# 2. Logs do Servidor
Write-Host "2. LOGS DO SERVIDOR:" -ForegroundColor Yellow
$wpDebug = Join-Path $wpContentPath "debug.log"
$vcDebug = Join-Path $wpContentPath "uploads\vemcomer-debug.log"

if (Test-Path $wpDebug) {
    $wpInfo = Get-Item $wpDebug
    Write-Host "   ✅ debug.log" -ForegroundColor Green
    Write-Host "      Modificado: $(Get-Date $wpInfo.LastWriteTime -Format 'dd/MM/yyyy HH:mm:ss')" -ForegroundColor Gray
    $files += @{ Type = "Server Log"; Path = $wpDebug; Priority = "HIGH" }
} else {
    Write-Host "   ❌ debug.log não encontrado" -ForegroundColor Red
}

if (Test-Path $vcDebug) {
    $vcInfo = Get-Item $vcDebug
    Write-Host "   ✅ vemcomer-debug.log" -ForegroundColor Green
    Write-Host "      Modificado: $(Get-Date $vcInfo.LastWriteTime -Format 'dd/MM/yyyy HH:mm:ss')" -ForegroundColor Gray
    $files += @{ Type = "VemComer Log"; Path = $vcDebug; Priority = "HIGH" }
} else {
    Write-Host "   ❌ vemcomer-debug.log não encontrado" -ForegroundColor Red
}
Write-Host ""

# 3. Logs do Navegador
Write-Host "3. LOGS DO NAVEGADOR:" -ForegroundColor Yellow
$browserDebugPath = Join-Path $wpContentPath "uploads\vemcomer-browser-debug"
if (Test-Path $browserDebugPath) {
    $browserLogs = Get-ChildItem -Path $browserDebugPath -Filter "browser-logs-*.json" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    $networkLogs = Get-ChildItem -Path $browserDebugPath -Filter "network-requests-*.json" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    
    if ($browserLogs) {
        Write-Host "   ✅ $($browserLogs.Name)" -ForegroundColor Green
        Write-Host "      Modificado: $(Get-Date $browserLogs.LastWriteTime -Format 'dd/MM/yyyy HH:mm:ss')" -ForegroundColor Gray
        $files += @{ Type = "Browser Logs"; Path = $browserLogs.FullName; Priority = "HIGH" }
    }
    
    if ($networkLogs) {
        Write-Host "   ✅ $($networkLogs.Name)" -ForegroundColor Green
        Write-Host "      Modificado: $(Get-Date $networkLogs.LastWriteTime -Format 'dd/MM/yyyy HH:mm:ss')" -ForegroundColor Gray
        $files += @{ Type = "Network Requests"; Path = $networkLogs.FullName; Priority = "HIGH" }
    }
} else {
    Write-Host "   ❌ Diretório não encontrado" -ForegroundColor Red
    Write-Host "      (Browser debug capture pode não estar ativo)" -ForegroundColor Yellow
}
Write-Host ""

# 4. Estado via API
Write-Host "4. ESTADO DO SISTEMA (REST API):" -ForegroundColor Yellow
$apiFiles = Get-ChildItem -Path $outputDir -Filter "api-*.json" | Sort-Object LastWriteTime -Descending | Select-Object -First 5
if ($apiFiles) {
    foreach ($file in $apiFiles) {
        Write-Host "   ✅ $($file.Name)" -ForegroundColor Green
        Write-Host "      Modificado: $(Get-Date $file.LastWriteTime -Format 'dd/MM/yyyy HH:mm:ss')" -ForegroundColor Gray
        $files += @{ Type = "API State"; Path = $file.FullName; Priority = "MEDIUM" }
    }
} else {
    Write-Host "   ❌ Nenhum arquivo encontrado" -ForegroundColor Red
    Write-Host "      Execute: .\scripts\collect-everything-via-api.ps1" -ForegroundColor Yellow
}
Write-Host ""

# Resumo
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "RESUMO" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Total de arquivos disponíveis: $($files.Count)" -ForegroundColor White
Write-Host ""

if ($files.Count -gt 0) {
    Write-Host "📋 PROTOCOLO PARA O ASSISTENTE:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Quando você reportar um problema, diga:" -ForegroundColor White
    Write-Host "  'Analisa todos os logs usando o protocolo'" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Ou execute primeiro:" -ForegroundColor White
    Write-Host "  .\scripts\auto-analyze-all-debug.ps1" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "E depois me peça:" -ForegroundColor White
    Write-Host "  'Lê a análise consolidada mais recente'" -ForegroundColor Cyan
    Write-Host ""
}

Write-Host "✅ Garantia: Eu vou SEMPRE analisar todos esses arquivos" -ForegroundColor Green
Write-Host "   antes de responder qualquer problema reportado!" -ForegroundColor Green
Write-Host ""

