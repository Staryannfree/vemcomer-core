<?php
/**
 * Loader dos shortcodes do VemComer.
 *
 * Inclui registradores e defaults de atributos (fallback via query string).
 *
 * @package VemComerCore
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Quando um shortcode for usado, enfileira tanto o CSS novo quanto o legado.
add_action( 'vc_shortcodes_used', function () {
    wp_enqueue_style( 'vc-shortcodes' );
    wp_enqueue_style( 'vemcomer-front' );
    wp_enqueue_script( 'vemcomer-front' );
    // Enfileirar modal de produto se necessário
    if ( ! wp_script_is( 'vemcomer-product-modal', 'enqueued' ) ) {
        wp_enqueue_style( 'vemcomer-product-modal' );
        wp_enqueue_script( 'vemcomer-product-modal' );
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
require_once __DIR__ . '/restaurants-map.php';
require_once __DIR__ . '/menu-items.php';
require_once __DIR__ . '/filters.php';
require_once __DIR__ . '/reviews.php';

// Defaults/Fallbacks de atributos (usar query string quando faltarem)
if ( file_exists( __DIR__ . '/defaults.php' ) ) {
    require_once __DIR__ . '/defaults.php';
}
