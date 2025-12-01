<?php
/**
 * Cuisine_Seeder — cria termos padrão para a taxonomia vc_cuisine
 * @package VemComerCore
 */

namespace VC\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cuisine_Seeder {
    public static function seed(): void {
        if ( ! function_exists( 'register_taxonomy' ) || ! taxonomy_exists( 'vc_cuisine' ) ) {
            return;
        }

        // Evita rodar mais de uma vez em produção
        $already = get_option( 'vemcomer_cuisines_seeded' );
        if ( $already ) {
            return;
        }

        $groups = [
            'brasileira' => [
                'label' => '🇧🇷 Restaurantes brasileiros & regionais',
                'items' => [
                    'Restaurante brasileiro caseiro',
                    'Comida mineira',
                    'Comida baiana',
                    'Comida nordestina',
                    'Comida gaúcha',
                    'Comida amazônica',
                    'Comida paraense',
                    'Comida caiçara',
                    'Comida pantaneira',
                    'Feijoada',
                    'Self-service / por quilo',
                    'Marmitaria / Marmitex',
                    'Prato feito (PF)',
                    'Restaurante executivo',
                    'Restaurante contemporâneo',
                    'Restaurante de alta gastronomia / fine dining',
                    'Bistrô',
                    'Comida caseira',
                    'Restaurante tropical / praiano',
                ],
            ],
            'internacional' => [
                'label' => '🌍 Cozinhas internacionais',
                'items' => [
                    'Culinária italiana',
                    'Pizzaria tradicional',
                    'Pizzaria napolitana',
                    'Pizzaria rodízio',
                    'Pizzaria delivery',
                    'Massas & risotos',
                    'Culinária francesa',
                    'Culinária portuguesa',
                    'Culinária espanhola',
                    'Tapas',
                    'Culinária mexicana',
                    'Tex-Mex',
                    'Culinária norte-americana',
                    'Hamburgueria artesanal',
                    'Hamburgueria smash',
                    'Hot dog / Cachorro-quente',
                    'Steakhouse',
                    'Culinária argentina',
                    'Culinária uruguaia',
                    'Culinária peruana',
                    'Cevicheria',
                    'Culinária japonesa',
                    'Sushi bar',
                    'Temakeria',
                    'Restaurante de lámen / ramen',
                    'Izakaya',
                    'Culinária chinesa',
                    'Culinária tailandesa',
                    'Culinária vietnamita',
                    'Culinária coreana',
                    'Churrasco coreano (K-BBQ)',
                    'Culinária indiana',
                    'Culinária árabe',
                    'Culinária turca',
                    'Culinária libanesa',
                    'Culinária grega',
                    'Culinária mediterrânea',
                    'Culinária oriental (mista)',
                    'Culinária africana',
                    'Culinária marroquina',
                    'Culinária fusion',
                ],
            ],
            'especialidades' => [
                'label' => '🍽️ Especialidades / tipos de prato',
                'items' => [
                    'Churrascaria rodízio',
                    'Churrascaria à la carte',
                    'Espetinhos',
                    'Grelhados',
                    'Frutos do mar',
                    'Peixes',
                    'Galeteria',
                    'Frango assado',
                    'Frango frito estilo americano',
                    'Assados & rotisserie',
                    'Sopas & caldos',
                    'Comida de boteco',
                    'Petiscos e porções',
                    'Pastelaria',
                    'Esfiharia',
                    'Creperia salgada',
                    'Tapiocaria',
                    'Panquecaria',
                    'Omeleteria',
                    'Comida fit / saudável',
                    'Saladas & bowls',
                    'Poke',
                    'Açaíteria',
                    'Refeições congeladas',
                ],
            ],
            'lanches' => [
                'label' => '🌯 Lanches & fast-food',
                'items' => [
                    'Lanchonete',
                    'Sanduíches & baguetes',
                    'Wraps & tortillas',
                    'Salgados variados',
                    'Coxinha & frituras',
                    'Kebab / shawarma',
                    'Food truck',
                    'Quiosque de praia',
                    'Trailer de lanches',
                    'Refeição rápida / fast-food',
                ],
            ],
            'cafes' => [
                'label' => '☕ Cafés, padarias & doces',
                'items' => [
                    'Cafeteria',
                    'Coffee shop especializado',
                    'Padaria tradicional',
                    'Padaria gourmet',
                    'Confeitaria',
                    'Doceria',
                    'Brigaderia',
                    'Brownieria',
                    'Loja de donuts',
                    'Casa de bolos',
                    'Chocolateria',
                    'Bomboniere',
                    'Gelateria',
                    'Sorveteria',
                    'Yogurteria',
                    'Creperia doce',
                    'Waffle house',
                    'Casa de chá',
                ],
            ],
            'bares' => [
                'label' => '🍻 Bares, bebidas & noite',
                'items' => [
                    'Bar',
                    'Boteco',
                    'Gastrobar',
                    'Pub',
                    'Sports bar / Bar esportivo',
                    'Bar de vinhos / Wine bar',
                    'Cervejaria artesanal',
                    'Choperia',
                    'Adega de bebidas',
                    'Bar de drinks / Coquetelaria',
                    'Bar de caipirinha',
                    'Rooftop bar',
                    'Lounge bar',
                    'Karaokê bar',
                    'Beach club',
                    'Hookah / Narguilé bar',
                    'Balada / Night club',
                ],
            ],
            'saudavel' => [
                'label' => '🥦 Saudável, dietas & restrições',
                'items' => [
                    'Vegetariano',
                    'Vegano',
                    'Plant-based',
                    'Sem glúten',
                    'Sem lactose',
                    'Orgânico',
                    'Natural / saudável',
                    'Comida funcional',
                    'Low carb',
                    'Marmita fitness',
                ],
            ],
            'estilo' => [
                'label' => '🎯 Estilo, experiência & formato de serviço',
                'items' => [
                    'Restaurante familiar / kids friendly',
                    'Restaurante romântico',
                    'Restaurante temático',
                    'Restaurante com música ao vivo',
                    'Rodízio (geral)',
                    'Buffet livre',
                    'À la carte',
                    'Delivery only / Dark kitchen',
                    'Drive-thru',
                    'Take-away / para levar',
                    'Praça de alimentação / food court',
                ],
            ],
            'outros' => [
                'label' => '🛒 Outros (mercados & lojas de alimentos/bebidas)',
                'items' => [
                    'Mercado / mini mercado',
                    'Empório',
                    'Loja de produtos naturais',
                    'Açougue gourmet',
                    'Hortifruti',
                    'Peixaria',
                    'Loja de conveniência',
                    'Loja de vinhos e destilados',
                ],
            ],
        ];

        foreach ( $groups as $group_key => $group ) {
            $parent_term = null;

            // Cria um termo "pai" opcional para organizar no admin (ex.: grupo: Brasileira)
            $parent = wp_insert_term(
                $group['label'],
                'vc_cuisine',
                [
                    'slug' => sanitize_title( 'grupo-' . $group_key ),
                ]
            );

            if ( ! is_wp_error( $parent ) && isset( $parent['term_id'] ) ) {
                $parent_term = (int) $parent['term_id'];
            } else {
                // Tenta recuperar se já existir
                $existing_parent = get_term_by( 'slug', sanitize_title( 'grupo-' . $group_key ), 'vc_cuisine' );
                if ( $existing_parent && ! is_wp_error( $existing_parent ) ) {
                    $parent_term = (int) $existing_parent->term_id;
                }
            }

            foreach ( $group['items'] as $label ) {
                $slug = sanitize_title( $label );

                if ( term_exists( $slug, 'vc_cuisine' ) ) {
                    continue;
                }

                wp_insert_term(
                    $label,
                    'vc_cuisine',
                    [
                        'slug'   => $slug,
                        'parent' => $parent_term ?: 0,
                    ]
                );
            }
        }

        update_option( 'vemcomer_cuisines_seeded', 1 );
    }
}


