<?php
/**
 * Addon_Catalog_Seeder — Popula o catálogo de adicionais com grupos recomendados
 * Vincula grupos às categorias de restaurantes (vc_cuisine)
 * 
 * @package VemComerCore
 */

namespace VC\Utils;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Addon_Catalog_Seeder {
    /**
     * Popula o catálogo com grupos de adicionais comuns
     */
    public static function seed(): void {
        // Verificar se os CPTs existem
        if ( ! post_type_exists( 'vc_addon_group' ) || ! post_type_exists( 'vc_addon_item' ) ) {
            return;
        }

        // Verificar se já foi populado
        $existing = get_posts( [
            'post_type'      => 'vc_addon_group',
            'posts_per_page' => 1,
            'post_status'    => 'any',
        ] );

        if ( ! empty( $existing ) ) {
            return; // Já foi populado
        }

        $groups = self::get_groups_data();

        foreach ( $groups as $group_data ) {
            $group_id = wp_insert_post( [
                'post_type'    => 'vc_addon_group',
                'post_title'   => $group_data['name'],
                'post_content' => $group_data['description'] ?? '',
                'post_status'  => 'publish',
            ] );

            if ( is_wp_error( $group_id ) ) {
                continue;
            }

            // Salvar configurações do grupo
            update_post_meta( $group_id, '_vc_selection_type', $group_data['selection_type'] ?? 'multiple' );
            update_post_meta( $group_id, '_vc_min_select', $group_data['min_select'] ?? 0 );
            update_post_meta( $group_id, '_vc_max_select', $group_data['max_select'] ?? 0 );
            update_post_meta( $group_id, '_vc_is_required', $group_data['is_required'] ? '1' : '0' );
            update_post_meta( $group_id, '_vc_is_active', '1' );

            // Vincular às categorias
            if ( ! empty( $group_data['categories'] ) ) {
                $category_ids = self::get_category_ids_by_names( $group_data['categories'] );
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
        }
    }

    /**
     * Atualiza os itens dos grupos existentes (para adicionar itens aos grupos já criados)
     */
    public static function update_group_items(): void {
        if ( ! post_type_exists( 'vc_addon_group' ) || ! post_type_exists( 'vc_addon_item' ) ) {
            return;
        }

        $groups_data = self::get_groups_data();
        $first_5_groups = array_slice( $groups_data, 0, 5 );

        foreach ( $first_5_groups as $group_data ) {
            // Buscar grupo existente pelo nome
            $existing_groups = get_posts( [
                'post_type'      => 'vc_addon_group',
                'posts_per_page' => -1,
                'post_status'    => 'any',
            ] );

            $group_id = null;
            foreach ( $existing_groups as $group ) {
                if ( $group->post_title === $group_data['name'] ) {
                    $group_id = $group->ID;
                    break;
                }
            }

            if ( ! $group_id ) {
                continue;
            }

            // Verificar quais itens já existem
            $existing_items = get_posts( [
                'post_type'      => 'vc_addon_item',
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'meta_query'     => [
                    [
                        'key'   => '_vc_group_id',
                        'value' => $group_id,
                    ],
                ],
            ] );

            $existing_item_names = [];
            foreach ( $existing_items as $item ) {
                $existing_item_names[] = $item->post_title;
            }

            // Adicionar apenas itens que não existem
            if ( ! empty( $group_data['items'] ) ) {
                foreach ( $group_data['items'] as $item_data ) {
                    if ( ! in_array( $item_data['name'], $existing_item_names, true ) ) {
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
            }
        }
    }

    /**
     * Retorna os dados dos grupos de adicionais
     */
    private static function get_groups_data(): array {
        return [
            // Grupos para Hamburgueria
            [
                'name'           => 'Adicionais de Hambúrguer',
                'description'    => 'Carnes, queijos e complementos extras para seu hambúrguer',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Hamburgueria artesanal',
                    'Hamburgueria smash',
                    'Culinária norte-americana',
                    'Lanchonete',
                    'Food truck',
                ],
                'items'          => [
                    [ 'name' => 'Queijo extra', 'price' => '0.00', 'allow_quantity' => true, 'max_quantity' => 3 ],
                    [ 'name' => 'Bacon extra', 'price' => '0.00', 'allow_quantity' => true, 'max_quantity' => 3 ],
                    [ 'name' => 'Ovo frito', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Cebola caramelizada', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Cogumelos', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Abacaxi grelhado', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Alface', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Tomate', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Picles', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Cebola roxa', 'price' => '0.00', 'allow_quantity' => false ],
                ],
            ],
            [
                'name'           => 'Molhos para Hambúrguer',
                'description'    => 'Molhos especiais para acompanhar seu hambúrguer',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Hamburgueria artesanal',
                    'Hamburgueria smash',
                    'Lanchonete',
                    'Food truck',
                ],
                'items'          => [
                    [ 'name' => 'Molho especial da casa', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Barbecue', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Maionese temperada', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Mostarda e mel', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Molho picante', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Ketchup', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Mostarda', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Maionese', 'price' => '0.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Pizzaria
            [
                'name'           => 'Borda Recheada',
                'description'    => 'Escolha o recheio da borda da sua pizza',
                'selection_type' => 'single',
                'min_select'     => 0,
                'max_select'     => 1,
                'is_required'    => false,
                'categories'     => [
                    'Pizzaria tradicional',
                    'Pizzaria napolitana',
                    'Pizzaria rodízio',
                    'Pizzaria delivery',
                    'Culinária italiana',
                ],
                'items'          => [
                    [ 'name' => 'Borda com catupiry', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Borda com cheddar', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Borda com requeijão', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Borda com chocolate', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Borda com doce de leite', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Borda sem recheio', 'price' => '0.00', 'allow_quantity' => false ],
                ],
            ],
            [
                'name'           => 'Coberturas Extras para Pizza',
                'description'    => 'Adicione ingredientes extras à sua pizza',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Pizzaria tradicional',
                    'Pizzaria napolitana',
                    'Pizzaria rodízio',
                    'Pizzaria delivery',
                    'Culinária italiana',
                ],
                'items'          => [
                    [ 'name' => 'Queijo extra', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Bacon', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Azeitona', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Cebola', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Pimentão', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Champignon', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Tomate', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Orégano', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Azeite extra', 'price' => '0.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Sorveteria/Gelateria
            [
                'name'           => 'Coberturas para Sorvete',
                'description'    => 'Coberturas deliciosas para seu sorvete',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Sorveteria',
                    'Gelateria',
                    'Yogurteria',
                    'Açaíteria',
                    'Doceria',
                ],
                'items'          => [
                    [ 'name' => 'Calda de chocolate', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Calda de morango', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Calda de caramelo', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Granulado', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Confete', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Castanha', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Morango fresco', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Calda de chocolate branco', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Calda de frutas vermelhas', 'price' => '0.00', 'allow_quantity' => false ],
                ],
            ],
            [
                'name'           => 'Complementos para Sorvete',
                'description'    => 'Acompanhamentos para seu sorvete',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Sorveteria',
                    'Gelateria',
                    'Yogurteria',
                    'Açaíteria',
                ],
                'items'          => [
                    [ 'name' => 'Banana', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Leite condensado', 'price' => '2.50', 'allow_quantity' => false ],
                    [ 'name' => 'Paçoca', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Biscoito', 'price' => '1.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Cafeteria
            [
                'name'           => 'Adicionais para Café',
                'description'    => 'Personalize seu café',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Cafeteria',
                    'Coffee shop especializado',
                    'Padaria tradicional',
                    'Padaria gourmet',
                ],
                'items'          => [
                    [ 'name' => 'Leite extra', 'price' => '1.00', 'allow_quantity' => false ],
                    [ 'name' => 'Espuma de leite', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Chantilly', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Canela', 'price' => '0.50', 'allow_quantity' => false ],
                    [ 'name' => 'Chocolate em pó', 'price' => '1.00', 'allow_quantity' => false ],
                    [ 'name' => 'Açúcar', 'price' => '0.00', 'allow_quantity' => false ],
                ],
            ],
            [
                'name'           => 'Tamanhos de Bebida',
                'description'    => 'Escolha o tamanho da sua bebida',
                'selection_type' => 'single',
                'min_select'     => 1,
                'max_select'     => 1,
                'is_required'    => true,
                'categories'     => [
                    'Cafeteria',
                    'Coffee shop especializado',
                    'Bar',
                    'Boteco',
                    'Gastrobar',
                    'Hamburgueria artesanal',
                    'Pizzaria tradicional',
                ],
                'items'          => [
                    [ 'name' => 'Pequeno (200ml)', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Médio (300ml)', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Grande (400ml)', 'price' => '4.00', 'allow_quantity' => false ],
                    [ 'name' => 'Extra grande (500ml)', 'price' => '6.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Bares
            [
                'name'           => 'Petiscos Extras',
                'description'    => 'Acompanhamentos para suas bebidas',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Bar',
                    'Boteco',
                    'Gastrobar',
                    'Pub',
                    'Sports bar / Bar esportivo',
                ],
                'items'          => [
                    [ 'name' => 'Amendoim', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Azeitona', 'price' => '4.00', 'allow_quantity' => false ],
                    [ 'name' => 'Batata frita', 'price' => '8.00', 'allow_quantity' => false ],
                    [ 'name' => 'Torresmo', 'price' => '6.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Restaurantes Brasileiros
            [
                'name'           => 'Acompanhamentos Brasileiros',
                'description'    => 'Acompanhamentos típicos da culinária brasileira',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Restaurante brasileiro caseiro',
                    'Comida mineira',
                    'Comida baiana',
                    'Comida nordestina',
                    'Comida gaúcha',
                    'Feijoada',
                    'Self-service / por quilo',
                    'Marmitaria / Marmitex',
                    'Prato feito (PF)',
                    'Restaurante executivo',
                ],
                'items'          => [
                    [ 'name' => 'Farofa', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Vinagrete', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Couve refogada', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Torresmo', 'price' => '4.00', 'allow_quantity' => false ],
                    [ 'name' => 'Linguiça extra', 'price' => '5.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Cozinha Japonesa
            [
                'name'           => 'Adicionais para Sushi',
                'description'    => 'Complementos para seus sushis e temakis',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Culinária japonesa',
                    'Sushi bar',
                    'Temakeria',
                    'Restaurante de lámen / ramen',
                    'Izakaya',
                ],
                'items'          => [
                    [ 'name' => 'Wasabi extra', 'price' => '1.00', 'allow_quantity' => false ],
                    [ 'name' => 'Gengibre extra', 'price' => '1.00', 'allow_quantity' => false ],
                    [ 'name' => 'Molho shoyu', 'price' => '0.50', 'allow_quantity' => false ],
                    [ 'name' => 'Cream cheese', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Cebolinha', 'price' => '1.50', 'allow_quantity' => false ],
                ],
            ],
            [
                'name'           => 'Proteínas Extras para Sushi',
                'description'    => 'Adicione mais proteína ao seu prato',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Culinária japonesa',
                    'Sushi bar',
                    'Temakeria',
                ],
                'items'          => [
                    [ 'name' => 'Salmão extra', 'price' => '8.00', 'allow_quantity' => false ],
                    [ 'name' => 'Atum extra', 'price' => '8.00', 'allow_quantity' => false ],
                    [ 'name' => 'Camarão extra', 'price' => '7.00', 'allow_quantity' => false ],
                    [ 'name' => 'Kani extra', 'price' => '6.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Churrascaria
            [
                'name'           => 'Acompanhamentos para Churrasco',
                'description'    => 'Acompanhamentos perfeitos para seu churrasco',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Churrascaria rodízio',
                    'Churrascaria à la carte',
                    'Espetinhos',
                    'Grelhados',
                ],
                'items'          => [
                    [ 'name' => 'Farofa', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Vinagrete', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Pão de alho', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Mandioca frita', 'price' => '4.00', 'allow_quantity' => false ],
                    [ 'name' => 'Polenta frita', 'price' => '4.00', 'allow_quantity' => false ],
                ],
            ],
            [
                'name'           => 'Molhos para Churrasco',
                'description'    => 'Molhos especiais para carnes',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Churrascaria rodízio',
                    'Churrascaria à la carte',
                    'Espetinhos',
                    'Grelhados',
                    'Steakhouse',
                ],
                'items'          => [
                    [ 'name' => 'Molho chimichurri', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Molho barbecue', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Molho de mostarda', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Molho de pimenta', 'price' => '1.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Frutos do Mar
            [
                'name'           => 'Acompanhamentos para Frutos do Mar',
                'description'    => 'Acompanhamentos para pratos de peixe e frutos do mar',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Frutos do mar',
                    'Peixes',
                    'Cevicheria',
                    'Culinária peruana',
                ],
                'items'          => [
                    [ 'name' => 'Arroz branco', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Batata frita', 'price' => '4.00', 'allow_quantity' => false ],
                    [ 'name' => 'Salada verde', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Pirão', 'price' => '3.50', 'allow_quantity' => false ],
                ],
            ],
            [
                'name'           => 'Molhos para Peixe',
                'description'    => 'Molhos especiais para peixes',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Frutos do mar',
                    'Peixes',
                    'Cevicheria',
                ],
                'items'          => [
                    [ 'name' => 'Molho de alho', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Molho tártaro', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Molho de limão', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Azeite extra', 'price' => '1.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Massas
            [
                'name'           => 'Adicionais para Massas',
                'description'    => 'Complementos para suas massas e risotos',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Culinária italiana',
                    'Massas & risotos',
                    'Bistrô',
                ],
                'items'          => [
                    [ 'name' => 'Queijo parmesão ralado', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Bacon', 'price' => '4.00', 'allow_quantity' => false ],
                    [ 'name' => 'Cogumelos', 'price' => '3.50', 'allow_quantity' => false ],
                    [ 'name' => 'Azeitonas', 'price' => '2.50', 'allow_quantity' => false ],
                    [ 'name' => 'Creme de leite', 'price' => '2.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Comida Árabe/Libanesa
            [
                'name'           => 'Adicionais Árabes',
                'description'    => 'Complementos para pratos árabes e libaneses',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Culinária árabe',
                    'Culinária libanesa',
                    'Culinária turca',
                    'Kebab / shawarma',
                ],
                'items'          => [
                    [ 'name' => 'Tahine', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Homus', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Baba ganoush', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Picles', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Pão sírio', 'price' => '2.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Comida Mexicana
            [
                'name'           => 'Adicionais Mexicanos',
                'description'    => 'Complementos para pratos mexicanos',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Culinária mexicana',
                    'Tex-Mex',
                ],
                'items'          => [
                    [ 'name' => 'Guacamole', 'price' => '4.00', 'allow_quantity' => false ],
                    [ 'name' => 'Sour cream', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Pimenta jalapeño', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Queijo cheddar', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Nachos', 'price' => '3.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Saladas e Bowls
            [
                'name'           => 'Proteínas para Salada/Bowl',
                'description'    => 'Adicione proteína à sua salada ou bowl',
                'selection_type' => 'single',
                'min_select'     => 0,
                'max_select'     => 1,
                'is_required'    => false,
                'categories'     => [
                    'Comida fit / saudável',
                    'Saladas & bowls',
                    'Poke',
                    'Vegetariano',
                    'Vegano',
                ],
                'items'          => [
                    [ 'name' => 'Frango grelhado', 'price' => '6.00', 'allow_quantity' => false ],
                    [ 'name' => 'Salmão grelhado', 'price' => '8.00', 'allow_quantity' => false ],
                    [ 'name' => 'Atum', 'price' => '7.00', 'allow_quantity' => false ],
                    [ 'name' => 'Ovo cozido', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Grão-de-bico', 'price' => '3.00', 'allow_quantity' => false ],
                ],
            ],
            [
                'name'           => 'Extras para Salada/Bowl',
                'description'    => 'Complementos para sua salada ou bowl',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Comida fit / saudável',
                    'Saladas & bowls',
                    'Poke',
                ],
                'items'          => [
                    [ 'name' => 'Abacate', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Queijo feta', 'price' => '3.50', 'allow_quantity' => false ],
                    [ 'name' => 'Nozes', 'price' => '2.50', 'allow_quantity' => false ],
                    [ 'name' => 'Sementes', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Croutons', 'price' => '1.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Açaí
            [
                'name'           => 'Complementos para Açaí',
                'description'    => 'Acompanhamentos para seu açaí',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Açaíteria',
                ],
                'items'          => [
                    [ 'name' => 'Banana', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Morango', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Granola', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Leite condensado', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Paçoca', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Castanha', 'price' => '2.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Doces e Sobremesas
            [
                'name'           => 'Coberturas para Doces',
                'description'    => 'Coberturas para seus doces e sobremesas',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Confeitaria',
                    'Doceria',
                    'Brigaderia',
                    'Brownieria',
                    'Casa de bolos',
                    'Chocolateria',
                ],
                'items'          => [
                    [ 'name' => 'Calda de chocolate', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Calda de caramelo', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Chantilly', 'price' => '2.50', 'allow_quantity' => false ],
                    [ 'name' => 'Morango', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Granulado', 'price' => '1.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Hot Dog
            [
                'name'           => 'Adicionais para Hot Dog',
                'description'    => 'Complementos para seu cachorro-quente',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Hot dog / Cachorro-quente',
                    'Lanchonete',
                    'Food truck',
                ],
                'items'          => [
                    [ 'name' => 'Bacon', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Queijo ralado', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Ovo de codorna', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Batata palha', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Vinagrete', 'price' => '1.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Pastel
            [
                'name'           => 'Molhos para Pastel',
                'description'    => 'Molhos para acompanhar seus pastéis',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Pastelaria',
                ],
                'items'          => [
                    [ 'name' => 'Molho de pimenta', 'price' => '0.50', 'allow_quantity' => false ],
                    [ 'name' => 'Molho de mostarda', 'price' => '0.50', 'allow_quantity' => false ],
                    [ 'name' => 'Vinagrete', 'price' => '1.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Esfiha
            [
                'name'           => 'Adicionais para Esfiha',
                'description'    => 'Complementos para suas esfihas',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Esfiharia',
                ],
                'items'          => [
                    [ 'name' => 'Limão', 'price' => '0.50', 'allow_quantity' => false ],
                    [ 'name' => 'Pimenta', 'price' => '0.50', 'allow_quantity' => false ],
                    [ 'name' => 'Azeitona', 'price' => '1.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Tapioca
            [
                'name'           => 'Adicionais para Tapioca',
                'description'    => 'Complementos para sua tapioca',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Tapiocaria',
                ],
                'items'          => [
                    [ 'name' => 'Queijo extra', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Coco ralado', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Leite condensado', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Banana', 'price' => '1.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Bebidas (mais completo)
            [
                'name'           => 'Tipos de Bebida',
                'description'    => 'Escolha o tipo da sua bebida',
                'selection_type' => 'single',
                'min_select'     => 0,
                'max_select'     => 1,
                'is_required'    => false,
                'categories'     => [
                    'Bar',
                    'Boteco',
                    'Gastrobar',
                    'Pub',
                    'Cervejaria artesanal',
                    'Choperia',
                ],
                'items'          => [
                    [ 'name' => 'Cerveja (lata)', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Cerveja (long neck)', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Chopp', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Refrigerante', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Suco', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Água', 'price' => '0.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Comida Indiana
            [
                'name'           => 'Adicionais Indianos',
                'description'    => 'Complementos para pratos indianos',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Culinária indiana',
                ],
                'items'          => [
                    [ 'name' => 'Raita (iogurte com pepino)', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Chutney de manga', 'price' => '2.50', 'allow_quantity' => false ],
                    [ 'name' => 'Chutney de hortelã', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Pão naan', 'price' => '4.00', 'allow_quantity' => false ],
                    [ 'name' => 'Arroz basmati', 'price' => '3.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Comida Tailandesa
            [
                'name'           => 'Adicionais Tailandeses',
                'description'    => 'Complementos para pratos tailandeses',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Culinária tailandesa',
                ],
                'items'          => [
                    [ 'name' => 'Molho de pimenta tailandês', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Amendoim torrado', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Broto de feijão', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Coentro fresco', 'price' => '1.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Comida Vietnamita
            [
                'name'           => 'Adicionais Vietnamitas',
                'description'    => 'Complementos para pratos vietnamitas',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Culinária vietnamita',
                ],
                'items'          => [
                    [ 'name' => 'Molho hoisin', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Molho de pimenta', 'price' => '1.00', 'allow_quantity' => false ],
                    [ 'name' => 'Broto de feijão', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Hortelã', 'price' => '1.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Comida Coreana
            [
                'name'           => 'Adicionais Coreanos',
                'description'    => 'Complementos para pratos coreanos',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Culinária coreana',
                    'Churrasco coreano (K-BBQ)',
                ],
                'items'          => [
                    [ 'name' => 'Kimchi', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Alface para wrap', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Molho gochujang', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Alho assado', 'price' => '1.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Comida Chinesa
            [
                'name'           => 'Adicionais Chineses',
                'description'    => 'Complementos para pratos chineses',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Culinária chinesa',
                ],
                'items'          => [
                    [ 'name' => 'Molho agridoce', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Molho de soja', 'price' => '0.50', 'allow_quantity' => false ],
                    [ 'name' => 'Biscoito da sorte', 'price' => '1.00', 'allow_quantity' => false ],
                    [ 'name' => 'Arroz frito', 'price' => '4.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Comida Grega
            [
                'name'           => 'Adicionais Gregos',
                'description'    => 'Complementos para pratos gregos',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Culinária grega',
                    'Culinária mediterrânea',
                ],
                'items'          => [
                    [ 'name' => 'Tzatziki', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Azeitonas kalamata', 'price' => '3.50', 'allow_quantity' => false ],
                    [ 'name' => 'Queijo feta', 'price' => '4.00', 'allow_quantity' => false ],
                    [ 'name' => 'Pão pita', 'price' => '2.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Comida Francesa
            [
                'name'           => 'Adicionais Franceses',
                'description'    => 'Complementos para pratos franceses',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Culinária francesa',
                    'Bistrô',
                ],
                'items'          => [
                    [ 'name' => 'Molho bearnês', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Molho holandês', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Batata rösti', 'price' => '4.00', 'allow_quantity' => false ],
                    [ 'name' => 'Pão baguette', 'price' => '2.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Comida Portuguesa
            [
                'name'           => 'Adicionais Portugueses',
                'description'    => 'Complementos para pratos portugueses',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Culinária portuguesa',
                ],
                'items'          => [
                    [ 'name' => 'Azeitonas', 'price' => '2.50', 'allow_quantity' => false ],
                    [ 'name' => 'Pão português', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Azeite extra virgem', 'price' => '1.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Tapas
            [
                'name'           => 'Adicionais para Tapas',
                'description'    => 'Complementos para suas tapas',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Tapas',
                    'Culinária espanhola',
                ],
                'items'          => [
                    [ 'name' => 'Azeitonas', 'price' => '2.50', 'allow_quantity' => false ],
                    [ 'name' => 'Pão torrado', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Azeite', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Alho', 'price' => '1.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Cevicheria
            [
                'name'           => 'Adicionais para Ceviche',
                'description'    => 'Complementos para seu ceviche',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Cevicheria',
                    'Culinária peruana',
                ],
                'items'          => [
                    [ 'name' => 'Batata doce', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Milho', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Cebola roxa', 'price' => '1.00', 'allow_quantity' => false ],
                    [ 'name' => 'Coentro', 'price' => '1.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Galeteria
            [
                'name'           => 'Acompanhamentos para Galeto',
                'description'    => 'Acompanhamentos para seu galeto',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Galeteria',
                    'Frango assado',
                    'Frango frito estilo americano',
                ],
                'items'          => [
                    [ 'name' => 'Batata frita', 'price' => '4.00', 'allow_quantity' => false ],
                    [ 'name' => 'Arroz', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Salada', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Farofa', 'price' => '2.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Sopas e Caldos
            [
                'name'           => 'Adicionais para Sopa/Caldo',
                'description'    => 'Complementos para suas sopas e caldos',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Sopas & caldos',
                ],
                'items'          => [
                    [ 'name' => 'Croutons', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Queijo ralado', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Creme de leite', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Pão', 'price' => '2.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Comida de Boteco
            [
                'name'           => 'Petiscos de Boteco',
                'description'    => 'Petiscos para acompanhar suas bebidas',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Comida de boteco',
                    'Petiscos e porções',
                    'Boteco',
                ],
                'items'          => [
                    [ 'name' => 'Amendoim', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Azeitona', 'price' => '4.00', 'allow_quantity' => false ],
                    [ 'name' => 'Torresmo', 'price' => '6.00', 'allow_quantity' => false ],
                    [ 'name' => 'Batata frita', 'price' => '8.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Creperia
            [
                'name'           => 'Adicionais para Crepe',
                'description'    => 'Complementos para seus crepes',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Creperia salgada',
                    'Creperia doce',
                ],
                'items'          => [
                    [ 'name' => 'Chantilly', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Calda de chocolate', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Sorvete', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Queijo extra', 'price' => '2.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Panquecaria
            [
                'name'           => 'Adicionais para Panqueca',
                'description'    => 'Complementos para suas panquecas',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Panquecaria',
                ],
                'items'          => [
                    [ 'name' => 'Queijo extra', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Bacon', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Ovo', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Calda de mel', 'price' => '1.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Omeleteria
            [
                'name'           => 'Adicionais para Omelete',
                'description'    => 'Complementos para seu omelete',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Omeleteria',
                ],
                'items'          => [
                    [ 'name' => 'Queijo extra', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Bacon', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Cogumelos', 'price' => '2.50', 'allow_quantity' => false ],
                    [ 'name' => 'Cebola', 'price' => '1.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Sanduíches
            [
                'name'           => 'Adicionais para Sanduíche',
                'description'    => 'Complementos para seus sanduíches',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Sanduíches & baguetes',
                    'Lanchonete',
                ],
                'items'          => [
                    [ 'name' => 'Queijo extra', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Bacon', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Ovo', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Batata palha', 'price' => '1.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Wraps
            [
                'name'           => 'Adicionais para Wrap',
                'description'    => 'Complementos para seus wraps',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Wraps & tortillas',
                ],
                'items'          => [
                    [ 'name' => 'Guacamole', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Sour cream', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Queijo extra', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Pimenta jalapeño', 'price' => '1.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Salgados
            [
                'name'           => 'Molhos para Salgados',
                'description'    => 'Molhos para acompanhar seus salgados',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Salgados variados',
                    'Coxinha & frituras',
                ],
                'items'          => [
                    [ 'name' => 'Molho de pimenta', 'price' => '0.50', 'allow_quantity' => false ],
                    [ 'name' => 'Ketchup', 'price' => '1.00', 'allow_quantity' => false ],
                    [ 'name' => 'Mostarda', 'price' => '1.00', 'allow_quantity' => false ],
                    [ 'name' => 'Maionese', 'price' => '1.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Padaria (Doces)
            [
                'name'           => 'Coberturas para Doces de Padaria',
                'description'    => 'Coberturas para doces e bolos',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Padaria tradicional',
                    'Padaria gourmet',
                    'Confeitaria',
                    'Casa de bolos',
                ],
                'items'          => [
                    [ 'name' => 'Calda de chocolate', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Chantilly', 'price' => '2.50', 'allow_quantity' => false ],
                    [ 'name' => 'Morango', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Granulado', 'price' => '1.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Rooftop Bar / Lounge
            [
                'name'           => 'Acompanhamentos Premium',
                'description'    => 'Acompanhamentos especiais para drinks',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Rooftop bar',
                    'Lounge bar',
                    'Bar de vinhos / Wine bar',
                ],
                'items'          => [
                    [ 'name' => 'Queijos especiais', 'price' => '12.00', 'allow_quantity' => false ],
                    [ 'name' => 'Azeitonas premium', 'price' => '6.00', 'allow_quantity' => false ],
                    [ 'name' => 'Castanhas', 'price' => '8.00', 'allow_quantity' => false ],
                    [ 'name' => 'Frutos secos', 'price' => '7.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Comida Africana/Marroquina
            [
                'name'           => 'Adicionais Africanos',
                'description'    => 'Complementos para pratos africanos e marroquinos',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Culinária africana',
                    'Culinária marroquina',
                ],
                'items'          => [
                    [ 'name' => 'Couscous', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Harissa (molho picante)', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Pão pita', 'price' => '2.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Comida Fusion
            [
                'name'           => 'Adicionais Fusion',
                'description'    => 'Complementos para pratos fusion',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Culinária fusion',
                    'Culinária oriental (mista)',
                ],
                'items'          => [
                    [ 'name' => 'Molho especial da casa', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Vegetais em conserva', 'price' => '2.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Refeições Congeladas
            [
                'name'           => 'Acompanhamentos Rápidos',
                'description'    => 'Acompanhamentos para refeições congeladas',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Refeições congeladas',
                ],
                'items'          => [
                    [ 'name' => 'Arroz', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Feijão', 'price' => '2.50', 'allow_quantity' => false ],
                    [ 'name' => 'Salada', 'price' => '3.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Marmita Fitness
            [
                'name'           => 'Extras para Marmita Fitness',
                'description'    => 'Complementos para sua marmita fitness',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Marmita fitness',
                    'Comida fit / saudável',
                ],
                'items'          => [
                    [ 'name' => 'Proteína extra', 'price' => '5.00', 'allow_quantity' => false ],
                    [ 'name' => 'Abacate', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Ovo cozido', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Sementes', 'price' => '2.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Comida Regional Brasileira
            [
                'name'           => 'Acompanhamentos Regionais',
                'description'    => 'Acompanhamentos típicos das regiões brasileiras',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Comida amazônica',
                    'Comida paraense',
                    'Comida caiçara',
                    'Comida pantaneira',
                    'Restaurante tropical / praiano',
                ],
                'items'          => [
                    [ 'name' => 'Farofa', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Pirão', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Vinagrete', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Arroz', 'price' => '2.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Marmitaria
            [
                'name'           => 'Extras para Marmita',
                'description'    => 'Complementos para sua marmita',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Marmitaria / Marmitex',
                ],
                'items'          => [
                    [ 'name' => 'Arroz extra', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Feijão extra', 'price' => '2.50', 'allow_quantity' => false ],
                    [ 'name' => 'Salada extra', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Proteína extra', 'price' => '5.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Restaurantes Fine Dining
            [
                'name'           => 'Acompanhamentos Premium',
                'description'    => 'Acompanhamentos especiais para pratos gourmet',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Restaurante contemporâneo',
                    'Restaurante de alta gastronomia / fine dining',
                ],
                'items'          => [
                    [ 'name' => 'Molho especial do chef', 'price' => '4.00', 'allow_quantity' => false ],
                    [ 'name' => 'Vegetais em conserva', 'price' => '3.50', 'allow_quantity' => false ],
                    [ 'name' => 'Pão artesanal', 'price' => '3.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Comida Caseira
            [
                'name'           => 'Acompanhamentos Caseiros',
                'description'    => 'Acompanhamentos para comida caseira',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Comida caseira',
                ],
                'items'          => [
                    [ 'name' => 'Arroz', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Feijão', 'price' => '2.50', 'allow_quantity' => false ],
                    [ 'name' => 'Farofa', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Salada', 'price' => '3.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Culinária Argentina/Uruguaia
            [
                'name'           => 'Adicionais Argentinos/Uruguaios',
                'description'    => 'Complementos para pratos argentinos e uruguaios',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Culinária argentina',
                    'Culinária uruguaia',
                    'Steakhouse',
                ],
                'items'          => [
                    [ 'name' => 'Chimichurri', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Provoleta', 'price' => '6.00', 'allow_quantity' => false ],
                    [ 'name' => 'Batata frita', 'price' => '4.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Izakaya / Lámen
            [
                'name'           => 'Adicionais para Lámen/Izakaya',
                'description'    => 'Complementos para lámen e pratos de izakaya',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Izakaya',
                    'Restaurante de lámen / ramen',
                ],
                'items'          => [
                    [ 'name' => 'Ovo cozido (ajitama)', 'price' => '2.50', 'allow_quantity' => false ],
                    [ 'name' => 'Naruto (peixe)', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Bambu em conserva', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Alho frito', 'price' => '1.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Culinária Turca/Libanesa (expandir)
            [
                'name'           => 'Adicionais Turcos',
                'description'    => 'Complementos para pratos turcos',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Culinária turca',
                    'Culinária libanesa',
                ],
                'items'          => [
                    [ 'name' => 'Tahine', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Homus', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Baba ganoush', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Pão pita', 'price' => '2.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Espetinhos/Grelhados
            [
                'name'           => 'Acompanhamentos para Espetinhos',
                'description'    => 'Acompanhamentos para espetinhos e grelhados',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Espetinhos',
                    'Grelhados',
                    'Assados & rotisserie',
                ],
                'items'          => [
                    [ 'name' => 'Farofa', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Vinagrete', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Pão de alho', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Molho de pimenta', 'price' => '1.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Kebab
            [
                'name'           => 'Adicionais para Kebab',
                'description'    => 'Complementos para seu kebab ou shawarma',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Kebab / shawarma',
                ],
                'items'          => [
                    [ 'name' => 'Molho de alho', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Molho picante', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Vegetais frescos', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Batata frita', 'price' => '4.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Food Truck / Quiosque / Trailer
            [
                'name'           => 'Extras para Lanche Rápido',
                'description'    => 'Complementos para lanches rápidos',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Quiosque de praia',
                    'Trailer de lanches',
                    'Refeição rápida / fast-food',
                ],
                'items'          => [
                    [ 'name' => 'Batata frita', 'price' => '4.00', 'allow_quantity' => false ],
                    [ 'name' => 'Refrigerante', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Molhos', 'price' => '1.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Doces Específicos
            [
                'name'           => 'Coberturas para Doces Especiais',
                'description'    => 'Coberturas para doces e sobremesas especiais',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Brigaderia',
                    'Brownieria',
                    'Loja de donuts',
                    'Bomboniere',
                ],
                'items'          => [
                    [ 'name' => 'Calda de chocolate', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Chantilly', 'price' => '2.50', 'allow_quantity' => false ],
                    [ 'name' => 'Granulado', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Morango', 'price' => '3.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Casa de Chá / Waffle
            [
                'name'           => 'Adicionais para Chá/Waffle',
                'description'    => 'Complementos para chás e waffles',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Casa de chá',
                    'Waffle house',
                ],
                'items'          => [
                    [ 'name' => 'Mel', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Limão', 'price' => '0.50', 'allow_quantity' => false ],
                    [ 'name' => 'Calda de chocolate', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Sorvete', 'price' => '3.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Bares Específicos
            [
                'name'           => 'Petiscos para Bares Especiais',
                'description'    => 'Petiscos para bares temáticos',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Sports bar / Bar esportivo',
                    'Karaokê bar',
                    'Beach club',
                    'Balada / Night club',
                ],
                'items'          => [
                    [ 'name' => 'Batata frita', 'price' => '8.00', 'allow_quantity' => false ],
                    [ 'name' => 'Amendoim', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Azeitona', 'price' => '4.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Coquetelaria / Caipirinha
            [
                'name'           => 'Extras para Drinks',
                'description'    => 'Complementos para seus drinks e coquetéis',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Bar de drinks / Coquetelaria',
                    'Bar de caipirinha',
                    'Hookah / Narguilé bar',
                ],
                'items'          => [
                    [ 'name' => 'Água tônica', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Refrigerante', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Energético', 'price' => '4.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Adega / Loja de Vinhos
            [
                'name'           => 'Acompanhamentos para Vinhos',
                'description'    => 'Acompanhamentos para vinhos e destilados',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Adega de bebidas',
                    'Loja de vinhos e destilados',
                ],
                'items'          => [
                    [ 'name' => 'Queijos especiais', 'price' => '12.00', 'allow_quantity' => false ],
                    [ 'name' => 'Azeitonas premium', 'price' => '6.00', 'allow_quantity' => false ],
                    [ 'name' => 'Castanhas', 'price' => '8.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Dietas Específicas
            [
                'name'           => 'Substituições para Dietas',
                'description'    => 'Opções de substituição para dietas especiais',
                'selection_type' => 'single',
                'min_select'     => 0,
                'max_select'     => 1,
                'is_required'    => false,
                'categories'     => [
                    'Vegetariano',
                    'Vegano',
                    'Plant-based',
                    'Sem glúten',
                    'Sem lactose',
                    'Orgânico',
                    'Natural / saudável',
                    'Comida funcional',
                    'Low carb',
                ],
                'items'          => [
                    [ 'name' => 'Proteína vegetal', 'price' => '4.00', 'allow_quantity' => false ],
                    [ 'name' => 'Queijo vegano', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Pão sem glúten', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Leite vegetal', 'price' => '2.50', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Restaurantes Temáticos
            [
                'name'           => 'Extras para Experiência',
                'description'    => 'Extras para melhorar sua experiência',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Restaurante familiar / kids friendly',
                    'Restaurante romântico',
                    'Restaurante temático',
                    'Restaurante com música ao vivo',
                ],
                'items'          => [
                    [ 'name' => 'Sobremesa especial', 'price' => '8.00', 'allow_quantity' => false ],
                    [ 'name' => 'Bebida especial', 'price' => '6.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Rodízio / Buffet / À la carte
            [
                'name'           => 'Extras para Rodízio/Buffet',
                'description'    => 'Extras para rodízio e buffet',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Rodízio (geral)',
                    'Buffet livre',
                    'À la carte',
                ],
                'items'          => [
                    [ 'name' => 'Bebida inclusa', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Sobremesa inclusa', 'price' => '0.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Delivery / Dark Kitchen
            [
                'name'           => 'Extras para Delivery',
                'description'    => 'Extras para pedidos de delivery',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Delivery only / Dark kitchen',
                    'Drive-thru',
                    'Take-away / para levar',
                ],
                'items'          => [
                    [ 'name' => 'Talheres descartáveis', 'price' => '0.00', 'allow_quantity' => false ],
                    [ 'name' => 'Molhos extras', 'price' => '1.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Food Court
            [
                'name'           => 'Extras para Food Court',
                'description'    => 'Extras para praça de alimentação',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Praça de alimentação / food court',
                ],
                'items'          => [
                    [ 'name' => 'Bebida', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Sobremesa', 'price' => '5.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos para Mercados / Lojas
            [
                'name'           => 'Produtos Complementares',
                'description'    => 'Produtos complementares para sua compra',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [
                    'Mercado / mini mercado',
                    'Empório',
                    'Loja de produtos naturais',
                    'Açougue gourmet',
                    'Hortifruti',
                    'Peixaria',
                    'Loja de conveniência',
                ],
                'items'          => [
                    [ 'name' => 'Embalagem especial', 'price' => '1.00', 'allow_quantity' => false ],
                    [ 'name' => 'Produto relacionado', 'price' => '5.00', 'allow_quantity' => false ],
                ],
            ],

            // Grupos genéricos (para qualquer tipo)
            [
                'name'           => 'Molhos Extras',
                'description'    => 'Molhos para acompanhar seus pratos',
                'selection_type' => 'multiple',
                'min_select'     => 0,
                'max_select'     => 0,
                'is_required'    => false,
                'categories'     => [], // Vazio = disponível para todos
                'items'          => [
                    [ 'name' => 'Ketchup', 'price' => '1.00', 'allow_quantity' => false ],
                    [ 'name' => 'Mostarda', 'price' => '1.00', 'allow_quantity' => false ],
                    [ 'name' => 'Maionese', 'price' => '1.00', 'allow_quantity' => false ],
                    [ 'name' => 'Molho picante', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Molho agridoce', 'price' => '1.50', 'allow_quantity' => false ],
                ],
            ],
        ];
    }

    /**
     * Busca IDs de categorias pelos nomes
     */
    private static function get_category_ids_by_names( array $category_names ): array {
        $ids = [];
        foreach ( $category_names as $name ) {
            $term = get_term_by( 'name', $name, 'vc_cuisine' );
            if ( $term && ! is_wp_error( $term ) ) {
                $ids[] = $term->term_id;
            }
        }
        return $ids;
    }
}

