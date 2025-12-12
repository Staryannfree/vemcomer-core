<?php
/**
 * Category_Helper — Helper para gerenciar categorias de cardápio
 * @package VemComerCore
 */

namespace VC\Utils;

use WP_Error;
use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper para gerenciar categorias de cardápio
 */
class Category_Helper {
    /**
     * Cria uma categoria específica do restaurante
     * 
     * @param int    $restaurant_id ID do restaurante
     * @param string $name          Nome da categoria
     * @param string $slug          Slug da categoria (opcional, será gerado se vazio)
     * @return int|WP_Error ID do termo criado ou WP_Error em caso de erro
     */
    public static function create_restaurant_category( int $restaurant_id, string $name, string $slug = '' ): int|\WP_Error {
        if ( $restaurant_id <= 0 || empty( trim( $name ) ) ) {
            return new \WP_Error( 'invalid_params', __( 'Parâmetros inválidos.', 'vemcomer' ) );
        }

        // Gerar slug se não fornecido
        if ( empty( $slug ) ) {
            $slug = sanitize_title( $name ) . '-rest-' . $restaurant_id;
        } else {
            $slug = sanitize_title( $slug );
        }

        // Criar termo
        $result = wp_insert_term( $name, 'vc_menu_category', [
            'slug' => $slug,
        ] );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $term_id = (int) $result['term_id'];

        // Definir como categoria do restaurante (não do catálogo global)
        update_term_meta( $term_id, '_vc_restaurant_id', $restaurant_id );
        delete_term_meta( $term_id, '_vc_is_catalog_category' );

        return $term_id;
    }

    /**
     * Busca categorias de um restaurante específico
     * 
     * @param int $restaurant_id ID do restaurante
     * @return array Array de WP_Term ou array vazio
     */
    public static function query_restaurant_categories( int $restaurant_id ): array {
        if ( $restaurant_id <= 0 ) {
            return [];
        }

        $terms = get_terms( [
            'taxonomy'   => 'vc_menu_category',
            'hide_empty' => false,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key'     => '_vc_restaurant_id',
                    'value'   => $restaurant_id,
                    'compare' => '=',
                ],
                [
                    'key'     => '_vc_is_catalog_category',
                    'value'   => '1',
                    'compare' => '!=',
                ],
            ],
        ] );

        if ( is_wp_error( $terms ) ) {
            return [];
        }

        return $terms;
    }

    /**
     * Verifica se uma categoria é do catálogo global
     * 
     * @param int $term_id ID do termo
     * @return bool True se for categoria de catálogo global
     */
    public static function is_catalog_category( int $term_id ): bool {
        if ( $term_id <= 0 ) {
            return false;
        }

        $is_catalog = get_term_meta( $term_id, '_vc_is_catalog_category', true );
        return '1' === $is_catalog;
    }

    /**
     * Verifica se uma categoria pertence a um restaurante específico
     * 
     * @param int $term_id       ID do termo
     * @param int $restaurant_id ID do restaurante
     * @return bool True se a categoria pertence ao restaurante
     */
    public static function belongs_to_restaurant( int $term_id, int $restaurant_id ): bool {
        if ( $term_id <= 0 || $restaurant_id <= 0 ) {
            return false;
        }

        $cat_restaurant_id = (int) get_term_meta( $term_id, '_vc_restaurant_id', true );
        return $cat_restaurant_id === $restaurant_id;
    }
}

