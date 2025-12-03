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

            // Categorias para Marmitaria / Marmitex / Prato feito (PF)
            [
                'name'         => 'Marmitas',
                'order'        => 1,
                'cuisine_types' => [
                    'Marmitaria / Marmitex',
                    'Prato feito (PF)',
                    'Self-service / por quilo',
                    'Restaurante executivo',
                    'Marmita fitness',
                ],
            ],
            [
                'name'         => 'Pratos executivos',
                'order'        => 2,
                'cuisine_types' => [
                    'Marmitaria / Marmitex',
                    'Prato feito (PF)',
                    'Self-service / por quilo',
                    'Restaurante executivo',
                    'Marmita fitness',
                ],
            ],
            [
                'name'         => 'Acompanhamentos',
                'order'        => 3,
                'cuisine_types' => [
                    'Marmitaria / Marmitex',
                    'Prato feito (PF)',
                    'Self-service / por quilo',
                    'Restaurante executivo',
                    'Marmita fitness',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 4,
                'cuisine_types' => [
                    'Marmitaria / Marmitex',
                    'Prato feito (PF)',
                    'Self-service / por quilo',
                    'Restaurante executivo',
                    'Marmita fitness',
                ],
            ],

            // Categorias para Churrascaria
            [
                'name'         => 'Carnes',
                'order'        => 1,
                'cuisine_types' => [
                    'Churrascaria rodízio',
                    'Churrascaria à la carte',
                    'Steakhouse',
                ],
            ],
            [
                'name'         => 'Acompanhamentos',
                'order'        => 2,
                'cuisine_types' => [
                    'Churrascaria rodízio',
                    'Churrascaria à la carte',
                    'Steakhouse',
                ],
            ],
            [
                'name'         => 'Saladas',
                'order'        => 3,
                'cuisine_types' => [
                    'Churrascaria rodízio',
                    'Churrascaria à la carte',
                    'Steakhouse',
                ],
            ],
            [
                'name'         => 'Sobremesas',
                'order'        => 4,
                'cuisine_types' => [
                    'Churrascaria rodízio',
                    'Churrascaria à la carte',
                    'Steakhouse',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 5,
                'cuisine_types' => [
                    'Churrascaria rodízio',
                    'Churrascaria à la carte',
                    'Steakhouse',
                ],
            ],

            // Categorias para Restaurante Chinês
            [
                'name'         => 'Entradas',
                'order'        => 1,
                'cuisine_types' => [
                    'Culinária chinesa',
                ],
            ],
            [
                'name'         => 'Pratos principais',
                'order'        => 2,
                'cuisine_types' => [
                    'Culinária chinesa',
                ],
            ],
            [
                'name'         => 'Yakissoba / Macarrão',
                'order'        => 3,
                'cuisine_types' => [
                    'Culinária chinesa',
                ],
            ],
            [
                'name'         => 'Combos',
                'order'        => 4,
                'cuisine_types' => [
                    'Culinária chinesa',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 5,
                'cuisine_types' => [
                    'Culinária chinesa',
                ],
            ],

            // Categorias para Restaurante Árabe / Libanês
            [
                'name'         => 'Entradas / Mezze',
                'order'        => 1,
                'cuisine_types' => [
                    'Culinária árabe',
                    'Culinária libanesa',
                    'Culinária turca',
                ],
            ],
            [
                'name'         => 'Esfihas / Pães',
                'order'        => 2,
                'cuisine_types' => [
                    'Culinária árabe',
                    'Culinária libanesa',
                    'Culinária turca',
                    'Esfiharia',
                ],
            ],
            [
                'name'         => 'Pratos principais',
                'order'        => 3,
                'cuisine_types' => [
                    'Culinária árabe',
                    'Culinária libanesa',
                    'Culinária turca',
                ],
            ],
            [
                'name'         => 'Sobremesas',
                'order'        => 4,
                'cuisine_types' => [
                    'Culinária árabe',
                    'Culinária libanesa',
                    'Culinária turca',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 5,
                'cuisine_types' => [
                    'Culinária árabe',
                    'Culinária libanesa',
                    'Culinária turca',
                ],
            ],

            // Categorias para Restaurante Mexicano / Tex-Mex
            [
                'name'         => 'Entradas',
                'order'        => 1,
                'cuisine_types' => [
                    'Culinária mexicana',
                    'Tex-Mex',
                ],
            ],
            [
                'name'         => 'Tacos / Burritos',
                'order'        => 2,
                'cuisine_types' => [
                    'Culinária mexicana',
                    'Tex-Mex',
                ],
            ],
            [
                'name'         => 'Pratos principais',
                'order'        => 3,
                'cuisine_types' => [
                    'Culinária mexicana',
                    'Tex-Mex',
                ],
            ],
            [
                'name'         => 'Molhos e acompanhamentos',
                'order'        => 4,
                'cuisine_types' => [
                    'Culinária mexicana',
                    'Tex-Mex',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 5,
                'cuisine_types' => [
                    'Culinária mexicana',
                    'Tex-Mex',
                ],
            ],

            // Categorias para Restaurante de Frutos do Mar / Peixes
            [
                'name'         => 'Entradas',
                'order'        => 1,
                'cuisine_types' => [
                    'Frutos do mar',
                    'Peixes',
                    'Cevicheria',
                ],
            ],
            [
                'name'         => 'Peixes',
                'order'        => 2,
                'cuisine_types' => [
                    'Frutos do mar',
                    'Peixes',
                ],
            ],
            [
                'name'         => 'Frutos do mar',
                'order'        => 3,
                'cuisine_types' => [
                    'Frutos do mar',
                    'Cevicheria',
                ],
            ],
            [
                'name'         => 'Ceviches',
                'order'        => 4,
                'cuisine_types' => [
                    'Cevicheria',
                    'Culinária peruana',
                ],
            ],
            [
                'name'         => 'Acompanhamentos',
                'order'        => 5,
                'cuisine_types' => [
                    'Frutos do mar',
                    'Peixes',
                    'Cevicheria',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 6,
                'cuisine_types' => [
                    'Frutos do mar',
                    'Peixes',
                    'Cevicheria',
                ],
            ],

            // Categorias para Restaurante Vegetariano / Vegano
            [
                'name'         => 'Entradas',
                'order'        => 1,
                'cuisine_types' => [
                    'Vegetariano',
                    'Vegano',
                    'Plant-based',
                    'Comida fit / saudável',
                    'Natural / saudável',
                ],
            ],
            [
                'name'         => 'Pratos principais',
                'order'        => 2,
                'cuisine_types' => [
                    'Vegetariano',
                    'Vegano',
                    'Plant-based',
                    'Comida fit / saudável',
                    'Natural / saudável',
                ],
            ],
            [
                'name'         => 'Saladas e bowls',
                'order'        => 3,
                'cuisine_types' => [
                    'Vegetariano',
                    'Vegano',
                    'Plant-based',
                    'Comida fit / saudável',
                    'Natural / saudável',
                    'Saladas & bowls',
                ],
            ],
            [
                'name'         => 'Sobremesas',
                'order'        => 4,
                'cuisine_types' => [
                    'Vegetariano',
                    'Vegano',
                    'Plant-based',
                    'Comida fit / saudável',
                    'Natural / saudável',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 5,
                'cuisine_types' => [
                    'Vegetariano',
                    'Vegano',
                    'Plant-based',
                    'Comida fit / saudável',
                    'Natural / saudável',
                ],
            ],

            // Categorias para Pastelaria / Esfiharia
            [
                'name'         => 'Pastéis salgados',
                'order'        => 1,
                'cuisine_types' => [
                    'Pastelaria',
                ],
            ],
            [
                'name'         => 'Pastéis doces',
                'order'        => 2,
                'cuisine_types' => [
                    'Pastelaria',
                ],
            ],
            [
                'name'         => 'Esfihas',
                'order'        => 3,
                'cuisine_types' => [
                    'Esfiharia',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 4,
                'cuisine_types' => [
                    'Pastelaria',
                    'Esfiharia',
                ],
            ],

            // Categorias para Restaurante de Massas / Risotos
            [
                'name'         => 'Entradas',
                'order'        => 1,
                'cuisine_types' => [
                    'Massas & risotos',
                    'Culinária italiana',
                ],
            ],
            [
                'name'         => 'Massas',
                'order'        => 2,
                'cuisine_types' => [
                    'Massas & risotos',
                    'Culinária italiana',
                ],
            ],
            [
                'name'         => 'Risotos',
                'order'        => 3,
                'cuisine_types' => [
                    'Massas & risotos',
                    'Culinária italiana',
                ],
            ],
            [
                'name'         => 'Pratos principais',
                'order'        => 4,
                'cuisine_types' => [
                    'Massas & risotos',
                    'Culinária italiana',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 5,
                'cuisine_types' => [
                    'Massas & risotos',
                    'Culinária italiana',
                ],
            ],

            // Categorias para Restaurante Brasileiro / Caseiro
            [
                'name'         => 'Entradas',
                'order'        => 1,
                'cuisine_types' => [
                    'Restaurante brasileiro caseiro',
                    'Comida caseira',
                    'Comida mineira',
                    'Comida baiana',
                    'Comida nordestina',
                    'Comida gaúcha',
                ],
            ],
            [
                'name'         => 'Pratos principais',
                'order'        => 2,
                'cuisine_types' => [
                    'Restaurante brasileiro caseiro',
                    'Comida caseira',
                    'Comida mineira',
                    'Comida baiana',
                    'Comida nordestina',
                    'Comida gaúcha',
                ],
            ],
            [
                'name'         => 'Feijoada',
                'order'        => 3,
                'cuisine_types' => [
                    'Feijoada',
                    'Restaurante brasileiro caseiro',
                    'Comida caseira',
                ],
            ],
            [
                'name'         => 'Acompanhamentos',
                'order'        => 4,
                'cuisine_types' => [
                    'Restaurante brasileiro caseiro',
                    'Comida caseira',
                    'Comida mineira',
                    'Comida baiana',
                    'Comida nordestina',
                    'Comida gaúcha',
                ],
            ],
            [
                'name'         => 'Sobremesas',
                'order'        => 5,
                'cuisine_types' => [
                    'Restaurante brasileiro caseiro',
                    'Comida caseira',
                    'Comida mineira',
                    'Comida baiana',
                    'Comida nordestina',
                    'Comida gaúcha',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 6,
                'cuisine_types' => [
                    'Restaurante brasileiro caseiro',
                    'Comida caseira',
                    'Comida mineira',
                    'Comida baiana',
                    'Comida nordestina',
                    'Comida gaúcha',
                ],
            ],

            // Categorias para Restaurante Tailandês / Vietnamita
            [
                'name'         => 'Entradas',
                'order'        => 1,
                'cuisine_types' => [
                    'Culinária tailandesa',
                    'Culinária vietnamita',
                ],
            ],
            [
                'name'         => 'Pratos principais',
                'order'        => 2,
                'cuisine_types' => [
                    'Culinária tailandesa',
                    'Culinária vietnamita',
                ],
            ],
            [
                'name'         => 'Pad Thai / Pho / Sopas',
                'order'        => 3,
                'cuisine_types' => [
                    'Culinária tailandesa',
                    'Culinária vietnamita',
                ],
            ],
            [
                'name'         => 'Curries',
                'order'        => 4,
                'cuisine_types' => [
                    'Culinária tailandesa',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 5,
                'cuisine_types' => [
                    'Culinária tailandesa',
                    'Culinária vietnamita',
                ],
            ],

            // Categorias para Restaurante Coreano
            [
                'name'         => 'Entradas',
                'order'        => 1,
                'cuisine_types' => [
                    'Culinária coreana',
                    'Churrasco coreano (K-BBQ)',
                ],
            ],
            [
                'name'         => 'Pratos principais',
                'order'        => 2,
                'cuisine_types' => [
                    'Culinária coreana',
                    'Churrasco coreano (K-BBQ)',
                ],
            ],
            [
                'name'         => 'Bibimbap / Tteokbokki',
                'order'        => 3,
                'cuisine_types' => [
                    'Culinária coreana',
                ],
            ],
            [
                'name'         => 'Carnes grelhadas (K-BBQ)',
                'order'        => 4,
                'cuisine_types' => [
                    'Churrasco coreano (K-BBQ)',
                ],
            ],
            [
                'name'         => 'Acompanhamentos (Banchan)',
                'order'        => 5,
                'cuisine_types' => [
                    'Culinária coreana',
                    'Churrasco coreano (K-BBQ)',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 6,
                'cuisine_types' => [
                    'Culinária coreana',
                    'Churrasco coreano (K-BBQ)',
                ],
            ],

            // Categorias para Restaurante Indiano
            [
                'name'         => 'Entradas',
                'order'        => 1,
                'cuisine_types' => [
                    'Culinária indiana',
                ],
            ],
            [
                'name'         => 'Curries',
                'order'        => 2,
                'cuisine_types' => [
                    'Culinária indiana',
                ],
            ],
            [
                'name'         => 'Tandoori / Grelhados',
                'order'        => 3,
                'cuisine_types' => [
                    'Culinária indiana',
                ],
            ],
            [
                'name'         => 'Pães (Naan, Roti)',
                'order'        => 4,
                'cuisine_types' => [
                    'Culinária indiana',
                ],
            ],
            [
                'name'         => 'Arroz e Biryani',
                'order'        => 5,
                'cuisine_types' => [
                    'Culinária indiana',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 6,
                'cuisine_types' => [
                    'Culinária indiana',
                ],
            ],

            // Categorias para Restaurante Grego / Mediterrâneo
            [
                'name'         => 'Entradas / Mezze',
                'order'        => 1,
                'cuisine_types' => [
                    'Culinária grega',
                    'Culinária mediterrânea',
                ],
            ],
            [
                'name'         => 'Pratos principais',
                'order'        => 2,
                'cuisine_types' => [
                    'Culinária grega',
                    'Culinária mediterrânea',
                ],
            ],
            [
                'name'         => 'Grelhados',
                'order'        => 3,
                'cuisine_types' => [
                    'Culinária grega',
                    'Culinária mediterrânea',
                ],
            ],
            [
                'name'         => 'Saladas',
                'order'        => 4,
                'cuisine_types' => [
                    'Culinária grega',
                    'Culinária mediterrânea',
                ],
            ],
            [
                'name'         => 'Sobremesas',
                'order'        => 5,
                'cuisine_types' => [
                    'Culinária grega',
                    'Culinária mediterrânea',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 6,
                'cuisine_types' => [
                    'Culinária grega',
                    'Culinária mediterrânea',
                ],
            ],

            // Categorias para Restaurante Argentino / Uruguaio
            [
                'name'         => 'Entradas',
                'order'        => 1,
                'cuisine_types' => [
                    'Culinária argentina',
                    'Culinária uruguaia',
                ],
            ],
            [
                'name'         => 'Carnes',
                'order'        => 2,
                'cuisine_types' => [
                    'Culinária argentina',
                    'Culinária uruguaia',
                ],
            ],
            [
                'name'         => 'Empanadas',
                'order'        => 3,
                'cuisine_types' => [
                    'Culinária argentina',
                    'Culinária uruguaia',
                ],
            ],
            [
                'name'         => 'Acompanhamentos',
                'order'        => 4,
                'cuisine_types' => [
                    'Culinária argentina',
                    'Culinária uruguaia',
                ],
            ],
            [
                'name'         => 'Sobremesas',
                'order'        => 5,
                'cuisine_types' => [
                    'Culinária argentina',
                    'Culinária uruguaia',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 6,
                'cuisine_types' => [
                    'Culinária argentina',
                    'Culinária uruguaia',
                ],
            ],

            // Categorias para Hot Dog / Cachorro-quente
            [
                'name'         => 'Cachorros-quentes',
                'order'        => 1,
                'cuisine_types' => [
                    'Hot dog / Cachorro-quente',
                ],
            ],
            [
                'name'         => 'Combos',
                'order'        => 2,
                'cuisine_types' => [
                    'Hot dog / Cachorro-quente',
                ],
            ],
            [
                'name'         => 'Acompanhamentos',
                'order'        => 3,
                'cuisine_types' => [
                    'Hot dog / Cachorro-quente',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 4,
                'cuisine_types' => [
                    'Hot dog / Cachorro-quente',
                ],
            ],

            // Categorias para Kebab / Shawarma
            [
                'name'         => 'Kebabs / Shawarmas',
                'order'        => 1,
                'cuisine_types' => [
                    'Kebab / shawarma',
                ],
            ],
            [
                'name'         => 'Pratos no prato',
                'order'        => 2,
                'cuisine_types' => [
                    'Kebab / shawarma',
                ],
            ],
            [
                'name'         => 'Acompanhamentos',
                'order'        => 3,
                'cuisine_types' => [
                    'Kebab / shawarma',
                ],
            ],
            [
                'name'         => 'Molhos',
                'order'        => 4,
                'cuisine_types' => [
                    'Kebab / shawarma',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 5,
                'cuisine_types' => [
                    'Kebab / shawarma',
                ],
            ],

            // Categorias para Tapiocaria / Panquecaria
            [
                'name'         => 'Tapiocas salgadas',
                'order'        => 1,
                'cuisine_types' => [
                    'Tapiocaria',
                ],
            ],
            [
                'name'         => 'Tapiocas doces',
                'order'        => 2,
                'cuisine_types' => [
                    'Tapiocaria',
                ],
            ],
            [
                'name'         => 'Panquecas salgadas',
                'order'        => 1,
                'cuisine_types' => [
                    'Panquecaria',
                ],
            ],
            [
                'name'         => 'Panquecas doces',
                'order'        => 2,
                'cuisine_types' => [
                    'Panquecaria',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 3,
                'cuisine_types' => [
                    'Tapiocaria',
                    'Panquecaria',
                ],
            ],

            // Categorias para Creperia
            [
                'name'         => 'Crepes salgadas',
                'order'        => 1,
                'cuisine_types' => [
                    'Creperia salgada',
                    'Creperia doce',
                ],
            ],
            [
                'name'         => 'Crepes doces',
                'order'        => 2,
                'cuisine_types' => [
                    'Creperia doce',
                    'Creperia salgada',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 3,
                'cuisine_types' => [
                    'Creperia salgada',
                    'Creperia doce',
                ],
            ],

            // Categorias para Restaurante de Sopas / Caldos
            [
                'name'         => 'Sopas',
                'order'        => 1,
                'cuisine_types' => [
                    'Sopas & caldos',
                ],
            ],
            [
                'name'         => 'Caldos',
                'order'        => 2,
                'cuisine_types' => [
                    'Sopas & caldos',
                ],
            ],
            [
                'name'         => 'Acompanhamentos',
                'order'        => 3,
                'cuisine_types' => [
                    'Sopas & caldos',
                ],
            ],
            [
                'name'         => 'Bebidas',
                'order'        => 4,
                'cuisine_types' => [
                    'Sopas & caldos',
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

