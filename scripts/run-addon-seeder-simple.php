<?php
/**
 * Script simples para executar o seeder de grupos de adicionais
 * Força a criação mesmo se já existirem grupos
 */

require_once __DIR__ . '/../wp-load.php';

if ( ! defined( 'ABSPATH' ) ) {
    die( 'Erro: WordPress não carregado.' );
}

if ( ! class_exists( '\\VC\\Utils\\Addon_Catalog_Seeder' ) ) {
    die( 'Erro: Classe Addon_Catalog_Seeder não encontrada.' );
}

// Usar Reflection para acessar o método privado get_groups_data
$reflection = new \ReflectionClass( '\\VC\\Utils\\Addon_Catalog_Seeder' );
$method = $reflection->getMethod( 'get_groups_data' );
$method->setAccessible( true );
$groups_data = $method->invoke( null );

// Obter grupos existentes
$existing_groups = get_posts( [
    'post_type'      => 'vc_addon_group',
    'posts_per_page' => -1,
    'post_status'    => 'any',
] );

$existing_names = [];
foreach ( $existing_groups as $group ) {
    $existing_names[] = $group->post_title;
}

$created = 0;
$skipped = 0;

foreach ( $groups_data as $group_data ) {
    // Verificar se já existe
    if ( in_array( $group_data['name'], $existing_names, true ) ) {
        $skipped++;
        continue;
    }
    
    // Criar grupo
    $group_id = wp_insert_post( [
        'post_type'    => 'vc_addon_group',
        'post_title'   => $group_data['name'],
        'post_content' => $group_data['description'] ?? '',
        'post_status'  => 'publish',
    ] );
    
    if ( is_wp_error( $group_id ) ) {
        continue;
    }
    
    // Configurações
    update_post_meta( $group_id, '_vc_selection_type', $group_data['selection_type'] ?? 'multiple' );
    update_post_meta( $group_id, '_vc_min_select', $group_data['min_select'] ?? 0 );
    update_post_meta( $group_id, '_vc_max_select', $group_data['max_select'] ?? 0 );
    update_post_meta( $group_id, '_vc_is_required', $group_data['is_required'] ? '1' : '0' );
    update_post_meta( $group_id, '_vc_is_active', '1' );
    
    // Vincular categorias
    if ( ! empty( $group_data['categories'] ) ) {
        $category_ids = [];
        foreach ( $group_data['categories'] as $category_name ) {
            $term = get_term_by( 'name', $category_name, 'vc_cuisine' );
            if ( $term && ! is_wp_error( $term ) ) {
                $category_ids[] = $term->term_id;
            }
        }
        if ( ! empty( $category_ids ) ) {
            wp_set_object_terms( $group_id, $category_ids, 'vc_cuisine' );
        }
    }
    
    // Criar itens
    if ( ! empty( $group_data['items'] ) ) {
        foreach ( $group_data['items'] as $item_data ) {
            $item_id = wp_insert_post( [
                'post_type'    => 'vc_addon_item',
                'post_title'   => $item_data['name'],
                'post_content' => $item_data['description'] ?? '',
                'post_status'  => 'publish',
            ] );
            
            if ( ! is_wp_error( $item_id ) ) {
                update_post_meta( $item_id, '_vc_group_id', $group_id );
                update_post_meta( $item_id, '_vc_default_price', $item_data['price'] ?? '0.00' );
                update_post_meta( $item_id, '_vc_allow_quantity', $item_data['allow_quantity'] ? '1' : '0' );
                update_post_meta( $item_id, '_vc_max_quantity', $item_data['max_quantity'] ?? 1 );
                update_post_meta( $item_id, '_vc_is_active', '1' );
            }
        }
    }
    
    $created++;
}

echo "✅ Processo concluído!\n";
echo "Grupos criados: $created\n";
echo "Grupos já existentes (pulados): $skipped\n";

