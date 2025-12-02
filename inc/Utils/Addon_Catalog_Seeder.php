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
                    [ 'name' => 'Queijo extra', 'price' => '3.00', 'allow_quantity' => true, 'max_quantity' => 3 ],
                    [ 'name' => 'Bacon extra', 'price' => '4.00', 'allow_quantity' => true, 'max_quantity' => 3 ],
                    [ 'name' => 'Ovo frito', 'price' => '2.50', 'allow_quantity' => false ],
                    [ 'name' => 'Cebola caramelizada', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Cogumelos', 'price' => '3.50', 'allow_quantity' => false ],
                    [ 'name' => 'Abacaxi grelhado', 'price' => '2.50', 'allow_quantity' => false ],
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
                    [ 'name' => 'Molho especial da casa', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Barbecue', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Maionese temperada', 'price' => '1.00', 'allow_quantity' => false ],
                    [ 'name' => 'Mostarda e mel', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Pimenta', 'price' => '0.50', 'allow_quantity' => false ],
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
                    [ 'name' => 'Borda com catupiry', 'price' => '8.00', 'allow_quantity' => false ],
                    [ 'name' => 'Borda com cheddar', 'price' => '8.00', 'allow_quantity' => false ],
                    [ 'name' => 'Borda com chocolate', 'price' => '10.00', 'allow_quantity' => false ],
                    [ 'name' => 'Borda com doce de leite', 'price' => '10.00', 'allow_quantity' => false ],
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
                    [ 'name' => 'Queijo extra', 'price' => '5.00', 'allow_quantity' => false ],
                    [ 'name' => 'Bacon', 'price' => '6.00', 'allow_quantity' => false ],
                    [ 'name' => 'Azeitona', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Cebola', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Pimentão', 'price' => '2.50', 'allow_quantity' => false ],
                    [ 'name' => 'Champignon', 'price' => '4.00', 'allow_quantity' => false ],
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
                    [ 'name' => 'Calda de chocolate', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Calda de morango', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Calda de caramelo', 'price' => '2.00', 'allow_quantity' => false ],
                    [ 'name' => 'Granulado', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Confete', 'price' => '1.50', 'allow_quantity' => false ],
                    [ 'name' => 'Castanha', 'price' => '3.00', 'allow_quantity' => false ],
                    [ 'name' => 'Morango fresco', 'price' => '3.50', 'allow_quantity' => false ],
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

