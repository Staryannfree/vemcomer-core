# ============================================================================
# ENCONTRAR E DESABILITAR HOOK AUTOMÁTICO
# ============================================================================
# Este script procura por hooks que estão executando auto-analyze-all-debug.ps1
# automaticamente quando comandos são executados
# ============================================================================

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "PROCURANDO HOOKS AUTOMÁTICOS" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Verificar profile do PowerShell
Write-Host "[1/5] Verificando profile do PowerShell..." -ForegroundColor Yellow
$profilePath = $PROFILE
if (Test-Path $profilePath) {
    Write-Host "  ⚠️  Profile encontrado: $profilePath" -ForegroundColor Red
    $profileContent = Get-Content $profilePath -Raw
    if ($profileContent -match "auto-analyze|AAA|debug-all") {
        Write-Host "  ❌ Profile contém referências a auto-analyze!" -ForegroundColor Red
        Write-Host "  Conteúdo relevante:" -ForegroundColor Yellow
        Select-String -Path $profilePath -Pattern "auto-analyze|AAA|debug" -Context 2,2 | ForEach-Object {
            Write-Host "    $($_.Line)" -ForegroundColor White
        }
    } else {
        Write-Host "  ✅ Profile não contém referências problemáticas" -ForegroundColor Green
    }
} else {
    Write-Host "  ✅ Nenhum profile encontrado" -ForegroundColor Green
}

Write-Host ""

# 2. Verificar variáveis de ambiente
Write-Host "[2/5] Verificando variáveis de ambiente..." -ForegroundColor Yellow
$envVars = Get-ChildItem Env: | Where-Object { $_.Name -match "AAA|AUTO|DEBUG|HOOK" }
if ($envVars) {
    Write-Host "  ⚠️  Variáveis de ambiente encontradas:" -ForegroundColor Red
    $envVars | ForEach-Object {
        Write-Host "    $($_.Name) = $($_.Value)" -ForegroundColor White
    }
} else {
    Write-Host "  ✅ Nenhuma variável de ambiente problemática encontrada" -ForegroundColor Green
}

Write-Host ""

# 3. Verificar aliases do PowerShell
Write-Host "[3/5] Verificando aliases do PowerShell..." -ForegroundColor Yellow
$aliases = Get-Alias | Where-Object { $_.Definition -match "auto-analyze|debug" }
if ($aliases) {
    Write-Host "  ⚠️  Aliases encontrados:" -ForegroundColor Red
    $aliases | ForEach-Object {
        Write-Host "    $($_.Name) -> $($_.Definition)" -ForegroundColor White
    }
} else {
    Write-Host "  ✅ Nenhum alias problemático encontrado" -ForegroundColor Green
}

Write-Host ""

# 4. Verificar funções do PowerShell
Write-Host "[4/5] Verificando funções do PowerShell..." -ForegroundColor Yellow
$functions = Get-ChildItem Function: | Where-Object { 
    $def = (Get-Content "Function:$($_.Name)" -ErrorAction SilentlyContinue) -join "`n"
    $def -match "auto-analyze|AAA|debug-all"
}
if ($functions) {
    Write-Host "  ⚠️  Funções encontradas:" -ForegroundColor Red
    $functions | ForEach-Object {
        Write-Host "    Função: $($_.Name)" -ForegroundColor White
    }
} else {
    Write-Host "  ✅ Nenhuma função problemática encontrada" -ForegroundColor Green
}

Write-Host ""

# 5. Verificar se há processos PowerShell rodando o script
Write-Host "[5/5] Verificando processos PowerShell..." -ForegroundColor Yellow
$processes = Get-Process | Where-Object { 
    $_.ProcessName -eq "powershell" -or $_.ProcessName -eq "pwsh"
} | ForEach-Object {
    try {
        $cmdLine = (Get-CimInstance Win32_Process -Filter "ProcessId = $($_.Id)").CommandLine
        if ($cmdLine -match "auto-analyze") {
            $_
        }
    } catch {
        # Ignorar erros
    }
}

if ($processes) {
    Write-Host "  ⚠️  Processos PowerShell executando auto-analyze:" -ForegroundColor Red
    $processes | ForEach-Object {
        Write-Host "    PID: $($_.Id) - $($_.ProcessName)" -ForegroundColor White
    }
} else {
    Write-Host "  ✅ Nenhum processo problemático encontrado" -ForegroundColor Green
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "RECOMENDAÇÕES" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

if (Test-Path $profilePath) {
    Write-Host "Para desabilitar hooks no profile:" -ForegroundColor Yellow
    Write-Host "  1. Abra: $profilePath" -ForegroundColor White
    Write-Host "  2. Comente ou remova linhas que referenciam:" -ForegroundColor White
    Write-Host "     - auto-analyze-all-debug.ps1" -ForegroundColor White
    Write-Host "     - check-AAA-trigger.ps1" -ForegroundColor White
    Write-Host "     - Qualquer função que intercepte comandos" -ForegroundColor White
    Write-Host ""
}

Write-Host "Para verificar se há hooks do Cursor/IDE:" -ForegroundColor Yellow
Write-Host "  1. Verifique configurações do Cursor" -ForegroundColor White
Write-Host "  2. Procure por 'terminal hooks' ou 'command interceptors'" -ForegroundColor White
Write-Host "  3. Verifique extensões instaladas" -ForegroundColor White
Write-Host ""

