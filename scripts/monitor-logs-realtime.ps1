# ============================================================================
# MONITOR DE LOGS EM TEMPO REAL
# ============================================================================
# Monitora os logs do WordPress e VemComer em tempo real
# Execute: .\scripts\monitor-logs-realtime.ps1
# Pressione Ctrl+C para parar
# ============================================================================

$wpContentPath = "C:\Users\Adm-Sup\Local Sites\pedevem-local\app\public\wp-content"
$debugLog = Join-Path $wpContentPath "debug.log"
$vemcomerLog = Join-Path $wpContentPath "uploads\vemcomer-debug.log"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "MONITOR DE LOGS EM TEMPO REAL" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Pressione Ctrl+C para parar" -ForegroundColor Yellow
Write-Host ""

# Função para monitorar um arquivo
function Monitor-Log {
    param(
        [string]$FilePath,
        [string]$Label,
        [System.ConsoleColor]$Color = "White"
    )
    
    if (-not (Test-Path $FilePath)) {
        Write-Host "[$Label] Arquivo não encontrado: $FilePath" -ForegroundColor Red
        return
    }
    
    Write-Host "[$Label] Monitorando: $FilePath" -ForegroundColor $Color
    Write-Host ""
    
    Get-Content $FilePath -Wait -Tail 10 -ErrorAction SilentlyContinue | ForEach-Object {
        $timestamp = Get-Date -Format "HH:mm:ss"
        Write-Host "[$timestamp] [$Label] $_" -ForegroundColor $Color
    }
}

# Monitorar ambos os logs em paralelo (usando jobs)
Write-Host "Iniciando monitoramento..." -ForegroundColor Green
Write-Host ""

# Monitorar debug.log do WordPress
Start-Job -ScriptBlock {
    param($path, $label)
    Get-Content $path -Wait -Tail 10 -ErrorAction SilentlyContinue | ForEach-Object {
        Write-Output "[$label] $_"
    }
} -ArgumentList $debugLog, "WP-DEBUG" | Out-Null

# Monitorar vemcomer-debug.log
Start-Job -ScriptBlock {
    param($path, $label)
    Get-Content $path -Wait -Tail 10 -ErrorAction SilentlyContinue | ForEach-Object {
        Write-Output "[$label] $_"
    }
} -ArgumentList $vemcomerLog, "VEMCOMER" | Out-Null

# Exibir saída dos jobs
try {
    while ($true) {
        Get-Job | Receive-Job | ForEach-Object {
            $timestamp = Get-Date -Format "HH:mm:ss"
            Write-Host "[$timestamp] $_" -ForegroundColor Cyan
        }
        Start-Sleep -Milliseconds 500
    }
} finally {
    Write-Host ""
    Write-Host "Parando monitoramento..." -ForegroundColor Yellow
    Get-Job | Stop-Job
    Get-Job | Remove-Job
}

