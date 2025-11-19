<?php
namespace VC\Checkout;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Define o contrato mínimo para qualquer método de fulfillment (entrega, retirada, etc.).
 */
interface FulfillmentMethod {
    /**
     * Indica se o método aceita calcular pedidos com os dados recebidos.
     *
     * @param array $order Dados como `restaurant_id`, `subtotal`, `items`.
     */
    public function supports_order( array $order ): bool;

    /**
     * Retorna o custo do fulfillment.
     *
     * @param array $order Dados do pedido (mesmos de supports_order).
     *
     * @return array Estrutura mínima: ['total' => float, 'free' => bool, 'label' => string, 'details' => array].
     */
    public function calculate_fee( array $order ): array;

    /**
     * Texto do ETA (ex.: "30-45 min") ou null.
     */
    public function get_eta( array $order ): ?string;
}
