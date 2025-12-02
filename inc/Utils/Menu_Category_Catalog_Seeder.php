<?php
/**
 * Menu_Category_Catalog_Seeder — Popula o catálogo de categorias de cardápio sugeridas
 * Vincula categorias aos tipos de restaurantes (vc_cuisine)
 * 
 * @package VemComerCore
 */

namespace VC\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Menu_Category_Catalog_Seeder {
    /**
     * Popula o catálogo com categorias de cardápio sugeridas por tipo de restaurante
     */
    public static function seed(): void {
        // Verificar se a taxonomia existe
        if ( ! taxonomy_exists( 'vc_menu_category' ) || ! taxonomy_exists( 'vc_cuisine' ) ) {
            return;
        }

        // Verificar se já foi populado
        $existing = get_terms( [
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

        if ( ! is_wp_error( $existing ) && ! empty( $existing ) ) {
            return; // Já foi populado
        }

        $categories_data = self::get_categories_data();

        foreach ( $categories_data as $category_data ) {
            // Verificar se a categoria já existe
            $existing_term = get_term_by( 'slug', sanitize_title( $category_data['name'] ), 'vc_menu_category' );
            
            if ( $existing_term ) {
                $term_id = $existing_term->term_id;
            } else {
                // Criar o termo
                $result = wp_insert_term(
                    $category_data['name'],
                    'vc_menu_category',
                    [
                        'slug' => sanitize_title( $category_data['name'] ),
                    ]
                );

                if ( is_wp_error( $result ) ) {
                    continue;
                }

                $term_id = is_array( $result ) ? $result['term_id'] : $result;
            }

            // Marcar como categoria do catálogo
            update_term_meta( $term_id, '_vc_is_catalog_category', '1' );

            // Salvar ordem se fornecida
            if ( isset( $category_data['order'] ) ) {
                update_term_meta( $term_id, '_vc_category_order', absint( $category_data['order'] ) );
            }

            // Vincular às categorias de restaurante (vc_cuisine)
            if ( ! empty( $category_data['cuisine_types'] ) ) {
                $cuisine_ids = self::get_cuisine_ids_by_names( $category_data['cuisine_types'] );
                if ( ! empty( $cuisine_ids ) ) {
                    // Salvar IDs das categorias de restaurante como term meta
                    update_term_meta( $term_id, '_vc_recommended_for_cuisines', wp_json_encode( $cuisine_ids ) );
                }
            }
        }
    }

    /**
     * Retorna os dados das categorias de cardápio sugeridas
     */
    private static function get_categories_data(): array {
        return [
            // Categorias para Hamburgueria
            [
                'name'         => 'Entradas / Porções',
                'order'        => 1,
                'cuisine_types' => [
                    'Hamburgueria artesanal',
                    'Hamburgueria smash',
                    'Lanchonete',
                    'Food truck',
                    'Culinária norte-americana',
                ],
            ],
            [
                'name'         => 'Hambúrgueres',
                'order'        => 2,
                'cuisine_types' => [
                    'Hamburgueria artesanal',
                    'Hamburgueria smash',
                    'Lanchonete',
                    'Food truck',
                    'Culinária norte-americana',
                ],
            ],
            [
                'name'         => 'Combos',
                'order'        => 3,
                'cuisine_types' => [
                    'Hamburgueria artesanal',
                    'Hamburgueria smash',
                    'Lanchonete',
                    'Food truck',
                    'Culinária norte-americana',
                ],
            ],
            [
                'name'         => 'Sobremesas',
                'order'        => 4,
                'cuisine_types' => [
                    'Hamburgueria artesanal',
                    'Hamburgueria smash',
                    'Lanchonete',
                    'Food truck',
                    'Culinária norte-americana',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 5,
                'cuisine_types' => [
                    'Hamburgueria artesanal',
                    'Hamburgueria smash',
                    'Lanchonete',
                    'Food truck',
                    'Culinária norte-americana',
                ],
            ],

            // Categorias para Pizzaria
            [
                'name'         => 'Pizzas salgadas',
                'order'        => 1,
                'cuisine_types' => [
                    'Pizzaria tradicional',
                    'Pizzaria napolitana',
                    'Pizzaria rodízio',
                    'Pizzaria delivery',
                    'Culinária italiana',
                ],
            ],
            [
                'name'         => 'Pizzas doces',
                'order'        => 2,
                'cuisine_types' => [
                    'Pizzaria tradicional',
                    'Pizzaria napolitana',
                    'Pizzaria rodízio',
                    'Pizzaria delivery',
                    'Culinária italiana',
                ],
            ],
            [
                'name'         => 'Combos',
                'order'        => 3,
                'cuisine_types' => [
                    'Pizzaria tradicional',
                    'Pizzaria napolitana',
                    'Pizzaria rodízio',
                    'Pizzaria delivery',
                    'Culinária italiana',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 4,
                'cuisine_types' => [
                    'Pizzaria tradicional',
                    'Pizzaria napolitana',
                    'Pizzaria rodízio',
                    'Pizzaria delivery',
                    'Culinária italiana',
                ],
            ],

            // Categorias para Sorveteria / Açaíteria
            [
                'name'         => 'Monte seu açaí / sorvete',
                'order'        => 1,
                'cuisine_types' => [
                    'Sorveteria',
                    'Gelateria',
                    'Açaíteria',
                    'Yogurteria',
                ],
            ],
            [
                'name'         => 'Combos',
                'order'        => 2,
                'cuisine_types' => [
                    'Sorveteria',
                    'Gelateria',
                    'Açaíteria',
                    'Yogurteria',
                ],
            ],
            [
                'name'         => 'Adicionais',
                'order'        => 3,
                'cuisine_types' => [
                    'Sorveteria',
                    'Gelateria',
                    'Açaíteria',
                    'Yogurteria',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 4,
                'cuisine_types' => [
                    'Sorveteria',
                    'Gelateria',
                    'Açaíteria',
                    'Yogurteria',
                ],
            ],

            // Categorias para Bar / Boteco
            [
                'name'         => 'Petiscos e porções',
                'order'        => 1,
                'cuisine_types' => [
                    'Bar',
                    'Boteco',
                    'Gastrobar',
                    'Pub',
                    'Sports bar / Bar esportivo',
                    'Comida de boteco',
                ],
            ],
            [
                'name'         => 'Pratos principais',
                'order'        => 2,
                'cuisine_types' => [
                    'Bar',
                    'Boteco',
                    'Gastrobar',
                    'Pub',
                    'Sports bar / Bar esportivo',
                    'Comida de boteco',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 3,
                'cuisine_types' => [
                    'Bar',
                    'Boteco',
                    'Gastrobar',
                    'Pub',
                    'Sports bar / Bar esportivo',
                    'Cervejaria artesanal',
                    'Choperia',
                    'Bar de vinhos / Wine bar',
                    'Bar de drinks / Coquetelaria',
                ],
            ],

            // Categorias para Cafeteria / Padaria
            [
                'name'         => 'Cafés',
                'order'        => 1,
                'cuisine_types' => [
                    'Cafeteria',
                    'Coffee shop especializado',
                    'Padaria tradicional',
                    'Padaria gourmet',
                ],
            ],
            [
                'name'         => 'Salgados',
                'order'        => 2,
                'cuisine_types' => [
                    'Cafeteria',
                    'Coffee shop especializado',
                    'Padaria tradicional',
                    'Padaria gourmet',
                ],
            ],
            [
                'name'         => 'Doces e sobremesas',
                'order'        => 3,
                'cuisine_types' => [
                    'Cafeteria',
                    'Coffee shop especializado',
                    'Padaria tradicional',
                    'Padaria gourmet',
                    'Confeitaria',
                    'Doceria',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 4,
                'cuisine_types' => [
                    'Cafeteria',
                    'Coffee shop especializado',
                    'Padaria tradicional',
                    'Padaria gourmet',
                ],
            ],

            // Categorias para Restaurante Japonês
            [
                'name'         => 'Entradas',
                'order'        => 1,
                'cuisine_types' => [
                    'Culinária japonesa',
                    'Sushi bar',
                    'Temakeria',
                    'Restaurante de lámen / ramen',
                    'Izakaya',
                ],
            ],
            [
                'name'         => 'Sushis e sashimis',
                'order'        => 2,
                'cuisine_types' => [
                    'Culinária japonesa',
                    'Sushi bar',
                    'Temakeria',
                ],
            ],
            [
                'name'         => 'Temakis',
                'order'        => 3,
                'cuisine_types' => [
                    'Culinária japonesa',
                    'Sushi bar',
                    'Temakeria',
                ],
            ],
            [
                'name'         => 'Pratos quentes',
                'order'        => 4,
                'cuisine_types' => [
                    'Culinária japonesa',
                    'Restaurante de lámen / ramen',
                    'Izakaya',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 5,
                'cuisine_types' => [
                    'Culinária japonesa',
                    'Sushi bar',
                    'Temakeria',
                    'Restaurante de lámen / ramen',
                    'Izakaya',
                ],
            ],

            // Categorias genéricas (para qualquer tipo)
            [
                'name'         => 'Entradas',
                'order'        => 1,
                'cuisine_types' => [], // Vazio = genérico, aparece para todos
            ],
            [
                'name'         => 'Pratos principais',
                'order'        => 2,
                'cuisine_types' => [], // Genérico
            ],
            [
                'name'         => 'Sobremesas',
                'order'        => 3,
                'cuisine_types' => [], // Genérico
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 4,
                'cuisine_types' => [], // Genérico
            ],
        ];
    }

    /**
     * Busca IDs de termos vc_cuisine pelos nomes
     */
    private static function get_cuisine_ids_by_names( array $names ): array {
        $ids = [];
        
        foreach ( $names as $name ) {
            $term = get_term_by( 'name', $name, 'vc_cuisine' );
            if ( $term && ! is_wp_error( $term ) ) {
                $ids[] = $term->term_id;
            }
        }

        return array_unique( array_filter( $ids ) );
    }
}

