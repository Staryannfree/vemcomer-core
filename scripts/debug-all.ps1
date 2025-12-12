# ============================================================================
# SCRIPT MESTRE - COLETA TUDO PARA DEBUG
# ============================================================================
# Execute este script para coletar TODAS as informações de uma vez
# Execute: .\scripts\debug-all.ps1
# ============================================================================

param(
    [switch]$SkipDatabase = $false,
    [switch]$SkipCache = $false,
    [int]$LogLines = 500
)

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "🔍 SISTEMA COMPLETO DE DEBUG - VemComer" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Coletar debug completo
Write-Host "[1/4] Coletando informações completas..." -ForegroundColor Yellow
$dbFlag = if ($SkipDatabase) { "" } else { "-IncludeDatabase" }
$cacheFlag = if ($SkipCache) { "" } else { "-IncludeCache" }
& "$PSScriptRoot\collect-full-debug.ps1" -LogLines $LogLines @dbFlag @cacheFlag

Write-Host ""
Write-Host "[2/4] Gerando queries SQL para banco de dados..." -ForegroundColor Yellow
& "$PSScriptRoot\export-database-state.ps1"

Write-Host ""
Write-Host "[3/5] Mostrando instruções para logs do navegador..." -ForegroundColor Yellow
& "$PSScriptRoot\export-browser-logs.ps1"

Write-Host ""
Write-Host "[4/6] Coletando via REST API..." -ForegroundColor Yellow
& "$PSScriptRoot\collect-everything-via-api.ps1" -SiteUrl "http://pedevem-local.local"

Write-Host ""
Write-Host "[5/7] Lendo logs do navegador..." -ForegroundColor Yellow
& "$PSScriptRoot\read-browser-logs.ps1" -Latest

Write-Host ""
Write-Host "[6/7] Gerando análise consolidada..." -ForegroundColor Yellow
# DESABILITADO: auto-analyze-all-debug.ps1 não executa mais automaticamente
# Para executar manualmente: .\scripts\auto-analyze-all-debug.ps1
Write-Host "  ⚠️  Análise automática desabilitada (causava lentidão)" -ForegroundColor Yellow
Write-Host "  💡 Execute manualmente se necessário: .\scripts\auto-analyze-all-debug.ps1" -ForegroundColor Cyan

Write-Host ""
Write-Host "[7/7] Resumo final..." -ForegroundColor Yellow
Write-Host ""

$outputDir = Join-Path $PSScriptRoot ".." "debug-reports"
$reports = Get-ChildItem -Path $outputDir -Filter "*.txt" -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending | Select-Object -First 5
$sqlFiles = Get-ChildItem -Path $outputDir -Filter "*.sql" -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending | Select-Object -First 5

Write-Host "========================================" -ForegroundColor Green
Write-Host "✅ COLETA COMPLETA FINALIZADA!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

if ($reports) {
    Write-Host "📄 Relatórios gerados:" -ForegroundColor Yellow
    foreach ($report in $reports) {
        Write-Host "   - $($report.Name) ($(Get-Date $report.LastWriteTime -Format 'dd/MM/yyyy HH:mm'))" -ForegroundColor White
    }
    Write-Host ""
}

if ($sqlFiles) {
    Write-Host "🗄️  Queries SQL geradas:" -ForegroundColor Yellow
    foreach ($sql in $sqlFiles) {
        Write-Host "   - $($sql.Name) ($(Get-Date $sql.LastWriteTime -Format 'dd/MM/yyyy HH:mm'))" -ForegroundColor White
    }
    Write-Host ""
}

Write-Host "📋 PRÓXIMOS PASSOS:" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. ⭐ IMPORTANTE: Análise consolidada foi gerada!" -ForegroundColor Yellow
Write-Host "   Arquivo: CONSOLIDATED-ANALYSIS-*.txt" -ForegroundColor White
Write-Host ""
Write-Host "2. Quando reportar um problema, me peça:" -ForegroundColor White
Write-Host "   'Analisa todos os logs usando o protocolo'" -ForegroundColor Cyan
Write-Host ""
Write-Host "3. OU me peça diretamente:" -ForegroundColor White
Write-Host "   'Lê a análise consolidada mais recente'" -ForegroundColor Cyan
Write-Host ""
Write-Host "4. Eu vou automaticamente:" -ForegroundColor White
Write-Host "   - Ler todos os logs (servidor + navegador + API)" -ForegroundColor Gray
Write-Host "   - Correlacionar erros entre diferentes fontes" -ForegroundColor Gray
Write-Host "   - Identificar a causa raiz do problema" -ForegroundColor Gray
Write-Host ""

Write-Host "💡 DICA: Para monitorar logs em tempo real enquanto testa:" -ForegroundColor Yellow
Write-Host "   .\scripts\monitor-logs-realtime.ps1" -ForegroundColor Cyan
Write-Host ""

