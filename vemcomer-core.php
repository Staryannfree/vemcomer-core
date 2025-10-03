<?php
/**
 * Plugin Name: VemComer Core
 * Description: Core do marketplace VemComer — CPTs, Admin e REST base.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: VemComer
 * Text Domain: vemcomer
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Constantes básicas
define( 'VEMCOMER_CORE_VERSION', '0.1.0' );
define( 'VEMCOMER_CORE_FILE', __FILE__ );
define( 'VEMCOMER_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'VEMCOMER_CORE_URL', plugin_dir_url( __FILE__ ) );

// Autoload simples das classes do inc/
spl_autoload_register( function ( $class ) {
    if ( str_starts_with( $class, 'VC_' ) ) {
        $path = VEMCOMER_CORE_DIR . 'inc/' . 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
} );

// Helpers comuns
require_once VEMCOMER_CORE_DIR . 'inc/helpers-sanitize.php';

// Bootstrap
add_action( 'plugins_loaded', function () {
    // Loader central (ganchos, assets, etc.)
    $loader = new VC_Loader();
    $loader->init();

    // CPTs
    ( new VC_CPT_Produto() )->init();
    ( new VC_CPT_Pedido() )->init();

    // Admin
    ( new VC_Admin_Menu() )->init();

    // REST
    ( new VC_REST() )->init();
} );
