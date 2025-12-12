<?php
/**
 * Script para executar o seeder de grupos de adicionais
 * 
 * Uso: php scripts/run-addon-seeder.php
 * ou acesse via navegador: /wp-content/plugins/vemcomer-core/scripts/run-addon-seeder.php
 */

// Carregar WordPress
require_once __DIR__ . '/../wp-load.php';

// Verificar se estamos no WordPress
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Este script deve ser executado dentro do WordPress.' );
}

// Verificar se a classe existe
if ( ! class_exists( '\\VC\\Utils\\Addon_Catalog_Seeder' ) ) {
    die( 'Classe Addon_Catalog_Seeder não encontrada.' );
}

// Verificar se os CPTs existem
if ( ! post_type_exists( 'vc_addon_group' ) || ! post_type_exists( 'vc_addon_item' ) ) {
    die( 'Os Custom Post Types vc_addon_group ou vc_addon_item não existem.' );
}

echo "=== EXECUTANDO SEEDER DE GRUPOS DE ADICIONAIS ===\n\n";

// Verificar se já existem grupos
$existing = get_posts( [
    'post_type'      => 'vc_addon_group',
    'posts_per_page' => 1,
    'post_status'    => 'any',
] );

if ( ! empty( $existing ) ) {
    echo "⚠️  ATENÇÃO: Já existem grupos de adicionais no banco de dados.\n";
    echo "O seeder padrão não executa se já houver grupos.\n\n";
    echo "Opções:\n";
    echo "1. Deletar grupos existentes e recriar (digite '1')\n";
    echo "2. Adicionar apenas grupos novos que não existem (digite '2')\n";
    echo "3. Cancelar (digite '3' ou qualquer outra tecla)\n\n";
    
    if ( php_sapi_name() === 'cli' ) {
        echo "Escolha uma opção: ";
        $handle = fopen( "php://stdin", "r" );
        $line = fgets( $handle );
        $choice = trim( $line );
        fclose( $handle );
    } else {
        // Via navegador, vamos apenas adicionar novos grupos
        $choice = '2';
    }
    
    if ( $choice === '1' ) {
        echo "\n🗑️  Deletando grupos existentes...\n";
        $all_groups = get_posts( [
            'post_type'      => 'vc_addon_group',
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ] );
        
        $deleted = 0;
        foreach ( $all_groups as $group ) {
            // Deletar itens do grupo primeiro
            $items = get_posts( [
                'post_type'      => 'vc_addon_item',
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'meta_query'     => [
                    [
                        'key'   => '_vc_group_id',
                        'value' => $group->ID,
                    ],
                ],
            ] );
            
            foreach ( $items as $item ) {
                wp_delete_post( $item->ID, true );
            }
            
            // Deletar o grupo
            wp_delete_post( $group->ID, true );
            $deleted++;
        }
        
        echo "✅ $deleted grupos deletados.\n\n";
        echo "🌱 Executando seeder...\n";
        \VC\Utils\Addon_Catalog_Seeder::seed();
        echo "✅ Seeder executado com sucesso!\n";
        
    } elseif ( $choice === '2' ) {
        echo "\n🌱 Adicionando apenas grupos novos...\n";
        
        // Obter todos os grupos existentes
        $existing_groups = get_posts( [
            'post_type'      => 'vc_addon_group',
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ] );
        
        $existing_names = [];
        foreach ( $existing_groups as $group ) {
            $existing_names[] = $group->post_title;
        }
        
        // Obter dados dos grupos do seeder
        $reflection = new \ReflectionClass( '\\VC\\Utils\\Addon_Catalog_Seeder' );
        $method = $reflection->getMethod( 'get_groups_data' );
        $method->setAccessible( true );
        $groups_data = $method->invoke( null );
        
        $added = 0;
        foreach ( $groups_data as $group_data ) {
            // Verificar se o grupo já existe
            if ( in_array( $group_data['name'], $existing_names, true ) ) {
                continue;
            }
            
            // Criar o grupo
            $group_id = wp_insert_post( [
                'post_type'    => 'vc_addon_group',
                'post_title'   => $group_data['name'],
                'post_content' => $group_data['description'] ?? '',
                'post_status'  => 'publish',
            ] );
            
            if ( is_wp_error( $group_id ) ) {
                echo "❌ Erro ao criar grupo: {$group_data['name']}\n";
                continue;
            }
            
            // Salvar configurações
            update_post_meta( $group_id, '_vc_selection_type', $group_data['selection_type'] ?? 'multiple' );
            update_post_meta( $group_id, '_vc_min_select', $group_data['min_select'] ?? 0 );
            update_post_meta( $group_id, '_vc_max_select', $group_data['max_select'] ?? 0 );
            update_post_meta( $group_id, '_vc_is_required', $group_data['is_required'] ? '1' : '0' );
            update_post_meta( $group_id, '_vc_is_active', '1' );
            
            // Vincular às categorias
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
            
            // Criar itens do grupo
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
            
            $added++;
            echo "✅ Grupo criado: {$group_data['name']}\n";
        }
        
        echo "\n✅ $added novos grupos adicionados!\n";
        
    } else {
        echo "\n❌ Operação cancelada.\n";
        exit;
    }
    
} else {
    echo "🌱 Nenhum grupo existente encontrado. Executando seeder...\n\n";
    \VC\Utils\Addon_Catalog_Seeder::seed();
    echo "✅ Seeder executado com sucesso!\n";
}

// Contar grupos criados
$total_groups = get_posts( [
    'post_type'      => 'vc_addon_group',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
] );

$total_items = get_posts( [
    'post_type'      => 'vc_addon_item',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
] );

echo "\n=== RESUMO ===\n";
echo "Total de grupos: " . count( $total_groups ) . "\n";
echo "Total de itens: " . count( $total_items ) . "\n";
echo "\n✅ Processo concluído!\n";

