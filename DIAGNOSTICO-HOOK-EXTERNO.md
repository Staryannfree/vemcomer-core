# 🔍 DIAGNÓSTICO: HOOK EXTERNO INTERCEPTANDO COMANDOS

## ❌ PROBLEMA CONFIRMADO

Mesmo após desabilitar todos os scripts do projeto, o comando `php -v` ainda mostra:
```
[1/4] Analisando logs do servidor...
```

Isso significa que o hook está **FORA** dos scripts do projeto.

---

## 🎯 ONDE PODE ESTAR O HOOK

### 1. PowerShell Profile (MAIS PROVÁVEL)

O PowerShell pode ter um profile que intercepta comandos.

**Verificar:**
```powershell
# Abra um PowerShell NOVO (fora do Cursor)
notepad $PROFILE
```

**Procurar por:**
- `auto-analyze-all-debug`
- `check-AAA`
- `Invoke-Command` com interceptação
- Funções que modificam `Invoke-Expression`

**Se encontrar, comente ou remova essas linhas.**

---

### 2. Extensão do Cursor

Alguma extensão pode estar interceptando comandos do terminal.

**Verificar:**
1. Abra Cursor → Extensions (Ctrl+Shift+X)
2. Procure por extensões relacionadas a:
   - "Terminal hooks"
   - "Command interceptors"
   - "Auto debug"
   - "PowerShell hooks"
3. Desabilite temporariamente todas as extensões
4. Teste novamente: `php -v`

---

### 3. Configurações do Cursor

O Cursor pode ter configurações que interceptam comandos.

**Verificar:**
1. Abra configurações (Ctrl+,)
2. Procure por:
   - `terminal.integrated.shellIntegration`
   - `terminal.integrated.commands`
   - Qualquer configuração com "hook" ou "intercept"
3. Verifique arquivo `.vscode/settings.json` no projeto

---

### 4. Script de Watch em Background

Pode haver um processo PowerShell rodando em background.

**Verificar:**
```powershell
# Execute FORA do Cursor
Get-Process | Where-Object { $_.ProcessName -match "powershell|pwsh" } | ForEach-Object {
    $cmdLine = (Get-CimInstance Win32_Process -Filter "ProcessId = $($_.Id)").CommandLine
    if ($cmdLine -match "auto-analyze|check-AAA|debug") {
        Write-Host "PID $($_.Id): $cmdLine"
    }
}
```

**Se encontrar, finalize o processo:**
```powershell
Stop-Process -Id <PID> -Force
```

---

## ✅ SOLUÇÃO PASSO A PASSO

### Passo 1: Executar Script de Emergência (FORA DO CURSOR)

1. **Feche o Cursor completamente**
2. **Abra um PowerShell novo** (não dentro do Cursor)
3. **Execute:**
   ```powershell
   cd C:\Users\Adm-Sup\Documents\Github\vemcomer-core
   .\scripts\emergency-disable-all-hooks.ps1
   ```
4. **Siga as instruções** (pode pedir para reiniciar PowerShell)

### Passo 2: Verificar PowerShell Profile

1. **No PowerShell (fora do Cursor):**
   ```powershell
   if (Test-Path $PROFILE) {
       notepad $PROFILE
   } else {
       Write-Host "Nenhum profile encontrado"
   }
   ```

2. **Procure e remova/comente:**
   - Qualquer linha com `auto-analyze-all-debug`
   - Qualquer linha com `check-AAA`
   - Qualquer função que intercepte comandos

3. **Salve e feche**

### Passo 3: Reiniciar Tudo

1. **Feche TODOS os PowerShells**
2. **Feche o Cursor completamente**
3. **Aguarde 10 segundos**
4. **Reabra o Cursor**
5. **Teste:** `php -v`

### Passo 4: Se Ainda Não Funcionar

1. **Desabilite TODAS as extensões do Cursor temporariamente**
2. **Teste novamente:** `php -v`
3. **Se funcionar:** Reabilite extensões uma por uma até encontrar a culpada

---

## 🔧 SCRIPT DE DIAGNÓSTICO COMPLETO

Execute este script **FORA DO CURSOR** para diagnosticar:

```powershell
# Salve como: diagnose-hook.ps1
# Execute: .\diagnose-hook.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "DIAGNÓSTICO COMPLETO DE HOOKS" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Profile do PowerShell
Write-Host "[1/4] Verificando PowerShell Profile..." -ForegroundColor Yellow
if (Test-Path $PROFILE) {
    Write-Host "  Profile encontrado: $PROFILE" -ForegroundColor Red
    $content = Get-Content $PROFILE -Raw
    if ($content -match "auto-analyze|check-AAA|debug-all") {
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
$functions = Get-ChildItem Function: | Where-Object {
    try {
        $def = (Get-Content "Function:$($_.Name)" -ErrorAction SilentlyContinue) -join "`n"
        $def -match "auto-analyze|check-AAA|debug-all"
    } catch {
        $false
    }
}
if ($functions) {
    Write-Host "  ❌ FUNÇÕES PROBLEMÁTICAS ENCONTRADAS:" -ForegroundColor Red
    $functions | ForEach-Object {
        Write-Host "    Função: $($_.Name)" -ForegroundColor White
    }
} else {
    Write-Host "  ✅ Nenhuma função problemática" -ForegroundColor Green
}

# 4. Processos PowerShell
Write-Host ""
Write-Host "[4/4] Verificando processos PowerShell..." -ForegroundColor Yellow
$processes = Get-Process | Where-Object { 
    $_.ProcessName -match "powershell|pwsh"
} | ForEach-Object {
    try {
        $cmdLine = (Get-CimInstance Win32_Process -Filter "ProcessId = $($_.Id)").CommandLine
        if ($cmdLine -match "auto-analyze|check-AAA|debug") {
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
    Write-Host "  ❌ PROCESSOS PROBLEMÁTICOS ENCONTRADOS:" -ForegroundColor Red
    $processes | ForEach-Object {
        Write-Host "    PID $($_.Id): $($_.CommandLine)" -ForegroundColor White
    }
} else {
    Write-Host "  ✅ Nenhum processo problemático" -ForegroundColor Green
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "DIAGNÓSTICO CONCLUÍDO" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
```

---

## 📋 CHECKLIST DE CORREÇÃO

- [ ] Executei `emergency-disable-all-hooks.ps1` FORA do Cursor
- [ ] Verifiquei e limpei o PowerShell Profile
- [ ] Fechei e reabri o Cursor completamente
- [ ] Fechei e reabri o PowerShell
- [ ] Testei `php -v` e não apareceu "[1/4] Analisando..."
- [ ] Se ainda aparecer, desabilitei extensões do Cursor
- [ ] Se ainda aparecer, executei o script de diagnóstico completo

---

## 🆘 SE NADA FUNCIONAR

O problema pode estar em:
- **Configuração do sistema Windows** (pouco provável)
- **Extensão do Cursor que não aparece na lista** (verificar extensões ocultas)
- **Script de inicialização do Windows** (verificar Startup)

Nesse caso, considere:
1. Reinstalar o Cursor
2. Criar um novo perfil de usuário do Windows
3. Verificar se há software de monitoramento/antivírus interceptando

---

**Última atualização:** 2025-12-04

