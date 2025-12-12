# ============================================================================
# DIAGNÓSTICO COMPLETO DE HOOKS EXTERNOS
# ============================================================================
# Execute este script FORA DO CURSOR para diagnosticar hooks que interceptam comandos
# Execute: .\scripts\diagnose-hook.ps1
# ============================================================================

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "DIAGNÓSTICO COMPLETO DE HOOKS" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Profile do PowerShell
Write-Host "[1/4] Verificando PowerShell Profile..." -ForegroundColor Yellow
if (Test-Path $PROFILE) {
    Write-Host "  Profile encontrado: $PROFILE" -ForegroundColor Red
    $content = Get-Content $PROFILE -Raw -ErrorAction SilentlyContinue
    if ($content -and $content -match "auto-analyze|check-AAA|debug-all") {
        Write-Host "  ❌ PROFILE CONTÉM HOOKS!" -ForegroundColor Red
        Select-String -Path $PROFILE -Pattern "auto-analyze|check-AAA|debug" | ForEach-Object {
            Write-Host "    Linha $($_.LineNumber): $($_.Line.Trim())" -ForegroundColor White
        }
    } else {
        Write-Host "  ✅ Profile limpo" -ForegroundColor Green
    }
} else {
    Write-Host "  ✅ Nenhum profile encontrado" -ForegroundColor Green
}

# 2. Variáveis de ambiente
Write-Host ""
Write-Host "[2/4] Verificando variáveis de ambiente..." -ForegroundColor Yellow
$envVars = Get-ChildItem Env: | Where-Object { $_.Name -match "AAA|AUTO|DEBUG|HOOK" }
if ($envVars) {
    Write-Host "  ⚠️  Variáveis encontradas:" -ForegroundColor Red
    $envVars | ForEach-Object {
        Write-Host "    $($_.Name) = $($_.Value)" -ForegroundColor White
    }
} else {
    Write-Host "  ✅ Nenhuma variável problemática" -ForegroundColor Green
}

# 3. Funções do PowerShell
Write-Host ""
Write-Host "[3/4] Verificando funções do PowerShell..." -ForegroundColor Yellow
$problemFunctions = @()
Get-ChildItem Function: | ForEach-Object {
    $funcName = $_.Name
    try {
        $def = (Get-Content "Function:$funcName" -ErrorAction SilentlyContinue) -join "`n"
        if ($def -and ($def -match "auto-analyze|check-AAA|debug-all")) {
            $problemFunctions += $funcName
        }
    } catch {
        # Ignorar
    }
}

if ($problemFunctions) {
    Write-Host "  ❌ FUNÇÕES PROBLEMÁTICAS ENCONTRADAS:" -ForegroundColor Red
    $problemFunctions | ForEach-Object {
        Write-Host "    Função: $_" -ForegroundColor White
    }
} else {
    Write-Host "  ✅ Nenhuma função problemática" -ForegroundColor Green
}

# 4. Processos PowerShell
Write-Host ""
Write-Host "[4/4] Verificando processos PowerShell..." -ForegroundColor Yellow
$problemProcesses = @()
Get-Process | Where-Object { 
    $_.ProcessName -match "powershell|pwsh"
} | ForEach-Object {
    try {
        $cmdLine = (Get-CimInstance Win32_Process -Filter "ProcessId = $($_.Id)").CommandLine
        if ($cmdLine -and ($cmdLine -match "auto-analyze|check-AAA|debug")) {
            $problemProcesses += [PSCustomObject]@{
                Id = $_.Id
                Name = $_.ProcessName
                CommandLine = $cmdLine
            }
        }
    } catch {
        # Ignorar
    }
}

if ($problemProcesses) {
    Write-Host "  ❌ PROCESSOS PROBLEMÁTICOS ENCONTRADOS:" -ForegroundColor Red
    $problemProcesses | ForEach-Object {
        Write-Host "    PID $($_.Id): $($_.CommandLine)" -ForegroundColor White
    }
} else {
    Write-Host "  ✅ Nenhum processo problemático" -ForegroundColor Green
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "DIAGNÓSTICO CONCLUÍDO" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

if ($problemFunctions -or $problemProcesses -or (Test-Path $PROFILE)) {
    Write-Host "⚠️  PROBLEMAS ENCONTRADOS!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Próximos passos:" -ForegroundColor Yellow
    Write-Host "  1. Execute: .\scripts\emergency-disable-all-hooks.ps1" -ForegroundColor White
    Write-Host "  2. Limpe o PowerShell Profile se necessário" -ForegroundColor White
    Write-Host "  3. Finalize processos problemáticos se encontrados" -ForegroundColor White
    Write-Host "  4. Reinicie o PowerShell e o Cursor" -ForegroundColor White
} else {
    Write-Host "✅ Nenhum problema encontrado nos scripts do PowerShell" -ForegroundColor Green
    Write-Host ""
    Write-Host "Se o problema persistir, verifique:" -ForegroundColor Yellow
    Write-Host "  - Extensões do Cursor" -ForegroundColor White
    Write-Host "  - Configurações do Cursor (.vscode/settings.json)" -ForegroundColor White
    Write-Host "  - Scripts de inicialização do Windows" -ForegroundColor White
}

