<?php
/**
 * Loader dos shortcodes do VemComer.
 *
 * Inclui registradores e defaults de atributos (fallback via query string).
 *
 * @package VemComerCore
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Registra o CSS legado (mantido para compatibilidade com temas antigos).
add_action( 'wp_enqueue_scripts', function () {
    wp_register_style(
        'vc-shortcodes',
        plugins_url( '../../assets/css/shortcodes.css', __FILE__ ),
        [],
        defined( 'VEMCOMER_CORE_VERSION' ) ? VEMCOMER_CORE_VERSION : '1.0.0'
    );
});

// Quando um shortcode for usado, enfileira tanto o CSS novo quanto o legado.
add_action( 'vc_shortcodes_used', function () {
    wp_enqueue_style( 'vc-shortcodes' );

    if ( wp_style_is( 'vemcomer-front', 'registered' ) ) {
        wp_enqueue_style( 'vemcomer-front' );
    } else {
        wp_enqueue_style(
            'vemcomer-front',
            plugins_url( '../../assets/css/frontend.css', __FILE__ ),
            [],
            defined( 'VEMCOMER_CORE_VERSION' ) ? VEMCOMER_CORE_VERSION : '1.0.0'
        );
    }
});

// Helper para os shortcodes chamarem assim que renderizarem.
if ( ! function_exists( 'vc_sc_mark_used' ) ) {
    function vc_sc_mark_used() {
        do_action( 'vc_shortcodes_used' );
    }
}

// Registrar shortcodes (arquivos originais)
require_once __DIR__ . '/restaurant-card.php';
require_once __DIR__ . '/restaurants-grid.php';
require_once __DIR__ . '/menu-items.php';
require_once __DIR__ . '/filters.php';

// Defaults/Fallbacks de atributos (usar query string quando faltarem)
if ( file_exists( __DIR__ . '/defaults.php' ) ) {
    require_once __DIR__ . '/defaults.php';
}
