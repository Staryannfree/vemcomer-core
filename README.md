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

