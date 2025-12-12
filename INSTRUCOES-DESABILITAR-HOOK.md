# 🚨 INSTRUÇÕES URGENTES: Desabilitar Hook Automático

## Problema Identificado

Algo está interceptando **TODOS** os comandos do terminal e executando `auto-analyze-all-debug.ps1` automaticamente, causando lentidão extrema.

## ✅ Solução Imediata

### Passo 1: Execute o Script de Emergência

**IMPORTANTE:** Execute **FORA DO CURSOR**, diretamente no PowerShell:

1. Abra o PowerShell como Administrador (não pelo Cursor)
2. Navegue até o projeto:
   ```powershell
   cd "C:\Users\Adm-Sup\Documents\Github\vemcomer-core"
   ```
3. Execute o script de emergência:
   ```powershell
   .\scripts\emergency-disable-all-hooks.ps1
   ```

### Passo 2: Reiniciar Tudo

1. **Feche TODOS os terminais do PowerShell**
2. **Feche o Cursor completamente**
3. **Reabra o Cursor**
4. **Reabra um novo terminal**

### Passo 3: Verificar

Teste executando um comando simples:
```powershell
php -v
```

Se **NÃO** aparecer mais `[1/4] Analisando logs do servidor...`, o problema foi resolvido!

---

## 🔍 Se o Problema Persistir

Se ainda aparecer `[1/4] Analisando...` após seguir os passos acima, o problema está em:

### Opção A: Extensão do Cursor

1. Abra as configurações do Cursor (Ctrl+,)
2. Vá em "Extensions"
3. Procure por extensões relacionadas a:
   - "Auto execute"
   - "Command hooks"
   - "Terminal interceptors"
   - "Debug scripts"
   - "PowerShell hooks"
4. **Desabilite ou remova** essas extensões

### Opção B: Configurações do Cursor

1. Abra as configurações do Cursor (Ctrl+,)
2. Procure por:
   - `terminal.integrated.automationProfile`
   - `terminal.integrated.commandsToSkipShell`
   - `terminal.integrated.shellIntegration.enabled`
   - Qualquer configuração relacionada a "auto execute" ou "hooks"
3. **Desabilite** essas configurações

### Opção C: Profile do PowerShell

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
4. **Comente ou remova** essas linhas (adicione `#` no início)
5. Salve e feche
6. **Reinicie o PowerShell**

### Opção D: Scripts de Watch em Background

1. Abra o Gerenciador de Tarefas (Ctrl+Shift+Esc)
2. Vá na aba "Detalhes"
3. Procure por processos:
   - `powershell.exe`
   - `pwsh.exe`
   - Qualquer processo executando scripts `.ps1`
4. Se encontrar algum executando `auto-analyze`, **finalize o processo**

---

## 📋 Checklist de Verificação

Após executar o script de emergência, verifique:

- [ ] Profile do PowerShell limpo
- [ ] Funções problemáticas removidas
- [ ] Processos finalizados
- [ ] Arquivo de bloqueio criado
- [ ] PowerShell reiniciado
- [ ] Cursor reiniciado
- [ ] Comando `php -v` funciona sem interceptação

---

## 🎯 Resultado Esperado

Após seguir todos os passos, quando você executar qualquer comando:

- ✅ **NÃO** deve aparecer `[1/4] Analisando logs do servidor...`
- ✅ Comandos devem executar normalmente
- ✅ Sem lentidão extrema

---

## ⚠️ Importante

O script `auto-analyze-all-debug.ps1` ainda existe e pode ser executado **manualmente** quando necessário. O que foi desabilitado é a **execução automática** que estava interceptando todos os comandos.

Para executar manualmente (quando realmente precisar):
```powershell
.\scripts\auto-analyze-all-debug.ps1
```

Mas agora ele **não vai mais** analisar logs do servidor por padrão (já foi desabilitado anteriormente).

---

## 🆘 Se Nada Funcionar

Se após seguir todos os passos o problema persistir:

1. Verifique se há algum **serviço do Windows** executando scripts automaticamente
2. Verifique se há algum **agendador de tarefas** (Task Scheduler) executando scripts
3. Verifique se há algum **script de inicialização** no Windows executando automaticamente
4. Me informe o que encontrou e criaremos uma solução mais específica

