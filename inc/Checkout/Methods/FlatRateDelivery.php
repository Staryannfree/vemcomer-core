<?php
namespace VC\Checkout\Methods;

use VC\Checkout\FulfillmentMethod;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class FlatRateDelivery implements FulfillmentMethod {
    public const SLUG = 'flat_rate_delivery';

    public function supports_order( array $order ): bool {
        return ! empty( $order['restaurant_id'] );
    }

    public function calculate_fee( array $order ): array {
        $restaurant_id = (int) ( $order['restaurant_id'] ?? 0 );
        $subtotal      = (float) ( $order['subtotal'] ?? 0 );

        $min   = (float) str_replace( ',', '.', (string) get_post_meta( $restaurant_id, '_vc_min_order', true ) );
        $flatS = get_post_meta( $restaurant_id, '_vc_ship_flat', true );
        $flat  = $flatS !== ''
            ? (float) str_replace( ',', '.', (string) $flatS )
            : (float) apply_filters( 'vemcomer/default_ship_flat', 9.90, $restaurant_id );

        $free  = $min > 0 && $subtotal >= $min;
        $price = $free ? 0.0 : $flat;

        return [
            'total'   => $price,
            'free'    => $free,
            'label'   => __( 'Entrega padrão', 'vemcomer' ),
            'details' => [
                'min_order' => $min,
                'flat_rate' => $flat,
            ],
        ];
    }

    public function get_eta( array $order ): ?string {
        $restaurant_id = (int) ( $order['restaurant_id'] ?? 0 );
        $eta = get_post_meta( $restaurant_id, '_vc_ship_eta', true );
        return $eta !== '' ? (string) $eta : null;
    }
}
