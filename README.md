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

### Modificadores de Produtos

* **GET** `/wp-json/vemcomer/v1/menu-items/{id}/modifiers` - Lista modificadores de um item do cardápio (público)
* **POST** `/wp-json/vemcomer/v1/menu-items/{id}/modifiers` - Criar modificador vinculado a um item (admin)
* **PATCH** `/wp-json/vemcomer/v1/modifiers/{id}` - Atualizar modificador (admin)
* **DELETE** `/wp-json/vemcomer/v1/modifiers/{id}` - Deletar modificador (admin)

### Pedidos

* **POST** `/wp-json/vemcomer/v1/pedidos`

* Body: `{ "restaurant_id": 123, "itens": [ {"produto_id": 123, "qtd": 2} ], "subtotal": "49,90", "fulfillment": { "method": "flat_rate_delivery", "ship_total": "9,90" } }`

### Fulfillment e Checkout

* O checkout público trabalha somente com **um restaurante por vez** e exige um método de fulfillment válido.
* Cada método implementa `VC\Checkout\FulfillmentMethod` (`inc/Checkout/FulfillmentMethod.php`).
* Registre seus métodos no action `vemcomer_register_fulfillment_method` — o registro padrão (`inc/Checkout/Methods/FlatRateDelivery.php`) aplica o frete fixo + pedido mínimo dos metadados do restaurante.
* Use os helpers JS em `assets/js/checkout.js` para testar rapidamente as rotas de frete/pedido (`window.VemComerCheckoutExamples.exampleQuote()` e `.exampleOrder()`).

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
- ✅ Sistema de Complementos/Modificadores de Produtos (1.1 - Estrutura de Dados implementada)
- Sistema de Frete por Distância e Bairro
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
