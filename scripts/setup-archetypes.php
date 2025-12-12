<?php
/**
 * Script para configurar o sistema de arquétipos
 * 
 * Executa:
 * 1. Migração de cuisines para arquétipos
 * 2. Limpeza e re-seed do catálogo de categorias
 * 
 * Uso: php scripts/setup-archetypes.php
 */

// Carregar WordPress
$wp_load_paths = [
    dirname( __DIR__ ) . '/../../wp-load.php',
    dirname( __DIR__ ) . '/../../../wp-load.php',
    dirname( __DIR__ ) . '/../../../../wp-load.php',
];

$wp_loaded = false;
foreach ( $wp_load_paths as $path ) {
    if ( file_exists( $path ) ) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if ( ! $wp_loaded ) {
    die( "❌ Não foi possível encontrar wp-load.php. Execute este script a partir da raiz do WordPress.\n" );
}

if ( ! function_exists( 'get_terms' ) ) {
    die( "❌ WordPress não foi carregado corretamente.\n" );
}

use VC\Utils\Cuisine_Helper;
use VC\Utils\Menu_Category_Catalog_Seeder;

echo "🚀 Configurando sistema de arquétipos...\n\n";

// 1. Migrar cuisines para arquétipos
echo "1️⃣ Migrando cuisines para arquétipos...\n";
if ( ! taxonomy_exists( 'vc_cuisine' ) ) {
    die( "❌ Taxonomia vc_cuisine não existe.\n" );
}

$terms = get_terms( [
    'taxonomy'   => 'vc_cuisine',
    'hide_empty' => false,
] );

if ( is_wp_error( $terms ) || empty( $terms ) ) {
    echo "⚠️  Nenhum termo vc_cuisine encontrado.\n";
} else {
    $stats = [
        'mapped'        => 0,
        'already_mapped' => 0,
        'style_tags'    => 0,
        'not_found'     => 0,
        'errors'        => 0,
    ];

    $map = Cuisine_Helper::get_slug_archetype_mapping();
    $style_tags_map = Cuisine_Helper::get_style_tags_mapping();

    foreach ( $terms as $term ) {
        if ( $term->parent === 0 && str_starts_with( $term->slug, 'grupo-' ) ) {
            continue;
        }

        $slug = $term->slug;
        $existing_archetype = get_term_meta( $term->term_id, '_vc_cuisine_archetype', true );
        
        if ( ! empty( $existing_archetype ) ) {
            $stats['already_mapped']++;
            continue;
        }

        if ( isset( $map[ $slug ] ) ) {
            $archetype = $map[ $slug ];
            if ( Cuisine_Helper::set_archetype_for_cuisine( $term->term_id, $archetype ) ) {
                $stats['mapped']++;
                echo "  ✓ {$term->name} → {$archetype}\n";
            } else {
                $stats['errors']++;
            }
        } elseif ( isset( $style_tags_map[ $slug ] ) ) {
            $tags = $style_tags_map[ $slug ];
            foreach ( $tags as $meta_key => $meta_value ) {
                update_term_meta( $term->term_id, $meta_key, $meta_value );
            }
            $stats['style_tags']++;
            echo "  ✓ {$term->name} → tags de estilo\n";
        } else {
            $stats['not_found']++;
            echo "  ⚠️  Sem mapeamento: {$term->name} (slug: {$slug})\n";
        }
    }

    echo "\n✅ Migração concluída:\n";
    echo "   - Mapeados: {$stats['mapped']}\n";
    echo "   - Já mapeados: {$stats['already_mapped']}\n";
    echo "   - Tags de estilo: {$stats['style_tags']}\n";
    echo "   - Sem mapeamento: {$stats['not_found']}\n";
    echo "   - Erros: {$stats['errors']}\n\n";
}

// 2. Limpar e re-seed do catálogo
echo "2️⃣ Limpando e re-seed do catálogo de categorias...\n";

if ( ! taxonomy_exists( 'vc_menu_category' ) || ! taxonomy_exists( 'vc_cuisine' ) ) {
    die( "❌ Taxonomias necessárias não existem.\n" );
}

// Buscar categorias de catálogo existentes
$existing_catalog = get_terms( [
    'taxonomy'   => 'vc_menu_category',
    'hide_empty' => false,
    'meta_query' => [
        [
            'key'     => '_vc_is_catalog_category',
            'value'   => '1',
            'compare' => '=',
        ],
    ],
] );

$deleted_count = 0;
if ( ! is_wp_error( $existing_catalog ) && ! empty( $existing_catalog ) ) {
    echo "   Encontradas " . count( $existing_catalog ) . " categorias de catálogo. Limpando...\n";
    
    foreach ( $existing_catalog as $term ) {
        delete_term_meta( $term->term_id, '_vc_is_catalog_category' );
        delete_term_meta( $term->term_id, '_vc_recommended_for_cuisines' );
        delete_term_meta( $term->term_id, '_vc_recommended_for_archetypes' );
        delete_term_meta( $term->term_id, '_vc_category_order' );
        
        if ( $term->count === 0 ) {
            wp_delete_term( $term->term_id, 'vc_menu_category' );
            $deleted_count++;
        } else {
            echo "   ⚠️  Categoria '{$term->name}' não deletada (possui {$term->count} produto(s))\n";
        }
    }
}

echo "   ✅ Limpeza concluída. {$deleted_count} categorias deletadas.\n";

// Limpar cache
clean_term_cache( null, 'vc_menu_category' );
delete_option( 'vemcomer_menu_categories_seeded' );

// Executar seed novamente
echo "   Executando seed com novos blueprints...\n";
Menu_Category_Catalog_Seeder::seed( true );

// Verificar resultado
$new_catalog = get_terms( [
    'taxonomy'   => 'vc_menu_category',
    'hide_empty' => false,
    'meta_query' => [
        [
            'key'     => '_vc_is_catalog_category',
            'value'   => '1',
            'compare' => '=',
        ],
    ],
] );

if ( ! is_wp_error( $new_catalog ) ) {
    echo "   ✅ Re-seed concluído! " . count( $new_catalog ) . " categorias de catálogo criadas/atualizadas.\n\n";
} else {
    echo "   ❌ Erro ao verificar categorias criadas.\n\n";
}

echo "✅ Configuração completa!\n";
echo "\n📋 Próximos passos:\n";
echo "   1. Teste o onboarding selecionando diferentes tipos de restaurante no passo 1\n";
echo "   2. Verifique se as categorias recomendadas aparecem corretamente no passo 4\n";
echo "   3. Se alguma categoria não aparecer, verifique se o tipo de restaurante tem arquétipo mapeado\n";

