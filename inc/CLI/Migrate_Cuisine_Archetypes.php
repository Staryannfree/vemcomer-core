<?php
/**
 * Migrate_Cuisine_Archetypes — Comando WP-CLI para migrar cuisines para arquétipos
 * 
 * @package VemComerCore
 */

namespace VC\CLI;

use VC\Utils\Cuisine_Helper;
use VC\Utils\Menu_Category_Catalog_Seeder;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

class Migrate_Cuisine_Archetypes {
    /**
     * Inicializa o comando WP-CLI
     */
    public function init(): void {
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            \WP_CLI::add_command( 'vemcomer migrate-cuisine-archetypes', [ $this, 'migrate' ] );
            \WP_CLI::add_command( 'vemcomer reseed-menu-categories', [ $this, 'reseed_menu_categories' ] );
        }
    }

    /**
     * Migra todos os termos vc_cuisine para arquétipos
     * 
     * ## EXEMPLOS
     * 
     *     wp vemcomer migrate-cuisine-archetypes
     * 
     * @when after_wp_load
     */
    public function migrate( $args, $assoc_args ): void {
        \WP_CLI::log( 'Iniciando migração de cuisines para arquétipos...' );

        if ( ! taxonomy_exists( 'vc_cuisine' ) ) {
            \WP_CLI::error( 'Taxonomia vc_cuisine não existe.' );
            return;
        }

        // Buscar todos os termos vc_cuisine
        $terms = get_terms( [
            'taxonomy'   => 'vc_cuisine',
            'hide_empty' => false,
        ] );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            \WP_CLI::warning( 'Nenhum termo vc_cuisine encontrado.' );
            return;
        }

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
            // Pular grupos pais (que começam com 'grupo-')
            if ( $term->parent === 0 && str_starts_with( $term->slug, 'grupo-' ) ) {
                continue;
            }

            $slug = $term->slug;

            // Verificar se já tem arquétipo definido
            $existing_archetype = get_term_meta( $term->term_id, '_vc_cuisine_archetype', true );
            if ( ! empty( $existing_archetype ) ) {
                $stats['already_mapped']++;
                continue;
            }

            // Mapear para arquétipo
            if ( isset( $map[ $slug ] ) ) {
                $archetype = $map[ $slug ];
                if ( Cuisine_Helper::set_archetype_for_cuisine( $term->term_id, $archetype ) ) {
                    $stats['mapped']++;
                    \WP_CLI::log( sprintf( '✓ %s → %s', $term->name, $archetype ) );
                } else {
                    $stats['errors']++;
                    \WP_CLI::warning( sprintf( 'Erro ao salvar arquétipo para: %s', $term->name ) );
                }
            } elseif ( isset( $style_tags_map[ $slug ] ) ) {
                // É uma tag de estilo/formato, não arquétipo
                $tags = $style_tags_map[ $slug ];
                foreach ( $tags as $meta_key => $meta_value ) {
                    update_term_meta( $term->term_id, $meta_key, $meta_value );
                }
                $stats['style_tags']++;
                \WP_CLI::log( sprintf( '✓ %s → tags de estilo', $term->name ) );
            } else {
                $stats['not_found']++;
                \WP_CLI::warning( sprintf( 'Sem mapeamento para: %s (slug: %s)', $term->name, $slug ) );
            }
        }

        // Resumo
        \WP_CLI::success( sprintf(
            'Migração concluída. Mapeados: %d | Já mapeados: %d | Tags de estilo: %d | Sem mapeamento: %d | Erros: %d',
            $stats['mapped'],
            $stats['already_mapped'],
            $stats['style_tags'],
            $stats['not_found'],
            $stats['errors']
        ) );

        if ( $stats['not_found'] > 0 ) {
            \WP_CLI::warning( sprintf(
                '%d termos não foram mapeados. Verifique se os slugs estão corretos no Cuisine_Helper.',
                $stats['not_found']
            ) );
        }
    }

    /**
     * Limpa e re-seed do catálogo de categorias de cardápio usando os novos blueprints
     * 
     * ## EXEMPLOS
     * 
     *     wp vemcomer reseed-menu-categories
     * 
     * @when after_wp_load
     */
    public function reseed_menu_categories( $args, $assoc_args ): void {
        \WP_CLI::log( 'Iniciando re-seed do catálogo de categorias de cardápio...' );

        if ( ! taxonomy_exists( 'vc_menu_category' ) || ! taxonomy_exists( 'vc_cuisine' ) ) {
            \WP_CLI::error( 'Taxonomias necessárias não existem.' );
            return;
        }

        // Buscar todas as categorias de catálogo existentes
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
            \WP_CLI::log( sprintf( 'Encontradas %d categorias de catálogo existentes. Limpando...', count( $existing_catalog ) ) );
            
            foreach ( $existing_catalog as $term ) {
                // Deletar meta antes de deletar o termo
                delete_term_meta( $term->term_id, '_vc_is_catalog_category' );
                delete_term_meta( $term->term_id, '_vc_recommended_for_cuisines' );
                delete_term_meta( $term->term_id, '_vc_recommended_for_archetypes' );
                delete_term_meta( $term->term_id, '_vc_category_order' );
                
                // Deletar o termo (se não tiver produtos associados)
                if ( $term->count === 0 ) {
                    wp_delete_term( $term->term_id, 'vc_menu_category' );
                    $deleted_count++;
                } else {
                    \WP_CLI::warning( sprintf( 'Categoria "%s" não deletada pois possui %d produto(s) associado(s). Apenas removida do catálogo.', $term->name, $term->count ) );
                }
            }
        }

        \WP_CLI::log( sprintf( 'Limpeza concluída. %d categorias deletadas.', $deleted_count ) );
        \WP_CLI::log( '' );

        // Limpar cache
        clean_term_cache( null, 'vc_menu_category' );
        delete_option( 'vemcomer_menu_categories_seeded' );

        // Executar seed novamente (forçar re-seed)
        \WP_CLI::log( 'Executando seed com novos blueprints...' );
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
            \WP_CLI::success( sprintf(
                'Re-seed concluído! %d categorias de catálogo criadas/atualizadas.',
                count( $new_catalog )
            ) );
        } else {
            \WP_CLI::error( 'Erro ao verificar categorias criadas.' );
        }
    }
}

