# ============================================================================
# ANÁLISE AUTOMÁTICA DE TODOS OS LOGS DE DEBUG
# ============================================================================
# Este script coleta e consolida TODOS os logs disponíveis
# Execute: .\scripts\auto-analyze-all-debug.ps1
# ============================================================================
# 
# PROTOCOLO: Sempre execute este script quando o usuário reportar um problema
# ============================================================================

param(
    [switch]$IncludeBrowserLogs = $true,
    [switch]$IncludeServerLogs = $false,  # Desabilitado por padrão - muito lento
    [switch]$IncludeAPILogs = $true,
    [switch]$IncludeDatabase = $false
)

$ErrorActionPreference = "Continue"
$pluginPath = "C:\Users\Adm-Sup\Documents\Github\vemcomer-core"
$wpPath = "C:\Users\Adm-Sup\Local Sites\pedevem-local\app\public"
$wpContentPath = Join-Path $wpPath "wp-content"
$outputDir = Join-Path $pluginPath "debug-reports"
$timestamp = Get-Date -Format 'yyyy-MM-dd-HHmmss'
$consolidatedFile = Join-Path $outputDir "CONSOLIDATED-ANALYSIS-$timestamp.txt"

if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "ANÁLISE AUTOMÁTICA COMPLETA" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Este script coleta TODOS os logs disponíveis para análise" -ForegroundColor Yellow
Write-Host ""

$analysis = @()
$analysis += "========================================"
$analysis += "ANÁLISE CONSOLIDADA DE DEBUG"
$analysis += "Gerado em: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
$analysis += "========================================"
$analysis += ""

# ============================================================================
# 1. LOGS DO SERVIDOR (PHP) - DESABILITADO (muito lento)
# ============================================================================
# Esta seção foi desabilitada porque demora muito tempo para executar
# Para habilitar, passe -IncludeServerLogs ao executar o script
if ($false -and $IncludeServerLogs) {
    Write-Host "[1/4] Analisando logs do servidor..." -ForegroundColor Yellow
    
    $analysis += "========================================"
    $analysis += "1. LOGS DO SERVIDOR (PHP)"
    $analysis += "========================================"
    $analysis += ""
    
    # WordPress debug.log
    $wpDebugLog = Join-Path $wpContentPath "debug.log"
    if (Test-Path $wpDebugLog) {
        $wpLogs = Get-Content $wpDebugLog -Tail 100 -ErrorAction SilentlyContinue
        $analysis += "WORDPRESS DEBUG.LOG (últimas 100 linhas):"
        $analysis += "----------------------------------------"
        $analysis += $wpLogs
        $analysis += ""
    } else {
        $analysis += "AVISO: debug.log nao encontrado"
        $analysis += ""
    }
    
    # VemComer debug.log
    $vcDebugLog = Join-Path $wpContentPath "uploads\vemcomer-debug.log"
    if (Test-Path $vcDebugLog) {
        $vcLogs = Get-Content $vcDebugLog -Tail 100 -ErrorAction SilentlyContinue
        $analysis += "VEMCOMER DEBUG.LOG (últimas 100 linhas):"
        $analysis += "----------------------------------------"
        $analysis += $vcLogs
        $analysis += ""
    } else {
        $analysis += "AVISO: vemcomer-debug.log nao encontrado"
        $analysis += ""
    }
}

# ============================================================================
# 2. LOGS DO NAVEGADOR (JavaScript)
# ============================================================================
if ($IncludeBrowserLogs) {
    Write-Host "[1/3] Analisando logs do navegador..." -ForegroundColor Yellow
    
    $analysis += "========================================"
    $analysis += "2. LOGS DO NAVEGADOR (JavaScript)"
    $analysis += "========================================"
    $analysis += ""
    
    $browserDebugPath = Join-Path $wpContentPath "uploads\vemcomer-browser-debug"
    if (Test-Path $browserDebugPath) {
        # Encontrar arquivo mais recente
        $latestLogFile = Get-ChildItem -Path $browserDebugPath -Filter "browser-logs-*.json" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
        $latestNetworkFile = Get-ChildItem -Path $browserDebugPath -Filter "network-requests-*.json" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
        
        if ($latestLogFile) {
            $browserLogs = Get-Content $latestLogFile.FullName -Raw | ConvertFrom-Json
            $errors = $browserLogs | Where-Object { 
                $_.type -like '*error*' -or 
                ($_.data -and $_.data.level -eq 'error')
            }
            $warnings = $browserLogs | Where-Object { 
                $_.type -like '*warn*' -or 
                ($_.data -and $_.data.level -eq 'warn')
            }
            
            $analysis += "Arquivo: $($latestLogFile.Name)"
            $analysis += "Total de logs: $($browserLogs.Count)"
            $analysis += "Erros: $($errors.Count)"
            $analysis += "Avisos: $($warnings.Count)"
            $analysis += ""
            
            if ($errors.Count -gt 0) {
                $analysis += "ERROS ENCONTRADOS:"
                $analysis += "----------------------------------------"
                foreach ($error in $errors | Select-Object -First 10) {
                    $analysis += "[$($error.timestamp)] $($error.type)"
                    if ($error.data.message) {
                        $analysis += "  Mensagem: $($error.data.message)"
                    }
                    if ($error.data.filename) {
                        $analysis += "  Arquivo: $($error.data.filename):$($error.data.lineno)"
                    }
                    if ($error.data.stack) {
                        $analysis += "  Stack: $($error.data.stack -replace "`n", " | ")"
                    }
                    $analysis += ""
                }
            }
        } else {
            $analysis += "AVISO: Nenhum log do navegador encontrado"
            $analysis += ""
        }
        
        if ($latestNetworkFile) {
            $networkRequests = Get-Content $latestNetworkFile.FullName -Raw | ConvertFrom-Json
            $failedRequests = $networkRequests | Where-Object { $_.status -ge 400 }
            
            $analysis += "REQUISIÇÕES DE REDE:"
            $analysis += "----------------------------------------"
            $analysis += "Total: $($networkRequests.Count)"
            $analysis += "Falhas (>=400): $($failedRequests.Count)"
            $analysis += ""
            
            if ($failedRequests.Count -gt 0) {
                $analysis += "REQUISIÇÕES COM ERRO:"
                foreach ($req in $failedRequests | Select-Object -First 10) {
                    $analysis += "[$($req.timestamp)] $($req.method) $($req.url)"
                    $analysis += "  Status: $($req.status) $($req.statusText)"
                    $analysis += ""
                }
            }
        }
    } else {
        $analysis += "AVISO: Diretorio de logs do navegador nao encontrado"
        $analysis += "   (O script browser-debug-capture.js pode não estar ativo)"
        $analysis += ""
    }
}

# ============================================================================
# 3. ESTADO VIA REST API
# ============================================================================
if ($IncludeAPILogs) {
    Write-Host "[2/3] Analisando estado via REST API..." -ForegroundColor Yellow
    
    $analysis += "========================================"
    $analysis += "3. ESTADO DO SISTEMA (REST API)"
    $analysis += "========================================"
    $analysis += ""
    
    $apiStateFile = Get-ChildItem -Path $outputDir -Filter "api-full-state-*.json" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    
    if ($apiStateFile) {
        $apiState = Get-Content $apiStateFile.FullName -Raw | ConvertFrom-Json
        
        $analysis += "Arquivo: $($apiStateFile.Name)"
        $analysis += "Timestamp: $($apiState.timestamp)"
        $analysis += ""
        
        if ($apiState.wordpress) {
            $analysis += "WordPress:"
            $analysis += "  Versão: $($apiState.wordpress.version)"
            $analysis += "  Is Admin: $($apiState.wordpress.is_admin)"
            $analysis += "  Is REST: $($apiState.wordpress.is_rest)"
            $analysis += ""
        }
        
        if ($apiState.current_user) {
            $analysis += "Usuário Atual:"
            $analysis += "  Logado: $($apiState.current_user.logged_in)"
            if ($apiState.current_user.logged_in) {
                $analysis += "  ID: $($apiState.current_user.ID)"
                $analysis += "  Login: $($apiState.current_user.login)"
                $analysis += "  Roles: $($apiState.current_user.roles -join ', ')"
                $analysis += "  Restaurant ID: $($apiState.current_user.restaurant_id)"
            }
            $analysis += ""
        }
        
        if ($apiState.restaurant) {
            $analysis += "Restaurante:"
            $analysis += "  Encontrado: $($apiState.restaurant.found)"
            if ($apiState.restaurant.found) {
                $analysis += "  ID: $($apiState.restaurant.ID)"
                $analysis += "  Título: $($apiState.restaurant.title)"
                $analysis += "  Status: $($apiState.restaurant.status)"
            }
            $analysis += ""
        }
        
        if ($apiState.performance) {
            $analysis += "Performance:"
            $analysis += "  Queries: $($apiState.performance.queries_count)"
            $analysis += "  Memória: $($apiState.performance.memory_usage_mb) MB"
            $analysis += "  Pico: $($apiState.performance.peak_memory_mb) MB"
            $analysis += ""
        }
    } else {
        $analysis += "AVISO: Nenhum estado da API encontrado"
        $analysis += "   Execute: .\scripts\collect-everything-via-api.ps1"
        $analysis += ""
    }
}

# ============================================================================
# 4. RESUMO E RECOMENDAÇÕES
# ============================================================================
Write-Host "[3/3] Gerando resumo..." -ForegroundColor Yellow

$analysis += "========================================"
$analysis += "4. RESUMO E RECOMENDAÇÕES"
$analysis += "========================================"
$analysis += ""

# Contar erros
$totalErrors = 0
$totalWarnings = 0

if ($IncludeBrowserLogs) {
    $browserDebugPath = Join-Path $wpContentPath "uploads\vemcomer-browser-debug"
    if (Test-Path $browserDebugPath) {
        $latestLogFile = Get-ChildItem -Path $browserDebugPath -Filter "browser-logs-*.json" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
        if ($latestLogFile) {
            $browserLogs = Get-Content $latestLogFile.FullName -Raw | ConvertFrom-Json
            $totalErrors += ($browserLogs | Where-Object { $_.type -like '*error*' -or ($_.data -and $_.data.level -eq 'error') }).Count
            $totalWarnings += ($browserLogs | Where-Object { $_.type -like '*warn*' -or ($_.data -and $_.data.level -eq 'warn') }).Count
        }
    }
}

$analysis += "Estatísticas:"
$analysis += "  - Erros encontrados: $totalErrors"
$analysis += "  - Avisos encontrados: $totalWarnings"
$analysis += ""

if ($totalErrors -gt 0) {
    $analysis += "ATENCAO: Erros foram encontrados!"
    $analysis += "   Revise a seção de logs do navegador acima."
    $analysis += ""
}

$analysis += "Próximos passos recomendados:"
$analysis += "  1. Revise os erros listados acima"
$analysis += "  2. Verifique requisições de rede com falha"
$analysis += "  3. Compartilhe este arquivo com o assistente"
$analysis += ""

# Salvar análise consolidada
$analysis | Out-File -FilePath $consolidatedFile -Encoding UTF8

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "✅ ANÁLISE CONSOLIDADA GERADA!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Arquivo: $consolidatedFile" -ForegroundColor Cyan
Write-Host ""
Write-Host "Este arquivo contem:" -ForegroundColor Yellow
Write-Host "   - Logs do servidor (PHP)" -ForegroundColor White
Write-Host "   - Logs do navegador (JavaScript)" -ForegroundColor White
Write-Host "   - Estado do sistema (REST API)" -ForegroundColor White
Write-Host "   - Resumo e recomendacoes" -ForegroundColor White
Write-Host ""
Write-Host "PROTOCOLO:" -ForegroundColor Cyan
Write-Host "   Quando voce reportar um problema, eu vou:" -ForegroundColor White
Write-Host "   1. Executar este script automaticamente" -ForegroundColor White
Write-Host "   2. Ler o arquivo CONSOLIDATED-ANALYSIS mais recente" -ForegroundColor White
Write-Host "   3. Analisar todos os logs em conjunto" -ForegroundColor White
Write-Host "   4. Identificar a causa raiz do problema" -ForegroundColor White
Write-Host ""

# Não perguntar interativamente quando executado via AAA
if (-not $env:AAA_AUTO_MODE) {
    $open = Read-Host "Deseja abrir o arquivo agora? (S/N)"
    if ($open -eq "S" -or $open -eq "s") {
        notepad $consolidatedFile
    }
}

