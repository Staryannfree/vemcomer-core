<?php
/**
 * Loader dos shortcodes do VemComer.
 *
 * Inclui registradores, helpers e defaults de atributos (fallback via query string).
 *
 * @package VemComerCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enqueue condicional: carrega CSS só quando algum shortcode do VC é processado.
add_action( 'wp_enqueue_scripts', function () {
    if ( did_action( 'vc_shortcodes_used' ) ) {
        wp_enqueue_style(
            'vc-shortcodes',
            plugins_url( '../../assets/css/shortcodes.css', __FILE__ ),
            [],
            defined( 'VEMCOMER_CORE_VERSION' ) ? VEMCOMER_CORE_VERSION : '1.0.0'
        );
    }
});

// Helpers.
function vc_sc_mark_used() {
    do_action( 'vc_shortcodes_used' );
}

function vc_sc_bool( $val ) {
    return filter_var( $val, FILTER_VALIDATE_BOOLEAN );
}

// Registrar shortcodes.
// Defaults/Fallbacks de atributos para shortcodes.
// Permite usar ?restaurant_id=123 na URL quando o atributo não for passado.
add_filter( 'shortcode_atts_vc_restaurant', function( $out, $pairs, $atts ) {
    if ( empty( $out['id'] ) ) {
        $qs = isset( $_GET['restaurant_id'] ) ? absint( wp_unslash( $_GET['restaurant_id'] ) ) : 0;
        if ( $qs ) {
            $out['id'] = $qs;
        }
    }

    return $out;
}, 10, 3 );

add_filter( 'shortcode_atts_vc_menu_items', function( $out, $pairs, $atts ) {
    if ( empty( $out['restaurant_id'] ) ) {
        $qs = isset( $_GET['restaurant_id'] ) ? absint( wp_unslash( $_GET['restaurant_id'] ) ) : 0;
        if ( $qs ) {
            $out['restaurant_id'] = $qs;
        }
    }

    return $out;
}, 10, 3 );

// Registrar shortcodes
require_once __DIR__ . '/restaurants-grid.php';
require_once __DIR__ . '/restaurant-card.php';
require_once __DIR__ . '/restaurants-grid.php';
require_once __DIR__ . '/menu-items.php';
require_once __DIR__ . '/filters.php';

// Defaults opcionais.
if ( file_exists( __DIR__ . '/defaults.php' ) ) {
    require_once __DIR__ . '/defaults.php';
}
