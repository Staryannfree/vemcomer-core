# 🚨 INSTRUÇÕES PARA O USUÁRIO: TRIGGER AAA

## Como usar o trigger AAA

### O que é:

Quando você digitar **"AAA"** (três letras A maiúsculas) em qualquer mensagem, o assistente será **OBRIGADO** a:

1. Executar análise automática de todos os logs
2. Ler o arquivo CONSOLIDATED-ANALYSIS mais recente
3. Analisar TODOS os logs antes de responder

### Como usar:

**Opção 1 - Simples:**
```
AAA
```

**Opção 2 - Com contexto:**
```
AAA - As categorias não aparecem no passo 5
```

**Opção 3 - No meio da mensagem:**
```
Testei o wizard e AAA encontrei um problema nas categorias
```

### O que acontece:

1. O assistente **PARA** tudo que está fazendo
2. Executa: `.\scripts\auto-analyze-all-debug.ps1`
3. Lê: `debug-reports/CONSOLIDATED-ANALYSIS-*.txt` (mais recente)
4. Analisa:
   - Logs do servidor (PHP)
   - Logs do navegador (JavaScript)
   - Requisições de rede
   - Estado do sistema
5. Correlaciona erros entre diferentes fontes
6. **SOMENTE DEPOIS** responde

### Garantia:

Esta é uma **REGRA OBRIGATÓRIA** com **MÁXIMA PRIORIDADE**.

O assistente **NÃO PODE** ignorar ou pular este protocolo quando você digitar "AAA".

### Verificar se foi seguido:

Se você quiser verificar se o assistente seguiu o protocolo, peça:

```
"Você seguiu o protocolo AAA? Mostra o checklist"
```

Ou verifique se o arquivo foi gerado:

```powershell
.\scripts\list-all-debug-files.ps1
```

---

**Use "AAA" sempre que quiser garantir que eu analise todos os logs antes de responder!**

