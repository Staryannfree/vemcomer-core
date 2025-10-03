<?php
/**
 * Plugin Name: VemComer Core
 * Description: Core do marketplace VemComer — CPTs, Admin e REST base.
 * Version: 0.2.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: VemComer
 * Text Domain: vemcomer
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'VEMCOMER_CORE_VERSION', '0.2.0' );
define( 'VEMCOMER_CORE_FILE', __FILE__ );
define( 'VEMCOMER_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'VEMCOMER_CORE_URL', plugin_dir_url( __FILE__ ) );

// Autoload legado: classes no formato VC_*
spl_autoload_register( function ( $class ) {
    if ( str_starts_with( $class, 'VC_' ) ) {
        $path = VEMCOMER_CORE_DIR . 'inc/' . 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
        if ( file_exists( $path ) ) { require_once $path; }
    }
} );

// Autoload PSR-4 simples: namespace VC\* mapeado para inc/
spl_autoload_register( function ( $class ) {
    if ( str_starts_with( $class, 'VC\\' ) ) {
        $relative = str_replace( 'VC\\', '', $class );
        $relative = str_replace( '\\', '/', $relative );
        $path = VEMCOMER_CORE_DIR . 'inc/' . $relative . '.php';
        if ( file_exists( $path ) ) { require_once $path; }
    }
} );

// Helpers comuns
require_once VEMCOMER_CORE_DIR . 'inc/helpers-sanitize.php';

add_action( 'plugins_loaded', function () {
    // Loader central (assets)
    if ( class_exists( 'VC_Loader' ) ) {
        $loader = new \VC_Loader();
        $loader->init();
    }

    // Legacy CPTs (pacote 1)
    if ( class_exists( 'VC_CPT_Produto' ) ) { ( new \VC_CPT_Produto() )->init(); }
    if ( class_exists( 'VC_CPT_Pedido' ) )  { ( new \VC_CPT_Pedido() )->init(); }
    if ( class_exists( 'VC_Admin_Menu' ) )  { ( new \VC_Admin_Menu() )->init(); }
    if ( class_exists( 'VC_REST' ) )        { ( new \VC_REST() )->init(); }

    // Novos módulos (pacote 2)
    if ( class_exists( '\\VC\\Model\\CPT_Restaurant' ) )      { ( new \VC\Model\CPT_Restaurant() )->init(); }
    if ( class_exists( '\\VC\\Model\\CPT_MenuItem' ) )        { ( new \VC\Model\CPT_MenuItem() )->init(); }
    if ( class_exists( '\\VC\\Admin\\Menu_Restaurant' ) )     { ( new \VC\Admin\Menu_Restaurant() )->init(); }
    if ( class_exists( '\\VC\\REST\\Restaurant_Controller' ) ) { ( new \VC\REST\Restaurant_Controller() )->init(); }
} );
