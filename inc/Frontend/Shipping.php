<?php
/**
 * Shipping — cálculo simples de frete
 * Regra: frete fixo por restaurante (meta `_vc_ship_flat`), grátis se subtotal >= pedido mínimo (`_vc_min_order`).
 * Se `_vc_ship_flat` não existir, usa valor padrão do filtro `vemcomer/default_ship_flat` (default: 9,90).
 * @package VemComerCore
 */

namespace VC\Frontend;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Shipping {
    public function init(): void { /* reservado para futuras integrações */ }

    public static function quote( int $restaurant_id, float $subtotal ): array {
        $min   = (float) str_replace( ',', '.', (string) get_post_meta( $restaurant_id, '_vc_min_order', true ) );
        $flatS = get_post_meta( $restaurant_id, '_vc_ship_flat', true );
        $flat  = $flatS !== '' ? (float) str_replace( ',', '.', (string) $flatS ) : (float) apply_filters( 'vemcomer/default_ship_flat', 9.90, $restaurant_id );

        $free = $min > 0 && $subtotal >= $min;
        $price = $free ? 0.0 : $flat;

        return [ 'restaurant_id' => $restaurant_id, 'subtotal' => $subtotal, 'min' => $min, 'ship' => $price, 'free' => $free ];
    }
}
