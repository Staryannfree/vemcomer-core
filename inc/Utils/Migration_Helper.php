<?php
/**
 * Migration_Helper — Helper para migrações de dados legados
 * @package VemComerCore
 */

namespace VC\Utils;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper para migrações de dados legados
 */
class Migration_Helper {
    /**
     * Migra produtos antigos que usam `_vc_menu_item_restaurant` para `_vc_restaurant_id`
     * 
     * Busca produtos com `_vc_menu_item_restaurant` mas sem `_vc_restaurant_id`
     * e copia o valor para o meta oficial.
     * 
     * @return array Estatísticas da migração: ['migrated' => int, 'skipped' => int, 'errors' => int]
     */
    public static function migrate_legacy_product_restaurant_meta(): array {
        $stats = [
            'migrated' => 0,
            'skipped'  => 0,
            'errors'   => 0,
        ];

        // Buscar produtos sem _vc_restaurant_id
        $query = new WP_Query( [
            'post_type'      => 'vc_menu_item',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'no_found_rows'  => true,
            'fields'         => 'ids',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => '_vc_restaurant_id',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => '_vc_menu_item_restaurant',
                    'compare' => 'EXISTS',
                ],
            ],
        ] );

        if ( empty( $query->posts ) ) {
            wp_reset_postdata();
            return $stats;
        }

        foreach ( $query->posts as $product_id ) {
            $legacy_id = (int) get_post_meta( $product_id, '_vc_menu_item_restaurant', true );

            if ( $legacy_id <= 0 ) {
                $stats['skipped']++;
                continue;
            }

            // Verificar se o restaurante existe
            $restaurant = get_post( $legacy_id );
            if ( ! $restaurant || 'vc_restaurant' !== $restaurant->post_type ) {
                $stats['errors']++;
                continue;
            }

            // Migrar para o meta oficial
            $result = update_post_meta( $product_id, '_vc_restaurant_id', $legacy_id );

            if ( $result ) {
                $stats['migrated']++;
            } else {
                $stats['errors']++;
            }
        }

        wp_reset_postdata();
        return $stats;
    }

    /**
     * Verifica quantos produtos precisam de migração
     * 
     * @return int Número de produtos que precisam ser migrados
     */
    public static function count_products_needing_migration(): int {
        $query = new WP_Query( [
            'post_type'      => 'vc_menu_item',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'no_found_rows'  => false,
            'fields'         => 'ids',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => '_vc_restaurant_id',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => '_vc_menu_item_restaurant',
                    'compare' => 'EXISTS',
                ],
            ],
        ] );

        $count = $query->found_posts;
        wp_reset_postdata();
        return $count;
    }

    /**
     * Migra categorias de cardápio
     * 
     * - Corrige categorias de restaurantes sem `_vc_restaurant_id` (busca por produtos associados)
     * - Limpa `_vc_restaurant_id` de categorias de catálogo (`_vc_is_catalog_category = '1'`)
     * 
     * @return array Estatísticas: ['migrated' => int, 'cleaned' => int, 'errors' => int]
     */
    public static function migrate_categories(): array {
        $stats = [
            'migrated' => 0,
            'cleaned'  => 0,
            'errors'   => 0,
        ];

        // Buscar todas as categorias de cardápio
        $all_categories = get_terms( [
            'taxonomy'   => 'vc_menu_category',
            'hide_empty' => false,
        ] );

        if ( is_wp_error( $all_categories ) || empty( $all_categories ) ) {
            return $stats;
        }

        foreach ( $all_categories as $term ) {
            $term_id = $term->term_id;
            $is_catalog = get_term_meta( $term_id, '_vc_is_catalog_category', true );
            $has_restaurant_id = get_term_meta( $term_id, '_vc_restaurant_id', true );

            // Caso 1: Categoria de catálogo com _vc_restaurant_id (limpar)
            if ( '1' === $is_catalog && ! empty( $has_restaurant_id ) ) {
                $result = delete_term_meta( $term_id, '_vc_restaurant_id' );
                if ( $result ) {
                    $stats['cleaned']++;
                } else {
                    $stats['errors']++;
                }
                continue;
            }

            // Caso 2: Categoria de restaurante sem _vc_restaurant_id (adicionar)
            if ( '1' !== $is_catalog && empty( $has_restaurant_id ) ) {
                // Tentar descobrir o restaurante pelos produtos associados
                $restaurant_id = self::find_restaurant_id_by_category_products( $term_id );
                
                if ( $restaurant_id > 0 ) {
                    $result = update_term_meta( $term_id, '_vc_restaurant_id', $restaurant_id );
                    if ( $result ) {
                        $stats['migrated']++;
                    } else {
                        $stats['errors']++;
                    }
                } else {
                    // Não conseguiu determinar restaurante - marcar como erro
                    $stats['errors']++;
                }
            }
        }

        return $stats;
    }

    /**
     * Tenta descobrir o restaurante de uma categoria através dos produtos associados
     * 
     * @param int $term_id ID do termo da categoria
     * @return int ID do restaurante encontrado ou 0
     */
    private static function find_restaurant_id_by_category_products( int $term_id ): int {
        if ( $term_id <= 0 ) {
            return 0;
        }

        // Buscar produtos associados a esta categoria
        $products = get_posts( [
            'post_type'      => 'vc_menu_item',
            'posts_per_page' => 10, // Limitar para performance
            'post_status'    => 'any',
            'fields'         => 'ids',
            'tax_query'      => [
                [
                    'taxonomy' => 'vc_menu_category',
                    'field'    => 'term_id',
                    'terms'    => $term_id,
                ],
            ],
        ] );

        if ( empty( $products ) ) {
            return 0;
        }

        // Verificar qual restaurante aparece mais vezes
        $restaurant_counts = [];
        foreach ( $products as $product_id ) {
            $restaurant_id = (int) get_post_meta( $product_id, '_vc_restaurant_id', true );
            if ( $restaurant_id > 0 ) {
                $restaurant_counts[ $restaurant_id ] = ( $restaurant_counts[ $restaurant_id ] ?? 0 ) + 1;
            }
        }

        if ( empty( $restaurant_counts ) ) {
            return 0;
        }

        // Retornar o restaurante mais comum
        arsort( $restaurant_counts );
        return (int) array_key_first( $restaurant_counts );
    }

    /**
     * Migra usuários: sincroniza vc_restaurant_id em usermeta
     * 
     * Para usuários que são autores de restaurantes mas não têm o meta setado
     * 
     * @return array Estatísticas: ['synced' => int, 'skipped' => int, 'errors' => int]
     */
    public static function migrate_users(): array {
        $stats = [
            'synced'  => 0,
            'skipped' => 0,
            'errors'  => 0,
        ];

        // Buscar todos os restaurantes
        $restaurants = get_posts( [
            'post_type'      => 'vc_restaurant',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ] );

        if ( empty( $restaurants ) ) {
            return $stats;
        }

        foreach ( $restaurants as $restaurant_id ) {
            $restaurant = get_post( $restaurant_id );
            if ( ! $restaurant ) {
                $stats['errors']++;
                continue;
            }

            $user_id = (int) $restaurant->post_author;
            if ( $user_id <= 0 ) {
                $stats['skipped']++;
                continue;
            }

            // Verificar se o usuário já tem o meta
            $existing_meta = get_user_meta( $user_id, 'vc_restaurant_id', true );
            if ( ! empty( $existing_meta ) ) {
                $stats['skipped']++;
                continue;
            }

            // Verificar se o usuário existe
            $user = get_user_by( 'id', $user_id );
            if ( ! $user ) {
                $stats['errors']++;
                continue;
            }

            // Sincronizar meta
            $result = update_user_meta( $user_id, 'vc_restaurant_id', $restaurant_id );
            if ( $result ) {
                $stats['synced']++;
            } else {
                $stats['errors']++;
            }
        }

        return $stats;
    }

    /**
     * Valida integridade dos dados
     * 
     * Verifica:
     * - Produtos sem `_vc_restaurant_id`
     * - Categorias de restaurante sem `_vc_restaurant_id`
     * - Categorias de catálogo com `_vc_restaurant_id`
     * 
     * @return array Relatório de inconsistências:
     *   [
     *     'products_without_restaurant' => int,
     *     'categories_without_restaurant' => int,
     *     'catalog_categories_with_restaurant' => int,
     *     'users_without_meta' => int,
     *   ]
     */
    public static function validate_data_integrity(): array {
        $report = [
            'products_without_restaurant'        => 0,
            'categories_without_restaurant'     => 0,
            'catalog_categories_with_restaurant' => 0,
            'users_without_meta'                 => 0,
        ];

        // 1. Produtos sem _vc_restaurant_id
        $products_query = new WP_Query( [
            'post_type'      => 'vc_menu_item',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'no_found_rows'  => false,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'     => '_vc_restaurant_id',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ] );
        $report['products_without_restaurant'] = $products_query->found_posts;
        wp_reset_postdata();

        // 2. Categorias de restaurante sem _vc_restaurant_id
        $all_categories = get_terms( [
            'taxonomy'   => 'vc_menu_category',
            'hide_empty' => false,
        ] );

        if ( ! is_wp_error( $all_categories ) && ! empty( $all_categories ) ) {
            foreach ( $all_categories as $term ) {
                $is_catalog = get_term_meta( $term->term_id, '_vc_is_catalog_category', true );
                $has_restaurant_id = get_term_meta( $term->term_id, '_vc_restaurant_id', true );

                // Categoria de restaurante sem _vc_restaurant_id
                if ( '1' !== $is_catalog && empty( $has_restaurant_id ) ) {
                    $report['categories_without_restaurant']++;
                }

                // Categoria de catálogo com _vc_restaurant_id
                if ( '1' === $is_catalog && ! empty( $has_restaurant_id ) ) {
                    $report['catalog_categories_with_restaurant']++;
                }
            }
        }

        // 3. Usuários autores de restaurantes sem vc_restaurant_id
        $restaurants = get_posts( [
            'post_type'      => 'vc_restaurant',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ] );

        $user_ids_checked = [];
        foreach ( $restaurants as $restaurant_id ) {
            $restaurant = get_post( $restaurant_id );
            if ( ! $restaurant ) {
                continue;
            }

            $user_id = (int) $restaurant->post_author;
            if ( $user_id <= 0 || isset( $user_ids_checked[ $user_id ] ) ) {
                continue;
            }

            $user_ids_checked[ $user_id ] = true;
            $has_meta = get_user_meta( $user_id, 'vc_restaurant_id', true );
            if ( empty( $has_meta ) ) {
                $report['users_without_meta']++;
            }
        }

        return $report;
    }
}

