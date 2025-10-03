<?php
/**
 * Plugin Name: VemComer Core
 * Description: Core do marketplace VemComer — Instalador de páginas com shortcodes
 * Version: 0.8.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: VemComer
 * Text Domain: vemcomer
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'VEMCOMER_CORE_VERSION', '0.8.0' );

define( 'VEMCOMER_CORE_FILE', __FILE__ );

define( 'VEMCOMER_CORE_DIR', plugin_dir_path( __FILE__ ) );

define( 'VEMCOMER_CORE_URL', plugin_dir_url( __FILE__ ) );

// autoloads (legado + PSR-4) — mesmos dos pacotes anteriores
spl_autoload_register( function ( $class ) {
    if ( str_starts_with( $class, 'VC_' ) ) {
        $path = VEMCOMER_CORE_DIR . 'inc/' . 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
        if ( file_exists( $path ) ) { require_once $path; }
    }
} );

spl_autoload_register( function ( $class ) {
    if ( str_starts_with( $class, 'VC\\' ) ) {
        $relative = str_replace( 'VC\\', '', $class );
        $relative = str_replace( '\\', '/', $relative );
        $path = VEMCOMER_CORE_DIR . 'inc/' . $relative . '.php';
        if ( file_exists( $path ) ) { require_once $path; }
    }
} );

require_once VEMCOMER_CORE_DIR . 'inc/helpers-sanitize.php';

add_action( 'plugins_loaded', function () {
    // (carrega os módulos já existentes dos Pacotes 1..7)
    if ( class_exists( 'VC_Loader' ) ) { ( new \VC_Loader() )->init(); }
    if ( class_exists( 'VC_CPT_Produto' ) ) { ( new \VC_CPT_Produto() )->init(); }
    if ( class_exists( 'VC_CPT_Pedido' ) )  { ( new \VC_CPT_Pedido() )->init(); }
    if ( class_exists( 'VC_Admin_Menu' ) )  { ( new \VC_Admin_Menu() )->init(); }
    if ( class_exists( 'VC_REST' ) )        { ( new \VC_REST() )->init(); }

    if ( class_exists( '\\VC\\Model\\CPT_Restaurant' ) )      { ( new \VC\Model\CPT_Restaurant() )->init(); }
    if ( class_exists( '\\VC\\Model\\CPT_MenuItem' ) )        { ( new \VC\Model\CPT_MenuItem() )->init(); }
    if ( class_exists( '\\VC\\Admin\\Menu_Restaurant' ) )     { ( new \VC\Admin\Menu_Restaurant() )->init(); }
    if ( class_exists( '\\VC\\REST\\Restaurant_Controller' ) ) { ( new \VC\REST\Restaurant_Controller() )->init(); }

    if ( class_exists( '\\VC\\Admin\\Settings' ) )            { ( new \VC\Admin\Settings() )->init(); }
    if ( class_exists( '\\VC\\Order\\Statuses' ) )            { ( new \VC\Order\Statuses() )->init(); }
    if ( class_exists( '\\VC\\REST\\Webhooks_Controller' ) )  { ( new \VC\REST\Webhooks_Controller() )->init(); }
    if ( class_exists( '\\VC\\CLI\\Seed' ) )                  { ( new \VC\CLI\Seed() )->init(); }

    if ( class_exists( '\\VC\\Frontend\\Shortcodes' ) )        { ( new \VC\Frontend\Shortcodes() )->init(); }
    if ( class_exists( '\\VC\\Frontend\\Shipping' ) )          { ( new \VC\Frontend\Shipping() )->init(); }
    if ( class_exists( '\\VC\\REST\\Shipping_Controller' ) )   { ( new \VC\REST\Shipping_Controller() )->init(); }

    if ( class_exists( '\\VC\\Frontend\\Coupons' ) )           { ( new \VC\Frontend\Coupons() )->init(); }
    if ( class_exists( '\\VC\\REST\\Coupons_Controller' ) )    { ( new \VC\REST\Coupons_Controller() )->init(); }
    if ( class_exists( '\\VC\\REST\\Orders_Controller' ) )     { ( new \VC\REST\Orders_Controller() )->init(); }
    if ( class_exists( '\\VC\\Email\\Templates' ) )            { ( new \VC\Email\Templates() )->init(); }
    if ( class_exists( '\\VC\\Email\\Events' ) )               { ( new \VC\Email\Events() )->init(); }

    if ( class_exists( '\\VC\\Admin\\Reports' ) )              { ( new \VC\Admin\Reports() )->init(); }
    if ( class_exists( '\\VC\\Admin\\Export' ) )               { ( new \VC\Admin\Export() )->init(); }
    if ( class_exists( '\\VC\\REST\\Cache_Middleware' ) )      { ( new \VC\REST\Cache_Middleware() )->init(); }
    if ( class_exists( '\\VC\\REST\\Invalidation' ) )          { ( new \VC\REST\Invalidation() )->init(); }

    // Pacote 8 — Instalador de Páginas
    if ( class_exists( '\\VC\\Admin\\Installer' ) )            { ( new \VC\Admin\Installer() )->init(); }
} );
