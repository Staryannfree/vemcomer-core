# VemComer Core

Core de marketplace para WordPress com:
- CPTs: **Produtos**, **Pedidos**, **Restaurantes**, **Itens do Cardápio**.
- Admin Menu, REST API, Status de Pedido, Webhooks e Seed via WP‑CLI.
- Integrações: **WooCommerce** (sincroniza pedidos/status) e **Automator** (hooks customizados).

## Instalação e Ativação
1. Copie o plugin para `wp-content/plugins/vemcomer-core/`.
2. Ative **VemComer Core** no painel do WordPress.
3. (Opcional) Configure **VemComer ▸ Configurações** → Segredo do Webhook e integrações.

### Páginas públicas (shortcodes)
Ao ativar o plugin o núcleo cria/atualiza automaticamente as páginas que contêm apenas os shortcodes principais (lista, cardápio e checkout) e elas passam a aparecer em **Páginas ▸ Todas** como qualquer outra página. Se quiser recriá-las depois — ou gerar versões parametrizadas para um restaurante específico — use **VemComer ▸ Instalador**, que reaproveita as mesmas rotinas sem duplicar conteúdos existentes.

Shortcodes principais disponíveis:

* `[vemcomer_restaurants]` — grade pública de restaurantes cadastrados.
* `[vc_restaurants_map]` — mapa público com pins e botão “Perto de mim”.
* `[vemcomer_menu]` — lista os itens de um restaurante (usa `?restaurant_id=` ou o atributo `restaurant_id`).
* `[vemcomer_checkout]` — checkout simplificado para o carrinho do marketplace.
* `[vemcomer_restaurant_panel]` — painel front-end para donos de restaurante (requer login). Inclui botão "Configuração Rápida" para onboarding de novos usuários.
* `[vemcomer_restaurant_signup]` — formulário público para restaurantes enviarem seus dados (entradas ficam pendentes para aprovação do admin).
* `[vemcomer_customer_signup]` — formulário de criação de conta para clientes finais.

**Página de validação de acesso**: `/validar-acesso/?token={access_url}` — página automática onde restaurantes aprovados podem criar sua conta de acesso usando o token recebido no webhook.

**Sistema de Onboarding**: O painel do restaurante inclui um sistema de onboarding guiado que ajuda novos donos a configurar seus restaurantes. Acessível via botão "⚡ Configuração Rápida" no painel. Veja mais detalhes em [`docs/ONBOARDING.md`](docs/ONBOARDING.md).

Todos os shortcodes acima renderizam HTML, CSS e JavaScript próprios do plugin — não há dependência de construtores como o Elementor para exibir as páginas públicas.

## Sistema de Onboarding

O sistema de onboarding guia novos donos de restaurantes através dos primeiros passos de configuração:

### Como funciona

1. **Acesso**: Quando um dono de restaurante acessa o painel pela primeira vez, vê o botão "⚡ Configuração Rápida"
2. **Ativação**: Ao clicar no botão, um modal interativo é aberto com 5 steps guiados
3. **Progresso**: O progresso é salvo automaticamente e pode ser retomado a qualquer momento
4. **Verificação**: Alguns steps são verificados automaticamente (perfil completo, itens no cardápio, etc.)
5. **Conclusão**: Ao completar todos os steps, o botão desaparece e o onboarding não aparece mais

### Steps do Onboarding

1. **Bem-vindo ao VemComer!** - Tela inicial de boas-vindas
2. **Complete seu perfil** - Adicionar WhatsApp, endereço e horários
3. **Adicione itens ao cardápio** - Criar pelo menos 3 itens
4. **Configure delivery** - Definir se oferece delivery
5. **Veja sua página pública** - Visualizar como os clientes veem o restaurante

### Recursos

- ✅ **Progresso persistente** - Salvo no banco de dados
- ✅ **Verificação automática** - Detecta quando tarefas são completadas
- ✅ **Dismissível** - Pode ser fechado e retomado depois
- ✅ **Responsivo** - Funciona em desktop e mobile
- ✅ **Acessível** - Segue boas práticas de acessibilidade

Para mais detalhes, consulte [`docs/ONBOARDING.md`](docs/ONBOARDING.md) e [`docs/ONBOARDING_VISUAL.md`](docs/ONBOARDING_VISUAL.md).

## Seed (dados de demonstração)
Cria 1 restaurante e 5 itens de cardápio:
```bash
wp vc seed
```

## Endpoints REST

### Restaurantes

* **GET** `/wp-json/vemcomer/v1/restaurants`
* **GET** `/wp-json/vemcomer/v1/restaurants/{id}/menu-items`
* **GET** `/wp-json/vemcomer/v1/restaurants/{id}/schedule` - Horários estruturados do restaurante (inclui feriados)
* **GET** `/wp-json/vemcomer/v1/restaurants/{id}/is-open?timestamp={opcional}` - Verifica se restaurante está aberto (retorna próximo horário de abertura se fechado)

### Modificadores de Produtos

* **GET** `/wp-json/vemcomer/v1/menu-items/{id}/modifiers` - Lista modificadores de um item do cardápio (público)
* **POST** `/wp-json/vemcomer/v1/menu-items/{id}/modifiers` - Criar modificador vinculado a um item (admin)
* **PATCH** `/wp-json/vemcomer/v1/modifiers/{id}` - Atualizar modificador (admin)
* **DELETE** `/wp-json/vemcomer/v1/modifiers/{id}` - Deletar modificador (admin)

### Avaliações e Ratings

* **GET** `/wp-json/vemcomer/v1/restaurants/{id}/reviews?per_page={opcional}&page={opcional}` - Lista avaliações aprovadas de um restaurante (público)
* **GET** `/wp-json/vemcomer/v1/restaurants/{id}/rating` - Retorna rating agregado (média e total) de um restaurante (público)
* **POST** `/wp-json/vemcomer/v1/reviews` - Criar avaliação (requer autenticação, body: `restaurant_id`, `rating` (1-5), `comment` (opcional), `order_id` (opcional))

### Favoritos

* **GET** `/wp-json/vemcomer/v1/favorites/restaurants` - Lista restaurantes favoritos do usuário autenticado
* **POST** `/wp-json/vemcomer/v1/favorites/restaurants/{id}` - Adicionar restaurante aos favoritos (requer autenticação)
* **DELETE** `/wp-json/vemcomer/v1/favorites/restaurants/{id}` - Remover restaurante dos favoritos (requer autenticação)
* **GET** `/wp-json/vemcomer/v1/favorites/menu-items` - Lista itens do cardápio favoritos do usuário autenticado
* **POST** `/wp-json/vemcomer/v1/favorites/menu-items/{id}` - Adicionar item do cardápio aos favoritos (requer autenticação)
* **DELETE** `/wp-json/vemcomer/v1/favorites/menu-items/{id}` - Remover item do cardápio dos favoritos (requer autenticação)

### Pedidos

* **GET** `/wp-json/vemcomer/v1/orders?status={opcional}&data_inicio={opcional}&data_fim={opcional}&restaurant_id={opcional}&per_page={opcional}&page={opcional}` - Lista pedidos do usuário autenticado com filtros
* **GET** `/wp-json/vemcomer/v1/orders/{id}` - Detalhes completos de um pedido (requer autenticação, apenas dono ou admin)

### Analytics

* **GET** `/wp-json/vemcomer/v1/restaurants/{id}/analytics?period={today|week|month|custom}&date_from={opcional}&date_to={opcional}` - Métricas de analytics do restaurante (requer autenticação, apenas dono ou admin)

### Pedidos

* **POST** `/wp-json/vemcomer/v1/pedidos`

* Body: `{ "restaurant_id": 123, "itens": [ {"produto_id": 123, "qtd": 2} ], "subtotal": "49,90", "fulfillment": { "method": "flat_rate_delivery", "ship_total": "9,90" } }`

### Fulfillment e Checkout

* O checkout público trabalha somente com **um restaurante por vez** e exige um método de fulfillment válido.
* Cada método implementa `VC\Checkout\FulfillmentMethod` (`inc/Checkout/FulfillmentMethod.php`).
* Registre seus métodos no action `vemcomer_register_fulfillment_method` — o registro padrão (`inc/Checkout/Methods/FlatRateDelivery.php`) aplica o frete fixo + pedido mínimo dos metadados do restaurante.
* Use os helpers JS em `assets/js/checkout.js` para testar rapidamente as rotas de frete/pedido (`window.VemComerCheckoutExamples.exampleQuote()` e `.exampleOrder()`).

#### Cotação de Frete

* **GET** `/wp-json/vemcomer/v1/shipping/quote?restaurant_id={id}&subtotal={valor}&lat={lat}&lng={lng}&address={endereco}&neighborhood={bairro}`
* Parâmetros obrigatórios: `restaurant_id`, `subtotal`
* Parâmetros opcionais: `lat`, `lng`, `address`, `neighborhood` (para cálculo por distância)
* Retorna: array de métodos disponíveis, distância calculada, se está no raio, se restaurante está aberto

Exemplo de registro:

```php
add_action( 'vemcomer_register_fulfillment_method', function () {
    \VC\Checkout\FulfillmentRegistry::register( new My_Custom_Method(), 'my-method' );
} );
```

### Webhook de Pagamento (entrada)

* **POST** `/wp-json/vemcomer/v1/webhook/payment`
* Header: `X-VemComer-Signature: sha256=<hmac_hex_do_corpo>`
* Body: `{ "order_id": 10, "status": "paid|refunded|failed", "amount": "99,90", "ts": 1690000000 }`

### Mercado Pago → VemComer

O plugin expõe um handler dedicado para notificações do Mercado Pago (`/wp-json/vemcomer/v1/mercadopago/webhook`).

1. Execute `composer require mercadopago/dx-php` no diretório do plugin e garanta que `vendor/autoload.php` esteja presente.
2. Em **VemComer ▸ Configurações** configure:
   * **Gateway de pagamento**: `mercadopago`.
   * **Segredo do webhook (HMAC)**: gere pelo botão "Gerar novo segredo" e compartilhe com o serviço intermediário.
   * **Token do Mercado Pago**: cole o `access_token` do APP (`APP_USR-...`).
3. No checkout do Mercado Pago informe `external_reference = <ID do vc_pedido>` (ou `metadata.vemcomer_order_id`).
4. Cadastre a URL `/wp-json/vemcomer/v1/mercadopago/webhook` nas notificações do Mercado Pago.

O handler valida o `id` recebido junto ao SDK oficial, resolve o pedido e encaminha o payload assinado para `/wp-json/vemcomer/v1/webhook/payment`. Após o processamento você pode ouvir `vemcomer_mercadopago_payment_processed` para executar automações adicionais (envio de comprovantes, atualização de painel, etc.).

## Status de Pedido

Os pedidos (`vc_pedido`) podem ter:
`vc-pending`, `vc-paid`, `vc-preparing`, `vc-delivering`, `vc-completed`, `vc-cancelled`.
Você pode mudar pelo metabox lateral do pedido ou via integrações.

## Integrações

### WooCommerce (opcional)

* Sincroniza **status**: `processing → vc-paid`, `completed → vc-completed`, `cancelled → vc-cancelled`, `on-hold → vc-pending`.
* Se um pedido WooCommerce não tiver vínculo, o plugin cria automaticamente um `vc_pedido` espelhando **itens** e **total**, e vincula via meta `_vc_wc_order_id` (WC) e `_vc_linked_wc_order` (VC).

### Automator (Uncanny/AutomatorWP)

O plugin expõe **actions** que podem ser usadas como **gatilhos de hook personalizado**:

* `vemcomer/order_status_changed`, args: `(int $vc_order_id, string $new_status, string $old_status)`
* `vemcomer/order_paid`, args: `(int $vc_order_id)`
* `vemcomer/webhook_payment_processed`, args: `(int $vc_order_id, array $payload)`
* `vemcomer/restaurant_created`, args: `(int $restaurant_id)`

Use esses nomes nos "Custom Action Hook" dos automators para disparar receitas.

### SMClick (Webhooks de Restaurantes)

O plugin integra com SMClick para notificações de eventos relacionados a restaurantes:

* **Webhook de Cadastro**: `restaurant_registered` — dispara quando um restaurante envia o formulário (status pendente).
* **Webhook de Aprovação**: `restaurant_approved` — dispara quando o restaurante é aprovado (status muda para publicado).

#### Sistema de Token de Acesso (access_url)

Quando um restaurante é aprovado:

1. **Token único gerado**: Um token único (`access_url`) é gerado automaticamente e armazenado no meta `vc_restaurant_access_url`.
2. **Webhook enviado**: O webhook `restaurant_approved` é enviado para a URL configurada (padrão: `https://api.smclick.com.br/integration/wordpress/5f98815b-640d-44c9-88b4-f17d6b059b35/`) contendo:
   - Todos os dados do restaurante
   - Campo `access_url`: token único para acesso
   - Campo `access_url_validation`: URL completa para validação (`/validar-acesso/?token={access_url}`)
3. **Página de validação**: O restaurante pode acessar `/validar-acesso/?token={access_url}` para:
   - Criar uma conta de acesso (email e senha)
   - Validar que as senhas coincidem (confirmação)
   - Fazer login automático após criação
   - Ser redirecionado para o painel do restaurante

**Configuração**: Em **VemComer ▸ Configurações**, configure as URLs dos webhooks SMClick para cada evento. O token `access_url` aparece automaticamente no metabox do restaurante após aprovação.

## Changelog

### v0.25 - Sistema de Analytics - Middleware de Tracking (7.3)

**Novas funcionalidades:**
- **Tracking automático de eventos**:
  - Visualização de restaurante: hook em `template_redirect` para single de restaurante
  - Visualização de cardápio: hook em `template_redirect` quando há `restaurant_id` na URL
  - Tracking via REST API: hooks em `rest_prepare_*` para visualizações via API
- **JavaScript de tracking**: Script inline no footer para eventos do lado do cliente
  - Cliques no WhatsApp: detecta links `wa.me`, `whatsapp.com`, `api.whatsapp.com`
  - Adições ao carrinho: detecta botões com `data-action="add-to-cart"`
  - Início de checkout: detecta botões com `data-action="checkout"`
  - Usa `navigator.sendBeacon` para não bloquear navegação
- **Endpoint REST para tracking**: `POST /analytics/track` para receber eventos via JavaScript
- **Processamento assíncrono**: Todos os eventos são logados via shutdown hook (não bloqueiam requisições)

**Arquivos novos:**
- `inc/Analytics/Tracking_Middleware.php` - Hooks automáticos para tracking
- `inc/Analytics/Tracking_Controller.php` - Endpoint REST para tracking via JS

**Arquivos modificados:**
- `vemcomer-core.php` - Registro dos novos controllers

### v0.24 - Sistema de Analytics - Dashboard (7.2)

**Novas funcionalidades:**
- **Endpoint REST de Analytics**: `GET /restaurants/{id}/analytics`
  - Métricas: visualizações de restaurante, visualizações de cardápio, cliques WhatsApp, adições ao carrinho, inícios de checkout
  - Taxa de conversão: cliques WhatsApp / visualizações de restaurante
  - Clientes únicos: total de clientes distintos que interagiram
  - Itens mais vistos: top 10 itens do cardápio mais visualizados
- **Filtros de período**: today, week, month, custom (com date_from e date_to)
- **Controle de acesso**: Apenas dono do restaurante ou admin pode ver analytics
- **Cálculo de métricas**: Agregação de eventos por tipo e período

**Arquivos novos:**
- `inc/Analytics/Analytics_Controller.php` - Controller REST para analytics

**Arquivos modificados:**
- `vemcomer-core.php` - Registro do Analytics_Controller

### v0.23 - Sistema de Analytics - Tracking de Eventos (7.1)

**Novas funcionalidades:**
- **CPT `vc_analytics_event`**: Custom Post Type para armazenar eventos de analytics
  - Tipos de eventos: view_restaurant, view_menu, click_whatsapp, add_to_cart, checkout_start
  - Meta fields: `_vc_event_type`, `_vc_restaurant_id`, `_vc_customer_id` (opcional), `_vc_event_metadata` (JSON), `_vc_event_timestamp`
- **Classe `Event_Logger`**: Sistema de logging assíncrono
  - Métodos helper: `log_view_restaurant()`, `log_view_menu()`, `log_click_whatsapp()`, `log_add_to_cart()`, `log_checkout_start()`
  - Processamento assíncrono via shutdown hook (não bloqueia requisições)
  - Validação de tipos de eventos

**Arquivos novos:**
- `inc/Model/CPT_AnalyticsEvent.php` - CPT para eventos
- `inc/Analytics/Event_Logger.php` - Sistema de logging assíncrono

**Arquivos modificados:**
- `vemcomer-core.php` - Registro do CPT_AnalyticsEvent

### v0.22 - Sistema de Histórico de Pedidos - REST API (6.2)

**Novas funcionalidades:**
- **Endpoints REST expandidos para pedidos**:
  - `GET /orders` - Lista pedidos do usuário autenticado com paginação
  - `GET /orders/{id}` - Detalhes completos de um pedido (expandido)
- **Filtros avançados**: status, data_inicio, data_fim, restaurant_id
- **Controle de acesso**: Usuários só veem seus próprios pedidos (admins veem todos)
- **Resposta detalhada**: Inclui dados do cliente, endereço, telefone e restaurante
- **Paginação**: Suporte a per_page e page

**Arquivos modificados:**
- `inc/REST/Orders_Controller.php` - Expandido com listagem, filtros e controle de acesso

### v0.21 - Sistema de Histórico de Pedidos - Estrutura de Dados (6.1)

**Novas funcionalidades:**
- **Expansão do CPT `vc_pedido`**: Novos campos para rastreamento de cliente
  - Meta `_vc_customer_id`: ID do cliente que fez o pedido
  - Meta `_vc_customer_address`: Endereço de entrega completo
  - Meta `_vc_customer_phone`: Telefone de contato do cliente
- **Metabox atualizado**: Interface admin para gerenciar dados do cliente no pedido
  - Campo de seleção de cliente (dropdown com usuários)
  - Campos de texto para endereço e telefone
  - Validação e sanitização de dados

**Arquivos modificados:**
- `inc/class-vc-cpt-pedido.php` - Adicionados campos de cliente no metabox e save_meta

### v0.20 - Sistema de Favoritos - REST API (5.2)

**Novas funcionalidades:**
- **Endpoints REST para favoritos**:
  - `GET /favorites/restaurants` - Lista restaurantes favoritos do usuário
  - `POST /favorites/restaurants/{id}` - Adicionar restaurante aos favoritos
  - `DELETE /favorites/restaurants/{id}` - Remover restaurante dos favoritos
  - `GET /favorites/menu-items` - Lista itens do cardápio favoritos do usuário
  - `POST /favorites/menu-items/{id}` - Adicionar item do cardápio aos favoritos
  - `DELETE /favorites/menu-items/{id}` - Remover item do cardápio dos favoritos
- **Validações**: Verifica se restaurante/item existe, se já está nos favoritos
- **Autenticação obrigatória**: Todos os endpoints requerem usuário autenticado
- **Integração completa**: Usa `Favorites_Helper` para todas as operações
- **Respostas detalhadas**: Retorna dados completos dos restaurantes/itens favoritos

**Arquivos novos:**
- `inc/REST/Favorites_Controller.php` - Controller REST para favoritos

**Arquivos modificados:**
- `vemcomer-core.php` - Registro do Favorites_Controller

### v0.19 - Sistema de Favoritos - Estrutura de Dados (5.1)

**Novas funcionalidades:**
- **Classe `Favorites_Helper`**: Helper para gerenciar favoritos de usuários
  - User meta: `vc_favorite_restaurants` (array de IDs)
  - User meta: `vc_favorite_menu_items` (array de IDs)
  - Métodos para adicionar/remover/verificar favoritos
  - Métodos toggle para alternar status
  - Métodos para limpar todos os favoritos
- **Validação**: Garante que apenas IDs numéricos válidos são armazenados
- **Reindexação automática**: Arrays são reindexados após remoção

**Arquivos novos:**
- `inc/Utils/Favorites_Helper.php` - Classe helper para gerenciar favoritos

**Arquivos modificados:**
- `vemcomer-core.php` - Registro do Favorites_Helper

### v0.18 - Sistema de Avaliações - REST API (4.3)

**Novas funcionalidades:**
- **Endpoints REST para avaliações**:
  - `GET /restaurants/{id}/reviews` - Lista avaliações aprovadas com paginação
  - `GET /restaurants/{id}/rating` - Retorna rating agregado (média, total, formato)
  - `POST /reviews` - Criar avaliação (requer autenticação)
- **Validações**: Verifica se restaurante existe, se usuário já avaliou, rating válido (1-5)
- **Status automático**: Novas avaliações criadas como "pending" (aguardando aprovação)
- **Integração completa**: Usa `Rating_Helper` para cálculos e cache

**Arquivos novos:**
- `inc/REST/Reviews_Controller.php` - Controller REST para avaliações

**Arquivos modificados:**
- `vemcomer-core.php` - Registro do Reviews_Controller

### v0.17 - Sistema de Avaliações - Cálculo de Rating Agregado (4.2)

**Novas funcionalidades:**
- **Classe `Rating_Helper`**: Helper para cálculo e cache de ratings agregados
  - `get_rating($restaurant_id)`: Retorna média, total e formato formatado
  - `get_average($restaurant_id)`: Retorna apenas a média
  - `get_count($restaurant_id)`: Retorna apenas o total
  - `recalculate($restaurant_id)`: Recalcula e atualiza rating
  - `invalidate_cache($restaurant_id)`: Invalida cache
- **Função global `vc_restaurant_get_rating()`**: Helper global para obter rating
- **Sistema de cache**: Transient de 1 hora para melhor performance
- **Invalidação automática**: Cache invalidado ao criar/atualizar/deletar avaliações
- **Atualização automática de meta fields**: `_vc_restaurant_rating_avg` e `_vc_restaurant_rating_count`

**Arquivos novos:**
- `inc/Utils/Rating_Helper.php` - Classe helper para ratings com cache

**Arquivos modificados:**
- `inc/Model/CPT_Review.php` - Integração com Rating_Helper, hooks para invalidação de cache
- `vemcomer-core.php` - Registro do Rating_Helper

### v0.16 - Sistema de Avaliações - Estrutura de Dados (4.1)

**Novas funcionalidades:**
- **CPT `vc_review`**: Custom Post Type para avaliações de restaurantes
  - Campos: restaurante_id, cliente_id, rating (1-5), comentário, pedido_id (opcional)
  - Meta fields: `_vc_restaurant_id`, `_vc_customer_id`, `_vc_rating`, `_vc_order_id`
  - Status customizados: `vc-review-pending`, `vc-review-approved`, `vc-review-rejected`
- **Metaboxes Admin**: Interface completa para gerenciar avaliações
  - Metabox de dados: restaurante, cliente, rating, pedido
  - Metabox de status: aprovar/rejeitar avaliações
- **Cálculo Automático de Rating**: Atualiza rating agregado do restaurante ao aprovar/rejeitar
  - Meta `_vc_restaurant_rating_avg` (média)
  - Meta `_vc_restaurant_rating_count` (total de avaliações aprovadas)
  - Invalidação automática de cache
- **Colunas Admin**: Visualização rápida de restaurante, cliente, rating e pedido na lista

**Arquivos novos:**
- `inc/Model/CPT_Review.php` - CPT e lógica de avaliações

**Arquivos modificados:**
- `inc/Admin/Menu_Restaurant.php` - Adicionado submenu "Avaliações"
- `vemcomer-core.php` - Registro do CPT_Review

### v0.15 - REST API de Horários (3.3)

**Novas funcionalidades:**
- **Endpoints REST para horários**:
  - `GET /wp-json/vemcomer/v1/restaurants/{id}/schedule` - Retorna horários estruturados, feriados e horário legado
  - `GET /wp-json/vemcomer/v1/restaurants/{id}/is-open?timestamp={opcional}` - Verifica se restaurante está aberto
  - Retorna próximo horário de abertura quando fechado
  - Suporta verificação em timestamp específico (útil para agendamentos)
- **Integração completa**: Usa `Schedule_Helper` para validações precisas

**Arquivos modificados:**
- `inc/REST/Restaurant_Controller.php` - Adicionados endpoints de schedule e is-open

### v0.14 - Validação de Horários (3.2)

**Novas funcionalidades:**
- **Função `vc_restaurant_is_open()`**: Verifica se restaurante está aberto em um timestamp específico
  - Verifica dia da semana e horário atual
  - Considera timezone do WordPress
  - Suporta períodos que cruzam a meia-noite (ex: 22:00 - 02:00)
  - Considera feriados configurados (meta `_vc_restaurant_holidays`)
  - Fallback para campo legado `_vc_is_open` se schedule não estiver configurado
- **Campo de Feriados**: Interface admin para adicionar datas de fechamento
  - Meta field: `_vc_restaurant_holidays` (JSON array de datas YYYY-MM-DD)
  - Botões para adicionar/remover feriados dinamicamente
  - Validação de formato de data
- **Funções auxiliares**: `Schedule_Helper` com métodos para:
  - `is_open()` - Verificar se está aberto
  - `is_holiday()` - Verificar se é feriado
  - `get_schedule()` - Obter horários estruturados
  - `get_next_open_time()` - Obter próximo horário de abertura

**Arquivos novos:**
- `inc/Utils/Schedule_Helper.php` - Classe helper para validação de horários

**Arquivos modificados:**
- `inc/meta-restaurants.php` - Adicionado campo de feriados e JavaScript para gerenciamento
- `vemcomer-core.php` - Registro da classe Schedule_Helper

### v0.13 - Sistema de Horários Estruturados (3.1)

**Novas funcionalidades:**
- **Horários estruturados em JSON**: Substituição do campo texto por estrutura JSON completa
  - Meta field: `_vc_restaurant_schedule` (JSON)
  - Formato: `{ "monday": { "enabled": true, "periods": [{"open": "09:00", "close": "22:00"}] }, ... }`
  - Suporte a múltiplos períodos por dia (ex: 09:00-14:00 e 18:00-22:00)
  - Interface admin visual para configurar horários por dia da semana
  - Checkbox para habilitar/desabilitar cada dia
  - Botões para adicionar/remover períodos por dia
  - Validação de formato HH:MM para horários
- **Compatibilidade**: Mantém campo legado `vc_restaurant_open_hours` (texto) para compatibilidade com código existente
- **JavaScript interativo**: Toggle de períodos, adicionar/remover períodos dinamicamente

**Arquivos modificados:**
- `inc/meta-restaurants.php` - Adicionada interface de horários estruturados e salvamento em JSON

### v0.12 - REST API de Cotação Expandida (2.3)

**Novas funcionalidades:**
- **Endpoint de cotação expandido**: `GET /wp-json/vemcomer/v1/shipping/quote` agora aceita parâmetros adicionais
  - `lat` (opcional) - Latitude do cliente
  - `lng` (opcional) - Longitude do cliente
  - `address` (opcional) - Endereço completo do cliente
  - `neighborhood` (opcional) - Bairro do cliente
- **Resposta expandida**: Retorna informações adicionais
  - `distance` - Distância calculada em km (se coordenadas fornecidas)
  - `within_radius` - Se está dentro do raio de entrega
  - `radius` - Raio máximo configurado
  - `is_open` - Se restaurante está aberto no momento
  - Detalhes de cada método de fulfillment (incluindo distância e erros)
- **Validações**: Verifica se restaurante existe, está publicado e está aberto
- **Integração completa**: Passa coordenadas e endereço para métodos de fulfillment (DistanceBasedDelivery)

**Arquivos modificados:**
- `inc/REST/Shipping_Controller.php` - Expandido para aceitar coordenadas e retornar informações adicionais
- `inc/Frontend/Shipping.php` - Modificado para aceitar dados adicionais do pedido

### v0.11 - Sistema de Frete por Distância (2.2)

**Novas funcionalidades:**
- **Método de Fulfillment DistanceBasedDelivery**: Implementação completa do cálculo de frete baseado em distância
  - Cálculo: `base_price + (distance * price_per_km)`
  - Verificação de raio máximo de entrega
  - Prioridade para preços por bairro (se configurado, usa preço do bairro em vez de cálculo por distância)
  - Verificação de pedido mínimo
  - Frete grátis acima de valor configurado
  - Cálculo automático de ETA baseado em distância (5 min/km)
  - Integração com função `vc_haversine_km` para cálculo de distância
- **Registro automático**: Método registrado no `FulfillmentRegistry` e disponível automaticamente

**Arquivos novos:**
- `inc/Checkout/Methods/DistanceBasedDelivery.php` - Classe do método de fulfillment por distância

**Arquivos modificados:**
- `inc/checkout.php` - Registro do método DistanceBasedDelivery

### v0.10 - Sistema de Frete por Distância (2.1)

**Novas funcionalidades:**
- **Configuração de Frete por Restaurante**: Campos no metabox do restaurante para configurar frete baseado em distância
  - Raio máximo de entrega (km)
  - Taxa base de entrega (R$)
  - Preço por quilômetro (R$)
  - Frete grátis acima de (R$)
  - Pedido mínimo (R$)
  - Preços por bairro (JSON) - permite configurar preços especiais por bairro com prioridade sobre cálculo por distância
- **Validação de JSON**: Validação automática do formato JSON para preços por bairro
- **Meta fields**: `_vc_delivery_radius`, `_vc_delivery_price_per_km`, `_vc_delivery_base_price`, `_vc_delivery_free_above`, `_vc_delivery_min_order`, `_vc_delivery_neighborhoods`

**Arquivos modificados:**
- `inc/meta-restaurants.php` - Adicionados campos de configuração de frete por distância no metabox

### v0.9 - Sistema de Complementos/Modificadores de Produtos (1.1 + 1.2 + 1.3)

**Novas funcionalidades:**
- **CPT `vc_product_modifier`**: Custom Post Type para gerenciar complementos/modificadores de produtos
  - Campos: tipo (obrigatório/opcional), preço, mínimo/máximo de seleção
  - Relacionamento Many-to-Many com `vc_menu_item` via meta fields
  - Meta fields: `_vc_modifier_type`, `_vc_modifier_price`, `_vc_modifier_min`, `_vc_modifier_max`
  - Meta field `_vc_modifier_menu_items` armazena array de IDs dos itens do cardápio relacionados
  - Meta field reverso `_vc_menu_item_modifiers` nos itens do cardápio para facilitar queries
- **Interface Admin**: Metabox completo com validações (mínimo <= máximo, preços não negativos)
- **Capabilities customizadas**: Permissões específicas para gerenciar modificadores
- **Submenu no Admin**: Adicionado "Modificadores" ao menu VemComer
- **REST API completa**: Endpoints para gerenciar modificadores via API
  - `GET /wp-json/vemcomer/v1/menu-items/{id}/modifiers` - Lista modificadores de um item (público)
  - `POST /wp-json/vemcomer/v1/menu-items/{id}/modifiers` - Criar modificador (admin)
  - `PATCH /wp-json/vemcomer/v1/modifiers/{id}` - Atualizar modificador (admin)
  - `DELETE /wp-json/vemcomer/v1/modifiers/{id}` - Deletar modificador (admin)
  - Validações: título obrigatório, mínimo <= máximo, preços não negativos
  - Gerenciamento automático de vínculos bidirecionais entre modificadores e itens do cardápio
- **Interface Admin Completa**: Metabox no `vc_menu_item` para gerenciar modificadores
  - Interface drag-and-drop para reordenar modificadores (jQuery UI Sortable)
  - Lista de modificadores vinculados e disponíveis
  - Adicionar/remover modificadores com um clique
  - Visualização de tipo (obrigatório/opcional), preço e limites (min/max)
  - Validações automáticas: mínimo <= máximo, preços não negativos
  - Sincronização automática de vínculos bidirecionais ao salvar

**Arquivos novos:**
- `inc/Model/CPT_ProductModifier.php` - Classe principal do CPT de modificadores
- `inc/REST/Modifiers_Controller.php` - Controller REST para endpoints de modificadores
- `inc/Admin/Modifiers_Metabox.php` - Metabox para gerenciar modificadores nos itens do cardápio

**Arquivos modificados:**
- `vemcomer-core.php` - Registro das classes CPT_ProductModifier, Modifiers_Controller e Modifiers_Metabox
- `inc/Admin/Menu_Restaurant.php` - Adicionado submenu para modificadores

### v0.8 - Sistema de Onboarding e Melhorias de Permissões

**Novas funcionalidades:**
- **Sistema de Onboarding para Donos de Restaurantes**: Guia interativo com 5 steps para configurar o restaurante
  - Step 1: Bem-vindo ao VemComer
  - Step 2: Complete seu perfil (WhatsApp, endereço, horários)
  - Step 3: Adicione itens ao cardápio (mínimo 3 itens)
  - Step 4: Configure delivery
  - Step 5: Veja sua página pública
- **Botão "Configuração Rápida"**: Botão no painel do restaurante que abre o onboarding sob demanda
- **Progresso persistente**: O progresso do onboarding é salvo e pode ser retomado a qualquer momento
- **Verificação automática**: Alguns steps são verificados automaticamente (perfil completo, itens no cardápio, delivery configurado)
- **Correção de permissões**: Usuários com role "lojista" agora têm acesso completo ao gerenciamento de cardápio
- **Capability `edit_posts`**: Adicionada à role "lojista" para permitir acesso ao admin do WordPress

**Arquivos novos:**
- `inc/Frontend/Onboarding.php` - Classe principal do sistema de onboarding
- `assets/css/onboarding.css` - Estilos do modal e componentes
- `assets/js/onboarding.js` - JavaScript com interatividade e AJAX
- `docs/ONBOARDING.md` - Documentação do sistema
- `docs/ONBOARDING_STEPS.md` - Detalhes de cada step
- `docs/ONBOARDING_VISUAL.md` - Visualização visual dos componentes

**Arquivos modificados:**
- `inc/Frontend/RestaurantPanel.php` - Integração do onboarding e botão de configuração rápida
- `inc/roles-capabilities.php` - Adicionada capability `edit_posts` à role "lojista"
- `inc/bootstrap.php` - Registro dos assets de onboarding
- `vemcomer-core.php` - Inicialização da classe Onboarding

### v0.7 - Sistema de Token de Acesso para Restaurantes Aprovados

**Novas funcionalidades:**
- Campo `access_url` adicionado ao modelo de restaurante (meta `vc_restaurant_access_url`)
- Geração automática de token único quando restaurante é aprovado
- Webhook `restaurant_approved` configurado com URL padrão do SMClick
- Campo `access_url` incluído no payload do webhook de aprovação
- Página de validação `/validar-acesso/?token={access_url}` para restaurantes criarem conta de acesso
- Formulário de validação com campos: email, senha e confirmação de senha
- Login automático e redirecionamento para painel após criação de conta
- Vinculação automática de usuário ao restaurante via meta `vc_restaurant_id`
- Concessão automática de permissões para o dono do restaurante editar seus dados e gerenciar itens de cardápio
- Nova role `Lojista` atribuída aos donos de restaurante, já com permissões de edição

**Arquivos modificados:**
- `inc/meta-restaurants.php` - Adicionado campo access_url no metabox
- `inc/Admin/class-vc-restaurants-table.php` - Geração de token na aprovação
- `inc/Integration/SMClick.php` - Geração de token, webhook configurado, payload atualizado
- `inc/Frontend/AccessValidation.php` - Nova classe para página de validação
- `vemcomer-core.php` - Registro da nova classe AccessValidation

## Recursos Backend Planejados

Para transformar o VemComer Core em um Marketplace de Delivery Híbrido completo, foi criado um documento detalhado com **25 recursos backend** necessários, organizados por prioridade e fases de implementação.

**Documentação completa**: [`docs/RECURSOS_BACKEND.md`](docs/RECURSOS_BACKEND.md)

### Principais recursos planejados:

**Fase 1 - Core Essencial:**
- ✅ Sistema de Complementos/Modificadores de Produtos (1.1 + 1.2 + 1.3 - Completo)
- ✅ Sistema de Frete por Distância e Bairro (2.1 + 2.2 + 2.3 - Completo)
- ✅ Sistema de Horários Estruturados (3.1 + 3.2 + 3.3 - Completo)
- ✅ Sistema de Avaliações e Ratings (4.1 + 4.2 + 4.3 - Completo)
- ✅ Sistema de Favoritos (5.1 + 5.2 - Completo)
- ✅ Sistema de Histórico de Pedidos para Clientes (6.1 + 6.2 - Completo)
- ✅ Sistema de Analytics/Cliques para Restaurantes (7.1 + 7.2 + 7.3 - Completo)
- Sistema de Horários Estruturados
- Sistema de Geração de Mensagem WhatsApp
- Sistema de Validação de Pedido

**Fase 2 - UX e Engajamento:**
- Sistema de Avaliações e Ratings
- Sistema de Favoritos
- Sistema de Histórico de Pedidos
- Sistema de Endereços de Entrega
- Sistema de Disponibilidade em Tempo Real

**Fase 3 - Analytics e SaaS:**
- Sistema de Analytics/Cliques
- Sistema de Planos/Assinaturas SaaS
- Sistema de Relatórios Avançados
- Sistema de Gestão de Usuários (Super Admin)

E mais 11 recursos adicionais para completar a plataforma.

## Desenvolvimento

* PHP ≥ 8.0, WP ≥ 6.0.
* Autoload interno + PSR‑4 simples (namespace `VC\*` mapeado para `inc/`).
* Sanitização e escapes seguindo o Handbook do WordPress.


## Troubleshooting

- **WP Pusher em PHP 8.2**: se o log mostrar `Creation of dynamic property Pusher\Log\Logger::$file` ou `Cannot declare class Elementor\Element_Column` (mesmo que o Elementor não esteja instalado), execute o script `bin/fix-wppusher-php82.php` descrito em [`docs/troubleshooting/wp-pusher.md`](docs/troubleshooting/wp-pusher.md).
