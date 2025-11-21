<?php
/**
 * Melhora os cards de restaurante com informações extras
 * @package VemComer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adiciona informações extras aos cards de restaurante
 */
add_filter( 'vc_restaurant_card_after_rating', 'vemcomer_enhance_restaurant_card', 10, 2 );
function vemcomer_enhance_restaurant_card( $content, $restaurant_id ) {
    if ( ! function_exists( 'vemcomer_get_restaurant_avg_price' ) ) {
        require_once get_template_directory() . '/inc/restaurant-helpers.php';
    }

    ob_start();
    
    // Badges
    $badges = [];
    if ( vemcomer_is_new_restaurant( $restaurant_id ) ) {
        $badges[] = '<span class="restaurant-badge restaurant-badge--new">' . esc_html__( 'Novo', 'vemcomer' ) . '</span>';
    }
    if ( vemcomer_has_free_shipping( $restaurant_id ) ) {
        $badges[] = '<span class="restaurant-badge restaurant-badge--free-shipping">' . esc_html__( 'Frete grátis', 'vemcomer' ) . '</span>';
    }
    
    // Preço médio
    $avg_price = vemcomer_get_restaurant_avg_price( $restaurant_id );
    
    // Tempo de entrega
    $delivery_time = vemcomer_get_delivery_time( $restaurant_id );
    
    // Distância (se geolocalização disponível)
    $distance = null;
    if ( isset( $_COOKIE['vc_user_location'] ) ) {
        $location = json_decode( wp_unslash( $_COOKIE['vc_user_location'] ), true );
        if ( isset( $location['lat'] ) && isset( $location['lng'] ) ) {
            $restaurant_lat = (float) get_post_meta( $restaurant_id, 'vc_restaurant_lat', true );
            $restaurant_lng = (float) get_post_meta( $restaurant_id, 'vc_restaurant_lng', true );
            if ( $restaurant_lat && $restaurant_lng ) {
                $distance = vemcomer_calculate_distance( 
                    $location['lat'], 
                    $location['lng'], 
                    $restaurant_lat, 
                    $restaurant_lng 
                );
            }
        }
    }
    
    ?>
    <div class="restaurant-card__badges">
        <?php echo implode( '', $badges ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php if ( $distance ) : ?>
            <span class="restaurant-badge restaurant-badge--closest"><?php echo esc_html( sprintf( __( '%.1f km', 'vemcomer' ), $distance ) ); ?></span>
        <?php endif; ?>
    </div>
    
    <div class="restaurant-card__info">
        <?php if ( $avg_price ) : ?>
            <div class="restaurant-card__info-item">
                <span class="restaurant-card__info-icon">💰</span>
                <span class="restaurant-card__info-text"><?php echo esc_html( $avg_price ); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ( $delivery_time ) : ?>
            <div class="restaurant-card__info-item">
                <span class="restaurant-card__info-icon">🕐</span>
                <span class="restaurant-card__info-text"><?php echo esc_html( $delivery_time ); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ( $distance ) : ?>
            <div class="restaurant-card__info-item">
                <span class="restaurant-card__info-icon">📍</span>
                <span class="restaurant-card__distance"><?php echo esc_html( sprintf( __( '%.1f km', 'vemcomer' ), $distance ) ); ?></span>
            </div>
        <?php endif; ?>
    </div>
    <?php
    
    return $content . ob_get_clean();
}

