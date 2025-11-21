# Análise de Integração: Shortcodes ↔ Backend

**Data:** 2025-11-21  
**Objetivo:** Verificar se todas as funcionalidades do backend estão integradas com os shortcodes e identificar lacunas.

---

## 📋 Resumo Executivo

### ✅ Funcionalidades Integradas
- Sistema de checkout básico (carrinho, frete, pedidos)
- REST API endpoints funcionando
- Shortcodes básicos de listagem funcionando

### ❌ Funcionalidades Faltando nos Shortcodes
1. **Modificadores de Produtos** - Modal não existe no frontend
2. **Ratings e Reviews** - Não exibidos nos cards de restaurante
3. **Status de Disponibilidade** - Não mostrado (aberto/fechado)
4. **Horários Estruturados** - Ainda usando campo texto antigo
5. **Favoritos** - Sem botão/interação no frontend
6. **Banners** - Sem shortcode para exibir
7. **Busca Avançada** - Filtros limitados
8. **WhatsApp Message Formatter** - Não integrado no checkout
9. **Validação de Pedido** - Não chamada antes do WhatsApp
10. **Endereços de Entrega** - Sem interface no checkout
11. **Múltiplos Métodos de Fulfillment** - Não exibidos como opções
12. **Cupons** - Lógica hardcoded no JS, não usa REST API

---

## 🔍 Análise Detalhada por Shortcode

### 1. `[vc_restaurant]` (restaurant-card.php)

**Status:** ⚠️ **Parcialmente Integrado**

**O que funciona:**
- Exibe endereço, horários (texto), delivery status
- Exibe taxonomias (cuisine, location)

**O que falta:**
- ❌ **Rating/Reviews**: Não exibe estrelas ou avaliações
- ❌ **Status Aberto/Fechado**: Não mostra se está aberto agora
- ❌ **Horários Estruturados**: Usa `vc_restaurant_open_hours` (texto) ao invés de `_vc_restaurant_schedule` (JSON)
- ❌ **Botão de Favorito**: Sem interação para adicionar aos favoritos
- ❌ **ETA**: Não mostra tempo estimado de entrega
- ❌ **Imagens Otimizadas**: Não usa diferentes tamanhos de thumbnail

**Código atual:**
```php
$hours = get_post_meta( $pid, 'vc_restaurant_open_hours', true ); // ❌ Campo antigo
```

**Deve usar:**
```php
use VC\Utils\Schedule_Helper;
use VC\Utils\Rating_Helper;
use VC\Utils\Availability_Helper;

$schedule = Schedule_Helper::get_schedule( $pid );
$rating = Rating_Helper::get_rating( $pid );
$availability = Availability_Helper::check_availability( $pid );
$is_open = Schedule_Helper::is_open( $pid );
```

---

### 2. `[vc_restaurants]` (restaurants-grid.php)

**Status:** ⚠️ **Parcialmente Integrado**

**O que funciona:**
- Filtros básicos (cuisine, location, delivery)
- Busca por texto
- Paginação

**O que falta:**
- ❌ **Filtro por Rating Mínimo**: Parâmetro `min_rating` não implementado
- ❌ **Filtro "Aberto Agora"**: Parâmetro `is_open_now` não implementado
- ❌ **Filtro por Faixa de Preço**: Parâmetro `price_range` não implementado
- ❌ **Busca Full-Text Avançada**: Não busca em itens do cardápio
- ❌ **Exibição de Rating**: Cards não mostram estrelas/avaliações
- ❌ **Status Aberto/Fechado**: Não indica visualmente

**Código atual:**
```php
$q = new WP_Query([
    's' => sanitize_text_field( $a['search'] ), // ❌ Busca limitada
    // ❌ Sem filtro min_rating
    // ❌ Sem filtro is_open_now
    // ❌ Sem filtro price_range
]);
```

**Deve usar REST API:**
```php
// Usar GET /wp-json/vemcomer/v1/restaurants com parâmetros:
// ?min_rating=4&is_open_now=true&price_range_min=10&price_range_max=50
```

---

### 3. `[vc_menu_items]` (menu-items.php)

**Status:** ⚠️ **Parcialmente Integrado**

**O que funciona:**
- Lista itens do cardápio
- Exibe preço, descrição, tempo de preparo
- Exibe categorias
- Indica disponibilidade

**O que falta:**
- ❌ **Modal de Produto**: Não existe modal para selecionar modificadores
- ❌ **Modificadores**: Não carrega ou exibe modificadores disponíveis
- ❌ **Botão "Adicionar" com Modificadores**: Botão atual não abre modal
- ❌ **Favoritos**: Sem botão para favoritar item
- ❌ **Imagens Otimizadas**: Não usa diferentes tamanhos

**Código atual:**
```php
// ❌ Não carrega modificadores
// ❌ Não tem modal
// ❌ Botão simples sem interação com modificadores
echo '<button class="vc-btn vc-add" data-id="...">Adicionar</button>';
```

**Deve ter:**
```javascript
// Modal com modificadores
// Carregar via: GET /wp-json/vemcomer/v1/menu-items/{id}/modifiers
// Validar modificadores obrigatórios antes de adicionar ao carrinho
```

---

### 4. `[vemcomer_menu]` (Shortcodes.php)

**Status:** ⚠️ **Parcialmente Integrado**

**O que funciona:**
- Lista itens do cardápio
- Botão "Adicionar" básico

**O que falta:**
- ❌ **Modal de Produto**: Mesma lacuna do `[vc_menu_items]`
- ❌ **Modificadores**: Não integrado
- ❌ **Categorias Ordenadas**: Não respeita `_vc_category_order`
- ❌ **Agrupamento por Categoria**: Não agrupa itens por categoria

---

### 5. `[vemcomer_checkout]` (Shortcodes.php)

**Status:** ⚠️ **Parcialmente Integrado**

**O que funciona:**
- Carrinho persistente (localStorage)
- Cálculo de frete básico
- Criação de pedido

**O que falta:**
- ❌ **Múltiplos Métodos de Fulfillment**: Não exibe opções (Delivery vs Pickup)
- ❌ **Validação de Pedido**: Não chama `/orders/validate` antes de finalizar
- ❌ **WhatsApp Message Formatter**: Não gera mensagem formatada
- ❌ **Endereços de Entrega**: Sem interface para selecionar/gerenciar endereços
- ❌ **Geolocalização**: Não usa lat/lng para cálculo de frete
- ❌ **Cupons REST API**: Usa lógica hardcoded, não chama `/coupons/validate`
- ❌ **Modificadores no Carrinho**: Não exibe modificadores selecionados
- ❌ **ETA Dinâmico**: Não mostra tempo estimado de entrega

**Código atual (frontend.js):**
```javascript
// ❌ Cupons hardcoded
const rules = {
  'DESC10': { type:'percent', value:10 },
  'DESC5': { type:'money', value:5.00 },
  // ...
};

// ❌ Não valida pedido antes
// ❌ Não gera mensagem WhatsApp
// ❌ Não exibe múltiplos métodos
```

**Deve usar:**
```javascript
// 1. Validar pedido: POST /orders/validate
// 2. Gerar mensagem: POST /orders/prepare-whatsapp
// 3. Validar cupom: POST /coupons/validate
// 4. Listar endereços: GET /addresses
// 5. Exibir métodos: GET /shipping/quote retorna array de methods
```

---

### 6. `[vc_restaurants_map]` (restaurants-map.php)

**Status:** ✅ **Bem Integrado**

**O que funciona:**
- Mapa com pins
- Busca por localização
- Filtro por raio

**O que falta:**
- ❌ **Status Aberto/Fechado nos Pins**: Não indica visualmente
- ❌ **Rating nos Pins**: Não exibe estrelas

---

### 7. `[vc_filters]` (filters.php)

**Status:** ⚠️ **Básico**

**O que funciona:**
- Filtros básicos (cuisine, location, delivery, search)

**O que falta:**
- ❌ **Filtro por Rating**: Sem campo para rating mínimo
- ❌ **Filtro "Aberto Agora"**: Sem checkbox
- ❌ **Filtro por Faixa de Preço**: Sem campos min/max
- ❌ **Filtro por Método de Fulfillment**: Sem opção (Delivery/Pickup)

---

## 🔧 Análise do JavaScript Frontend

### `assets/js/frontend.js`

**Lacunas encontradas:**

1. **Cupons Hardcoded** (linhas 45-63)
   - ❌ Não usa REST API `/coupons/validate`
   - ❌ Regras fixas no código

2. **Checkout sem Validação** (linhas 157-197)
   - ❌ Não chama `/orders/validate` antes de criar pedido
   - ❌ Não gera mensagem WhatsApp formatada
   - ❌ Não exibe modificadores no carrinho

3. **Frete Limitado** (linhas 100-155)
   - ❌ Não exibe múltiplos métodos (só pega o primeiro)
   - ❌ Não permite escolher entre Delivery/Pickup
   - ❌ Não usa geolocalização (lat/lng)

4. **Sem Modal de Produto**
   - ❌ Não existe código para modal
   - ❌ Não carrega modificadores via REST API
   - ❌ Não valida modificadores obrigatórios

5. **Sem Integração de Favoritos**
   - ❌ Não há botões de favorito
   - ❌ Não chama endpoints `/favorites/*`

6. **Sem Integração de Reviews**
   - ❌ Não exibe reviews
   - ❌ Não permite criar review

---

## 📝 Funcionalidades Backend Não Expostas

### 1. **Sistema de Banners** (#8)
- ❌ Sem shortcode `[vc_banners]` ou `[vemcomer_banners]`
- ❌ REST API existe mas não é usada no frontend

### 2. **Sistema de Notificações** (#15)
- ❌ Sem interface para exibir notificações
- ❌ REST API existe mas não é consumida

### 3. **Sistema de Histórico de Pedidos** (#6)
- ❌ Sem página/shortcode para listar pedidos do usuário
- ❌ REST API existe mas não é usada

### 4. **Sistema de Analytics** (#7)
- ❌ Tracking automático funciona (middleware)
- ❌ Mas não há dashboard público ou widgets

### 5. **Sistema de Planos/Assinaturas** (#9)
- ❌ Sem interface para exibir plano atual
- ❌ Sem validação de limites no frontend (só no backend)

---

## 🎯 Priorização de Correções

### 🔴 CRÍTICO (Bloqueia funcionalidades core)
1. **Modal de Produto com Modificadores**
   - Criar modal HTML/CSS/JS
   - Integrar com `/menu-items/{id}/modifiers`
   - Validar modificadores obrigatórios
   - Adicionar modificadores ao carrinho

2. **WhatsApp Message Formatter no Checkout**
   - Chamar `/orders/prepare-whatsapp`
   - Abrir WhatsApp com mensagem formatada
   - Remover criação de pedido direto (usar validação primeiro)

3. **Validação de Pedido**
   - Chamar `/orders/validate` antes de finalizar
   - Exibir erros de validação
   - Bloquear checkout se inválido

4. **Múltiplos Métodos de Fulfillment**
   - Exibir opções (Delivery/Pickup) no checkout
   - Permitir escolha do usuário
   - Atualizar cálculo de frete baseado na escolha

### 🟡 ALTA (Melhora UX significativamente)
5. **Ratings e Reviews nos Cards**
   - Exibir estrelas e contagem
   - Adicionar seção de reviews na página do restaurante
   - Permitir criar review após pedido

6. **Status Aberto/Fechado**
   - Indicador visual nos cards
   - Bloquear checkout se fechado
   - Mostrar próximo horário de abertura

7. **Horários Estruturados**
   - Migrar de texto para JSON
   - Exibir horários formatados
   - Mostrar múltiplos períodos por dia

8. **Cupons via REST API**
   - Substituir lógica hardcoded
   - Chamar `/coupons/validate`
   - Exibir erros de cupom inválido

### 🟢 MÉDIA (Nice to have)
9. **Favoritos**
   - Botões de favorito nos cards
   - Página de favoritos
   - Integração com REST API

10. **Endereços de Entrega**
    - Interface para gerenciar endereços
    - Seleção de endereço no checkout
    - Geocodificação automática

11. **Busca Avançada**
    - Filtros adicionais (rating, preço, aberto agora)
    - Busca full-text em cardápios

12. **Banners**
    - Shortcode para exibir banners
    - Carrossel na home

---

## 📋 Checklist de Implementação

### Shortcodes
- [ ] Atualizar `[vc_restaurant]` com ratings, status aberto, horários estruturados
- [ ] Atualizar `[vc_restaurants]` com filtros avançados
- [ ] Atualizar `[vc_menu_items]` com modal de modificadores
- [ ] Criar `[vc_banners]` para exibir banners
- [ ] Criar `[vc_reviews]` para exibir avaliações
- [ ] Criar `[vc_favorites]` para listar favoritos
- [ ] Criar `[vc_orders]` para histórico de pedidos

### JavaScript
- [ ] Criar `assets/js/product-modal.js` para modal de produto
- [ ] Atualizar `assets/js/frontend.js`:
  - [ ] Integrar cupons REST API
  - [ ] Adicionar validação de pedido
  - [ ] Integrar WhatsApp formatter
  - [ ] Suportar múltiplos métodos de fulfillment
  - [ ] Adicionar favoritos
- [ ] Criar `assets/js/addresses.js` para gerenciar endereços
- [ ] Criar `assets/js/reviews.js` para exibir/criar reviews

### Templates
- [ ] Atualizar `templates/single-vc-restaurant.php`:
  - [ ] Adicionar seção de reviews
  - [ ] Adicionar rating/estrelas
  - [ ] Adicionar status aberto/fechado
  - [ ] Usar horários estruturados

---

## 🔗 Referências

- **REST API Endpoints:** `README.md` (seção "Endpoints REST")
- **Backend Features:** `docs/RECURSOS_BACKEND.md`
- **Verificação de Implementação:** `docs/VERIFICACAO_IMPLEMENTACAO.md`

---

**Conclusão:** O backend está completo e funcional, mas aproximadamente **60-70% das funcionalidades não estão integradas nos shortcodes e no JavaScript frontend**. As funcionalidades críticas (modificadores, WhatsApp, validação) precisam ser implementadas antes de ir para produção.

