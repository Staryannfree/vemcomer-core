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
* `[vemcomer_restaurant_panel]` — painel front-end para donos de restaurante (requer login).
* `[vemcomer_restaurant_signup]` — formulário público para restaurantes enviarem seus dados (entradas ficam pendentes para aprovação do admin).
* `[vemcomer_customer_signup]` — formulário de criação de conta para clientes finais.

Todos os shortcodes acima renderizam HTML, CSS e JavaScript próprios do plugin — não há dependência de construtores como o Elementor para exibir as páginas públicas.

## Seed (dados de demonstração)
Cria 1 restaurante e 5 itens de cardápio:
```bash
wp vc seed
```

## Endpoints REST

### Restaurantes

* **GET** `/wp-json/vemcomer/v1/restaurants`
* **GET** `/wp-json/vemcomer/v1/restaurants/{id}/menu-items`

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

Use esses nomes nos “Custom Action Hook” dos automators para disparar receitas.

## Desenvolvimento

* PHP ≥ 8.0, WP ≥ 6.0.
* Autoload interno + PSR‑4 simples (namespace `VC\*` mapeado para `inc/`).
* Sanitização e escapes seguindo o Handbook do WordPress.


## Troubleshooting

- **WP Pusher em PHP 8.2**: se o log mostrar `Creation of dynamic property Pusher\Log\Logger::$file` ou `Cannot declare class Elementor\Element_Column` (mesmo que o Elementor não esteja instalado), execute o script `bin/fix-wppusher-php82.php` descrito em [`docs/troubleshooting/wp-pusher.md`](docs/troubleshooting/wp-pusher.md).
