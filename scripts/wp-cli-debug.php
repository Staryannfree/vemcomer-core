<?php
/**
 * Script WP-CLI para capturar TODAS as variáveis
 * Execute: wp eval-file scripts/wp-cli-debug.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    // Carregar WordPress se executado diretamente
    require_once dirname( dirname( __DIR__ ) ) . '/wp-load.php';
}

$output_dir = dirname( __DIR__ ) . '/debug-reports';
if ( ! file_exists( $output_dir ) ) {
    wp_mkdir_p( $output_dir );
}

$timestamp = date( 'Y-m-d-His' );
$output_file = $output_dir . '/wp-cli-full-state-' . $timestamp . '.json';

// Incluir o controller de debug
require_once dirname( __DIR__ ) . '/inc/REST/Debug_Controller.php';

$controller = new \VC\REST\Debug_Controller();
$request = new WP_REST_Request( 'GET', '/vemcomer/v1/debug/state' );
$response = $controller->get_full_state( $request );

$data = $response->get_data();
$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

file_put_contents( $output_file, $json );

echo "✅ Estado completo salvo em: $output_file\n";
echo "Tamanho: " . number_format( strlen( $json ) ) . " bytes\n";

