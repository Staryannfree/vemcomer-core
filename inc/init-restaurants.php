<?php
/**
 * Bootstrap do módulo de Restaurantes – Atualizado com REST e Roles
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once __DIR__ . '/post-types.php';
require_once __DIR__ . '/taxonomies.php';
require_once __DIR__ . '/meta-restaurants.php';
require_once __DIR__ . '/admin-columns.php';
require_once __DIR__ . '/admin/list-filters.php';
require_once __DIR__ . '/rest-api.php';
require_once __DIR__ . '/rest-api-write.php';
require_once __DIR__ . '/roles-capabilities.php';
require_once __DIR__ . '/templates-loader.php';
require_once __DIR__ . '/shortcodes/loader.php';

$preenchedor_file = __DIR__ . '/admin/preenchedor.php';
if ( file_exists( $preenchedor_file ) ) {
    require_once $preenchedor_file;
}

// Assets de admin
add_action( 'admin_enqueue_scripts', function() {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ( $screen && isset( $screen->post_type ) && 'vc_restaurant' === $screen->post_type ) {
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

// Garante capabilities ao ativar o plugin principal
if ( function_exists( 'register_activation_hook' ) ) {
    // Tenta detectar o arquivo principal do plugin a partir deste diretório
    $plugin_main = dirname( __DIR__ ) . '/vemcomer-core.php';
    if ( file_exists( $plugin_main ) ) {
        register_activation_hook( $plugin_main, function() {
            // Registra CPT antes de atribuir caps (para map_meta_cap)
            if ( function_exists( 'register_post_type' ) && ! post_type_exists( 'vc_restaurant' ) ) {
                do_action( 'init' ); // garante init para registrar CPT/Tax
            }
            vc_assign_caps_to_roles();
        });
    }
}
