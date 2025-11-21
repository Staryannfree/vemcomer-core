<?php
/**
 * Helpers para restaurantes - Preço médio, distância, etc.
 * @package VemComer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Calcula preço médio dos itens do cardápio de um restaurante
 */
function vemcomer_get_restaurant_avg_price( int $restaurant_id ): ?string {
    $cache_key = 'vc_restaurant_avg_price_' . $restaurant_id;
    $cached = get_transient( $cache_key );
    
    if ( false !== $cached ) {
        return $cached;
    }

    $items = get_posts([
        'post_type' => 'vc_menu_item',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => '_vc_restaurant_id',
                'value' => (string) $restaurant_id,
            ],
        ],
    ]);

    if ( empty( $items ) ) {
        return null;
    }

    $prices = [];
    foreach ( $items as $item ) {
        $price_raw = get_post_meta( $item->ID, '_vc_price', true );
        if ( empty( $price_raw ) ) {
            continue;
        }
        
        // Converter para float (suporta "10,00" ou "10.00")
        $price = str_replace( ',', '.', str_replace( '.', '', $price_raw ) );
        $price = (float) $price;
        
        if ( $price > 0 ) {
            $prices[] = $price;
        }
    }

    if ( empty( $prices ) ) {
        return null;
    }

    $avg = array_sum( $prices ) / count( $prices );
    $min = min( $prices );
    $max = max( $prices );

    // Formatar: "R$ 25-40" ou "R$ 30" se min = max
    if ( $min === $max ) {
        $result = 'R$ ' . number_format( $avg, 2, ',', '.' );
    } else {
        $result = 'R$ ' . number_format( $min, 0, '', '.' ) . '-' . number_format( $max, 0, '', '.' );
    }

    set_transient( $cache_key, $result, 3600 ); // Cache 1 hora
    return $result;
}

/**
 * Calcula distância entre coordenadas (Haversine)
 */
function vemcomer_calculate_distance( float $lat1, float $lng1, float $lat2, float $lng2 ): float {
    $earth_radius = 6371; // km

    $d_lat = deg2rad( $lat2 - $lat1 );
    $d_lng = deg2rad( $lng2 - $lng1 );

    $a = sin( $d_lat / 2 ) * sin( $d_lat / 2 ) +
         cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) *
         sin( $d_lng / 2 ) * sin( $d_lng / 2 );

    $c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
    $distance = $earth_radius * $c;

    return round( $distance, 2 );
}

/**
 * Verifica se restaurante é novo (criado nos últimos 30 dias)
 */
function vemcomer_is_new_restaurant( int $restaurant_id ): bool {
    $post = get_post( $restaurant_id );
    if ( ! $post ) {
        return false;
    }

    $created = strtotime( $post->post_date );
    $days_ago = ( time() - $created ) / DAY_IN_SECONDS;

    return $days_ago <= 30;
}

/**
 * Verifica se restaurante oferece frete grátis acima de X
 */
function vemcomer_has_free_shipping( int $restaurant_id ): bool {
    $free_above = (float) get_post_meta( $restaurant_id, '_vc_delivery_free_above', true );
    return $free_above > 0;
}

/**
 * Obtém tempo estimado de entrega
 */
function vemcomer_get_delivery_time( int $restaurant_id ): ?string {
    if ( ! class_exists( '\\VC\\Utils\\Delivery_Time_Calculator' ) ) {
        return null;
    }

    // Tempo padrão do restaurante
    $eta = get_post_meta( $restaurant_id, '_vc_ship_eta', true );
    if ( ! empty( $eta ) ) {
        return $eta;
    }

    // Fallback: calcular baseado em distância média
    return '30-45 min';
}

