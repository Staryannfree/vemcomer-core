# ============================================================================
# LER LOGS DO NAVEGADOR
# ============================================================================
# Lê os logs capturados automaticamente pelo browser
# Execute: .\scripts\read-browser-logs.ps1
# ============================================================================

param(
    [string]$Date = (Get-Date -Format 'yyyy-MM-dd'),
    [switch]$Latest = $false,
    [switch]$NetworkOnly = $false,
    [switch]$ErrorsOnly = $false
)

$ErrorActionPreference = "Continue"
$wpPath = "C:\Users\Adm-Sup\Local Sites\pedevem-local\app\public"
$wpContentPath = Join-Path $wpPath "wp-content"
$uploadsPath = Join-Path $wpContentPath "uploads"
$debugPath = Join-Path $uploadsPath "vemcomer-browser-debug"
$pluginPath = "C:\Users\Adm-Sup\Documents\Github\vemcomer-core"
$outputDir = Join-Path $pluginPath "debug-reports"

if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "LENDO LOGS DO NAVEGADOR" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path $debugPath)) {
    Write-Host "❌ Diretório de logs do navegador não encontrado: $debugPath" -ForegroundColor Red
    Write-Host ""
    Write-Host "Isso significa que:" -ForegroundColor Yellow
    Write-Host "  1. O script browser-debug-capture.js ainda não foi executado" -ForegroundColor White
    Write-Host "  2. Ou VC_DEBUG não está ativo no wp-config.php" -ForegroundColor White
    Write-Host "  3. Ou nenhum log foi capturado ainda" -ForegroundColor White
    Write-Host ""
    exit
}

# Encontrar arquivos mais recentes se solicitado
if ($Latest) {
    $logFiles = Get-ChildItem -Path $debugPath -Filter "browser-logs-*.json" | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    if ($logFiles) {
        $Date = $logFiles.Name -replace 'browser-logs-', '' -replace '\.json', ''
        Write-Host "Usando logs mais recentes: $Date" -ForegroundColor Yellow
        Write-Host ""
    }
}

$logsFile = Join-Path $debugPath "browser-logs-$Date.json"
$networkFile = Join-Path $debugPath "network-requests-$Date.json"
$performanceFile = Join-Path $debugPath "performance-$Date.json"

$report = @()
$report += "========================================"
$report += "LOGS DO NAVEGADOR - $Date"
$report += "========================================"
$report += ""

# Ler logs do console
if (Test-Path $logsFile) {
    Write-Host "Lendo logs do console..." -ForegroundColor Yellow
    $logs = Get-Content $logsFile -Raw | ConvertFrom-Json
    
    if ($ErrorsOnly) {
        $logs = $logs | Where-Object { 
            $_.type -like '*error*' -or 
            $_.type -like '*warn*' -or
            ($_.data -and $_.data.level -in @('error', 'warn'))
        }
    }
    
    $report += "LOGS DO CONSOLE (Total: $($logs.Count))"
    $report += "========================================"
    
    $errorCount = 0
    $warnCount = 0
    $logCount = 0
    
    foreach ($log in $logs) {
        $type = $log.type
        $data = $log.data
        $timestamp = $log.timestamp
        $url = $log.url
        
        $level = if ($data.level) { $data.level } else { 'info' }
        
        if ($level -eq 'error') { $errorCount++ }
        elseif ($level -eq 'warn') { $warnCount++ }
        else { $logCount++ }
        
        $report += ""
        $report += "[$timestamp] [$type] $level"
        if ($data.message) {
            $report += "  Mensagem: $($data.message)"
        }
        if ($data.filename) {
            $report += "  Arquivo: $($data.filename):$($data.lineno)"
        }
        if ($data.url) {
            $report += "  URL: $($data.url)"
        }
        if ($data.stack) {
            $report += "  Stack:"
            $report += "    $($data.stack -replace "`n", "`n    ")"
        }
    }
    
    $report += ""
    $report += "Resumo:"
    $report += "  - Erros: $errorCount"
    $report += "  - Avisos: $warnCount"
    $report += "  - Info/Debug: $logCount"
    $report += ""
} else {
    Write-Host "⚠️  Arquivo de logs não encontrado: $logsFile" -ForegroundColor Yellow
    $report += "Nenhum log do console encontrado para $Date"
    $report += ""
}

# Ler requisições de rede
if (-not $NetworkOnly -or $NetworkOnly) {
    if (Test-Path $networkFile) {
        Write-Host "Lendo requisições de rede..." -ForegroundColor Yellow
        $networkRequests = Get-Content $networkFile -Raw | ConvertFrom-Json
        
        $report += "========================================"
        $report += "REQUISIÇÕES DE REDE (Total: $($networkRequests.Count))"
        $report += "========================================"
        
        $failedRequests = 0
        $successRequests = 0
        
        foreach ($req in $networkRequests) {
            $status = $req.status
            if ($status -ge 400) {
                $failedRequests++
            } else {
                $successRequests++
            }
            
            $report += ""
            $report += "[$($req.timestamp)] $($req.method) $($req.url)"
            $report += "  Status: $status $($req.statusText)"
            $report += "  Duração: $($req.duration)ms"
            
            if ($status -ge 400) {
                $report += "  ❌ ERRO"
            }
            
            if ($req.responseBody -and $req.responseBody -is [string] -and $req.responseBody.Length -gt 200) {
                $report += "  Response: $($req.responseBody.Substring(0, 200))..."
            } elseif ($req.responseBody) {
                $report += "  Response: $($req.responseBody)"
            }
        }
        
        $report += ""
        $report += "Resumo de Requisições:"
        $report += "  - Sucesso: $successRequests"
        $report += "  - Erros: $failedRequests"
        $report += ""
    } else {
        Write-Host "⚠️  Arquivo de requisições de rede não encontrado: $networkFile" -ForegroundColor Yellow
        $report += "Nenhuma requisição de rede encontrada para $Date"
        $report += ""
    }
}

# Ler métricas de performance
if (-not $NetworkOnly) {
    if (Test-Path $performanceFile) {
        Write-Host "Lendo métricas de performance..." -ForegroundColor Yellow
        $performance = Get-Content $performanceFile -Raw | ConvertFrom-Json
        
        $report += "========================================"
        $report += "MÉTRICAS DE PERFORMANCE"
        $report += "========================================"
        
        foreach ($metric in $performance) {
            $report += ""
            $report += "DNS: $($metric.dns)ms"
            $report += "TCP: $($metric.tcp)ms"
            $report += "Request: $($metric.request)ms"
            $report += "Response: $($metric.response)ms"
            $report += "DOM: $($metric.dom)ms"
            $report += "Load Total: $($metric.load)ms"
        }
        $report += ""
    }
}

# Salvar relatório
$timestamp = Get-Date -Format 'yyyy-MM-dd-HHmmss'
$outputFile = Join-Path $outputDir "browser-logs-$Date-$timestamp.txt"
$report | Out-File -FilePath $outputFile -Encoding UTF8

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "✅ RELATÓRIO GERADO!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Arquivo: $outputFile" -ForegroundColor Cyan
Write-Host ""
Write-Host "📋 Você pode:" -ForegroundColor Yellow
Write-Host "   1. Abrir o arquivo e copiar o conteúdo para mim" -ForegroundColor White
Write-Host "   2. OU me pedir: 'Lê os logs do navegador mais recentes'" -ForegroundColor White
Write-Host ""

$open = Read-Host "Deseja abrir o arquivo agora? (S/N)"
if ($open -eq "S" -or $open -eq "s") {
    notepad $outputFile
}

