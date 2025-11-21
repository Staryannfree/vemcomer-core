# Visualização dos Steps do Onboarding

Este documento mostra como cada step do onboarding aparece quando está ativo/completo.

---

## Step 1: Bem-vindo ao VemComer! ✅

**Quando está ativo:**
- **Título:** "Bem-vindo ao VemComer!"
- **Descrição:** "Vamos configurar seu restaurante em poucos passos."
- **Conteúdo especial:** Banner azul com texto: "Vamos começar configurando seu restaurante para receber pedidos!"
- **Botão de ação:** "Começar" (azul, no rodapé)
- **Ícone no step:** → (seta azul, indicando que é o atual)

**Quando está completo:**
- **Ícone no step:** ✓ (check verde)
- **Cor do texto:** Verde (#059669)
- **Background do ícone:** Verde claro (#d1fae5)

---

## Step 2: Complete seu perfil 📝

**Quando está ativo:**
- **Título:** "Complete seu perfil"
- **Descrição:** "Adicione informações importantes como horários, telefone e endereço."
- **Botão de ação:** "Editar restaurante" (azul, abre em nova aba)
- **Ícone no step:** → (seta azul)
- **Botão no rodapé:** "Concluído" (azul)

**Verificação automática:**
O sistema verifica se os seguintes campos estão preenchidos:
- `vc_restaurant_whatsapp` (WhatsApp)
- `vc_restaurant_address` (Endereço)
- `vc_restaurant_open_hours` (Horários)

**Quando está completo:**
- **Ícone no step:** ✓ (check verde)
- **Status:** Verde, indicando que o perfil está completo

---

## Step 3: Adicione itens ao cardápio 🍽️

**Quando está ativo:**
- **Título:** "Adicione itens ao cardápio"
- **Descrição:** "Crie pelo menos 3 itens para começar a receber pedidos."
- **Botão de ação:** "Gerenciar cardápio" (azul, abre em nova aba)
- **Ícone no step:** → (seta azul)
- **Botão no rodapé:** "Concluído" (azul)

**Verificação automática:**
O sistema verifica se há pelo menos **3 itens de cardápio** publicados vinculados ao restaurante.

**Quando está completo:**
- **Ícone no step:** ✓ (check verde)
- **Status:** Verde, indicando que há itens suficientes no cardápio

---

## Step 4: Configure delivery 🚚

**Quando está ativo:**
- **Título:** "Configure delivery"
- **Descrição:** "Defina se oferece delivery e valores de entrega."
- **Botão de ação:** "Editar restaurante" (azul, abre em nova aba)
- **Ícone no step:** → (seta azul)
- **Botão no rodapé:** "Concluído" (azul)

**Verificação automática:**
O sistema verifica se o campo `vc_restaurant_delivery` está configurado (não vazio).

**Quando está completo:**
- **Ícone no step:** ✓ (check verde)
- **Status:** Verde, indicando que delivery está configurado

---

## Step 5: Veja sua página pública 👁️

**Quando está ativo:**
- **Título:** "Veja sua página pública"
- **Descrição:** "Confira como os clientes veem seu restaurante."
- **Botão de ação:** "Ver página pública" (azul, abre em nova aba)
- **Ícone no step:** → (seta azul)
- **Botão no rodapé:** "Concluído" (azul)

**Verificação automática:**
Este step é completado manualmente quando o usuário clica em "Concluído".

**Quando está completo:**
- **Ícone no step:** ✓ (check verde)
- **Status:** Verde, indicando que o usuário visualizou a página

---

## Tela de Conclusão 🎉

**Quando todos os steps estão completos:**

O modal mostra uma tela especial de conclusão:

```
┌─────────────────────────────────────┐
│         🎉 (emoji grande)           │
│                                     │
│  Parabéns! Você completou a        │
│  configuração inicial.              │
│                                     │
│  Seu restaurante está pronto para  │
│  receber pedidos!                   │
└─────────────────────────────────────┘
```

- **Animação:** Emoji de celebração (🎉)
- **Mensagem:** "Parabéns! Você completou a configuração inicial."
- **Submensagem:** "Seu restaurante está pronto para receber pedidos!"
- **Ação automática:** O modal fecha automaticamente após 3 segundos
- **Footer:** Oculto na tela de conclusão

---

## Barra de Progresso 📊

A barra de progresso aparece no topo do modal e mostra:

- **0%** - Nenhum step completo
- **20%** - 1 step completo (Bem-vindo)
- **40%** - 2 steps completos (+ Complete seu perfil)
- **60%** - 3 steps completos (+ Adicione itens ao cardápio)
- **80%** - 4 steps completos (+ Configure delivery)
- **100%** - Todos os steps completos (+ Veja sua página pública)

**Visual:**
```
[████████████░░░░░░░░] 60% completo
```

A barra tem um gradiente azul/roxo e animação suave ao atualizar.

---

## Estados Visuais dos Steps

### Step Pendente (○)
- **Ícone:** ○ (círculo cinza)
- **Cor do texto:** Cinza (#6b7280)
- **Background do ícone:** Cinza claro (#f3f4f6)
- **Status:** Ainda não iniciado

### Step Atual (→)
- **Ícone:** → (seta azul)
- **Cor do texto:** Azul (#3b82f6) e negrito
- **Background do ícone:** Azul claro (#dbeafe)
- **Status:** Step ativo no momento

### Step Completo (✓)
- **Ícone:** ✓ (check verde)
- **Cor do texto:** Verde (#059669)
- **Background do ícone:** Verde claro (#d1fae5)
- **Status:** Step concluído com sucesso

---

## Funcionalidades por Step

### Navegação
- **Botão "Começar":** Apenas no step de boas-vindas, avança para o próximo step
- **Botão "Concluído":** Marca o step atual como completo e avança automaticamente
- **Botão "Pular por enquanto":** Dispensa o onboarding (pode ser retomado depois)

### Ações
- **Botões de ação:** Abrem em nova aba (`target="_blank"`)
- **Links diretos:** Levam o usuário para a página específica de cada ação
- **Verificação automática:** Alguns steps são verificados automaticamente ao carregar

### Persistência
- **Progresso salvo:** Cada step completado é salvo no banco de dados
- **Retomada:** O usuário pode fechar e retomar de onde parou
- **Dispensar:** O usuário pode pular e o onboarding não aparecerá novamente (até ser resetado)

---

## Exemplo de Fluxo Completo

1. **Usuário acessa o painel pela primeira vez**
   → Modal aparece com step "Bem-vindo"

2. **Usuário clica em "Começar"**
   → Avança para "Complete seu perfil"
   → Barra de progresso: 20%

3. **Usuário clica em "Editar restaurante"**
   → Abre página de edição em nova aba
   → Preenche WhatsApp, endereço e horários
   → Volta ao painel

4. **Sistema verifica automaticamente**
   → Detecta que o perfil está completo
   → Avança para "Adicione itens ao cardápio"
   → Barra de progresso: 40%

5. **Usuário clica em "Gerenciar cardápio"**
   → Abre página de cardápio em nova aba
   → Adiciona 3+ itens
   → Volta ao painel

6. **Sistema verifica automaticamente**
   → Detecta que há 3+ itens
   → Avança para "Configure delivery"
   → Barra de progresso: 60%

7. **Usuário configura delivery**
   → Sistema detecta automaticamente
   → Avança para "Veja sua página pública"
   → Barra de progresso: 80%

8. **Usuário visualiza página pública**
   → Clica em "Concluído"
   → Sistema marca como completo
   → Barra de progresso: 100%

9. **Tela de conclusão**
   → Mostra mensagem de parabéns
   → Fecha automaticamente após 3 segundos
   → Onboarding nunca mais aparece para este usuário

---

## Código de Verificação

Cada step tem uma função de verificação que pode ser chamada automaticamente:

```php
// Verifica se perfil está completo
check_profile_complete($restaurant)

// Verifica se há itens no cardápio
check_menu_items_count($restaurant)

// Verifica se delivery está configurado
check_delivery_configured($restaurant)
```

Essas verificações são executadas ao calcular o progresso e podem marcar steps como completos automaticamente.

