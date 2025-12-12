# Sistema de Debug Completo - VemComer Core

Este diretório contém scripts e ferramentas para coletar **TODAS** as informações necessárias para debug completo do plugin.

## 🚨 TRIGGER AUTOMÁTICO: AAA

**IMPORTANTE:** Se você digitar **"AAA"** em qualquer mensagem, o assistente será **OBRIGADO** a:

1. Executar análise automática de todos os logs
2. Ler o arquivo CONSOLIDATED-ANALYSIS mais recente
3. Analisar TODOS os logs antes de responder

**AAA = "Analisa Automaticamente Agora"**

Esta é uma **REGRA OBRIGATÓRIA** com **MÁXIMA PRIORIDADE**.

**Exemplo de uso:**
```
"AAA - As categorias não aparecem no passo 5"
```

O assistente DEVE analisar todos os logs antes de responder.

## 📋 Scripts Disponíveis

### 1. `collect-full-debug.ps1` - Coleta Completa de Debug

**Uso:**
```powershell
.\scripts\collect-full-debug.ps1
.\scripts\collect-full-debug.ps1 -IncludeDatabase
.\scripts\collect-full-debug.ps1 -IncludeCache -LogLines 1000
```

**O que coleta:**
- ✅ Informações do sistema (Windows, PowerShell, caminhos)
- ✅ Configurações do WordPress (wp-config.php)
- ✅ Versões (WordPress, plugin, PHP)
- ✅ Plugins instalados
- ✅ Logs do WordPress (debug.log)
- ✅ Logs do VemComer (vemcomer-debug.log)
- ✅ Estado do banco de dados (queries SQL) - opcional
- ✅ Configurações do plugin
- ✅ Estrutura de arquivos importantes
- ✅ Cache e transients - opcional
- ✅ Endpoints REST API

**Saída:** Arquivo em `debug-reports/full-debug-YYYY-MM-DD-HHmmss.txt`

---

### 2. `monitor-logs-realtime.ps1` - Monitor em Tempo Real

**Uso:**
```powershell
.\scripts\monitor-logs-realtime.ps1
```

**O que faz:**
- Monitora `debug.log` e `vemcomer-debug.log` em tempo real
- Mostra novas linhas conforme são escritas
- Pressione `Ctrl+C` para parar

**Útil para:** Ver erros enquanto testa o wizard de onboarding

---

### 3. `export-database-state.ps1` - Exportar Estado do Banco

**Uso:**
```powershell
.\scripts\export-database-state.ps1
```

**O que faz:**
- Gera queries SQL para verificar o estado completo do banco
- Inclui contagens, verificações de onboarding, categorias, produtos, etc.

**Saída:** Arquivo em `debug-reports/database-state-YYYY-MM-DD-HHmmss.sql`

**Próximos passos:**
1. No Local: Clique direito no site → Database → Open Adminer
2. Execute as queries do arquivo gerado
3. Copie os resultados e compartilhe

---

## 🔍 O que o Assistente Pode Acessar

### ✅ Acesso Direto (via ferramentas):
- Arquivos do plugin (`vemcomer-core/`)
- Arquivos do tema (`theme-vemcomer/`)
- Logs (`debug.log`, `vemcomer-debug.log`)
- Configurações (`wp-config.php`)
- Estrutura de pastas
- Código-fonte completo

### ❌ Não Acesso Direto (precisa compartilhar):
- Console do navegador (JavaScript errors)
- Network requests (REST API responses)
- Sessões do usuário
- Banco de dados (precisa de queries SQL)
- Cache do WordPress

---

## 🚀 Fluxo de Debug Recomendado

### Quando encontrar um problema:

1. **Coletar informações completas:**
   ```powershell
   .\scripts\collect-full-debug.ps1 -IncludeDatabase
   ```

2. **Se for problema de banco de dados:**
   ```powershell
   .\scripts\export-database-state.ps1
   ```
   Execute as queries no Adminer e compartilhe os resultados

3. **Se for problema em tempo real:**
   ```powershell
   .\scripts\monitor-logs-realtime.ps1
   ```
   Deixe rodando enquanto reproduz o problema

4. **Compartilhar com o assistente:**
   - Opção 1: Copie o conteúdo do arquivo gerado e cole aqui
   - Opção 2: Peça: "Lê o relatório de debug mais recente"
   - Opção 3: Para logs específicos: "Mostra os últimos erros do debug.log"

---

## 📊 Melhorias de Logging

O arquivo `enhance-debug-logging.php` adiciona:
- Captura de todos os erros PHP
- Logging de requisições REST API
- Detecção de queries SQL lentas
- Logging de hooks do WordPress (opcional)

**Para ativar:**
1. O arquivo já está incluído no `wp-config.php` se existir
2. Para ativar logging de hooks, adicione no `wp-config.php`:
   ```php
   define('VC_DEBUG_HOOKS', true);
   ```

---

## 🎯 Casos de Uso Específicos

### Debug do Wizard de Onboarding:

1. Execute o monitor em tempo real:
   ```powershell
   .\scripts\monitor-logs-realtime.ps1
   ```

2. Reproduza o problema (Passo 1 → Passo 4 → Passo 5)

3. Compartilhe:
   - Logs do console do navegador (F12)
   - Network requests (aba Network, filtre por `onboarding`)
   - Logs do PowerShell que apareceram

### Debug de Categorias Não Aparecendo:

1. Coletar estado completo:
   ```powershell
   .\scripts\collect-full-debug.ps1 -IncludeDatabase
   ```

2. Exportar estado do banco:
   ```powershell
   .\scripts\export-database-state.ps1
   ```

3. Verificar no Adminer:
   - Execute a query "VERIFICAR CATEGORIAS DE CARDÁPIO"
   - Execute a query "VERIFICAR META DE ONBOARDING"
   - Compartilhe os resultados

---

## 📁 Estrutura de Arquivos Gerados

```
vemcomer-core/
├── debug-reports/
│   ├── full-debug-2025-12-03-201530.txt
│   ├── database-state-2025-12-03-201530.sql
│   └── ...
└── scripts/
    ├── collect-full-debug.ps1
    ├── monitor-logs-realtime.ps1
    ├── export-database-state.ps1
    └── enhance-debug-logging.php
```

---

## 💡 Dicas

1. **Sempre execute `collect-full-debug.ps1` primeiro** - ele coleta tudo de uma vez
2. **Use `monitor-logs-realtime.ps1` durante testes** - vê erros na hora
3. **Compartilhe screenshots do console** - JavaScript errors são importantes
4. **Execute queries SQL e compartilhe resultados** - estado do banco é crucial
5. **Mantenha os logs organizados** - delete relatórios antigos periodicamente

---

## 🔧 Troubleshooting

### Script não executa:
- Execute PowerShell como Administrador
- Verifique se os caminhos estão corretos

### Logs não aparecem:
- Verifique se `WP_DEBUG` está `true` no `wp-config.php`
- Verifique se `VC_DEBUG` está definido

### Queries SQL não funcionam:
- Verifique o prefixo das tabelas (pode não ser `wp_`)
- Ajuste as queries no arquivo gerado

---

## 📞 Como Compartilhar com o Assistente

1. **Relatório completo:**
   ```
   "Lê o relatório de debug mais recente"
   ```

2. **Logs específicos:**
   ```
   "Mostra os últimos 100 erros do debug.log"
   "Mostra os logs do VemComer das últimas 2 horas"
   ```

3. **Arquivo específico:**
   ```
   "Lê o arquivo X do plugin"
   "Verifica o conteúdo do arquivo Y"
   ```

4. **Estado do banco:**
   ```
   "Executei as queries SQL, aqui estão os resultados: [cole aqui]"
   ```

---

## 🔬 Coleta via REST API (NOVO)

O sistema agora inclui endpoints REST para capturar variáveis em tempo real:

### Endpoints disponíveis:

- `GET /wp-json/vemcomer/v1/debug/state` - Estado completo do sistema
- `GET /wp-json/vemcomer/v1/debug/globals` - Variáveis globais
- `GET /wp-json/vemcomer/v1/debug/current-user` - Dados do usuário atual
- `GET /wp-json/vemcomer/v1/debug/restaurant-state` - Estado do restaurante
- `GET /wp-json/vemcomer/v1/debug/hooks` - Hooks registrados
- `GET /wp-json/vemcomer/v1/debug/rest-routes` - Rotas REST API
- `GET /wp-json/vemcomer/v1/debug/phpinfo` - PHP Info completo

### Como usar:

```powershell
.\scripts\collect-everything-via-api.ps1
```

Isso vai coletar TODAS as variáveis via REST API e salvar em JSON.

### Ou via WP-CLI:

```bash
wp eval-file scripts/wp-cli-debug.php
```

### Ou criar snapshot completo:

```powershell
.\scripts\create-snapshot.ps1
```

Isso executa TODOS os scripts e cria um índice consolidado.

### O que é capturado via API:

- ✅ Todas as variáveis globais (`$wpdb`, `$wp_query`, `$post`, etc.)
- ✅ Todas as constantes (WordPress + VemComer)
- ✅ Todas as opções do WordPress
- ✅ Todo o meta do usuário atual
- ✅ Todo o meta do restaurante atual
- ✅ Todos os termos de taxonomias com seus meta
- ✅ Todos os hooks registrados (filtrados por `vemcomer`/`vc_`)
- ✅ Todas as rotas REST API do VemComer
- ✅ Todos os Custom Post Types do VemComer
- ✅ Todas as Taxonomies do VemComer
- ✅ Performance metrics (queries, memória, tempo)
- ✅ Estado do PHP (versão, extensões, configurações)
- ✅ Transients ativos
- ✅ PHP Info completo (HTML)

---

## 🎯 PROTOCOLO OBRIGATÓRIO DE ANÁLISE

**IMPORTANTE:** Quando você reportar um problema, eu vou **SEMPRE** seguir este protocolo:

### 1. Executar análise automática:
```powershell
.\scripts\auto-analyze-all-debug.ps1
```

### 2. Ler análise consolidada:
```
Ler: debug-reports/CONSOLIDATED-ANALYSIS-*.txt (mais recente)
```

### 3. Verificar todos os logs:
- ✅ Logs do servidor (PHP)
- ✅ Logs do navegador (JavaScript)
- ✅ Estado do sistema (REST API)
- ✅ Requisições de rede

### 4. Correlacionar erros:
- Erros do servidor ↔ Erros do navegador
- Requisições REST ↔ Respostas do servidor
- Console errors ↔ Network failures

### Como garantir que eu siga o protocolo:

**Opção 1 (Recomendada):**
```
"Analisa todos os logs usando o protocolo"
```

**Opção 2:**
```
"Lê a análise consolidada mais recente"
```

**Opção 3:**
```
Execute: .\scripts\auto-analyze-all-debug.ps1
E depois: "Lê o arquivo CONSOLIDATED-ANALYSIS mais recente"
```

### Verificar arquivos disponíveis:

```powershell
.\scripts\list-all-debug-files.ps1
```

Isso lista todos os arquivos que devem ser analisados.

---

**Última atualização:** 2025-12-03

