<?php
/**
 * Cuisine_Helper — Helper para gerenciar arquétipos de restaurante
 * Mapeia vc_cuisine (tipos de restaurante) para arquétipos (blueprints de cardápio)
 * 
 * @package VemComerCore
 */

namespace VC\Utils;

use WP_Term;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cuisine_Helper {
    /**
     * Mapeamento completo: slug de vc_cuisine => arquétipo
     * Usa slugs, não nomes, para evitar problemas com acentos/renomeações
     */
    private static function get_slug_to_archetype_map(): array {
        return [
            // hamburgueria
            'hamburgueria-artesanal' => 'hamburgueria',
            'hamburgueria-smash' => 'hamburgueria',
            'lanchonete' => 'hamburgueria',
            'food-truck' => 'hamburgueria',
            'culinaria-norte-americana' => 'hamburgueria',

            // pizzaria
            'pizzaria-tradicional' => 'pizzaria',
            'pizzaria-napolitana' => 'pizzaria',
            'pizzaria-rodizio' => 'pizzaria',
            'pizzaria-delivery' => 'pizzaria',
            'culinaria-italiana' => 'pizzaria', // quando não for massas

            // massas_risotos
            'massas-risotos' => 'massas_risotos',

            // japones
            'culinaria-japonesa' => 'japones',
            'sushi-bar' => 'japones',
            'temakeria' => 'japones',
            'restaurante-de-lamen-ramen' => 'japones',
            'izakaya' => 'japones',

            // chines
            'culinaria-chinesa' => 'chines',

            // oriental_misto
            'culinaria-tailandesa' => 'oriental_misto',
            'culinaria-vietnamita' => 'oriental_misto',
            'culinaria-coreana' => 'oriental_misto',
            'churrasco-coreano-k-bbq' => 'oriental_misto',
            'culinaria-oriental-mista' => 'oriental_misto',
            'culinaria-indiana' => 'oriental_misto',
            'culinaria-africana' => 'oriental_misto',
            'culinaria-marroquina' => 'oriental_misto',
            'culinaria-fusion' => 'oriental_misto',

            // arabe_mediterraneo
            'culinaria-arabe' => 'arabe_mediterraneo',
            'culinaria-turca' => 'arabe_mediterraneo',
            'culinaria-libanesa' => 'arabe_mediterraneo',
            'culinaria-grega' => 'arabe_mediterraneo',
            'culinaria-mediterranea' => 'arabe_mediterraneo',
            'esfiharia' => 'arabe_mediterraneo',
            'kebab-shawarma' => 'arabe_mediterraneo', // CORRIGIDO: estava em latino_americano

            // mexicano_texmex
            'culinaria-mexicana' => 'mexicano_texmex',
            'tex-mex' => 'mexicano_texmex',

            // latino_americano
            'culinaria-argentina' => 'latino_americano',
            'culinaria-uruguaia' => 'latino_americano',
            'culinaria-peruana' => 'latino_americano',
            'cevicheria' => 'latino_americano', // também pode usar frutos_mar_peixes

            // europeu_ocidental
            'culinaria-francesa' => 'europeu_ocidental',
            'culinaria-portuguesa' => 'europeu_ocidental',
            'culinaria-espanhola' => 'europeu_ocidental',
            'tapas' => 'europeu_ocidental',
            'restaurante-de-alta-gastronomia-fine-dining' => 'europeu_ocidental',
            'restaurante-contemporaneo' => 'europeu_ocidental',
            'bistro' => 'europeu_ocidental',

            // brasileiro_caseiro
            'restaurante-brasileiro-caseiro' => 'brasileiro_caseiro',
            'comida-mineira' => 'brasileiro_caseiro',
            'comida-baiana' => 'brasileiro_caseiro',
            'comida-nordestina' => 'brasileiro_caseiro',
            'comida-gaucha' => 'brasileiro_caseiro',
            'comida-amazonica' => 'brasileiro_caseiro',
            'comida-paraense' => 'brasileiro_caseiro',
            'comida-caicara' => 'brasileiro_caseiro',
            'comida-pantaneira' => 'brasileiro_caseiro',
            'comida-caseira' => 'brasileiro_caseiro',
            'feijoada' => 'brasileiro_caseiro',
            'sopas-caldos' => 'brasileiro_caseiro', // ANEXADO
            'restaurante-tropical-praiano' => 'brasileiro_caseiro',

            // marmitaria_pf
            'marmitaria-marmitex' => 'marmitaria_pf',
            'prato-feito-pf' => 'marmitaria_pf',
            'self-service-por-quilo' => 'marmitaria_pf',
            'restaurante-executivo' => 'marmitaria_pf',
            'marmita-fitness' => 'marmitaria_pf',
            'refeicoes-congeladas' => 'marmitaria_pf', // com flag _vc_service = 'frozen'

            // churrascaria
            'churrascaria-rodizio' => 'churrascaria',
            'churrascaria-a-la-carte' => 'churrascaria',
            'steakhouse' => 'churrascaria',

            // grelhados_espetinhos
            'espetinhos' => 'grelhados_espetinhos',
            'grelhados' => 'grelhados_espetinhos',
            'assados-rotisserie' => 'grelhados_espetinhos',

            // frango_galeteria
            'galeteria' => 'frango_galeteria',
            'frango-assado' => 'frango_galeteria',
            'frango-frito-estilo-americano' => 'frango_galeteria',

            // frutos_mar_peixes
            'frutos-do-mar' => 'frutos_mar_peixes',
            'peixes' => 'frutos_mar_peixes',
            'peixaria' => 'frutos_mar_peixes',

            // bar_boteco
            'bar' => 'bar_boteco',
            'boteco' => 'bar_boteco',
            'gastrobar' => 'bar_boteco',
            'pub' => 'bar_boteco',
            'sports-bar-bar-esportivo' => 'bar_boteco',
            'bar-de-vinhos-wine-bar' => 'bar_boteco',
            'cervejaria-artesanal' => 'bar_boteco',
            'choperia' => 'bar_boteco',
            'adega-de-bebidas' => 'bar_boteco',
            'bar-de-drinks-coquetelaria' => 'bar_boteco',
            'bar-de-caipirinha' => 'bar_boteco',
            'rooftop-bar' => 'bar_boteco',
            'lounge-bar' => 'bar_boteco',
            'karaoke-bar' => 'bar_boteco',
            'beach-club' => 'bar_boteco',
            'hookah-narguile-bar' => 'bar_boteco',
            'balada-night-club' => 'bar_boteco',
            'comida-de-boteco' => 'bar_boteco',
            'petiscos-e-porcoes' => 'bar_boteco',

            // cafeteria_padaria
            'cafeteria' => 'cafeteria_padaria',
            'coffee-shop-especializado' => 'cafeteria_padaria',
            'padaria-tradicional' => 'cafeteria_padaria',
            'padaria-gourmet' => 'cafeteria_padaria',
            'casa-de-cha' => 'cafeteria_padaria',

            // sorveteria_acaiteria
            'sorveteria' => 'sorveteria_acaiteria',
            'gelateria' => 'sorveteria_acaiteria',
            'acaieteria' => 'sorveteria_acaiteria',
            'yogurteria' => 'sorveteria_acaiteria',

            // doces_sobremesas
            'confeitaria' => 'doces_sobremesas',
            'doceria' => 'doces_sobremesas',
            'brigaderia' => 'doces_sobremesas',
            'brownieria' => 'doces_sobremesas',
            'loja-de-donuts' => 'doces_sobremesas',
            'casa-de-bolos' => 'doces_sobremesas',
            'chocolateria' => 'doces_sobremesas',
            'bomboniere' => 'doces_sobremesas',
            'creperia-doce' => 'doces_sobremesas',
            'waffle-house' => 'doces_sobremesas',

            // lanches_fastfood
            'hot-dog-cachorro-quente' => 'lanches_fastfood',
            'sanduiches-baguetes' => 'lanches_fastfood',
            'wraps-tortillas' => 'lanches_fastfood',
            'salgados-variados' => 'lanches_fastfood',
            'coxinha-frituras' => 'lanches_fastfood',
            'pastelaria' => 'lanches_fastfood',
            'creperia-salgada' => 'lanches_fastfood',
            'tapiocaria' => 'lanches_fastfood',
            'panquecaria' => 'lanches_fastfood',
            'omeleteria' => 'lanches_fastfood',
            'quiosque-de-praia' => 'lanches_fastfood',
            'trailer-de-lanches' => 'lanches_fastfood',
            'refeicao-rapida-fast-food' => 'lanches_fastfood',

            // poke
            'poke' => 'poke',

            // saudavel_vegetariano
            'vegetariano' => 'saudavel_vegetariano',
            'vegano' => 'saudavel_vegetariano',
            'plant-based' => 'saudavel_vegetariano',
            'sem-gluten' => 'saudavel_vegetariano',
            'sem-lactose' => 'saudavel_vegetariano',
            'organico' => 'saudavel_vegetariano',
            'natural-saudavel' => 'saudavel_vegetariano',
            'comida-funcional' => 'saudavel_vegetariano',
            'low-carb' => 'saudavel_vegetariano',
            'comida-fit-saudavel' => 'saudavel_vegetariano',
            'saladas-bowls' => 'saudavel_vegetariano',

            // mercado_emporio
            'mercado-mini-mercado' => 'mercado_emporio',
            'emporio' => 'mercado_emporio',
            'loja-de-produtos-naturais' => 'mercado_emporio',
            'acougue-gourmet' => 'mercado_emporio',
            'hortifruti' => 'mercado_emporio',
            'loja-de-conveniencia' => 'mercado_emporio',
            'loja-de-vinhos-e-destilados' => 'mercado_emporio',
        ];
    }

    /**
     * Mapeamento de tags de estilo/formato (NÃO são arquétipos)
     * Retorna array com as tags a serem salvas no term meta
     */
    private static function get_style_tags_map(): array {
        return [
            // Tags de Formato de Serviço
            'delivery-only-dark-kitchen' => ['_vc_style' => 'dark_kitchen'],
            'drive-thru' => ['_vc_style' => 'drive_thru'],
            'take-away-para-levar' => ['_vc_style' => 'take_away'],
            'praca-de-alimentacao-food-court' => ['_vc_style' => 'food_court'],
            'rodizio-geral' => ['_vc_service' => 'rodizio'],
            'buffet-livre' => ['_vc_service' => 'buffet'],
            'a-la-carte' => ['_vc_service' => 'a_la_carte'],

            // Tags de Experiência/Audiência
            'restaurante-familiar-kids-friendly' => ['_vc_audience' => 'family'],
            'restaurante-romantico' => ['_vc_occasion' => 'romantic'],
            'restaurante-tematico' => ['_vc_occasion' => 'themed'],
            'restaurante-com-musica-ao-vivo' => ['_vc_occasion' => 'live_music'],
        ];
    }

    /**
     * Retorna o arquétipo de uma cuisine baseado no term_id
     * 
     * @param int|WP_Term $cuisine Term ID ou objeto WP_Term
     * @return string|null Arquétipo ou null se não encontrado
     */
    public static function get_archetype_for_cuisine( $cuisine ): ?string {
        $term = null;

        if ( $cuisine instanceof WP_Term ) {
            $term = $cuisine;
        } elseif ( is_numeric( $cuisine ) ) {
            $term = get_term( (int) $cuisine, 'vc_cuisine' );
            if ( ! $term || is_wp_error( $term ) ) {
                return null;
            }
        } else {
            return null;
        }

        // Primeiro tenta ler do meta (já migrado)
        $archetype = get_term_meta( $term->term_id, '_vc_cuisine_archetype', true );
        if ( ! empty( $archetype ) ) {
            return sanitize_key( $archetype );
        }

        // Fallback: mapeia pelo slug
        $map = self::get_slug_to_archetype_map();
        $slug = $term->slug;
        
        return $map[ $slug ] ?? null;
    }

    /**
     * Define o arquétipo de uma cuisine
     * 
     * @param int $term_id ID do termo
     * @param string $archetype Arquétipo a ser definido
     * @return bool True se salvou com sucesso
     */
    public static function set_archetype_for_cuisine( int $term_id, string $archetype ): bool {
        if ( $term_id <= 0 || empty( $archetype ) ) {
            return false;
        }

        $archetype = sanitize_key( $archetype );
        return update_term_meta( $term_id, '_vc_cuisine_archetype', $archetype );
    }

    /**
     * Retorna os arquétipos de um restaurante baseado nas suas cuisines
     * 
     * @param int $restaurant_id ID do restaurante
     * @return array Array de arquétipos únicos
     */
    public static function get_archetypes_for_restaurant( int $restaurant_id ): array {
        if ( $restaurant_id <= 0 ) {
            return [];
        }

        $cuisines = wp_get_object_terms( $restaurant_id, 'vc_cuisine' );
        
        if ( is_wp_error( $cuisines ) || empty( $cuisines ) ) {
            return [];
        }

        $archetypes = [];
        foreach ( $cuisines as $cuisine ) {
            $archetype = self::get_archetype_for_cuisine( $cuisine );
            if ( $archetype ) {
                $archetypes[] = $archetype;
            }
        }

        return array_unique( $archetypes );
    }

    /**
     * Retorna as tags de estilo/formato de uma cuisine
     * 
     * @param int|WP_Term $cuisine Term ID ou objeto WP_Term
     * @return array Array associativo com tags (ex: ['_vc_style' => 'dark_kitchen'])
     */
    public static function get_style_tags_for_cuisine( $cuisine ): array {
        $term = null;

        if ( $cuisine instanceof WP_Term ) {
            $term = $cuisine;
        } elseif ( is_numeric( $cuisine ) ) {
            $term = get_term( (int) $cuisine, 'vc_cuisine' );
            if ( ! $term || is_wp_error( $term ) ) {
                return [];
            }
        } else {
            return [];
        }

        $map = self::get_style_tags_map();
        $slug = $term->slug;
        
        return $map[ $slug ] ?? [];
    }

    /**
     * Retorna o mapeamento completo slug => archetype
     * Útil para scripts de migração
     * 
     * @return array
     */
    public static function get_slug_archetype_mapping(): array {
        return self::get_slug_to_archetype_map();
    }

    /**
     * Retorna o mapeamento completo de tags de estilo
     * Útil para scripts de migração
     * 
     * @return array
     */
    public static function get_style_tags_mapping(): array {
        return self::get_style_tags_map();
    }
}

