<?php
/**
 * Script para re-seed do catálogo de categorias de cardápio
 * 
 * Uso: php scripts/reseed-menu-categories.php
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

use VC\Utils\Menu_Category_Catalog_Seeder;

echo "🚀 Iniciando re-seed do catálogo de categorias de cardápio...\n\n";

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
$kept_count = 0;
if ( ! is_wp_error( $existing_catalog ) && ! empty( $existing_catalog ) ) {
    echo "📋 Encontradas " . count( $existing_catalog ) . " categorias de catálogo. Limpando...\n";
    
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
            $kept_count++;
        }
    }
}

echo "   ✅ Limpeza concluída. {$deleted_count} categorias deletadas";
if ( $kept_count > 0 ) {
    echo ", {$kept_count} mantidas (com produtos)";
}
echo ".\n\n";

// Limpar cache
clean_term_cache( null, 'vc_menu_category' );
delete_option( 'vemcomer_menu_categories_seeded' );
wp_cache_flush();

// Executar seed novamente
echo "🌱 Executando seed com novos blueprints...\n";
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
    
    // Mostrar algumas categorias como exemplo
    echo "📋 Exemplos de categorias criadas:\n";
    $examples = array_slice( $new_catalog, 0, 10 );
    foreach ( $examples as $cat ) {
        $archetypes = get_term_meta( $cat->term_id, '_vc_recommended_for_archetypes', true );
        $archetype_list = ! empty( $archetypes ) ? json_decode( $archetypes, true ) : [];
        $archetypes_str = ! empty( $archetype_list ) ? implode( ', ', $archetype_list ) : '(genérica)';
        echo "   - {$cat->name} → arquétipos: {$archetypes_str}\n";
    }
    if ( count( $new_catalog ) > 10 ) {
        echo "   ... e mais " . ( count( $new_catalog ) - 10 ) . " categoria(s)\n";
    }
} else {
    echo "   ❌ Erro ao verificar categorias criadas.\n\n";
}

echo "\n✅ Processo completo!\n";
echo "\n📋 Próximos passos:\n";
echo "   1. Limpe o cache do WordPress\n";
echo "   2. Teste o onboarding selecionando 'Hamburgueria' no passo 1\n";
echo "   3. Verifique se as categorias aparecem corretamente no passo 4\n";

