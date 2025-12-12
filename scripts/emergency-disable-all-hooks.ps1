# ============================================================================
# EMERGÊNCIA: DESABILITAR TODOS OS HOOKS AUTOMÁTICOS
# ============================================================================
# Execute este script DIRETAMENTE no PowerShell (fora do Cursor)
# para desabilitar qualquer hook que esteja interceptando comandos
# ============================================================================

Write-Host "========================================" -ForegroundColor Red
Write-Host "🚨 MODO EMERGÊNCIA - DESABILITANDO HOOKS" -ForegroundColor Red
Write-Host "========================================" -ForegroundColor Red
Write-Host ""

$pluginPath = "C:\Users\Adm-Sup\Documents\Github\vemcomer-core"

# 1. Remover TODOS os arquivos de trigger
Write-Host "[1/6] Removendo arquivos de trigger..." -ForegroundColor Yellow
$triggerFiles = @(
    Join-Path $pluginPath "debug-reports" "AAA-TRIGGER-ACTIVE.txt",
    Join-Path $pluginPath "debug-reports" "*.trigger",
    Join-Path $pluginPath "*.trigger"
)

foreach ($pattern in $triggerFiles) {
    Get-ChildItem -Path $pattern -ErrorAction SilentlyContinue | Remove-Item -Force
}
Write-Host "  ✅ Arquivos de trigger removidos" -ForegroundColor Green

# 2. Desabilitar funções do PowerShell que interceptam comandos
Write-Host ""
Write-Host "[2/6] Removendo funções problemáticas..." -ForegroundColor Yellow
$problemFunctions = @(
    "Invoke-Command",
    "Invoke-Expression", 
    "auto-analyze",
    "debug-all",
    "check-AAA"
)

foreach ($funcName in $problemFunctions) {
    if (Get-Command $funcName -ErrorAction SilentlyContinue) {
        Remove-Item "Function:$funcName" -Force -ErrorAction SilentlyContinue
        Write-Host "  ✅ Função $funcName removida" -ForegroundColor Green
    }
}

# Remover qualquer função que contenha "auto-analyze" no corpo
Get-ChildItem Function: | ForEach-Object {
    $func = $_
    try {
        $def = (Get-Content "Function:$($func.Name)" -ErrorAction SilentlyContinue) -join "`n"
        if ($def -match "auto-analyze-all-debug|check-AAA-trigger") {
            Remove-Item "Function:$($func.Name)" -Force -ErrorAction SilentlyContinue
            Write-Host "  ✅ Função $($func.Name) removida (continha auto-analyze)" -ForegroundColor Green
        }
    } catch {
        # Ignorar
    }
}

# 3. Limpar variáveis de ambiente problemáticas
Write-Host ""
Write-Host "[3/6] Limpando variáveis de ambiente..." -ForegroundColor Yellow
$envVars = @("AAA_AUTO_MODE", "AUTO_ANALYZE", "DEBUG_AUTO", "HOOK_ACTIVE")
foreach ($var in $envVars) {
    if (Test-Path "Env:$var") {
        Remove-Item "Env:$var" -Force
        Write-Host "  ✅ Variável $var removida" -ForegroundColor Green
    }
}

# 4. Verificar e limpar profile do PowerShell
Write-Host ""
Write-Host "[4/6] Verificando profile do PowerShell..." -ForegroundColor Yellow
$profilePath = $PROFILE
if (Test-Path $profilePath) {
    $profileContent = Get-Content $profilePath -Raw
    if ($profileContent -match "auto-analyze-all-debug|check-AAA-trigger|debug-all\.ps1") {
        Write-Host "  ⚠️  Profile contém referências problemáticas!" -ForegroundColor Red
        Write-Host "  📋 Criando backup e limpando..." -ForegroundColor Yellow
        
        $backupPath = "$profilePath.backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
        Copy-Item $profilePath $backupPath
        
        # Comentar linhas problemáticas
        $newContent = Get-Content $profilePath | ForEach-Object {
            if ($_ -match "auto-analyze-all-debug|check-AAA-trigger|debug-all\.ps1") {
                "# DESABILITADO EMERGENCIALMENTE: $_"
            } else {
                $_
            }
        }
        $newContent | Set-Content $profilePath
        Write-Host "  ✅ Profile limpo. Backup em: $backupPath" -ForegroundColor Green
        Write-Host "  ⚠️  REINICIE O POWERSHELL para aplicar!" -ForegroundColor Red
    } else {
        Write-Host "  ✅ Profile não contém referências problemáticas" -ForegroundColor Green
    }
} else {
    Write-Host "  ✅ Nenhum profile encontrado" -ForegroundColor Green
}

# 5. Finalizar processos PowerShell que estejam executando auto-analyze
Write-Host ""
Write-Host "[5/6] Verificando processos PowerShell..." -ForegroundColor Yellow
$processes = Get-Process | Where-Object { 
    $_.ProcessName -match "powershell|pwsh"
} | ForEach-Object {
    try {
        $cmdLine = (Get-CimInstance Win32_Process -Filter "ProcessId = $($_.Id)").CommandLine
        if ($cmdLine -match "auto-analyze") {
            [PSCustomObject]@{
                Id = $_.Id
                Name = $_.ProcessName
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
        Stop-Process -Id $_.Id -Force -ErrorAction SilentlyContinue
        Write-Host "      ✅ Processo finalizado" -ForegroundColor Green
    }
} else {
    Write-Host "  ✅ Nenhum processo problemático encontrado" -ForegroundColor Green
}

# 6. Criar arquivo de bloqueio permanente
Write-Host ""
Write-Host "[6/6] Criando arquivo de bloqueio..." -ForegroundColor Yellow
$blockFile = Join-Path $pluginPath "debug-reports" "HOOKS-DISABLED-PERMANENTLY.txt"
$blockContent = @"
HOOKS AUTOMÁTICOS DESABILITADOS PERMANENTEMENTE
===============================================
Data: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')
Motivo: Interceptação de comandos causando lentidão extrema

Para reabilitar (NÃO RECOMENDADO):
1. Delete este arquivo
2. Edite o profile do PowerShell
3. Reinicie o PowerShell

ATENÇÃO: Reabilitar pode causar lentidão extrema novamente!
"@
$blockContent | Out-File -FilePath $blockFile -Encoding UTF8 -Force
Write-Host "  ✅ Arquivo de bloqueio criado: $blockFile" -ForegroundColor Green

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "✅ HOOKS DESABILITADOS!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "⚠️  AÇÕES NECESSÁRIAS:" -ForegroundColor Yellow
Write-Host "  1. FECHE E REABRA O POWERSHELL" -ForegroundColor Red
Write-Host "  2. FECHE E REABRA O CURSOR" -ForegroundColor Red
Write-Host "  3. Teste executando: php -v" -ForegroundColor White
Write-Host ""
Write-Host "Se ainda aparecer '[1/4] Analisando...', o problema está:" -ForegroundColor Yellow
Write-Host "  - Em uma extensão do Cursor" -ForegroundColor White
Write-Host "  - Em configurações do Cursor" -ForegroundColor White
Write-Host "  - Em algum script de watch em background" -ForegroundColor White
Write-Host ""

