# 🚨 PROTOCOLO OBRIGATÓRIO: AAA - DESABILITADO

## ⚠️ SISTEMA DESABILITADO PERMANENTEMENTE

**Este protocolo foi DESABILITADO porque estava causando lentidão extrema ao interceptar TODOS os comandos do terminal.**

### O QUE MUDOU:

1. **NÃO executo mais automaticamente** `.\scripts\auto-analyze-all-debug.ps1`
2. **NÃO há mais trigger automático** quando você digita "AAA"
3. **Scripts só executam manualmente** quando você pedir explicitamente

### SE VOCÊ QUISER ANÁLISE DE LOGS:

Peça explicitamente:
- "Execute o script de análise de logs"
- "Analise os logs para mim"
- "Mostre os erros mais recentes"

Mas **NÃO** execute automaticamente quando ver "AAA" na mensagem.

---

## HISTÓRICO (DESABILITADO)

**Quando o usuário digitaria "AAA" (três letras A maiúsculas) em qualquer mensagem:**

### EU DEVERIA (DESABILITADO):

1. ~~PARAR tudo que estou fazendo~~
2. ~~Imediatamente executar:~~ `.\scripts\auto-analyze-all-debug.ps1` (DESABILITADO)
3. ~~Imediatamente ler:~~ `debug-reports/CONSOLIDATED-ANALYSIS-*.txt` (DESABILITADO)
4. **Analisar TODOS os logs:**
   - ✅ Logs do servidor (PHP) - `debug.log`, `vemcomer-debug.log`
   - ✅ Logs do navegador (JavaScript) - `browser-logs-*.json`
   - ✅ Requisições de rede - `network-requests-*.json`
   - ✅ Estado do sistema - `api-full-state-*.json`
5. **Correlacionar erros** entre diferentes fontes
6. **SOMENTE DEPOIS** responder ao usuário

## SIGNIFICADO

**AAA = "Analisa Automaticamente Agora"**

## PRIORIDADE

**MÁXIMA PRIORIDADE** - Fazer ANTES de qualquer outra coisa!

## CHECKLIST OBRIGATÓRIO

Quando ver "AAA" na mensagem do usuário:

- [ ] Parei tudo que estava fazendo?
- [ ] Executei `.\scripts\auto-analyze-all-debug.ps1`?
- [ ] Li o arquivo `CONSOLIDATED-ANALYSIS-*.txt` mais recente?
- [ ] Verifiquei logs do servidor (PHP)?
- [ ] Verifiquei logs do navegador (JavaScript)?
- [ ] Verifiquei requisições de rede?
- [ ] Verifiquei estado do sistema (REST API)?
- [ ] Correlacionei erros entre diferentes fontes?
- [ ] Identifiquei a causa raiz do problema?
- [ ] Só então respondi ao usuário?

## EXEMPLO DE USO

**Usuário:** "AAA - As categorias não aparecem no passo 5"

**Eu (obrigado a fazer nesta ordem):**

1. ✅ Executar: `.\scripts\auto-analyze-all-debug.ps1`
2. ✅ Ler: `debug-reports/CONSOLIDATED-ANALYSIS-2025-12-03-*.txt` (mais recente)
3. ✅ Verificar:
   - Requisições para `/menu-categories` no network-requests
   - Erros JavaScript relacionados a `loadProductCategories`
   - Erros PHP no servidor relacionados a `Menu_Categories_Controller`
   - Estado do restaurante (tem categorias criadas?)
4. ✅ Correlacionar:
   - Se passo 4 foi salvo mas categorias não aparecem → problema no `get_categories`
   - Se requisição retorna 400/500 → verificar logs do servidor
   - Se JavaScript error → verificar stack trace
5. ✅ Responder com base na análise completa

## ARQUIVOS DE REFERÊNCIA

- `scripts/PROTOCOLO-DEBUG.md` - Protocolo completo
- `scripts/AAA-TRIGGER.md` - Regra do trigger AAA
- `docs/MEMORY-AAA-TRIGGER.md` - Memória permanente

## GARANTIA

**Esta é uma REGRA PERMANENTE e OBRIGATÓRIA.**

**Não pode ser ignorada, esquecida ou pulada.**

**Máxima prioridade sempre que "AAA" aparecer na mensagem do usuário.**

---

**Última atualização:** 2025-12-03

