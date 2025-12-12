<?php
/**
 * Script para conectar grupos de adicionais às categorias de restaurantes
 * Execute: php scripts/connect-addons-to-categories.php
 */

// Carregar WordPress
require_once dirname( __DIR__, 3 ) . '/wp-load.php';

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo "========================================\n";
echo "CONECTANDO GRUPOS DE ADICIONAIS\n";
echo "========================================\n\n";

// Verificar se o controller existe
if ( ! class_exists( '\\VC\\REST\\Seeder_Controller' ) ) {
    echo "❌ Erro: Seeder_Controller não encontrado.\n";
    exit( 1 );
}

$controller = new \VC\REST\Seeder_Controller();

// 1. Verificar status atual
echo "[1/3] Verificando status atual das conexões...\n";
$verify_request = new WP_REST_Request( 'GET', '/vemcomer/v1/seed/verify-connections' );
$verify_response = $controller->verify_connections( $verify_request );
$verify_data = $verify_response->get_data() ?? [];

echo "  Total de grupos: {$verify_data['total_groups']}\n";
echo "  Grupos com categorias: {$verify_data['groups_with_categories']}\n";
echo "  Grupos sem categorias: {$verify_data['groups_without_categories']}\n";
echo "  Total de categorias: {$verify_data['total_cuisines']}\n";
echo "  Categorias com grupos: {$verify_data['cuisines_with_groups']}\n";
echo "  Categorias sem grupos: {$verify_data['cuisines_without_groups']}\n\n";

if ( ! empty( $verify_data['groups_without_categories_list'] ) ) {
    echo "  ⚠️  Grupos sem categorias:\n";
    foreach ( $verify_data['groups_without_categories_list'] as $group ) {
        echo "    - {$group['name']} (ID: {$group['id']})\n";
    }
    echo "\n";
}

if ( ! empty( $verify_data['cuisines_without_groups_list'] ) ) {
    echo "  ⚠️  Categorias sem grupos:\n";
    foreach ( array_slice( $verify_data['cuisines_without_groups_list'], 0, 10 ) as $cuisine ) {
        echo "    - {$cuisine['name']} (ID: {$cuisine['id']})\n";
    }
    if ( count( $verify_data['cuisines_without_groups_list'] ) > 10 ) {
        echo "    ... e mais " . ( count( $verify_data['cuisines_without_groups_list'] ) - 10 ) . " categorias\n";
    }
    echo "\n";
}

// 2. Conectar grupos
echo "[2/3] Conectando grupos às categorias...\n";
$connect_request = new WP_REST_Request( 'POST', '/vemcomer/v1/seed/connect-addons' );
$connect_response = $controller->connect_addons_to_categories( $connect_request );
$connect_data = $connect_response->get_data();

if ( $connect_data['success'] ) {
    echo "  ✅ Conexões atualizadas!\n";
    echo "  Já conectados: {$connect_data['already_connected']}\n";
    echo "  Reconectados: {$connect_data['reconnected']}\n";
    echo "  Genéricos conectados: {$connect_data['generic_connected']}\n";
    echo "  Categorias sem grupos: {$connect_data['categories_without_groups']}\n";
    
    if ( ! empty( $connect_data['errors'] ) ) {
        echo "\n  ⚠️  Erros:\n";
        foreach ( $connect_data['errors'] as $error ) {
            echo "    - $error\n";
        }
    }
} else {
    echo "  ❌ Erro ao conectar: {$connect_data['message']}\n";
}

echo "\n";

// 3. Verificar status final
echo "[3/3] Verificando status final...\n";
$final_request = new WP_REST_Request( 'GET', '/vemcomer/v1/seed/verify-connections' );
$final_verify = $controller->verify_connections( $final_request );
$final_data = $final_verify->get_data() ?? [];

echo "  Total de grupos: {$final_data['total_groups']}\n";
echo "  Grupos com categorias: {$final_data['groups_with_categories']}\n";
echo "  Grupos sem categorias: {$final_data['groups_without_categories']}\n";
echo "  Categorias com grupos: {$final_data['cuisines_with_groups']}\n";
echo "  Categorias sem grupos: {$final_data['cuisines_without_groups']}\n\n";

if ( $final_data['groups_without_categories'] === 0 && $final_data['cuisines_without_groups'] === 0 ) {
    echo "========================================\n";
    echo "✅ TUDO CONECTADO COM SUCESSO!\n";
    echo "========================================\n";
} else {
    echo "========================================\n";
    echo "⚠️  AINDA HÁ CONEXÕES PENDENTES\n";
    echo "========================================\n";
}

