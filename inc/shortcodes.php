<?php
/**
 * Helpers for shortcode attribute defaults/fallbacks.
 *
 * @package VemComerCore
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Etapas futuras: [vc_explore], [vc_restaurant_menu], [vc_kds], [vc_onboarding], etc.

// [vc_restaurant id="..."] => se "id" não vier, usa ?restaurant_id=
add_filter( 'shortcode_atts_vc_restaurant', function( $out, $pairs, $atts ) {
    if ( empty( $out['id'] ) ) {
        $qs = isset( $_GET['restaurant_id'] ) ? absint( $_GET['restaurant_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Apenas leitura de parâmetro público.
        if ( $qs ) {
            $out['id'] = $qs;
        }
    }
    return $out;
}, 10, 3 );

// [vc_menu_items restaurant_id="..."] => se "restaurant_id" não vier, usa ?restaurant_id=
add_filter( 'shortcode_atts_vc_menu_items', function( $out, $pairs, $atts ) {
    if ( empty( $out['restaurant_id'] ) ) {
        $qs = isset( $_GET['restaurant_id'] ) ? absint( $_GET['restaurant_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Apenas leitura de parâmetro público.
        if ( $qs ) {
            $out['restaurant_id'] = $qs;
        }
    }
    return $out;
}, 10, 3 );
