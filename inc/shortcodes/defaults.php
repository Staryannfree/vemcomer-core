<?php
/**
 * Defaults/Fallbacks de atributos para shortcodes.
 * Permite usar ?restaurant_id=123 na URL quando o atributo não for passado.
 *
 * @package VemComerCore
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// [vc_restaurant id="..."] => se "id" não vier, usa ?restaurant_id=
add_filter( 'shortcode_atts_vc_restaurant', function ( $out, $pairs, $atts ) {
    if ( empty( $out['id'] ) ) {
        $qs = isset( $_GET['restaurant_id'] ) ? absint( wp_unslash( $_GET['restaurant_id'] ) ) : 0;
        if ( $qs ) {
            $out['id'] = $qs;
        }
    }
    return $out;
}, 10, 3 );

// [vc_menu_items restaurant="..."] => se "restaurant" não vier, usa ?restaurant_id=
add_filter( 'shortcode_atts_vc_menu_items', function ( $out, $pairs, $atts ) {
    if ( empty( $out['restaurant'] ) ) {
        $qs = isset( $_GET['restaurant_id'] ) ? absint( wp_unslash( $_GET['restaurant_id'] ) ) : 0;
        if ( $qs ) {
            $out['restaurant'] = $qs;
        }
    }
    return $out;
}, 10, 3 );
