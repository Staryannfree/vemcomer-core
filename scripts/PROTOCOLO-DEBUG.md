# PROTOCOLO DE ANÁLISE DE DEBUG

## 🚨 TRIGGER AUTOMÁTICO: AAA

**REGRA CRÍTICA:** Se o usuário digitar **"AAA"** em qualquer mensagem, eu DEVO:

1. **Imediatamente** executar: `.\scripts\auto-analyze-all-debug.ps1`
2. **Imediatamente** ler: `debug-reports/CONSOLIDATED-ANALYSIS-*.txt` (mais recente)
3. **Analisar TODOS os logs** antes de responder
4. **SOMENTE DEPOIS** responder ao usuário

**AAA = "Analisa Automaticamente Agora"**

Esta é uma **REGRA OBRIGATÓRIA** com **MÁXIMA PRIORIDADE**.

---

## 🎯 OBJETIVO

Garantir que **SEMPRE** que o usuário reportar um problema, eu analise **TODOS** os arquivos de debug disponíveis antes de dar qualquer resposta.

## 📋 PROTOCOLO OBRIGATÓRIO

### Quando o usuário reportar um problema:

1. **SEMPRE execute primeiro:**
   ```powershell
   .\scripts\auto-analyze-all-debug.ps1
   ```

2. **SEMPRE leia o arquivo gerado:**
   ```
   debug-reports/CONSOLIDATED-ANALYSIS-*.txt (mais recente)
   ```

3. **SEMPRE verifique nesta ordem:**
   - ✅ Logs do servidor (PHP) - erros, warnings
   - ✅ Logs do navegador (JavaScript) - console errors, network failures
   - ✅ Estado do sistema (REST API) - variáveis, configurações
   - ✅ Requisições de rede - falhas, timeouts, 400/500 errors

4. **SEMPRE analise em conjunto:**
   - Não analise apenas um tipo de log
   - Correlacione erros do servidor com erros do navegador
   - Verifique se requisições de rede correspondem a erros no console

5. **SEMPRE busque por:**
   - Erros JavaScript não tratados
   - Requisições REST API com status >= 400
   - Erros PHP no debug.log
   - Warnings no vemcomer-debug.log
   - Problemas de performance (queries lentas, memória)

## 🔍 CHECKLIST DE ANÁLISE

Antes de responder ao usuário, verifique:

- [ ] Executei `auto-analyze-all-debug.ps1`?
- [ ] Li o arquivo CONSOLIDATED-ANALYSIS mais recente?
- [ ] Verifiquei logs do servidor (debug.log, vemcomer-debug.log)?
- [ ] Verifiquei logs do navegador (browser-logs-*.json)?
- [ ] Verifiquei requisições de rede (network-requests-*.json)?
- [ ] Verifiquei estado do sistema (api-full-state-*.json)?
- [ ] Correlacionei erros entre diferentes fontes?
- [ ] Identifiquei a causa raiz do problema?

## 📁 ARQUIVOS QUE DEVEM SER ANALISADOS

### 1. Logs do Servidor:
- `wp-content/debug.log` (WordPress)
- `wp-content/uploads/vemcomer-debug.log` (VemComer)

### 2. Logs do Navegador:
- `wp-content/uploads/vemcomer-browser-debug/browser-logs-*.json`
- `wp-content/uploads/vemcomer-browser-debug/network-requests-*.json`
- `wp-content/uploads/vemcomer-browser-debug/performance-*.json`

### 3. Estado do Sistema:
- `debug-reports/api-full-state-*.json`
- `debug-reports/api-globals-*.json`
- `debug-reports/api-current-user-*.json`
- `debug-reports/api-restaurant-state-*.json`

### 4. Análise Consolidada:
- `debug-reports/CONSOLIDATED-ANALYSIS-*.txt` (MAIS IMPORTANTE)

## 🚨 REGRAS CRÍTICAS

1. **SE O USUÁRIO DIGITAR "AAA"** → Seguir protocolo imediatamente (MÁXIMA PRIORIDADE)
2. **NUNCA** responda sem analisar os logs primeiro
3. **SEMPRE** use o script `auto-analyze-all-debug.ps1` para consolidar
4. **SEMPRE** leia o arquivo CONSOLIDATED-ANALYSIS mais recente
5. **SEMPRE** correlacione erros entre diferentes fontes
6. **SEMPRE** busque por padrões (mesmo erro repetido, mesma URL falhando, etc.)

## 💡 EXEMPLO DE USO

### Cenário: Usuário reporta "Categorias não aparecem no passo 5"

**Protocolo a seguir:**

1. Executar análise automática:
   ```powershell
   .\scripts\auto-analyze-all-debug.ps1
   ```

2. Ler arquivo consolidado:
   ```
   Ler: debug-reports/CONSOLIDATED-ANALYSIS-*.txt
   ```

3. Verificar especificamente:
   - Requisições para `/menu-categories` no network-requests
   - Erros JavaScript relacionados a `loadProductCategories`
   - Erros PHP no servidor relacionados a `Menu_Categories_Controller`
   - Estado do restaurante (tem categorias criadas?)
   - Logs do onboarding (passo 4 foi salvo?)

4. Correlacionar:
   - Se passo 4 foi salvo mas categorias não aparecem → problema no `get_categories`
   - Se requisição retorna 400/500 → verificar logs do servidor
   - Se JavaScript error → verificar stack trace

5. Responder com base na análise completa

## ✅ GARANTIA

**EU GARANTO** que seguirei este protocolo sempre que você reportar um problema.

Se eu não seguir, me lembre: "Analisa todos os logs primeiro usando o protocolo!"

---

**Última atualização:** 2025-12-03

