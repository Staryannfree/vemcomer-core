# 🚨 MEMÓRIA PERMANENTE: TRIGGER AAA

## REGRA CRÍTICA E OBRIGATÓRIA

**Quando o usuário digitar "AAA" (três letras A maiúsculas) em qualquer mensagem:**

1. **PARAR tudo que estou fazendo**
2. **Imediatamente executar:** `.\scripts\auto-analyze-all-debug.ps1`
3. **Imediatamente ler:** `debug-reports/CONSOLIDATED-ANALYSIS-*.txt` (arquivo mais recente)
4. **Analisar TODOS os logs:**
   - Logs do servidor (PHP)
   - Logs do navegador (JavaScript)
   - Estado do sistema (REST API)
   - Requisições de rede
5. **Correlacionar erros** entre diferentes fontes
6. **SOMENTE DEPOIS** responder ao usuário

## SIGNIFICADO

**AAA = "Analisa Automaticamente Agora"**

## PRIORIDADE

**MÁXIMA PRIORIDADE** - Fazer ANTES de qualquer outra coisa!

## CHECKLIST OBRIGATÓRIO

Quando ver "AAA", verificar:
- [ ] Executei `auto-analyze-all-debug.ps1`?
- [ ] Li o arquivo CONSOLIDATED-ANALYSIS mais recente?
- [ ] Verifiquei logs do servidor?
- [ ] Verifiquei logs do navegador?
- [ ] Verifiquei requisições de rede?
- [ ] Verifiquei estado do sistema?
- [ ] Correlacionei erros entre diferentes fontes?
- [ ] Identifiquei a causa raiz?

## EXEMPLO

**Usuário:** "AAA - As categorias não aparecem no passo 5"

**Eu (obrigado a fazer):**
1. Executar: `.\scripts\auto-analyze-all-debug.ps1`
2. Ler: `CONSOLIDATED-ANALYSIS-*.txt`
3. Analisar todos os logs
4. Correlacionar erros
5. Responder com base na análise completa

---

**Esta é uma REGRA PERMANENTE. Não pode ser ignorada ou esquecida.**

