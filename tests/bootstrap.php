<?php
/**
 * Bootstrap para testes PHPUnit do WordPress
 * 
 * @package VemComerCore
 */

// Carregar WordPress
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
    $_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    echo "Não foi possível encontrar o WordPress Test Suite.\n";
    echo "Defina a variável de ambiente WP_TESTS_DIR ou instale o WordPress Test Suite.\n";
    exit( 1 );
}

// Carregar funções do WordPress Test Suite
require_once $_tests_dir . '/includes/functions.php';

/**
 * Carrega o plugin
 */
function _manually_load_plugin() {
    // Carregar o plugin principal
    require dirname( dirname( __FILE__ ) ) . '/vemcomer-core.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Carregar o WordPress Test Suite
require $_tests_dir . '/includes/bootstrap.php';

