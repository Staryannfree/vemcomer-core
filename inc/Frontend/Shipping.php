<?php
/**
 * Shipping — cálculo simples de frete
 * Regra: frete fixo por restaurante (meta `_vc_ship_flat`), grátis se subtotal >= pedido mínimo (`_vc_min_order`).
 * Se `_vc_ship_flat` não existir, usa valor padrão do filtro `vemcomer/default_ship_flat` (default: 9,90).
 * @package VemComerCore
 */

namespace VC\Frontend;

use VC\Checkout\FulfillmentRegistry;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Shipping {
    public function init(): void { /* reservado para futuras integrações */ }

    public static function quote( int $restaurant_id, float $subtotal, array $order_data = [] ): array {
        $order = array_merge(
            [
                'restaurant_id' => $restaurant_id,
                'subtotal'      => $subtotal,
            ],
            $order_data
        );
        $methods = FulfillmentRegistry::get_quotes( $order );

        return [
            'restaurant_id' => $restaurant_id,
            'subtotal'      => $subtotal,
            'methods'       => $methods,
        ];
    }
}
