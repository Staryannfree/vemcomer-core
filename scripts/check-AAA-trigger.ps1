# ============================================================================
# VERIFICAR SE HÁ TRIGGER AAA ATIVO - DESABILITADO PERMANENTEMENTE
# ============================================================================
# ESTE SCRIPT FOI DESABILITADO PORQUE ESTAVA CAUSANDO LENTIDÃO EXTREMA
# Ele interceptava TODOS os comandos e executava auto-analyze automaticamente
# ============================================================================
# 
# NÃO EXECUTAR AUTOMATICAMENTE - APENAS MANUALMENTE QUANDO NECESSÁRIO
# ============================================================================

# BLOQUEIO PERMANENTE - NÃO EXECUTAR AUTOMATICAMENTE
$blockFile = Join-Path (Split-Path $PSScriptRoot -Parent) "debug-reports" "HOOKS-DISABLED-PERMANENTLY.txt"
if (-not (Test-Path $blockFile)) {
    Write-Host "⚠️  Sistema de trigger AAA foi DESABILITADO permanentemente" -ForegroundColor Yellow
    Write-Host "   Para executar análise manualmente, use: .\scripts\auto-analyze-all-debug.ps1" -ForegroundColor White
    exit
}

# Se o arquivo de bloqueio existir, não fazer nada
Write-Host "✅ Sistema de trigger AAA está desabilitado (bloqueio ativo)" -ForegroundColor Green
exit

