<?php
/**
 * Plugin Name:       VemComer Core
 * Plugin URI:        https://github.com/Staryannfree/vemcomer-core
 * Description:       Núcleo do marketplace VemComer (WordPress).
 * Version:           0.1.0
 * Requires at least: 6.2
 * Requires PHP:      8.1
 * Author:            VemComer
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       vemcomer-core
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Constantes úteis
 */
define( 'VMC_VERSION', '0.1.0' );
define( 'VMC_FILE', __FILE__ );
define( 'VMC_DIR', plugin_dir_path( __FILE__ ) );
define( 'VMC_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoload via Composer, com fallback PSR-4 simples
 */
$composer = VMC_DIR . 'vendor/autoload.php';
if ( file_exists( $composer ) ) {
	require_once $composer;
} else {
	// Fallback PSR-4 básico para o namespace "VemComer\Core\"
	spl_autoload_register( function ( $class ) {
		$prefix   = 'VemComer\\Core\\';
		$base_dir = VMC_DIR . 'inc/';
		$len      = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}
		$relative_class = substr( $class, $len );
		$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	} );
}

/**
 * Inicialização principal
 */
add_action( 'plugins_loaded', static function () {
	// Carrega o core
	if ( class_exists( 'VemComer\\Core\\Plugin' ) ) {
		VemComer\Core\Plugin::instance()->boot();
	}
} );

/**
 * Hooks de ativação/desativação
 */
register_activation_hook( __FILE__, [ 'VemComer\\Core\\Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'VemComer\\Core\\Plugin', 'deactivate' ] );
