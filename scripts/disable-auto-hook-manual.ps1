# ============================================================================
# DESABILITAR HOOK AUTOMÁTICO - VERSÃO MANUAL
# ============================================================================
# Execute este script DIRETAMENTE no PowerShell (não via Cursor)
# ============================================================================

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "DESABILITANDO HOOK AUTOMÁTICO" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$pluginPath = "C:\Users\Adm-Sup\Documents\Github\vemcomer-core"

# 1. Remover arquivo de trigger se existir
Write-Host "[1/4] Removendo arquivo de trigger..." -ForegroundColor Yellow
$triggerFile = Join-Path $pluginPath "debug-reports" "AAA-TRIGGER-ACTIVE.txt"
if (Test-Path $triggerFile) {
    Remove-Item $triggerFile -Force
    Write-Host "  ✅ Arquivo de trigger removido" -ForegroundColor Green
} else {
    Write-Host "  ✅ Nenhum arquivo de trigger encontrado" -ForegroundColor Green
}

# 2. Verificar e limpar profile do PowerShell
Write-Host ""
Write-Host "[2/4] Verificando profile do PowerShell..." -ForegroundColor Yellow
$profilePath = $PROFILE
if (Test-Path $profilePath) {
    $profileContent = Get-Content $profilePath -Raw
    $backupPath = "$profilePath.backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
    
    if ($profileContent -match "auto-analyze-all-debug|check-AAA-trigger") {
        Write-Host "  ⚠️  Profile contém referências problemáticas!" -ForegroundColor Red
        Write-Host "  📋 Criando backup: $backupPath" -ForegroundColor Yellow
        Copy-Item $profilePath $backupPath
        
        Write-Host ""
        Write-Host "  Linhas problemáticas encontradas:" -ForegroundColor Yellow
        Select-String -Path $profilePath -Pattern "auto-analyze|AAA|check-AAA" | ForEach-Object {
            Write-Host "    Linha $($_.LineNumber): $($_.Line.Trim())" -ForegroundColor White
        }
        
        Write-Host ""
        $remove = Read-Host "  Deseja comentar essas linhas automaticamente? (S/N)"
        if ($remove -eq "S" -or $remove -eq "s") {
            $newContent = Get-Content $profilePath | ForEach-Object {
                if ($_ -match "auto-analyze-all-debug|check-AAA-trigger|debug-all\.ps1") {
                    "# DESABILITADO: $_"
                } else {
                    $_
                }
            }
            $newContent | Set-Content $profilePath
            Write-Host "  ✅ Linhas comentadas. Reinicie o PowerShell." -ForegroundColor Green
        } else {
            Write-Host "  ⚠️  Edite manualmente: $profilePath" -ForegroundColor Yellow
        }
    } else {
        Write-Host "  ✅ Profile não contém referências problemáticas" -ForegroundColor Green
    }
} else {
    Write-Host "  ✅ Nenhum profile encontrado" -ForegroundColor Green
}

# 3. Verificar e remover funções problemáticas
Write-Host ""
Write-Host "[3/4] Verificando funções do PowerShell..." -ForegroundColor Yellow
$problemFunctions = Get-ChildItem Function: | Where-Object { 
    $name = $_.Name
    $def = (Get-Content "Function:$name" -ErrorAction SilentlyContinue) -join "`n"
    $def -match "auto-analyze|AAA-trigger|debug-all"
}

if ($problemFunctions) {
    Write-Host "  ⚠️  Funções problemáticas encontradas:" -ForegroundColor Red
    $problemFunctions | ForEach-Object {
        Write-Host "    - $($_.Name)" -ForegroundColor White
    }
    
    Write-Host ""
    $remove = Read-Host "  Deseja remover essas funções? (S/N)"
    if ($remove -eq "S" -or $remove -eq "s") {
        $problemFunctions | ForEach-Object {
            Remove-Item "Function:$($_.Name)" -Force
            Write-Host "  ✅ Função $($_.Name) removida" -ForegroundColor Green
        }
    }
} else {
    Write-Host "  ✅ Nenhuma função problemática encontrada" -ForegroundColor Green
}

# 4. Verificar processos em execução
Write-Host ""
Write-Host "[4/4] Verificando processos PowerShell..." -ForegroundColor Yellow
$processes = Get-Process | Where-Object { 
    $_.ProcessName -match "powershell|pwsh"
} | ForEach-Object {
    try {
        $cmdLine = (Get-CimInstance Win32_Process -Filter "ProcessId = $($_.Id)").CommandLine
        if ($cmdLine -match "auto-analyze") {
            [PSCustomObject]@{
                Id = $_.Id
                Name = $_.ProcessName
                CommandLine = $cmdLine
            }
        }
    } catch {
        # Ignorar
    }
}

if ($processes) {
    Write-Host "  ⚠️  Processos encontrados executando auto-analyze:" -ForegroundColor Red
    $processes | ForEach-Object {
        Write-Host "    PID $($_.Id): $($_.Name)" -ForegroundColor White
    }
    
    Write-Host ""
    $kill = Read-Host "  Deseja finalizar esses processos? (S/N)"
    if ($kill -eq "S" -or $kill -eq "s") {
        $processes | ForEach-Object {
            Stop-Process -Id $_.Id -Force
            Write-Host "  ✅ Processo $($_.Id) finalizado" -ForegroundColor Green
        }
    }
} else {
    Write-Host "  ✅ Nenhum processo problemático encontrado" -ForegroundColor Green
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✅ PROCESSO CONCLUÍDO" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Próximos passos:" -ForegroundColor Yellow
Write-Host "  1. Feche e reabra o PowerShell" -ForegroundColor White
Write-Host "  2. Teste executando um comando simples: php -v" -ForegroundColor White
Write-Host "  3. Se ainda aparecer '[1/4] Analisando...', verifique:" -ForegroundColor White
Write-Host "     - Configurações do Cursor/IDE" -ForegroundColor White
Write-Host "     - Extensões instaladas" -ForegroundColor White
Write-Host "     - Scripts de watch em background" -ForegroundColor White
Write-Host ""

