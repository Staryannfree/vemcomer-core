<?php
/**
 * Script de migração: Converte grupos de adicionais de taxonomia para meta
 * Migra de wp_set_object_terms (tax_query) para update_post_meta (_vc_recommended_for_cuisines)
 * Execute: php scripts/migrate-addon-groups-to-meta.php
 */

// Carregar WordPress
require_once dirname( __DIR__, 3 ) . '/wp-load.php';

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo "========================================\n";
echo "MIGRAÇÃO: TAXONOMIA → META\n";
echo "========================================\n\n";

// Buscar todos os grupos de adicionais
$groups = get_posts( [
    'post_type'      => 'vc_addon_group',
    'posts_per_page' => -1,
    'post_status'    => 'any',
] );

if ( empty( $groups ) ) {
    echo "✅ Nenhum grupo encontrado. Nada para migrar.\n";
    exit( 0 );
}

echo "Encontrados " . count( $groups ) . " grupos.\n\n";

$migrated = 0;
$skipped = 0;
$errors = 0;

foreach ( $groups as $group ) {
    // Verificar se já tem meta (já migrado)
    $existing_meta = get_post_meta( $group->ID, '_vc_recommended_for_cuisines', true );
    if ( ! empty( $existing_meta ) ) {
        $decoded = json_decode( $existing_meta, true );
        if ( is_array( $decoded ) && ! empty( $decoded ) ) {
            echo "  ⏭️  Grupo '{$group->post_title}' já tem meta. Pulando...\n";
            $skipped++;
            continue;
        }
    }

    // Buscar categorias via taxonomia (abordagem antiga)
    $terms = wp_get_object_terms( $group->ID, 'vc_cuisine', [ 'fields' => 'ids' ] );
    
    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        echo "  ⚠️  Grupo '{$group->post_title}' não tem categorias via taxonomia. Pulando...\n";
        $skipped++;
        continue;
    }

    // Migrar para meta
    $result = update_post_meta( $group->ID, '_vc_recommended_for_cuisines', wp_json_encode( $terms ) );
    
    if ( $result !== false ) {
        echo "  ✅ Grupo '{$group->post_title}': migrado " . count( $terms ) . " categorias\n";
        $migrated++;
    } else {
        echo "  ❌ Erro ao migrar grupo '{$group->post_title}'\n";
        $errors++;
    }
}

echo "\n";
echo "========================================\n";
echo "MIGRAÇÃO CONCLUÍDA\n";
echo "========================================\n";
echo "Migrados: $migrated\n";
echo "Pulados: $skipped\n";
echo "Erros: $errors\n";
echo "\n";

if ( $migrated > 0 ) {
    echo "✅ Migração concluída com sucesso!\n";
    echo "   Os grupos agora usam a mesma abordagem do Menu_Category_Catalog_Seeder.\n";
} else {
    echo "ℹ️  Nenhum grupo precisou ser migrado.\n";
}

