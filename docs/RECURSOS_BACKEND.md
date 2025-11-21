# Recursos Backend Necessários - Marketplace de Delivery Híbrido

## 1. Sistema de Complementos/Modificadores de Produtos

**Prioridade: CRÍTICA** - Essencial para o modal de produto funcionar

### 1.1 Estrutura de Dados

- Criar CPT `vc_product_modifier` (Complementos/Modificadores)
- Campos: nome, tipo (obrigatório/opcional), preço, mínimo/máximo de seleção
- Relacionamento: Many-to-Many com `vc_menu_item`
- Meta fields: `_vc_modifier_type` (required/optional), `_vc_modifier_price`, `_vc_modifier_min`, `_vc_modifier_max`

### 1.2 REST API

- `GET /wp-json/vemcomer/v1/menu-items/{id}/modifiers` - Lista modificadores de um item
- `POST /wp-json/vemcomer/v1/menu-items/{id}/modifiers` - Criar modificador (admin)
- `PATCH /wp-json/vemcomer/v1/modifiers/{id}` - Atualizar modificador
- `DELETE /wp-json/vemcomer/v1/modifiers/{id}` - Deletar modificador

### 1.3 Admin Interface

- Metabox no `vc_menu_item` para gerenciar modificadores
- Interface drag-and-drop para ordenação
- Validação: mínimo <= máximo, preços não negativos

**Arquivos a criar:**

- `inc/Model/CPT_ProductModifier.php`
- `inc/REST/Modifiers_Controller.php`
- `inc/Admin/Modifiers_Metabox.php`

---

## 2. Sistema de Frete por Distância e Bairro

**Prioridade: CRÍTICA** - Core do modelo de negócio

### 2.1 Configuração de Frete por Restaurante

- Meta fields no `vc_restaurant`:
  - `_vc_delivery_radius` (raio máximo em km)
  - `_vc_delivery_price_per_km` (preço por km)
  - `_vc_delivery_base_price` (taxa base)
  - `_vc_delivery_free_above` (frete grátis acima de X)
  - `_vc_delivery_min_order` (pedido mínimo)
  - `_vc_delivery_neighborhoods` (JSON com preços por bairro)

### 2.2 Método de Fulfillment: DistanceBasedDelivery

- Criar `inc/Checkout/Methods/DistanceBasedDelivery.php`
- Implementar `FulfillmentMethod` interface
- Cálculo: base_price + (distance * price_per_km)
- Verificar se está dentro do raio
- Verificar se bairro tem preço especial

### 2.3 REST API de Cotação

- Expandir `Shipping_Controller` para aceitar `lat`, `lng`, `address`
- Retornar: preço, distância, tempo estimado, se está no raio
- Validar se restaurante está aberto no momento

**Arquivos a modificar:**

- `inc/Checkout/Methods/DistanceBasedDelivery.php` (novo)
- `inc/REST/Shipping_Controller.php` (expandir)
- `inc/meta-restaurants.php` (adicionar campos)

---

## 3. Sistema de Horários Estruturados

**Prioridade: ALTA** - Bloqueia pedidos fora do horário

### 3.1 Estrutura de Dados

- Substituir `vc_restaurant_open_hours` (texto) por JSON estruturado
- Formato: `{ "monday": { "open": "09:00", "close": "22:00", "enabled": true }, ... }`
- Suportar múltiplos períodos por dia (ex: 09:00-14:00, 18:00-22:00)
- Meta field: `_vc_restaurant_schedule` (JSON)

### 3.2 Validação de Horário

- Função `vc_restaurant_is_open($restaurant_id, $timestamp = null)`
- Verificar dia da semana, horário atual, timezone
- Considerar feriados (meta `_vc_restaurant_holidays`)

### 3.3 REST API

- `GET /wp-json/vemcomer/v1/restaurants/{id}/schedule` - Horários do restaurante
- `GET /wp-json/vemcomer/v1/restaurants/{id}/is-open` - Status atual (aberto/fechado)

**Arquivos a criar/modificar:**

- `inc/Utils/Schedule_Helper.php` (novo)
- `inc/meta-restaurants.php` (atualizar metabox)
- `inc/REST/Restaurant_Controller.php` (adicionar endpoints)

---

## 4. Sistema de Avaliações e Ratings

**Prioridade: ALTA** - Social proof essencial

### 4.1 Estrutura de Dados

- Criar CPT `vc_review` (Avaliações)
- Campos: restaurante_id, cliente_id, rating (1-5), comentário, data
- Meta fields: `_vc_restaurant_id`, `_vc_customer_id`, `_vc_rating`, `_vc_order_id` (opcional)
- Status: pending, approved, rejected

### 4.2 Cálculo de Rating Agregado

- Função `vc_restaurant_get_rating($restaurant_id)` - Retorna média e total
- Atualizar meta `_vc_restaurant_rating_avg` e `_vc_restaurant_rating_count` automaticamente
- Cache com transient (atualizar ao criar nova avaliação)

### 4.3 REST API

- `GET /wp-json/vemcomer/v1/restaurants/{id}/reviews` - Lista avaliações
- `POST /wp-json/vemcomer/v1/reviews` - Criar avaliação (requer autenticação)
- `GET /wp-json/vemcomer/v1/restaurants/{id}/rating` - Rating agregado

**Arquivos a criar:**

- `inc/Model/CPT_Review.php`
- `inc/REST/Reviews_Controller.php`
- `inc/Utils/Rating_Helper.php`

---

## 5. Sistema de Favoritos

**Prioridade: MÉDIA** - Melhora retenção

### 5.1 Estrutura de Dados

- User meta: `vc_favorite_restaurants` (array de IDs)
- User meta: `vc_favorite_menu_items` (array de IDs)

### 5.2 REST API

- `GET /wp-json/vemcomer/v1/favorites/restaurants` - Lista favoritos do usuário
- `POST /wp-json/vemcomer/v1/favorites/restaurants/{id}` - Adicionar favorito
- `DELETE /wp-json/vemcomer/v1/favorites/restaurants/{id}` - Remover favorito
- Similar para menu items

**Arquivos a criar:**

- `inc/REST/Favorites_Controller.php`

---

## 6. Sistema de Histórico de Pedidos para Clientes

**Prioridade: ALTA** - UX essencial

### 6.1 Estrutura de Dados

- Expandir `vc_pedido` com meta `_vc_customer_id`
- Adicionar meta `_vc_customer_address` (endereço de entrega)
- Adicionar meta `_vc_customer_phone` (telefone do cliente)

### 6.2 REST API

- `GET /wp-json/vemcomer/v1/orders` - Lista pedidos do usuário autenticado
- `GET /wp-json/vemcomer/v1/orders/{id}` - Detalhes do pedido
- Filtros: status, data_inicio, data_fim, restaurante_id

**Arquivos a modificar:**

- `inc/class-vc-cpt-pedido.php` (adicionar campos)
- `inc/REST/Orders_Controller.php` (expandir)

---

## 7. Sistema de Analytics/Cliques para Restaurantes

**Prioridade: ALTA** - Métricas para SaaS

### 7.1 Tracking de Eventos

- Criar CPT `vc_analytics_event` ou tabela customizada
- Eventos: view_restaurant, view_menu, click_whatsapp, add_to_cart, checkout_start
- Campos: restaurant_id, customer_id (opcional), event_type, timestamp, metadata (JSON)

### 7.2 Dashboard de Analytics

- Endpoint: `GET /wp-json/vemcomer/v1/restaurants/{id}/analytics`
- Métricas: visualizações, cliques WhatsApp, conversão, itens mais vistos
- Filtros: período (hoje, semana, mês, custom)

### 7.3 Middleware de Tracking

- Hook automático em visualizações de restaurante
- Hook em cliques de botão WhatsApp
- Não bloquear performance (async logging)

**Arquivos a criar:**

- `inc/Analytics/Event_Logger.php`
- `inc/Analytics/Analytics_Controller.php`
- `inc/Model/CPT_AnalyticsEvent.php` (ou usar transients/options)

---

## 8. Sistema de Banners da Home

**Prioridade: MÉDIA** - Marketing

### 8.1 Estrutura de Dados

- Criar CPT `vc_banner`
- Campos: imagem, título, link, restaurante_id (opcional), ordem, ativo
- Meta fields: `_vc_banner_image`, `_vc_banner_link`, `_vc_banner_restaurant_id`, `_vc_banner_order`, `_vc_banner_active`

### 8.2 REST API

- `GET /wp-json/vemcomer/v1/banners` - Lista banners ativos
- `POST /wp-json/vemcomer/v1/banners` - Criar banner (admin)
- `PATCH /wp-json/vemcomer/v1/banners/{id}` - Atualizar
- `DELETE /wp-json/vemcomer/v1/banners/{id}` - Deletar

**Arquivos a criar:**

- `inc/Model/CPT_Banner.php`
- `inc/REST/Banners_Controller.php`
- `inc/Admin/Banners_Menu.php`

---

## 9. Sistema de Planos/Assinaturas SaaS

**Prioridade: CRÍTICA** - Modelo de receita

### 9.1 Estrutura de Dados

- Criar CPT `vc_subscription_plan`
- Campos: nome, preço mensal, recursos (JSON), ativo
- User meta: `vc_restaurant_subscription_plan_id`, `vc_restaurant_subscription_status`, `vc_restaurant_subscription_expires_at`

### 9.2 Limites por Plano

- Número máximo de itens no cardápio
- Número máximo de modificadores por item
- Analytics avançado (sim/não)
- Suporte prioritário (sim/não)

### 9.3 Validação de Limites

- Hook ao criar item: verificar limite do plano
- Hook ao criar modificador: verificar limite
- Mensagens de erro claras quando limite atingido

### 9.4 REST API

- `GET /wp-json/vemcomer/v1/subscription/plans` - Lista planos
- `GET /wp-json/vemcomer/v1/subscription/current` - Plano atual do restaurante
- `POST /wp-json/vemcomer/v1/subscription/upgrade` - Upgrade (admin)

**Arquivos a criar:**

- `inc/Model/CPT_SubscriptionPlan.php`
- `inc/Subscription/Plan_Manager.php`
- `inc/Subscription/Limits_Validator.php`
- `inc/REST/Subscription_Controller.php`

---

## 10. Sistema de Geração de Mensagem WhatsApp

**Prioridade: CRÍTICA** - Core do checkout híbrido

### 10.1 Formatador de Mensagem

- Classe `VC\WhatsApp\Message_Formatter`
- Método `format_order($order_data, $customer_data, $restaurant_data)`
- Template configurável via filtro `vemcomer/whatsapp_message_template`

### 10.2 Estrutura de Dados do Pedido

- Expandir criação de pedido para incluir:
  - Itens com modificadores selecionados
  - Endereço de entrega completo
  - Tipo de fulfillment (delivery/pickup)
  - Total calculado (subtotal + frete - descontos)

### 10.3 REST API

- `POST /wp-json/vemcomer/v1/orders/prepare-whatsapp` - Gera mensagem formatada
- Retorna: `{ "message": "...", "whatsapp_url": "https://wa.me/..." }`
- Valida: restaurante aberto, dentro do raio, itens disponíveis

**Arquivos a criar:**

- `inc/WhatsApp/Message_Formatter.php`
- `inc/WhatsApp/Template_Engine.php`
- `inc/REST/Orders_Controller.php` (adicionar endpoint)

---

## 11. Sistema de Endereços de Entrega

**Prioridade: ALTA** - Necessário para delivery

### 11.1 Estrutura de Dados

- Criar CPT `vc_delivery_address` ou user meta
- Campos: nome, rua, número, complemento, bairro, cidade, CEP, lat, lng, principal
- User meta: `vc_delivery_addresses` (array de objetos)

### 11.2 REST API

- `GET /wp-json/vemcomer/v1/addresses` - Lista endereços do usuário
- `POST /wp-json/vemcomer/v1/addresses` - Criar endereço
- `PATCH /wp-json/vemcomer/v1/addresses/{id}` - Atualizar
- `DELETE /wp-json/vemcomer/v1/addresses/{id}` - Deletar
- `POST /wp-json/vemcomer/v1/addresses/{id}/set-primary` - Definir principal

### 11.3 Geocodificação

- Integrar com API de geocodificação ao salvar endereço
- Cache de resultados (usar `vc_geo_cache_set/get`)

**Arquivos a criar:**

- `inc/Model/CPT_DeliveryAddress.php` (ou usar user meta)
- `inc/REST/Addresses_Controller.php`
- `inc/Utils/Geocoding_Helper.php`

---

## 12. Sistema de Disponibilidade em Tempo Real

**Prioridade: ALTA** - UX crítica

### 12.1 Verificação Automática

- Função `vc_restaurant_check_availability($restaurant_id)`
- Verifica: horário de funcionamento, status do restaurante, se está no raio (se delivery)
- Cache resultado por 1 minuto (transient)

### 12.2 REST API

- `GET /wp-json/vemcomer/v1/restaurants/{id}/availability` - Status atual
- Retorna: `{ "available": true, "reason": null }` ou `{ "available": false, "reason": "closed" }`

**Arquivos a criar:**

- `inc/Utils/Availability_Helper.php`
- `inc/REST/Restaurant_Controller.php` (adicionar endpoint)

---

## 13. Sistema de Categorias de Cardápio Robusto

**Prioridade: MÉDIA** - Organização

### 13.1 Expandir Taxonomia

- Taxonomia `vc_menu_category` já existe, mas precisa:
  - Ordem customizada (meta `_vc_category_order`)
  - Imagem da categoria (meta `_vc_category_image`)
  - Descrição

### 13.2 REST API

- `GET /wp-json/vemcomer/v1/restaurants/{id}/menu-categories` - Lista categorias com itens
- Ordenação respeitada

**Arquivos a modificar:**

- `inc/Model/CPT_MenuItem.php` (expandir taxonomia)

---

## 14. Sistema de Busca Avançada

**Prioridade: MÉDIA** - Descoberta

### 14.1 Busca Full-Text

- Buscar em: nome do restaurante, descrição, itens do cardápio, categorias
- Filtros combinados: categoria, localização, delivery, aberto agora, rating mínimo

### 14.2 REST API

- Expandir `GET /wp-json/vemcomer/v1/restaurants` com:
  - `search` (já existe)
  - `min_rating` (novo)
  - `is_open_now` (novo)
  - `has_delivery` (novo)
  - `price_range` (novo - min/max preço médio)

**Arquivos a modificar:**

- `inc/REST/Restaurant_Controller.php`

---

## 15. Sistema de Notificações

**Prioridade: MÉDIA** - Engajamento

### 15.1 Tipos de Notificação

- Novo pedido (restaurante)
- Status do pedido atualizado (cliente)
- Restaurante favorito abriu
- Promoção disponível

### 15.2 Estrutura de Dados

- User meta: `vc_notifications` (array de notificações não lidas)
- Ou criar CPT `vc_notification`

### 15.3 REST API

- `GET /wp-json/vemcomer/v1/notifications` - Lista notificações
- `POST /wp-json/vemcomer/v1/notifications/{id}/read` - Marcar como lida
- `POST /wp-json/vemcomer/v1/notifications/read-all` - Marcar todas como lidas

**Arquivos a criar:**

- `inc/Notifications/Notification_Manager.php`
- `inc/REST/Notifications_Controller.php`

---

## 16. Sistema de Tempo Estimado de Entrega Dinâmico

**Prioridade: MÉDIA** - UX

### 16.1 Cálculo Dinâmico

- Base: tempo médio de preparo dos itens
- Adicionar: tempo de entrega (baseado em distância)
- Considerar: horário de pico (multiplicador)

### 16.2 REST API

- `GET /wp-json/vemcomer/v1/restaurants/{id}/estimated-delivery` - Tempo estimado
- Parâmetros: `lat`, `lng`, `items` (array de IDs)

**Arquivos a criar:**

- `inc/Utils/Delivery_Time_Calculator.php`

---

## 17. Sistema de Preços por Bairro

**Prioridade: MÉDIA** - Flexibilidade

### 17.1 Estrutura de Dados

- Meta `_vc_delivery_neighborhoods` (JSON)
- Formato: `{ "bairro1": { "price": 5.00, "free_above": 50.00 }, ... }`

### 17.2 Integração com Frete

- Método `DistanceBasedDelivery` verifica primeiro bairro, depois calcula por distância

**Arquivos a modificar:**

- `inc/Checkout/Methods/DistanceBasedDelivery.php`

---

## 18. Sistema de Múltiplos Métodos de Fulfillment

**Prioridade: ALTA** - Retirada vs Entrega

### 18.1 Método: Pickup (Retirada)

- Criar `inc/Checkout/Methods/Pickup.php`
- Frete: R$ 0,00
- Não verifica raio de entrega
- Verifica apenas se restaurante está aberto

### 18.2 REST API

- Expandir `GET /wp-json/vemcomer/v1/shipping/quote` para retornar múltiplos métodos
- Incluir: `delivery` e `pickup` quando disponíveis

**Arquivos a criar:**

- `inc/Checkout/Methods/Pickup.php`

---

## 19. Sistema de Gestão de Imagens Otimizadas

**Prioridade: MÉDIA** - Performance

### 19.1 Geração de Thumbnails

- Hook ao fazer upload de imagem de restaurante/item
- Gerar tamanhos: thumbnail (150x150), medium (300x300), large (800x800)
- Meta: `_vc_image_sizes` (array com URLs)

### 19.2 REST API

- Incluir URLs de diferentes tamanhos na resposta de restaurantes/itens

**Arquivos a criar:**

- `inc/Utils/Image_Optimizer.php`

---

## 20. Sistema de Validação de Pedido Antes do WhatsApp

**Prioridade: CRÍTICA** - Previne erros

### 20.1 Validações

- Restaurante está aberto
- Todos os itens estão disponíveis
- Cliente está dentro do raio (se delivery)
- Pedido mínimo atingido
- Itens têm modificadores obrigatórios preenchidos

### 20.2 REST API

- `POST /wp-json/vemcomer/v1/orders/validate` - Valida pedido antes de gerar mensagem
- Retorna: `{ "valid": true }` ou `{ "valid": false, "errors": [...] }`

**Arquivos a criar:**

- `inc/Order/Order_Validator.php`
- `inc/REST/Orders_Controller.php` (adicionar endpoint)

---

## 21. Sistema de Cache Inteligente

**Prioridade: ALTA** - Performance

### 21.1 Cache de Cardápios

- Transient: `vc_menu_cache_{restaurant_id}` (1 hora)
- Invalidar ao criar/editar/deletar item

### 21.2 Cache de Restaurantes

- Transient: `vc_restaurant_cache_{id}` (30 minutos)
- Invalidar ao atualizar restaurante

### 21.3 REST API de Invalidação

- `POST /wp-json/vemcomer/v1/cache/invalidate` - Invalidar cache (admin)

**Arquivos a criar:**

- `inc/Cache/Cache_Manager.php`
- Expandir `inc/REST/Invalidation.php`

---

## 22. Sistema de Relatórios Avançados para Restaurantes

**Prioridade: MÉDIA** - Analytics

### 22.1 Métricas

- Pedidos por período
- Ticket médio
- Itens mais vendidos
- Horários de pico
- Taxa de conversão (cliques WhatsApp / visualizações)

### 22.2 REST API

- `GET /wp-json/vemcomer/v1/restaurants/{id}/reports/sales` - Vendas
- `GET /wp-json/vemcomer/v1/restaurants/{id}/reports/analytics` - Analytics
- Filtros: período, agrupamento (dia/semana/mês)

**Arquivos a criar:**

- `inc/Reports/Restaurant_Reports.php`
- `inc/REST/Reports_Controller.php`

---

## 23. Sistema de Cupons/Descontos (Backend Completo)

**Prioridade: MÉDIA** - Marketing

### 23.1 Estrutura de Dados

- Criar CPT `vc_coupon`
- Campos: código, tipo (percent/money), valor, válido até, uso máximo, usado, restaurante_id (opcional)
- Meta fields: `_vc_coupon_code`, `_vc_coupon_type`, `_vc_coupon_value`, `_vc_coupon_expires_at`, `_vc_coupon_max_uses`, `_vc_coupon_used_count`, `_vc_coupon_restaurant_id`

### 23.2 Validação

- Verificar: código existe, não expirado, não excedeu uso máximo, válido para restaurante

### 23.3 REST API

- `POST /wp-json/vemcomer/v1/coupons/validate` - Validar cupom
- `GET /wp-json/vemcomer/v1/coupons` - Lista cupons (admin)
- `POST /wp-json/vemcomer/v1/coupons` - Criar cupom (admin)

**Arquivos a criar:**

- `inc/Model/CPT_Coupon.php`
- `inc/Coupons/Coupon_Validator.php`
- `inc/REST/Coupons_Controller.php` (completar)

---

## 24. Sistema de Gestão de Usuários (Super Admin)

**Prioridade: ALTA** - Administração

### 24.1 Interface Admin

- Lista de restaurantes com filtros
- Ativar/desativar planos
- Resetar senhas
- Visualizar analytics agregados

### 24.2 REST API

- `GET /wp-json/vemcomer/v1/admin/restaurants` - Lista todos restaurantes
- `POST /wp-json/vemcomer/v1/admin/restaurants/{id}/subscription` - Alterar plano
- `GET /wp-json/vemcomer/v1/admin/dashboard` - Dashboard geral

**Arquivos a criar:**

- `inc/Admin/Super_Admin_Dashboard.php`
- `inc/REST/Admin_Controller.php`

---

## 25. Sistema de Logs e Auditoria Avançado

**Prioridade: BAIXA** - Debugging

### 25.1 Expandir Sistema de Auditoria

- Log de todas as ações importantes
- Filtros por tipo, usuário, data
- Export para CSV

**Arquivos a modificar:**

- `inc/audit/logger.php` (expandir)

---

## Priorização de Implementação

### Fase 1 - Core Essencial (Sprint 1-2)

1. Sistema de Complementos/Modificadores (#1)
2. Sistema de Frete por Distância (#2)
3. Sistema de Horários Estruturados (#3)
4. Sistema de Geração de Mensagem WhatsApp (#10)
5. Sistema de Validação de Pedido (#20)

### Fase 2 - UX e Engajamento (Sprint 3-4)

6. Sistema de Avaliações (#4)
7. Sistema de Favoritos (#5)
8. Sistema de Histórico de Pedidos (#6)
9. Sistema de Endereços de Entrega (#11)
10. Sistema de Disponibilidade (#12)

### Fase 3 - Analytics e SaaS (Sprint 5-6)

11. Sistema de Analytics (#7)
12. Sistema de Planos/Assinaturas (#9)
13. Sistema de Relatórios Avançados (#22)
14. Sistema de Gestão de Usuários (#24)

### Fase 4 - Melhorias e Otimizações (Sprint 7+)

15. Sistema de Banners (#8)
16. Sistema de Busca Avançada (#14)
17. Sistema de Notificações (#15)
18. Sistema de Cache Inteligente (#21)
19. Sistema de Cupons Completo (#23)
20. Demais recursos

