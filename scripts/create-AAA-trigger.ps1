# ============================================================================
# CRIAR TRIGGER AAA - DESABILITADO PERMANENTEMENTE
# ============================================================================
# ESTE SCRIPT FOI DESABILITADO PORQUE ESTAVA CAUSANDO LENTIDÃO EXTREMA
# NÃO USE MAIS - O sistema de trigger automático foi removido
# ============================================================================

$ErrorActionPreference = "Continue"
$pluginPath = "C:\Users\Adm-Sup\Documents\Github\vemcomer-core"
$outputDir = Join-Path $pluginPath "debug-reports"
$triggerFile = Join-Path $outputDir "AAA-TRIGGER-ACTIVE.txt"

if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
}

$timestamp = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
$triggerContent = @"
TRIGGER AAA ATIVO
==================

Criado em: $timestamp

O assistente DEVE:
1. Executar: .\scripts\auto-analyze-all-debug.ps1
2. Ler: debug-reports/CONSOLIDATED-ANALYSIS-*.txt (mais recente)
3. Analisar TODOS os logs antes de responder

Este arquivo foi criado porque você digitou "AAA" na conversa.

Para remover o trigger, o assistente deve executar:
.\scripts\check-AAA-trigger.ps1
"@

$triggerContent | Out-File -FilePath $triggerFile -Encoding UTF8

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "TRIGGER AAA CRIADO!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Arquivo: $triggerFile" -ForegroundColor Yellow
Write-Host ""
Write-Host "O assistente DEVE agora:" -ForegroundColor Yellow
Write-Host "  1. Executar análise automática" -ForegroundColor White
Write-Host "  2. Ler todos os logs" -ForegroundColor White
Write-Host "  3. Analisar antes de responder" -ForegroundColor White
Write-Host ""
Write-Host "Para verificar se o assistente seguiu o protocolo," -ForegroundColor Cyan
Write-Host "peça para ele executar: .\scripts\check-AAA-trigger.ps1" -ForegroundColor Cyan
Write-Host ""

