# Como Desabilitar o Hook Automático que Executa Análise de Logs

## 🚨 Problema

Toda vez que você executa um comando (PHP, PowerShell, etc.), aparece:
```
[1/4] Analisando logs do servidor...
```

Isso está causando lentidão extrema porque o script tenta analisar logs grandes.

## ✅ Solução

### Opção 1: Verificar Profile do PowerShell (Mais Provável)

1. Abra o PowerShell como Administrador
2. Execute:
   ```powershell
   notepad $PROFILE
   ```
3. Procure por linhas que contenham:
   - `auto-analyze-all-debug.ps1`
   - `check-AAA-trigger.ps1`
   - `debug-all.ps1`
   - Qualquer função que intercepte comandos
4. **Comente ou remova essas linhas** (adicione `#` no início)
5. Salve o arquivo
6. Feche e reabra o PowerShell

### Opção 2: Verificar Configurações do Cursor

1. Abra as configurações do Cursor (Ctrl+,)
2. Procure por:
   - "terminal hooks"
   - "command interceptors"
   - "auto execute"
   - "debug scripts"
3. Desabilite qualquer extensão ou configuração que execute scripts automaticamente

### Opção 3: Executar Script de Diagnóstico

Execute diretamente no PowerShell (não via Cursor):

```powershell
cd "C:\Users\Adm-Sup\Documents\Github\vemcomer-core"
.\scripts\find-and-disable-auto-hook.ps1
```

Este script vai mostrar exatamente onde está o problema.

### Opção 4: Verificar Processos em Background

1. Abra o Gerenciador de Tarefas (Ctrl+Shift+Esc)
2. Vá para a aba "Detalhes"
3. Procure por processos `powershell.exe` ou `pwsh.exe`
4. Se encontrar algum executando `auto-analyze-all-debug.ps1`, finalize-o

### Opção 5: Verificar Arquivos de Trigger

Verifique se existe um arquivo de trigger ativo:

```powershell
Test-Path "C:\Users\Adm-Sup\Documents\Github\vemcomer-core\debug-reports\AAA-TRIGGER-ACTIVE.txt"
```

Se existir, delete-o:
```powershell
Remove-Item "C:\Users\Adm-Sup\Documents\Github\vemcomer-core\debug-reports\AAA-TRIGGER-ACTIVE.txt" -Force
```

## 🔍 Verificação Rápida

Execute estes comandos no PowerShell (fora do Cursor):

```powershell
# Verificar profile
if (Test-Path $PROFILE) {
    Write-Host "Profile encontrado: $PROFILE"
    Get-Content $PROFILE | Select-String -Pattern "auto-analyze|AAA|debug" -Context 2,2
} else {
    Write-Host "Nenhum profile encontrado"
}

# Verificar variáveis de ambiente
Get-ChildItem Env: | Where-Object { $_.Name -match "AAA|AUTO|DEBUG" }

# Verificar funções
Get-ChildItem Function: | Where-Object { $_.Name -match "auto|debug|AAA" }
```

## ✅ Já Fizemos

- ✅ Desabilitamos a análise de logs do servidor no script `auto-analyze-all-debug.ps1`
- ✅ Mudamos o padrão de `$IncludeServerLogs = $true` para `$IncludeServerLogs = $false`
- ✅ Bloqueamos a execução com `if ($false -and $IncludeServerLogs)`

Mas o problema persiste porque algo está **interceptando comandos antes** de chegar ao script.

## 🎯 Próximos Passos

1. Execute o script de diagnóstico manualmente (fora do Cursor)
2. Verifique o profile do PowerShell
3. Verifique configurações do Cursor
4. Me informe o que encontrou!

