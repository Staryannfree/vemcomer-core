<?php
/**
 * Bootstrap do módulo de Restaurantes do VemComer Core
 *
 * Carrega CPT, taxonomias, metaboxes e colunas administrativas.
 */

// Impede acesso direto.
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once __DIR__ . '/post-types.php';
require_once __DIR__ . '/taxonomies.php';
require_once __DIR__ . '/meta-restaurants.php';
require_once __DIR__ . '/admin-columns.php';

// Assets de admin (opcional)
add_action( 'admin_enqueue_scripts', function( $hook ) {
    // Carrega apenas nas telas do CPT de restaurantes
    $screen = get_current_screen();
    if ( isset( $screen->post_type ) && 'vc_restaurant' === $screen->post_type ) {
        wp_enqueue_script(
            'vc-restaurants-admin',
            plugins_url( '../assets/js/restaurants-admin.js', __FILE__ ),
            [ 'jquery' ],
            defined('VEMCOMER_CORE_VERSION') ? VEMCOMER_CORE_VERSION : '1.0.0',
            true
        );
        wp_enqueue_style(
            'vc-restaurants-admin',
            plugins_url( '../assets/css/restaurants-admin.css', __FILE__ ),
            [],
            defined('VEMCOMER_CORE_VERSION') ? VEMCOMER_CORE_VERSION : '1.0.0'
        );
    }
});
