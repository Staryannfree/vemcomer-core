# Pedevem Core

## 🚨 TRIGGER AUTOMÁTICO: AAA

**IMPORTANTE:** Se você digitar **"AAA"** em qualquer mensagem, o assistente será **OBRIGADO** a analisar todos os logs antes de responder.

**AAA = "Analisa Automaticamente Agora"**

Veja: `AAA-PROTOCOLO-OBRIGATORIO.md` para detalhes completos.

---

Core de marketplace para WordPress com:
- CPTs: **Produtos**, **Pedidos**, **Restaurantes**, **Itens do Cardápio**.
- Admin Menu, REST API, Status de Pedido, Webhooks e Seed via WP‑CLI.
- Integrações: **WooCommerce** (sincroniza pedidos/status) e **Automator** (hooks customizados).

## Instalação e Ativação
1. Copie o plugin para `wp-content/plugins/vemcomer-core/`.
2. Ative **Pedevem Core** no painel do WordPress.
3. (Opcional) Configure **Pedevem ▸ Configurações** → Segredo do Webhook e integrações.

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

1. **Bem-vindo ao Pedevem!** - Tela inicial de boas-vindas
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

### WhatsApp

* **POST** `/wp-json/vemcomer/v1/orders/prepare-whatsapp` - Gera mensagem formatada para WhatsApp (body: `restaurant_id`, `items`, `customer`, `fulfillment`)

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

### v0.35 - Página de Teste de Reverse Geocoding no Admin

**Nova implementação:**
- **Página de Teste no Admin**: Interface para testar o reverse geocoding (conversão de coordenadas em endereço)
- **Formulário de Coordenadas**: Campos para inserir latitude e longitude
- **Exibição de Resultados**: Mostra rua, número, bairro, cidade, estado, CEP, país e endereço completo
- **Integração com Nominatim**: Usa a mesma API do OpenStreetMap usada no frontend
- **Dados Brutos**: Exibe JSON completo retornado pela API em um detalhes colapsável

**Arquivos criados:**
- `inc/Admin/Geocoding_Test.php` - Classe para renderizar a página de teste

**Arquivos modificados:**
- `vemcomer-core.php` - Inicialização da classe `Geocoding_Test`
- `README.md` - Documentação da funcionalidade

**Funcionalidades implementadas:**
- Formulário com validação de coordenadas (lat: -90 a 90, lng: -180 a 180)
- Botão "Testar Geocoding" que faz requisição à API Nominatim
- Exibição de resultados formatados (rua, cidade, estado, etc.)
- Tratamento de erros com mensagens amigáveis
- Loading state durante processamento
- Reutiliza a função `VemComerReverseGeocode.reverseGeocode()` se disponível, ou faz requisição direta

**Como usar:**
1. Acesse o admin do WordPress
2. Vá em **Pedevem → Teste Geocoding**
3. Informe a latitude e longitude (ex: -16.6864, -49.2643 para Goiânia)
4. Clique em "Testar Geocoding"
5. Veja os resultados exibidos abaixo do formulário

**Exemplo de coordenadas para teste:**
- Goiânia: Lat: -16.6864, Lng: -49.2643
- São Paulo: Lat: -23.5505, Lng: -46.6333
- Rio de Janeiro: Lat: -22.9068, Lng: -43.1729

### v0.34 - Mobile UI Moderno (Design Estilo iFood)

**Nova implementação:**
- **Design Mobile Completo**: Interface moderna estilo iFood para dispositivos móveis
- **Top Bar Aprimorado**: Logo, seletor de localização (com bairro) e botão de notificações
- **Hero Banner Carousel**: Carrossel de banners promocionais com navegação por dots e swipe
- **Stories Section**: Seção de stories estilo Instagram (estrutura pronta, aguardando integração com API)
- **Quick Actions**: Botões rápidos para Delivery, Reservas, Eventos e Promoções
- **Search Bar**: Barra de busca com filtros integrada
- **Seções de Conteúdo**:
  - Pratos do Dia (carrossel horizontal)
  - Restaurantes em Destaque (grid)
  - Todos os Restaurantes (grid com cards)
- **Cart Button Flutuante**: Botão de carrinho fixo com badge de quantidade
- **Story Viewer Modal**: Modal completo para visualização de stories com progress bars e navegação
- **Meta Tags Mobile**: Viewport otimizado, apple-mobile-web-app-capable, viewport-fit=cover

**Arquivos criados:**
- `theme-vemcomer/assets/css/mobile-ui.css` - Estilos completos do mobile UI (baseado no HTML fornecido)
- `theme-vemcomer/assets/js/mobile-ui.js` - JavaScript para funcionalidades (carousel, stories, notificações, cart)
- `theme-vemcomer/template-parts/home/mobile-home.php` - Template partial com HTML completo do design fornecido

**Arquivos modificados:**
- `theme-vemcomer/header.php` - Adicionado botão de notificações no top bar mobile e meta tags mobile otimizadas
- `theme-vemcomer/functions.php` - Enqueue de `mobile-ui.css` e `mobile-ui.js`
- `templates/page-home.php` - Detecção de mobile (`wp_is_mobile()`) e renderização do template mobile

**Como funciona:**
- Quando `wp_is_mobile()` retorna `true` na página home, o template `mobile-home.php` é carregado automaticamente
- O template renderiza o HTML completo baseado no design fornecido
- CSS e JavaScript são carregados automaticamente via WordPress enqueue
- O template busca dados reais do WordPress (banners, restaurantes, pratos) e renderiza dinamicamente

**Funcionalidades implementadas:**
- Banner carousel com auto-play e navegação por swipe
- Estrutura de stories (aguardando integração com API)
- Sistema de notificações (badge com contagem)
- Botão de carrinho com atualização dinâmica
- Cards de restaurantes com favoritos
- Cards de pratos com preços e badges
- JavaScript inline no template para stories viewer completo

**Próximos passos (Backend):**
- Integrar stories com API REST
- Implementar sistema de notificações
- Conectar pratos do dia com menu items destacados
- Integrar eventos gastronômicos
- Conectar favoritos com API

### v0.33.2 - Top Bar Mobile Exibe Bairro ao Invés de Cidade

**Novas funcionalidades:**
- **Top Bar Mobile - Exibição de Bairro**:
  - Prioriza exibição do bairro sobre a cidade no top bar mobile
  - Salva bairro no localStorage (`vc_user_neighborhood`) e cookie (`vc_user_neighborhood`)
  - Atualização automática quando localização é obtida via reverse geocoding
  - Fallback: se não houver bairro, exibe cidade; se não houver cidade, exibe endereço completo
- **Integração com Reverse Geocoding**:
  - Extrai bairro do Nominatim (suburb, neighbourhood, quarter)
  - Salva bairro em todas as funções de geolocalização
  - Sincroniza bairro entre localStorage e cookies

**Arquivos modificados:**
- `theme-vemcomer/header.php` - Prioriza bairro na exibição do top bar
- `theme-vemcomer/assets/js/mobile-app.js` - Busca bairro primeiro, depois cidade
- `theme-vemcomer/assets/js/home-improvements.js` - Função `saveNeighborhood()` para salvar bairro
- `assets/js/reverse-geocoding.js` - Salva bairro no localStorage
- `theme-vemcomer/functions.php` - Todas as funções de geolocalização agora salvam bairro

**Resultado:**
Top bar mobile agora exibe o nome do bairro (ex: "Centro", "Jardim América") ao invés da cidade, proporcionando informação mais específica e útil para o usuário.

### v0.33.1 - Página de Categorias e Menu Mobile Atualizado

**Novas funcionalidades:**
- **Item "Categorias" no Menu Mobile**:
  - Adicionado entre "Buscar" e "Pedidos" na bottom navigation
  - Link para `/categorias/` com ícone de grid
  - Estado ativo destacado quando na página de categorias
- **Shortcode `[vc_categories]`**:
  - Lista todas as categorias de restaurantes (taxonomia `vc_cuisine`)
  - Grid responsivo com cards de categoria
  - Exibe ícone ou imagem da categoria
  - Mostra contagem de restaurantes por categoria
  - Links para filtrar restaurantes por categoria
  - Suporte a imagens customizadas (meta `_vc_category_image`)
  - Ícones padrão para categorias comuns (pizza, brasileira, lanches, etc.)

**Arquivos modificados:**
- `theme-vemcomer/footer.php` - Adicionado item "Categorias" na bottom nav
- `inc/Frontend/Shortcodes.php` - Novo shortcode `sc_categories()` com grid de categorias

**Próximos passos:**
- Criar página `/categorias/` no WordPress com o shortcode `[vc_categories]`

### v0.33 - Navegação Mobile App Nativo (Estilo iFood)

**Novas funcionalidades:**
- **Estrutura CSS Mobile-First (`mobile-app.css`)**:
  - Ocultação de header padrão (`.site-header`, `#masthead`), footer padrão (`.site-footer`) e sidebars em telas < 768px
  - Ajuste do body: `padding-top: 60px` e `padding-bottom: 80px` para não esconder conteúdo atrás das barras fixas
  - Top bar fixa: `position: fixed; top: 0; z-index: 999` com fundo branco e sombra suave
  - Bottom nav fixa: `position: fixed; bottom: 0; z-index: 1000` com suporte a `safe-area-inset-bottom` (iPhone X+)
- **Bottom Navigation Bar (4 itens - estilo iFood)**:
  - Renderizada apenas com `wp_is_mobile()` no PHP
  - 4 ícones SVG inline: Início (Casa), Buscar (Lupa), Pedidos (Lista/Documento), Perfil (Usuário)
  - Lógica de UX: Item da página atual recebe classe `.active` (cor `#ea1d2c`)
  - Links: `/` (Início), `/busca` (Buscar), `/meus-pedidos` (Pedidos), `/minha-conta` (Perfil)
- **Header Minimalista (Mobile Only)**:
  - Exibido apenas com `wp_is_mobile()` no PHP
  - Barra simples com Logo pequeno (à esquerda) e texto "Entregar em: [Endereço Atual] ▾"
  - Integrado com sistema de geolocalização (atualização automática)
- **Categorias Estilo Pílulas (Carrossel Horizontal)**:
  - CSS: `display: flex; overflow-x: auto; white-space: nowrap; -webkit-overflow-scrolling: touch;`
  - Estilo: Botões arredondados (border-radius: 20px), fundo cinza claro (`#f2f2f2`), texto preto
  - Item ativo: Fundo vermelho (`#ea1d2c`), texto branco
  - Scrollbar oculta: `::-webkit-scrollbar { display: none; }`

**Arquivos novos:**
- `theme-vemcomer/assets/css/mobile-app.css` - Estilos completos para navegação mobile app
- `theme-vemcomer/assets/js/mobile-app.js` - JavaScript para integração e funcionalidades mobile

**Arquivos modificados:**
- `theme-vemcomer/footer.php` - Bottom navigation bar com ícones SVG e estados ativos
- `theme-vemcomer/header.php` - Top bar minimalista com seletor de endereço
- `theme-vemcomer/template-parts/home/section-categories.php` - Categorias estilo pílulas para mobile
- `theme-vemcomer/functions.php` - Enfileiramento de CSS e JS mobile app
- `theme-vemcomer/assets/js/home-improvements.js` - Disparo de evento customizado para atualização de endereço

**Resultado:**
Experiência mobile idêntica a um app nativo, com navegação controlada pelo polegar na parte inferior da tela, top bar minimalista e categorias em carrossel horizontal estilo pílulas.

### v0.32 - PWA (Progressive Web App) - Infraestrutura Completa

**Novas funcionalidades:**
- **Manifest.json**: Arquivo de manifesto PWA configurado
  - Nome: "VemComer"
  - Display: `standalone` (remove barra do navegador)
  - Theme Color: `#ea1d2c` (vermelho iFood)
  - Background: `#ffffff`
  - Ícones: 192x192 e 512x512 (placeholders configurados)
  - Shortcuts: Atalho para "Restaurantes"
- **Service Worker (`sw.js`)**: Estratégia de cache híbrida
  - **Cache Name**: `vemcomer-pwa-v1`
  - **App Shell Pré-cacheado**: Home, CSS críticos (style.css, main.css, product-modal.css)
  - **Estratégias de Cache**:
    - **Network First para APIs REST** (`/wp-json/`): Tenta rede primeiro, fallback para cache, retorna erro JSON se offline
    - **Cache First para Assets** (imagens, CSS, JS): Retorna cache imediatamente, atualiza em background (stale-while-revalidate)
    - **Network First para Navegação HTML**: Tenta rede primeiro, fallback para App Shell se offline
  - **Ignora**: Não intercepta `/wp-admin/` ou `/wp-login.php`
  - **Instalação e Ativação**: Auto-install, skip waiting, clients claim
- **Integração WordPress**:
  - **Meta Tags PWA**: Injetadas no `wp_head` (manifest, theme-color, apple-touch-icon, apple-mobile-web-app)
  - **Registro do Service Worker**: Script inline no footer para registrar SW automaticamente
  - **Rota Virtual `/sw.js`**: Rewrite rule para servir SW em escopo global
    - Query var `vemcomer_sw` para identificar requisição
    - Template redirect para servir arquivo com `Content-Type: application/javascript`
    - Flush automático de rewrite rules na ativação do tema

**Arquivos novos:**
- `theme-vemcomer/manifest.json` - Manifesto PWA
- `theme-vemcomer/assets/js/sw.js` - Service Worker com estratégia de cache
- `theme-vemcomer/PWA_SETUP.md` - Documentação de configuração e setup

**Arquivos modificados:**
- `theme-vemcomer/functions.php` - Integração PWA completa (meta tags, registro SW, rewrite rules)

**Próximos passos:**
- Criar ícones PWA (192x192 e 512x512) e salvar em `theme-vemcomer/assets/images/`
- Fazer flush de rewrite rules (Configurações → Links Permanentes → Salvar)
- Testar instalação PWA no mobile e desktop

### v0.31 - Modal de Upgrade e Pricing (10.3)

**Novas funcionalidades:**
- **Modal de Pricing (Tabela de Preços)**:
  - Interface moderna com 3 colunas (Vitrine, Delivery Pro, Growth).
  - Destaque visual para o plano "Delivery Pro" (Recomendado).
  - Lista de recursos comparativa (check/cross).
- **Fluxo de Assinatura**:
  - Botões de "Assinar" redirecionam para o WhatsApp do suporte com mensagem pré-formatada contendo o nome do restaurante e o plano desejado.
- **Pontos de Entrada**:
  - Links de upgrade espalhados pelo painel (widget lateral, alerta de limite, blur de analytics) abrem o modal automaticamente.

**Arquivos novos:**
- `assets/js/admin-panel.js` - Lógica de interação do painel e modal

**Arquivos modificados:**
- `inc/Frontend/RestaurantPanel.php` - Inclusão do HTML do modal e enfileiramento do JS
- `assets/css/admin-panel-basic.css` - Estilos do modal e tabela de preços

### v0.30 - Frontend e Dashboard para Plano Vitrine/Básico (10.1 + 10.2)

**Novas funcionalidades:**
- **Layout de Loja Pública Simplificado (Plano Vitrine)**:
  - CSS específico para plano básico (`frontend-basic-plan.css`).
  - Botão "Adicionar" direto (sem modal complexo).
  - Layout de cards de itens simplificado (lista).
  - Rodapé "Powered by VemComer" obrigatório.
- **Painel do Restaurante Limitado**:
  - Widget "Seu Plano: VITRINE" com barra de progresso de itens usados.
  - Alerta visual quando o limite de itens está próximo (80%).
  - Card de Analytics com efeito "Blur Overlay" e botão de upgrade para bloquear acesso a dados avançados.
  - Bloqueio visual de itens de menu não permitidos no plano.

**Arquivos novos:**
- `assets/css/frontend-basic-plan.css` - Estilos para a loja pública básica
- `assets/css/admin-panel-basic.css` - Estilos para o painel admin limitado

**Arquivos modificados:**
- `inc/Frontend/RestaurantPanel.php` - Integração de widgets de plano e restrições visuais
- `inc/Frontend/Shortcodes.php` - Renderização condicional do layout simplificado

### v0.29 - Sistema de Planos/Assinaturas SaaS - Enforcers e Seed (9.1 + 9.2 + 9.3)

**Novas funcionalidades:**
- **Gestão de Planos**:
  - Planos criados automaticamente via seed: **Vitrine (Grátis)**, **Delivery Pro** e **Gestão & Growth**.
  - Menu de gestão de planos exposto para o Admin ("VemComer > Planos de Assinatura").
  - Atribuição de plano ao restaurante via metabox lateral no editor do restaurante.
- **Limites e Restrições (Enforcers)**:
  - **Limite de Itens**: Bloqueia criação de novos itens no cardápio se o limite do plano for atingido (ex: 20 itens no Vitrine).
  - **Permissão de Modificadores**: Bloqueia criação de modificadores se o plano não permitir (ex: Vitrine não tem modificadores).
  - **Mensagem WhatsApp Dinâmica**:
    - **Vitrine**: Envia mensagem de texto simples.
    - **Pro/Growth**: Envia mensagem formatada rica (negrito, separadores, detalhes).
- **Integração Frontend**:
  - O checkout detecta o plano e ajusta a mensagem enviada ao WhatsApp automaticamente.

**Arquivos novos:**
- `inc/Utils/Plan_Seeder.php` - Criação automática dos planos padrão

**Arquivos modificados:**
- `inc/Admin/Menu_Restaurant.php` - Adicionado submenu de Planos
- `inc/Model/CPT_Restaurant.php` - Metabox de seleção de plano
- `inc/Model/CPT_MenuItem.php` - Validação de limite de itens
- `inc/Model/CPT_ProductModifier.php` - Validação de permissão de modificadores
- `inc/WhatsApp/Message_Formatter.php` - Templates dinâmicos (simples vs rico)
- `inc/REST/Orders_Controller.php` - Passagem de dados para formatação
- `vemcomer-core.php` - Execução do seed automático

### v0.28+ - Implementação Completa de Recursos Backend (Seções 8-25.1)

**Todas as seções de 8 a 25.1 foram implementadas:**

- ✅ **Seção 8**: Sistema de Banners da Home (CPT, REST API completa)
- ✅ **Seção 9**: Sistema de Planos/Assinaturas SaaS (CPT, limites, validação, REST API)
- ✅ **Seção 10**: Sistema de Geração de Mensagem WhatsApp (Message_Formatter, REST API)
- ✅ **Seção 11**: Sistema de Endereços de Entrega (Helper, REST API, Geocodificação)
- ✅ **Seção 12**: Sistema de Disponibilidade em Tempo Real (Helper, REST API)
- ✅ **Seção 13**: Sistema de Categorias de Cardápio Robusto (ordem, imagem, REST API)
- ✅ **Seção 14**: Sistema de Busca Avançada (full-text, filtros, ordenação)
- ✅ **Seção 15**: Sistema de Notificações (Manager, REST API)
- ✅ **Seção 16**: Sistema de Tempo Estimado de Entrega Dinâmico (Calculator, REST API)
- ✅ **Seção 17**: Sistema de Preços por Bairro (já implementado, melhorado)
- ✅ **Seção 18**: Sistema de Múltiplos Métodos de Fulfillment (Pickup adicionado)
- ✅ **Seção 19**: Sistema de Gestão de Imagens Otimizadas (Image_Optimizer)
- ✅ **Seção 20**: Sistema de Validação de Pedido Antes do WhatsApp (Validator, REST API)
- ✅ **Seção 21**: Sistema de Cache Inteligente (Cache_Manager, REST API de invalidação)
- ✅ **Seção 22**: Sistema de Relatórios Avançados (Restaurant_Reports, REST API)
- ✅ **Seção 23**: Sistema de Cupons/Descontos Completo (CPT, Validator, REST API)
- ✅ **Seção 24**: Sistema de Gestão de Usuários Super Admin (Admin_Controller)
- ✅ **Seção 25.1**: Sistema de Logs e Auditoria Avançado (Audit_Controller, export CSV)

**Total de arquivos criados/modificados:** 50+ arquivos
**Total de endpoints REST adicionados:** 30+ endpoints
**Todas as funcionalidades críticas e importantes implementadas!**

### v0.44 - Frontend Completo - Integração Total com Backend

**Implementação completa do frontend cobrindo todas as funcionalidades backend:**

#### Fase 1: Core do Checkout
- ✅ **Modal de Produto com Modificadores** (Fase 1.1)
  - Modal responsivo (bottom sheet no mobile, centralizado no desktop)
  - Carrega modificadores via REST API `/menu-items/{id}/modifiers`
  - Valida modificadores obrigatórios e min/max
  - Adiciona itens com modificadores ao carrinho
  - Cálculo correto de preços incluindo modificadores

- ✅ **Checkout Completo com WhatsApp** (Fase 1.2)
  - Validação de pedido antes de finalizar (`/orders/validate`)
  - Geração de mensagem WhatsApp formatada (`/orders/prepare-whatsapp`)
  - Coleta dados do cliente (nome, telefone, endereço)
  - Abre WhatsApp automaticamente com mensagem pronta
  - Remove criação de pedido direto (usa validação + WhatsApp)

- ✅ **Múltiplos Métodos de Fulfillment** (Fase 1.3)
  - UI de seleção entre Delivery e Pickup
  - Exibe preço e ETA de cada método
  - Atualiza cálculo de frete baseado na escolha

#### Fase 2: Social Proof
- ✅ **Ratings nos Cards** (Fase 2.1)
  - Exibe estrelas e avaliação média nos cards de restaurante
  - Integrado com `Rating_Helper`
  - Formatação visual consistente

- ✅ **Seção de Reviews** (Fase 2.2)
  - Shortcode `[vc_reviews]` integrado no template single
  - Carrega reviews via REST API
  - Formulário para criar nova avaliação
  - Paginação de resultados

#### Fase 3: Disponibilidade
- ✅ **Status Aberto/Fechado** (Fase 3.1)
  - Badges visuais nos cards (Aberto/Fechado)
  - Mostra próximo horário de abertura se fechado
  - Bloqueia checkout se restaurante fechado
  - Verificação em tempo real via `Schedule_Helper`

- ✅ **Horários Estruturados** (Fase 3.2)
  - Migração de campo texto para JSON estruturado
  - Suporta múltiplos períodos por dia
  - Formatação legível nos shortcodes
  - Fallback para campo legado

#### Fase 4: Engajamento
- ✅ **Sistema de Favoritos** (Fase 4.1)
  - Botões de favorito nos cards de restaurante e itens
  - Shortcode `[vc_favorites]` para listar favoritos
  - Integração com REST API `/favorites/*`
  - Atualização visual em tempo real

- ✅ **Endereços de Entrega** (Fase 4.2)
  - Interface para gerenciar endereços salvos
  - Seleção de endereço no checkout
  - Preenchimento automático de campos
  - Suporta criar, editar, deletar e definir principal

#### Fase 5: Descoberta
- ✅ **Busca Avançada** (Fase 5.1)
  - Filtros: min_rating, is_open_now, has_delivery, price_range
  - Busca full-text em restaurantes e itens
  - UI organizada e responsiva

- ✅ **Filtros Combinados** (Fase 5.2)
  - Suporta múltiplos filtros simultâneos
  - Filtros aplicados no shortcode `[vc_restaurants]`
  - Botão para limpar filtros

#### Fase 6: Funcionalidades Extras
- ✅ **Banners** (Fase 6.1)
  - Shortcode `[vc_banners]` para exibir banners
  - Layout responsivo com grid
  - Suporta links e imagens
  - Filtro por restaurante

- ✅ **Notificações** (Fase 6.2)
  - Shortcode `[vc_notifications]` para exibir notificações
  - Badge com contador de não lidas
  - Marcar como lida e marcar todas como lidas
  - Formatação de data relativa

- ✅ **Histórico de Pedidos** (Fase 6.3)
  - Shortcode `[vc_orders_history]` para listar pedidos
  - Filtro por status
  - Paginação de resultados
  - Exibe detalhes completos (itens, total, frete, desconto)

#### Fase 7: Melhorias e Otimizações
- ✅ **Cálculo Correto de Preços**
  - Inclui modificadores no cálculo do subtotal
  - Exibe preço total por item no carrinho
  - Cálculo correto no checkout

**Arquivos criados:**
- `assets/css/product-modal.css`, `assets/js/product-modal.js`
- `assets/css/favorites.css`, `assets/js/favorites.js`
- `assets/css/addresses.css`, `assets/js/addresses.js`, `assets/js/checkout-addresses.js`
- `assets/css/banners.css`
- `assets/css/notifications.css`, `assets/js/notifications.js`
- `assets/css/orders-history.css`, `assets/js/orders-history.js`
- `inc/shortcodes/favorites.php`, `inc/shortcodes/banners.php`
- `inc/shortcodes/notifications.php`, `inc/shortcodes/orders-history.php`

**Total de funcionalidades frontend implementadas:** 15 fases completas
**Cobertura do backend:** 100% das funcionalidades críticas e importantes integradas

### v0.27 - Sistema de Geração de Mensagem WhatsApp (10.1 + 10.2 + 10.3)

**Novas funcionalidades:**
- **Classe `Message_Formatter`**: Formatador de mensagens WhatsApp
  - Template configurável via filtro `vemcomer/whatsapp_message_template`
  - Suporte a itens com modificadores
  - Formatação de valores monetários
  - Geração de URL do WhatsApp (`wa.me`)
- **Endpoint REST**: `POST /orders/prepare-whatsapp`
  - Valida: restaurante existe, está aberto, tem WhatsApp configurado
  - Retorna: mensagem formatada e URL do WhatsApp
  - Suporta: delivery e pickup, modificadores, cálculo de totais

**Arquivos novos:**
- `inc/WhatsApp/Message_Formatter.php` - Formatador de mensagens

**Arquivos modificados:**
- `inc/REST/Orders_Controller.php` - Adicionado endpoint prepare-whatsapp

### v0.26 - Sistema de Banners da Home (8.1 + 8.2)

**Novas funcionalidades:**
- **CPT `vc_banner`**: Custom Post Type para banners da home
  - Campos: imagem (thumbnail), título, link, restaurante_id (opcional), ordem, ativo
  - Meta fields: `_vc_banner_link`, `_vc_banner_restaurant_id`, `_vc_banner_order`, `_vc_banner_active`
- **REST API completa**:
  - `GET /banners` - Lista banners ativos (público, ordenados)
  - `POST /banners` - Criar banner (admin)
  - `PATCH /banners/{id}` - Atualizar banner (admin)
  - `DELETE /banners/{id}` - Deletar banner (admin)
- **Interface admin**: Metabox completo e colunas customizadas

**Arquivos novos:**
- `inc/Model/CPT_Banner.php` - CPT de banners
- `inc/REST/Banners_Controller.php` - Controller REST

**Arquivos modificados:**
- `inc/Admin/Menu_Restaurant.php` - Adicionado submenu "Banners"
- `vemcomer-core.php` - Registro do CPT e controller

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

**Análise de Integração Shortcodes ↔ Backend**: [`docs/ANALISE_INTEGRACAO_SHORTCODES.md`](docs/ANALISE_INTEGRACAO_SHORTCODES.md) - Documento detalhado identificando lacunas entre funcionalidades do backend e integração nos shortcodes/frontend.

### Principais recursos planejados:

**Fase 1 - Core Essencial:**
- ✅ Sistema de Complementos/Modificadores de Produtos (1.1 + 1.2 + 1.3 - Completo)
- ✅ Sistema de Frete por Distância e Bairro (2.1 + 2.2 + 2.3 - Completo)
- ✅ Sistema de Horários Estruturados (3.1 + 3.2 + 3.3 - Completo)
- ✅ Sistema de Avaliações e Ratings (4.1 + 4.2 + 4.3 - Completo)
- ✅ Sistema de Favoritos (5.1 + 5.2 - Completo)
- ✅ Sistema de Histórico de Pedidos para Clientes (6.1 + 6.2 - Completo)
- ✅ Sistema de Analytics/Cliques para Restaurantes (7.1 + 7.2 + 7.3 - Completo)
- ✅ Sistema de Banners da Home (8.1 + 8.2 - Completo)
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

### Git e Deploy (WP Pusher)

**⚠️ IMPORTANTE:** Este projeto usa **WP Pusher** para sincronizar automaticamente com o site na Hostinger. Sempre faça commit e push após alterações.

#### Comandos para Commit e Push

```bash
# 1. Verificar arquivos modificados
git status

# 2. Adicionar arquivos específicos
git add caminho/do/arquivo.php

# OU adicionar todos os arquivos modificados
git add .

# 3. Criar commit com mensagem descritiva
git commit -m "Descrição clara do que foi alterado"

# 4. Enviar para o GitHub (sincroniza automaticamente via WP Pusher)
git push origin main
```

#### Exemplo Completo

```bash
# Ver o que mudou
git status

# Adicionar arquivos modificados
git add inc/REST/Subscription_Controller.php README.md

# Commit
git commit -m "Adiciona webhook do Mercado Pago para assinaturas pagas"

# Push para GitHub
git push origin main
```

**Nota:** Se sua branch principal for `master` em vez de `main`, use:
```bash
git push origin master
```

#### Verificar se o Push Funcionou

Após o push, o WP Pusher na Hostinger deve sincronizar automaticamente. Você pode verificar:
1. No painel do WordPress → Plugins → WP Pusher (verificar logs)
2. No GitHub → Verificar se o commit aparece no histórico

## Troubleshooting

- **WP Pusher em PHP 8.2**: se o log mostrar `Creation of dynamic property Pusher\Log\Logger::$file` ou `Cannot declare class Elementor\Element_Column` (mesmo que o Elementor não esteja instalado), execute o script `bin/fix-wppusher-php82.php` descrito em [`docs/troubleshooting/wp-pusher.md`](docs/troubleshooting/wp-pusher.md).

### Problema do Popup de Boas-Vindas e Solução

**Problema identificado:**
O popup de boas-vindas na home page não aparecia e os botões não eram clicáveis, mesmo com o HTML presente no DOM. Isso ocorria devido a:

1. **Conflitos de CSS**: Estilos externos (plugins, tema) sobrescreviam os estilos do popup, especialmente `z-index`, `display`, `pointer-events` e `visibility`.
2. **Problemas de timing**: O JavaScript do tema executava antes do popup estar completamente renderizado no DOM, causando falhas na inicialização.
3. **Ordem de carregamento**: Scripts externos carregavam depois do script do tema, interferindo nos event listeners.

**Solução implementada:**
Foi criada uma função `vemcomer_force_popup_and_cards()` em `theme-vemcomer/functions.php` que:

1. **CSS inline no footer** (prioridade 9999): CSS com `!important` para sobrescrever estilos conflitantes, garantindo que o popup tenha `z-index` alto (2147483647) e `pointer-events` corretos.
2. **JavaScript inline no footer**: Executa imediatamente no `DOMContentLoaded`, sem depender de outros scripts, garantindo que:
   - O popup seja inicializado corretamente
   - Os event listeners sejam anexados aos botões
   - Múltiplas tentativas de inicialização (imediatamente, após 2s e após 5s)
3. **Event delegation**: Usa event delegation no `document` para capturar cliques mesmo se elementos forem re-renderizados.

**Arquivos modificados:**
- `theme-vemcomer/functions.php` - Função `vemcomer_force_popup_and_cards()` adicionada
- `theme-vemcomer/assets/js/home-improvements.js` - Múltiplas abordagens de detecção e inicialização
- `theme-vemcomer/assets/css/home-improvements.css` - Estilos com `!important` para garantir exibição

**Resultado:**
O popup agora funciona corretamente, aparecendo após 1 segundo na home page e permitindo que os usuários:
- Cliquem em "Ver restaurantes perto de mim" para obter localização GPS
- Cliquem em "Pular por enquanto" para fechar o popup
- Vejam o popup novamente apenas após limpar o cookie `vc_welcome_popup_seen`

---

## v0.36 - Integração Mobile com API REST

**Data:** 2024-12-XX

**Objetivo:**
Conectar o "App Shell" mobile (`theme-vemcomer/template-parts/content-mobile-home.php`) com a API REST real do backend, substituindo todos os dados hardcoded por chamadas dinâmicas.

**Implementação:**

### 1. Funções de Mapeamento de Dados
- `mapApiBannerToBanner()` - Mapeia resposta da API de banners para formato do frontend
- `mapApiRestaurantToRestaurant()` - Mapeia resposta da API de restaurantes
- `mapApiRestaurantToFeatured()` - Mapeia restaurantes para seção de destaques

### 2. Funções de Busca de Dados
- `fetchBanners()` - Busca banners ativos via `GET /wp-json/vemcomer/v1/banners`
- `fetchRestaurants(params)` - Busca restaurantes via `GET /wp-json/vemcomer/v1/restaurants` com suporte a:
  - `per_page` - Limite de resultados
  - `orderby` - Ordenação (title, date, rating)
  - `order` - Direção (asc, desc)
  - `search` - Busca por texto
- `fetchFeaturedRestaurants()` - Busca 4 restaurantes com maior rating
- `getRestaurantImage(restaurantId)` - Busca imagem destacada via WordPress REST API padrão

### 3. Renderização Dinâmica
- `renderBanners()` - Renderiza banners do carousel (agora assíncrono)
- `renderRestaurants()` - Renderiza lista completa de restaurantes
- `renderFeatured()` - Renderiza restaurantes em destaque
- Todas as funções mostram skeleton loading durante carregamento

### 4. Inicialização Assíncrona
- Função `initApp()` criada para carregar todos os dados em paralelo
- Uso de `Promise.all()` para otimizar carregamento
- Skeleton loading em todas as seções

**Arquivos modificados:**
- `theme-vemcomer/assets/js/mobile-shell-v2.js` - Refatoração completa para usar API REST

**Endpoints utilizados:**
- `GET /wp-json/vemcomer/v1/banners` - Lista banners ativos
- `GET /wp-json/vemcomer/v1/restaurants` - Lista restaurantes com filtros
- `GET /wp-json/wp/v2/vc_restaurant/{id}?_embed=true` - Busca imagem destacada

**Endpoints implementados:**
- `GET /wp-json/vemcomer/v1/menu-items?featured=true` - Pratos do Dia
- `GET /wp-json/vemcomer/v1/events?featured=true&date=today` - Eventos do dia
- `GET /wp-json/vemcomer/v1/restaurants?featured=true` - Restaurantes em Destaque
- `POST /wp-json/vemcomer/v1/menu-items/{id}/toggle-featured` - Toggle rápido de Prato do Dia
- `POST /wp-json/vemcomer/v1/restaurants/{id}/toggle-featured` - Toggle rápido de Restaurante em Destaque

**Mantido hardcoded (endpoints futuros):**
- Stories (storiesData) - Aguardando endpoint de stories

**Melhorias futuras:**
- Adicionar campo `image` na resposta da API de restaurantes (evitar requisições extras)
- Adicionar campos `delivery_time` e `delivery_fee` na API
- Criar endpoint para Stories
- Implementar cache de imagens no frontend

## Toggle Rápido de Featured (Admin)

### Pratos do Dia
Na lista de **Itens do Cardápio** (`Pedevem > Itens do Cardápio`), há uma coluna **"⭐ Prato do Dia"** com checkbox que permite marcar/desmarcar rapidamente sem entrar no item individualmente.

### Restaurantes em Destaque
Na lista de **Restaurantes** (`Pedevem > Restaurantes`), há uma coluna **"⭐ Em Destaque"** com checkbox que permite marcar/desmarcar rapidamente sem entrar no restaurante individualmente.

**Como usar:**
1. Acesse a lista de Menu Items ou Restaurantes
2. Clique no checkbox na coluna "⭐ Prato do Dia" ou "⭐ Em Destaque"
3. A atualização é feita via AJAX sem recarregar a página
4. Uma notificação confirma a ação

**Backend:**
- Meta field `_vc_menu_item_featured` para menu items
- Meta field `_vc_restaurant_featured` para restaurantes
- Endpoints REST para toggle via AJAX
- JavaScript `admin-quick-toggle.js` para interação sem reload

## v0.47 - Sistema de Smart Fallback para Imagens

**Nova implementação:**
- **Sistema de Placeholders Inteligentes**: Sistema completo de fallback baseado em categoria para restaurantes e itens sem imagem
- **Objeto PLACEHOLDERS**: 20+ categorias com imagens do Unsplash (pizza, japonesa, lanches, açaí, brasileira, etc.)
- **Função `getSmartImage()`**: Análise inteligente de texto (nome + categoria) para decidir qual placeholder usar
- **Função `getLogoFallback()`**: Gera avatares com iniciais e cores para logos faltantes
- **Validação de Imagens**: Helper `PLACEHOLDERS.isValid()` para validar URLs de imagem
- **Proteção Multi-Camada**:
  1. **Camada 1 (API Check)**: Se API retorna `null`, usa `getSmartImage()` imediatamente
  2. **Camada 2 (Contexto)**: Analisa nome + categoria para escolher placeholder correto
  3. **Camada 3 (404 Protection)**: `onerror` substitui automaticamente se URL estiver quebrada
  4. **Camada 4 (Logo)**: Avatares com iniciais quando não há logo

**Arquivos modificados:**
- `theme-vemcomer/assets/js/mobile-shell-v2.js` - Sistema completo de smart fallback
- `theme-vemcomer/assets/css/mobile-shell-v2.css` - Estilos para logo-fallback e object-fit

**Funcionalidades:**
- Normalização de texto (remove acentos, lowercase)
- Match parcial (ex: "pizza italiana" encontra "pizza")
- Palavras-chave adicionais (hamburguer, refri, temaki, etc.)
- Cores consistentes para avatares baseadas em hash do nome
- Todas as imagens têm `onerror` com fallback inteligente
- CSS com `object-fit: cover` para evitar distorção

**Exemplos:**
- Restaurante "Pizzaria do João" sem foto → mostra imagem de pizza
- Prato "Coca Cola" sem foto + categoria "Bebidas" → mostra foto de bebida
- Restaurante "Açaí da Vila" sem logo → mostra círculo roxo com "A"
- Link quebrado (404) → substituído automaticamente pelo placeholder

## v0.46 - Modo Cardápio Digital Standalone

**Nova implementação:**
- **Dois Modos de Visualização Mobile**:
  1. **Modo Marketplace**: Navegação completa com vários restaurantes (modo atual)
  2. **Modo Cardápio Digital (Standalone)**: Link direto de restaurante, navegação blindada

**Funcionalidades:**
- **Detecção de Contexto**: Função `vc_is_standalone_mode()` detecta `?mode=menu` ou `/cardapio/{slug}`
- **Rewrite Rule**: URL `/cardapio/{slug}` aponta para restaurante com modo standalone
- **CSS Adaptado**: Classe `vc-standalone-mode` no body esconde elementos do marketplace:
  - Logo do marketplace
  - Links Home e Busca na bottom nav
  - Seção de restaurantes relacionados
- **Bottom Nav Adaptada**: 
  - Marketplace: 5 itens (Início, Buscar, Categorias, Pedidos, Perfil)
  - Standalone: 3 itens (Cardápio, Info, Pedidos)
- **Modal de Informações**: Modal com dados do restaurante (endereço, telefone, WhatsApp, horários)
- **Link do Cardápio Digital**: Seção no metabox do restaurante com link copiável

**Arquivos criados/modificados:**
- `theme-vemcomer/functions.php` - Função `vc_is_standalone_mode()` e rewrite rules
- `theme-vemcomer/header.php` - Classe `vc-standalone-mode` no body
- `theme-vemcomer/footer.php` - Bottom nav adaptada para modo standalone
- `theme-vemcomer/assets/css/mobile-shell-v2.css` - Estilos para modo standalone
- `templates/single-vc-restaurant.php` - Modal de informações
- `inc/meta-restaurants.php` - Link do cardápio digital no metabox

**URLs suportadas:**
- `https://seusite.com.br/restaurante/{slug}/?mode=menu`
- `https://seusite.com.br/cardapio/{slug}/`

**Como usar:**
1. Edite um restaurante no admin
2. No metabox "Informações do restaurante", role até "Link do Cardápio Digital"
3. Clique em "Copiar Link"
4. Compartilhe o link com seus clientes

**Resultado:**
Clientes que acessam o link do cardápio digital veem apenas o restaurante específico, sem opções de navegação para outros restaurantes, focando no pedido rápido.

## v0.48 - Correção Force Feed para Placeholders

**Problema identificado:**
Os placeholders não estavam funcionando corretamente - restaurantes sem imagem continuavam aparecendo com espaço em branco ou ícone de imagem quebrada. O problema estava na ordem de execução: o código dependia apenas do `onerror` (reativo), que não disparava como esperado.

**Solução implementada:**
- **Abordagem "Force Feed"**: Os placeholders agora são aplicados ANTES da renderização, não dependendo apenas do `onerror`
- **Função `isValidImage()` robusta**: Validação prévia que verifica se URL é válida (não aceita strings vazias, null, undefined, ou "placeholder")
- **Fallback na Origem**: Funções de mapeamento (`mapApiRestaurantToRestaurant`, `mapApiProductToDish`) agora decidem a imagem ANTES de criar o HTML
- **Logo Wrapper**: Container HTML específico para o logo (`card-logo-wrapper`) com lógica CSS inline para garantir que, se a imagem falhar, a "bolinha colorida com a letra" apareça no lugar exato
- **CSS Aprimorado**: 
  - `min-height: 100%` nas imagens para forçar altura
  - `background-color: #eee` enquanto carrega
  - Estilos específicos para `.card-logo-wrapper` com posicionamento absoluto

**Arquivos modificados:**
- `theme-vemcomer/assets/js/mobile-shell-v2.js` - Função `isValidImage()`, refatoração de `mapApiRestaurantToRestaurant()`, atualização de `renderRestaurants()`, `renderFeatured()`, `renderDishes()`
- `theme-vemcomer/assets/css/mobile-shell-v2.css` - CSS aprimorado para logo fallback e imagens

**O que mudou:**
1. **Validação Prévia**: Antes, o código podia aceitar uma string vazia `""` ou `null` como URL válida. Agora ele checa explicitamente.
2. **Fallback na Origem**: A função `mapApi...` agora decide a imagem. Se não tiver na API, ela **já injeta o placeholder** antes mesmo de criar o HTML. O `onerror` vira apenas uma rede de segurança para links quebrados (404).
3. **Logo Wrapper**: Container HTML específico para o logo com lógica CSS inline para garantir que, se a imagem falhar, o avatar com inicial apareça no lugar exato.

**Resultado:**
Todas as imagens agora têm garantia de exibição - se não tiverem URL válida na API, o placeholder inteligente é aplicado imediatamente, antes da renderização. O `onerror` funciona como backup secundário apenas para links quebrados (404).

## v0.49 - Correção de Links de Restaurantes

**Problema identificado:**
Os links dos restaurantes não estavam funcionando - ao clicar nos cards de restaurantes, não havia redirecionamento. O problema estava na implementação dos event handlers usando `onclick` inline, que não funcionavam corretamente após renderização dinâmica.

**Solução implementada:**
- **Event Delegation**: Substituição de `onclick` inline por event delegation no `document`
- **Funções no Escopo Global**: Todas as funções de navegação (`openRestaurant`, `openDish`, `openEvent`, etc.) agora estão no objeto `window` para garantir acesso global
- **Data Attributes**: Cards de restaurantes agora usam `data-restaurant-id` e `data-restaurant-url` para armazenar informações
- **URLs Corretas**: Suporte a slug quando disponível, fallback para ID
- **Event Listeners Dedicados**: 
  - `attachRestaurantCardListeners()` - Para cards da lista principal
  - `attachFeaturedCardListeners()` - Para cards em destaque
  - `attachSearchResultListeners()` - Para resultados de busca
- **Prevenção de Propagação**: Botões de favorito e reserva usam `stopPropagation()` corretamente

**Arquivos modificados:**
- `theme-vemcomer/assets/js/mobile-shell-v2.js` - Refatoração completa dos event handlers

**O que mudou:**
1. **Remoção de `onclick` inline**: Todos os `onclick="openRestaurant(${id})"` foram substituídos por data attributes
2. **Event Delegation**: Um único listener no `document` captura todos os cliques nos cards
3. **URLs Dinâmicas**: Suporte a slug quando disponível na API, fallback para ID
4. **Inicialização Garantida**: Event listeners são anexados na inicialização do app e após cada renderização

**Resultado:**
Todos os links de restaurantes agora funcionam corretamente:
- Cards da lista principal redirecionam para `/restaurante/{id}/`
- Cards em destaque funcionam da mesma forma
- Resultados de busca redirecionam corretamente
- Botões de favorito e reserva não interferem na navegação

## v0.50 - Padronização de URLs de Restaurantes para /restaurante/{id}/

**Problema identificado:**
As URLs de restaurantes estavam usando o padrão `/restaurant/{slug}/` (em inglês, com slug), mas o padrão desejado é `/restaurante/{id}/` (em português, usando ID numérico).

**Solução implementada:**
- **Mudança de Slug do CPT**: Alterado de `'restaurant'` para `'restaurante'` (português, singular)
- **Rewrite Rule Customizado**: Criado rewrite rule `^restaurante/([0-9]+)/?$` para aceitar apenas IDs numéricos
- **Template Redirect**: Implementado `template_redirect_by_id()` para buscar restaurante por ID e carregar o template correto
- **Filtro de Permalink**: Adicionado filtro `post_type_link` para que `get_permalink()` sempre retorne `/restaurante/{id}/`
- **JavaScript Atualizado**: Todas as referências no JavaScript agora usam `/restaurante/{id}/` ao invés de slug

**Arquivos modificados:**
- `inc/Model/CPT_Restaurant.php` - Mudança de slug, rewrite rules, template redirect e filtro de permalink
- `theme-vemcomer/assets/js/mobile-shell-v2.js` - URLs atualizadas para usar ID

**O que mudou:**
1. **Slug do CPT**: De `'restaurant'` para `'restaurante'` (português)
2. **Padrão de URL**: De `/restaurant/{slug}/` para `/restaurante/{id}/`
3. **Rewrite Rule**: Nova regra que aceita apenas números no lugar do slug
4. **Template Redirect**: Busca restaurante por ID e carrega template single
5. **get_permalink()**: Agora sempre retorna URL com ID, mesmo quando chamado no PHP

## v0.51 - Correção: URLs de Restaurantes no Mobile usando Slug

**Problema identificado:**
No mobile-shell-v2.js, as URLs estavam sendo geradas com ID (`/restaurante/{id}/`) ao invés de slug (`/restaurant/{slug}/`), causando inconsistência com o padrão do site desktop.

**Solução implementada:**
- **API REST Atualizada**: Adicionado campo `slug` na resposta da API de restaurantes
- **JavaScript Corrigido**: Todas as URLs no mobile-shell-v2.js agora usam slug quando disponível
- **Fallback para ID**: Se slug não estiver disponível, usa ID como fallback
- **CPT Mantido**: Slug do CPT permanece como `'restaurant'` (padrão WordPress)

**Arquivos modificados:**
- `inc/REST/Restaurant_Controller.php` - Adicionado campo `slug` na resposta
- `theme-vemcomer/assets/js/mobile-shell-v2.js` - URLs atualizadas para usar slug

**O que mudou:**
1. **API REST**: Agora retorna `slug` (post_name) junto com os outros dados
2. **URLs no Mobile**: Todas as URLs agora usam `/restaurant/{slug}/` ao invés de `/restaurante/{id}/`
3. **Consistência**: Mobile e desktop agora usam o mesmo padrão de URL

**Resultado:**
Todas as URLs de restaurantes no mobile agora seguem o padrão `/restaurant/{slug}/`:
- Exemplo: `https://seusite.com.br/restaurant/cantina-da-praca-1/` ✅
- Consistente com o padrão do site desktop
- URLs mais amigáveis e SEO-friendly

## v0.52 - Correção de Bugs Críticos na REST API e Mobile Shell

**Bugs corrigidos:**

### 1. Bug na busca por restaurantes via itens de cardápio
- **Problema**: Meta_query incorreta usando `_vc_restaurant_id` (meta do item, não do restaurante)
- **Solução**: Substituído por `post__in` para buscar restaurantes cujo ID está na lista
- **Performance**: Limitado busca de itens a 200 resultados (ao invés de -1)

### 2. Composição de meta_query corrigida
- **Problema**: Arrays aninhados quando já existia `relation` na meta_query
- **Solução**: Adiciona cada condição diretamente (flat), garantindo relation 'AND' quando necessário

### 3. Endpoint GET /restaurants/{id} criado
- **Problema**: JavaScript chamava endpoint inexistente, causando 404
- **Solução**: Criado endpoint completo que retorna dados de um único restaurante
- **Uso**: Mobile-shell agora pode buscar dados de restaurante individual sem erro

### 4. Parametrização unificada: delivery x has_delivery
- **Problema**: Dois parâmetros fazendo a mesma coisa, risco de conflito
- **Solução**: Unificado com prioridade para `has_delivery`, `delivery` como fallback (deprecated)

### 5. Performance: busca por itens de cardápio
- **Problema**: `posts_per_page = -1` em tabela possivelmente grande
- **Solução**: Limitado a 200 resultados para evitar sobrecarga

### 6. URLs consistentes no mobile-shell
- **Problema**: URLs de pratos não usavam slug do restaurante
- **Solução**: API de menu-items agora retorna `restaurant_slug`, URLs corrigidas para usar slug

**Arquivos modificados:**
- `inc/REST/Restaurant_Controller.php` - Todas as correções acima
- `inc/REST/Menu_Items_Controller.php` - Adicionado `restaurant_slug` na resposta
- `theme-vemcomer/assets/js/mobile-shell-v2.js` - URLs corrigidas para usar slug

**Resultado:**
- Busca de restaurantes funciona corretamente via itens de cardápio
- Meta_query composta corretamente sem arrays aninhados
- Endpoint `/restaurants/{id}` disponível e funcional
- Parâmetros unificados, sem conflitos
- Performance melhorada na busca
- URLs consistentes em todo o mobile-shell

## v0.53 - URLs Flexíveis para Restaurantes (Slug e ID)

**Funcionalidade implementada:**
Agora os restaurantes podem ser acessados tanto por slug quanto por ID, garantindo compatibilidade total:

- **URLs com Slug**: `/restaurant/come-quieto/` ✅
- **URLs com ID**: `/restaurant/139/` ✅
- **Ambos funcionam**: Qualquer um dos formatos abre o mesmo restaurante

**Solução implementada:**
- **Rewrite Rules**: Adicionada regra para aceitar IDs numéricos em `/restaurant/{id}/`
- **Template Redirect**: Busca inteligente que tenta primeiro por ID, depois por slug
- **Atualização Automática**: Rewrite rules são atualizadas automaticamente quando um restaurante é criado ou atualizado
- **Compatibilidade**: Links antigos (com ID) e novos (com slug) funcionam perfeitamente

**Arquivos modificados:**
- `inc/Model/CPT_Restaurant.php` - Adicionadas rewrite rules, query vars e template redirect

**Como funciona:**
1. Quando um restaurante é salvo, as rewrite rules são atualizadas automaticamente
2. O sistema detecta se a URL contém um número (ID) ou texto (slug)
3. Busca o restaurante pelo método apropriado
4. Carrega o template single normalmente

**Resultado:**
- Compatibilidade total com links antigos e novos
- URLs mais flexíveis e amigáveis
- Atualização automática sem intervenção manual
- SEO-friendly (slug) e compatibilidade (ID)

## v0.54 - Biblioteca Completa de Telas do Marketplace

**Nova implementação:**
Criação de uma biblioteca completa de telas HTML/CSS/JS para o marketplace, replicando o design fornecido e organizadas na pasta `theme-vemcomer/templates/marketplace/`.

### Telas de Cliente (Frontend)

#### Navegação e Descoberta
- ✅ **Busca Avançada** (`busca-avancada.html`) - Busca com autocomplete, filtros e resultados
- ✅ **Todas as Categorias** (`todas-as-categorias.html`) - Grid de categorias de restaurantes
- ✅ **Perfil do Restaurante** (`perfil-restaurante.html`) - Página completa do restaurante com cardápio, avaliações e informações
- ✅ **Cardápio Digital Standalone** (`cardapio-digital-standalone.html`) - Modo standalone para cardápio isolado

#### Produtos e Pedidos
- ✅ **Modal de Detalhes do Produto** (`modal-detalhes-produto.html`) - Bottom sheet com modificadores, observações e adicionar ao carrinho
- ✅ **Carrinho Lateral** (`carrinho-side-cart.html`) - Side cart com itens, quantidades e totais
- ✅ **Checkout Simplificado** (`checkout-simplificado.html`) - Checkout com delivery/pickup, endereços e resumo

#### Conta e Perfil
- ✅ **Minha Conta Cliente** (`minha-conta-cliente.html`) - Perfil do cliente com links para sub-páginas
- ✅ **Meus Endereços** (`meus-enderecos.html`) - Gerenciamento de endereços salvos
- ✅ **Meus Favoritos** (`meus-favoritos.html`) - Lista de restaurantes e itens favoritos
- ✅ **Meus Pedidos** (`meus-pedidos.html`) - Histórico de pedidos do cliente
- ✅ **Minhas Reservas** (`minhas-reservas.html`) - Lista de reservas com status
- ✅ **Pagamentos e Cartões** (`pagamentos-e-cartoes.html`) - Gerenciamento de métodos de pagamento
- ✅ **Dados Pessoais** (`dados-pessoais.html`) - Formulário de edição de dados pessoais
- ✅ **Segurança da Conta** (`seguranca-da-conta.html`) - Alteração de senha e exclusão de conta
- ✅ **Notificações** (`notificacoes.html`) - Feed de notificações do cliente

#### Eventos e Stories
- ✅ **Feed de Eventos** (`feed-eventos.html`) - Lista de eventos gastronômicos com filtros
- ✅ **Detalhes de Evento** (`detalhes-evento.html`) - Página completa de um evento
- ✅ **Story Viewer Cliente** (`story-viewer-cliente.html`) - Visualizador de stories estilo Instagram

#### Outros
- ✅ **Seção de Avaliações** (`secao-avaliacoes.html`) - Lista de avaliações com formulário
- ✅ **Modal de Informações do Restaurante** (`modal-informacoes-restaurante.html`) - Modal com endereço, mapa e horários
- ✅ **Offline PWA** (`offline-pwa.html`) - Tela de offline para PWA

### Telas de Restaurante (Backend/Admin)

#### Onboarding e Configuração
- ✅ **Wizard de Onboarding** (`wizard-onboarding.html`) - Onboarding completo em 5 steps
- ✅ **Configuração de Loja** (`configuracao-loja.html`) - Configurações completas (logo, capa, horários, frete)

#### Painéis por Plano
- ✅ **Painel Lojista - Plano Grátis** (`painel-lojista-plano-gratis.html`) - Dashboard básico para plano Vitrine
- ✅ **Painel Lojista - Plano Delivery Pro** (`painel-lojista-plano-delivery-pro.html`) - Dashboard PRO com analytics
- ✅ **Painel Lojista - Plano Growth Master** (`painel-lojista-plano-growth-master.html`) - Dashboard premium completo

#### Gestão
- ✅ **Painel de Pedidos** (`painel-pedidos.html`) - Kanban completo para gestão de pedidos
- ✅ **Gestão de Cardápio** (`gestao-cardapio.html`) - Gerenciamento de itens por categoria
- ✅ **Gestor de Eventos** (`gestor-eventos.html`) - Criação e gestão de eventos gastronômicos
- ✅ **Criador de Stories** (`criador-stories-restaurantes.html`) - Interface para criar stories

#### Marketing e Crescimento
- ✅ **Central Marketing** (`central-marketing.html`) - Dashboard de marketing com analytics, destaques e cupons
- ✅ **Popup com Planos Disponíveis** (`popup-planos-disponiveis.html`) - Comparativo de planos

**Total de telas criadas:** 28 telas completas

**Estrutura:**
- Todas as telas estão em `theme-vemcomer/templates/marketplace/`
- Cada tela é um arquivo HTML standalone com CSS e JavaScript inline
- Design replicado exatamente como fornecido, sem alterações
- Prontas para integração com backend via REST API

**Próximos passos:**
- Integrar telas com templates PHP do WordPress
- Conectar com endpoints REST API existentes
- Adicionar autenticação e controle de acesso

## v0.55 - Navegação Completa entre Telas do Marketplace

**Data:** 2024-12-XX

**Objetivo:**
Implementar navegação funcional entre todas as telas do marketplace, conectando os elementos clicáveis do JavaScript dinâmico com os templates HTML estáticos.

**Implementação:**

### 1. Atualização do JavaScript (`mobile-shell-v2.js`)
- ✅ **Cards de Restaurante**: Redirecionam para `perfil-restaurante.html`
- ✅ **Cards de Pratos**: Redirecionam para `modal-detalhes-produto.html`
- ✅ **Banners**: Redirecionam para `feed-eventos.html`
- ✅ **Stories**: Redirecionam para `story-viewer-cliente.html`
- ✅ **Eventos**: Corrigido para redirecionar para `detalhes-evento.html`

### 2. Atualização dos Templates HTML
- ✅ **Bottom Navigation**: Adicionada em todas as páginas principais:
  - `busca-avancada.html`
  - `todas-as-categorias.html`
  - `perfil-restaurante.html`
  - `minha-conta-cliente.html`
- ✅ **Links Corrigidos**:
  - `checkout-simplificado.html`: Botão "Enviar" agora redireciona para `meus-pedidos.html`
  - `content-mobile-home.php`: Bottom navigation atualizada com links corretos

### 3. Fluxo de Navegação Implementado

**Home → Detalhes:**
- Cards de Restaurante → `perfil-restaurante.html`
- Cards de Pratos → `modal-detalhes-produto.html`
- Banners → `feed-eventos.html`
- Stories → `story-viewer-cliente.html`
- Eventos → `detalhes-evento.html`

**Fluxo de Pedido:**
- `perfil-restaurante.html` → `modal-detalhes-produto.html` → `carrinho-side-cart.html` → `checkout-simplificado.html` → `meus-pedidos.html`

**Bottom Navigation (todas as páginas):**
- Início → `index.html` (home)
- Buscar → `busca-avancada.html`
- Categorias → `todas-as-categorias.html`
- Pedidos → `meus-pedidos.html`
- Perfil → `minha-conta-cliente.html`

**Arquivos modificados:**
- `theme-vemcomer/assets/js/mobile-shell-v2.js` - Links de navegação atualizados
- `theme-vemcomer/template-parts/content-mobile-home.php` - Bottom navigation corrigida
- `theme-vemcomer/templates/marketplace/checkout-simplificado.html` - Link de redirecionamento corrigido
- `theme-vemcomer/templates/marketplace/busca-avancada.html` - Bottom navigation adicionada
- `theme-vemcomer/templates/marketplace/todas-as-categorias.html` - Bottom navigation adicionada
- `theme-vemcomer/templates/marketplace/minha-conta-cliente.html` - Bottom navigation adicionada
- `theme-vemcomer/templates/marketplace/perfil-restaurante.html` - Bottom navigation adicionada

**Resultado:**
Protótipo navegável completo - todos os elementos clicáveis agora redirecionam corretamente para as telas correspondentes, criando uma experiência de navegação fluida entre todas as 28 telas do marketplace.

## v0.56 - Navegação Híbrida: Home WordPress ↔ Páginas HTML Estáticas

**Data:** 2024-12-XX

**Objetivo:**
Conectar a Home Page gerada pelo WordPress (`front-page.php`) com as páginas HTML estáticas na pasta `templates/marketplace/`, garantindo navegação bidirecional funcional.

**Implementação:**

### 1. Constante de Caminho no JavaScript
- ✅ **Constante `TEMPLATE_PATH`**: Definida no topo de `mobile-shell-v2.js`
  - Valor: `/wp-content/themes/theme-vemcomer/templates/marketplace/`
  - Usada em todas as funções de navegação e renders dinâmicos

### 2. Funções Globais de Navegação Atualizadas
- ✅ `window.openRestaurant(id)` → Redireciona para `perfil-restaurante.html`
- ✅ `window.openDish(id)` → Redireciona para `modal-detalhes-produto.html`
- ✅ `window.openEvent(id)` → Redireciona para `detalhes-evento.html`
- ✅ `window.openReservation(id)` → Redireciona para `minhas-reservas.html`

### 3. Renders Dinâmicos Atualizados
- ✅ **Banners**: `onclick` aponta para `feed-eventos.html`
- ✅ **Stories**: `onclick` aponta para `story-viewer-cliente.html`
- ✅ **Cards de Restaurantes**: Usam `TEMPLATE_PATH` para links
- ✅ **Cards de Pratos**: Usam `TEMPLATE_PATH` para links
- ✅ **Cards de Eventos**: Usam `TEMPLATE_PATH` para links
- ✅ **Cards em Destaque**: Usam `TEMPLATE_PATH` para links

### 4. Template PHP (`content-mobile-home.php`) Atualizado
- ✅ **Quick Actions**: Todos os links agora usam caminhos absolutos
  - Delivery → `busca-avancada.html`
  - Reservas → `minhas-reservas.html`
  - Eventos → `feed-eventos.html`
  - Promoções → `todas-as-categorias.html`
- ✅ **Links "Ver todos"**: Atualizados para apontar para templates corretos
- ✅ **Bottom Navigation**: Links atualizados com caminhos absolutos
  - Início → `/?mode=app` (volta para home WordPress)
  - Outros → Caminhos absolutos para templates

### 5. Arquivos HTML Estáticos Atualizados
- ✅ **Bottom Navigation**: Em todos os 4 arquivos principais
  - Link "Início" → `/?mode=app` (volta para home WordPress)
  - Outros links → Caminhos relativos (já estão na mesma pasta)
- ✅ **Fluxo de Compra**: Links internos já corretos
  - Carrinho → `checkout-simplificado.html` (relativo)
  - Checkout → `meus-pedidos.html` (relativo)
- ✅ **Botões Voltar**: `history.back()` já implementado nos modais

**Arquivos modificados:**
- `theme-vemcomer/assets/js/mobile-shell-v2.js` - Constante TEMPLATE_PATH e todas as funções de navegação
- `theme-vemcomer/template-parts/content-mobile-home.php` - Quick Actions, seções e bottom nav
- `theme-vemcomer/templates/marketplace/busca-avancada.html` - Bottom nav atualizada
- `theme-vemcomer/templates/marketplace/todas-as-categorias.html` - Bottom nav atualizada
- `theme-vemcomer/templates/marketplace/minha-conta-cliente.html` - Bottom nav atualizada
- `theme-vemcomer/templates/marketplace/perfil-restaurante.html` - Bottom nav atualizada

**Estrutura de Navegação:**
- **Home (WordPress)** → Usa caminhos **absolutos** para acessar templates HTML
- **Templates HTML** → Usam caminhos **relativos** entre si, **absoluto** (`/?mode=app`) para voltar à home

**Resultado:**
Navegação híbrida funcional - usuários podem navegar da Home WordPress para qualquer página HTML estática e voltar para a Home usando o botão "Início" na bottom navigation.

## v0.57 - Correção Crítica: Caminhos Absolutos para Templates HTML

**Data:** 2024-12-XX

**Problema identificado:**
Links gerados pelo JavaScript estavam apontando para a raiz do site (ex: `/perfil-restaurante.html`), gerando erro 404. O padrão correto deve ser o caminho completo do tema.

**Solução implementada:**

### 1. Constante TEMPLATE_PATH
- ✅ Constante definida no topo de `mobile-shell-v2.js`
- ✅ Valor: `/wp-content/themes/theme-vemcomer/templates/marketplace/`

### 2. Correções no JavaScript
- ✅ **Função `openStory()`**: Corrigida para usar `TEMPLATE_PATH + 'story-viewer-cliente.html'`
- ✅ **Função `attachRestaurantCardListeners()`**: Corrigida para usar `TEMPLATE_PATH + 'perfil-restaurante.html'`
- ✅ **Função `attachFeaturedCardListeners()`**: Corrigida para usar `TEMPLATE_PATH + 'perfil-restaurante.html'`
- ✅ **Função `renderSearchResults()`**: 
  - Restaurantes: Corrigido para usar `TEMPLATE_PATH + 'perfil-restaurante.html'`
  - Itens do cardápio: Corrigido para usar `TEMPLATE_PATH + 'modal-detalhes-produto.html'`
- ✅ **Todos os renders dinâmicos**: Já estavam usando TEMPLATE_PATH corretamente

### 3. Verificação do PHP
- ✅ **Quick Actions**: Todos os links já usam caminhos absolutos completos
- ✅ **Bottom Navigation**: Todos os links já usam caminhos absolutos completos
- ✅ **Links "Ver todos"**: Todos os links já usam caminhos absolutos completos

**Arquivos modificados:**
- `theme-vemcomer/assets/js/mobile-shell-v2.js` - Correção de 4 funções que não usavam TEMPLATE_PATH

**Resultado:**
Todos os links agora usam caminhos absolutos completos (`/wp-content/themes/theme-vemcomer/templates/marketplace/`), eliminando erros 404 e garantindo navegação funcional entre Home WordPress e páginas HTML estáticas.

## v0.58 - Atualização do Design do Checkout Simplificado

**Data:** 2024-12-XX

**Objetivo:**
Atualizar o arquivo `checkout-simplificado.html` com o novo design fornecido pelo usuário.

**Mudanças:**
- ✅ **Design atualizado**: Novo layout com estilos inline melhorados
- ✅ **Background**: Alterado de `#f6f9f6` para `#f9fbfa`
- ✅ **Box shadow**: Atualizado para `0 2px 16px #45c67620`
- ✅ **Responsividade**: Adicionado media query para telas menores
- ✅ **Navegação mantida**: Link do botão "Enviar pedido no WhatsApp" mantido para `meus-pedidos.html`

**Arquivos modificados:**
- `theme-vemcomer/templates/marketplace/checkout-simplificado.html` - Conteúdo completo atualizado

**Resultado:**
Checkout simplificado com design atualizado e navegação funcional mantida.

## v0.59 - Atualização de Carrinho Lateral e Seção de Avaliações

**Data:** 2024-12-XX

**Objetivo:**
Atualizar os arquivos `carrinho-side-cart.html` e `secao-avaliacoes.html` com novos designs.

**Mudanças:**

### Carrinho Lateral (`carrinho-side-cart.html`)
- ✅ **Design atualizado**: Novo layout com estilos melhorados
- ✅ **Botão flutuante**: Adicionado botão FAB (Floating Action Button) para abrir carrinho
- ✅ **Carrinho lateral**: Modal lateral com animação e transições suaves
- ✅ **Funcionalidades**: Controle de quantidade, remoção de itens, cálculo de subtotal
- ✅ **Navegação mantida**: Link do botão "Finalizar Pedido" mantido para `checkout-simplificado.html`

### Seção de Avaliações (`secao-avaliacoes.html`)
- ✅ **Design atualizado**: Novo layout com cards de avaliação
- ✅ **Formulário de avaliação**: Sistema de avaliação com estrelas e comentários
- ✅ **Respostas do restaurante**: Exibição de respostas do restaurante às avaliações
- ✅ **Badge "Verificada"**: Indicador visual para avaliações verificadas

**Arquivos modificados:**
- `theme-vemcomer/templates/marketplace/carrinho-side-cart.html` - Conteúdo completo atualizado
- `theme-vemcomer/templates/marketplace/secao-avaliacoes.html` - Conteúdo completo atualizado

**Resultado:**
Carrinho lateral e seção de avaliações com designs atualizados e funcionalidades mantidas.

## v0.60 - Atualização do Modal de Informações do Restaurante

**Data:** 2024-12-XX

**Objetivo:**
Atualizar o arquivo `modal-informacoes-restaurante.html` com novo design.

**Mudanças:**
- ✅ **Design atualizado**: Novo layout com modal animado
- ✅ **Informações estruturadas**: Endereço, mapa, horários e formas de pagamento
- ✅ **Mapa integrado**: Iframe do OpenStreetMap para exibir localização
- ✅ **Horários dinâmicos**: Sistema para exibir horários de funcionamento estruturados
- ✅ **Animação**: Efeito de entrada suave com `modalIn` animation
- ✅ **Responsividade**: Adaptação para telas menores

**Arquivos modificados:**
- `theme-vemcomer/templates/marketplace/modal-informacoes-restaurante.html` - Conteúdo completo atualizado

**Resultado:**
Modal de informações do restaurante com design atualizado e funcionalidades completas.

## v0.61 - Atualização do Modal de Detalhes do Produto

**Data:** 2024-12-XX

**Objetivo:**
Atualizar o arquivo `modal-detalhes-produto.html` com novo design e sistema de modificadores.

**Mudanças:**
- ✅ **Design atualizado**: Novo layout com modal animado e estilos melhorados
- ✅ **Sistema de modificadores**: 
  - Modificadores obrigatórios (radio buttons) - exemplo: escolha de proteína
  - Modificadores opcionais (checkboxes) - exemplo: adicionais
  - Cálculo dinâmico de preço baseado em modificadores selecionados
- ✅ **Controle de quantidade**: Botões +/- para ajustar quantidade do produto
- ✅ **Cálculo de total**: Atualização automática do preço total (base + modificadores) × quantidade
- ✅ **Campo de observação**: Textarea para observações do cliente
- ✅ **Navegação mantida**: Link do botão "Adicionar" mantido para `carrinho-side-cart.html`
- ✅ **Animação**: Efeito de entrada suave com `fadeIn` animation
- ✅ **Responsividade**: Adaptação para telas menores

**Arquivos modificados:**
- `theme-vemcomer/templates/marketplace/modal-detalhes-produto.html` - Conteúdo completo atualizado

**Resultado:**
Modal de detalhes do produto com design atualizado, sistema completo de modificadores e cálculo dinâmico de preços.
