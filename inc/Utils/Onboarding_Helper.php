<?php
/**
 * Onboarding Helper — Funções para gerenciar o wizard de onboarding de lojistas
 * 
 * @package VemComerCore
 */

namespace VC\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Onboarding_Helper {
    
    /**
     * Verifica se o onboarding do restaurante está incompleto
     * 
     * Critérios para considerar onboarding NÃO concluído:
     * - Loja ainda NÃO tem pelo menos 1 categoria de cardápio OU
     * - Loja ainda NÃO tem pelo menos 1 produto OU
     * - Loja ainda NÃO tem pelo menos 1 dia de horário de funcionamento configurado
     * 
     * @param int|null $restaurant_id ID do restaurante. Se null, busca automaticamente.
     * @return array {
     *     @type bool   $needs_onboarding Se precisa mostrar o wizard
     *     @type int    $current_step     Passo atual (1-7)
     *     @type array  $completed_steps  Array de slugs dos passos concluídos
     *     @type array  $missing_data      Array com o que está faltando
     * }
     */
    public static function get_onboarding_status( ?int $restaurant_id = null ): array {
        if ( ! $restaurant_id ) {
            $restaurant = self::get_current_restaurant();
            if ( ! $restaurant ) {
                return [
                    'needs_onboarding' => false,
                    'current_step'     => 0,
                    'completed_steps'  => [],
                    'missing_data'     => [],
                ];
            }
            $restaurant_id = $restaurant->ID;
        }

        $restaurant = get_post( $restaurant_id );
        if ( ! $restaurant || 'vc_restaurant' !== $restaurant->post_type ) {
            return [
                'needs_onboarding' => false,
                'current_step'     => 0,
                'completed_steps'  => [],
                'missing_data'     => [],
            ];
        }

        // Verificar meta de onboarding concluído (mas priorizar dados reais)
        $onboarding_completed_meta = get_post_meta( $restaurant_id, '_vc_onboarding_completed', true ) === '1';
        $onboarding_step_meta      = (int) get_post_meta( $restaurant_id, '_vc_onboarding_step', true );
        $completed_steps_meta      = get_post_meta( $restaurant_id, '_vc_onboarding_completed_steps', true );
        $completed_steps           = is_array( $completed_steps_meta ) ? $completed_steps_meta : [];
        $forced_first_visit        = get_post_meta( $restaurant_id, '_vc_onboarding_force_first_visit', true ) === '1';

        // Verificar dados reais da loja
        $has_categories = self::has_menu_categories( $restaurant_id );
        $has_products   = self::has_menu_items( $restaurant_id );
        $has_schedule   = self::has_schedule( $restaurant_id );
        $has_basic_data = self::has_basic_data( $restaurant_id );
        $has_address    = self::has_address( $restaurant_id );
        $has_cuisine    = self::has_cuisine_type( $restaurant_id );

        $missing_data = [];
        if ( ! $has_cuisine ) {
            $missing_data[] = 'cuisine_type';
        }
        if ( ! $has_basic_data ) {
            $missing_data[] = 'basic_data';
        }
        if ( ! $has_address ) {
            $missing_data[] = 'address';
        }
        if ( ! $has_schedule ) {
            $missing_data[] = 'schedule';
        }
        if ( ! $has_categories ) {
            $missing_data[] = 'categories';
        }
        if ( ! $has_products ) {
            $missing_data[] = 'products';
        }

        // Se tem todos os dados essenciais, considera concluído (mesmo que o meta não esteja setado)
        $needs_onboarding = ! empty( $missing_data ) || $forced_first_visit;

        // Se o meta diz que está concluído mas faltam dados, forçar onboarding
        if ( $onboarding_completed_meta && $needs_onboarding ) {
            $needs_onboarding = true;
        }

        // Determinar passo atual baseado no que falta
        $current_step = $forced_first_visit
            ? 1
            : self::determine_current_step( $missing_data, $completed_steps, $onboarding_step_meta );

        return [
            'needs_onboarding' => $needs_onboarding,
            'current_step'     => $current_step,
            'completed_steps'  => $completed_steps,
            'missing_data'     => $missing_data,
        ];
    }

    /**
     * Determina qual passo do wizard deve ser exibido
     */
    private static function determine_current_step( array $missing_data, array $completed_steps, int $meta_step ): int {
        // Se já tem um passo salvo no meta e não está nos dados faltando, usar o meta
        if ( $meta_step > 0 && $meta_step <= 7 ) {
            return $meta_step;
        }

        // Determinar baseado no que falta
        if ( in_array( 'cuisine_type', $missing_data, true ) ) {
            return 1;
        }
        if ( in_array( 'basic_data', $missing_data, true ) ) {
            return 2;
        }
        if ( in_array( 'address', $missing_data, true ) || in_array( 'schedule', $missing_data, true ) ) {
            return 3;
        }
        if ( in_array( 'categories', $missing_data, true ) ) {
            return 4;
        }
        if ( in_array( 'products', $missing_data, true ) ) {
            return 5;
        }

        // Se chegou aqui, só falta revisão/ativação
        return 7;
    }

    /**
     * Verifica se o restaurante tem tipo de cozinha definido
     */
    private static function has_cuisine_type( int $restaurant_id ): bool {
        $primary = (int) get_post_meta( $restaurant_id, '_vc_primary_cuisine', true );
        if ( $primary > 0 ) {
            return true;
        }

        $terms = wp_get_post_terms( $restaurant_id, 'vc_cuisine', [ 'fields' => 'ids' ] );
        return ! is_wp_error( $terms ) && ! empty( $terms );
    }

    /**
     * Verifica se o restaurante tem dados básicos (nome, telefone)
     */
    private static function has_basic_data( int $restaurant_id ): bool {
        $restaurant = get_post( $restaurant_id );
        if ( ! $restaurant ) {
            return false;
        }

        $name = trim( $restaurant->post_title );
        if ( empty( $name ) ) {
            return false;
        }

        $whatsapp = (string) get_post_meta( $restaurant_id, 'vc_restaurant_whatsapp', true );
        return ! empty( trim( $whatsapp ) );
    }

    /**
     * Verifica se o restaurante tem endereço configurado
     */
    private static function has_address( int $restaurant_id ): bool {
        $address = (string) get_post_meta( $restaurant_id, 'vc_restaurant_address', true );
        return ! empty( trim( $address ) );
    }

    /**
     * Verifica se o restaurante tem horário de funcionamento configurado
     */
    private static function has_schedule( int $restaurant_id ): bool {
        $schedule_json = get_post_meta( $restaurant_id, '_vc_restaurant_schedule', true );
        if ( ! $schedule_json ) {
            return false;
        }

        $schedule = json_decode( $schedule_json, true );
        if ( ! is_array( $schedule ) ) {
            return false;
        }

        // Verificar se pelo menos um dia está habilitado
        $days = [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ];
        foreach ( $days as $day ) {
            if ( ! empty( $schedule[ $day ]['enabled'] ) ) {
                $periods = $schedule[ $day ]['periods'] ?? [];
                if ( ! empty( $periods ) && is_array( $periods ) ) {
                    foreach ( $periods as $period ) {
                        if ( ! empty( $period['open'] ) && ! empty( $period['close'] ) ) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Verifica se o restaurante tem categorias de cardápio
     */
    private static function has_menu_categories( int $restaurant_id ): bool {
        $terms = get_terms( [
            'taxonomy'   => 'vc_menu_category',
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key'   => '_vc_restaurant_id',
                    'value' => $restaurant_id,
                ],
            ],
        ] );

        if ( is_wp_error( $terms ) ) {
            return false;
        }

        // Filtrar categorias do catálogo
        $user_categories = array_filter( $terms, function( $term ) {
            $is_catalog = get_term_meta( $term->term_id, '_vc_is_catalog_category', true );
            return $is_catalog !== '1';
        } );

        return count( $user_categories ) > 0;
    }

    /**
     * Verifica se o restaurante tem produtos no cardápio
     * Requer pelo menos MIN_PRODUCTS produtos para considerar completo
     */
    private static function has_menu_items( int $restaurant_id ): bool {
        // Usar o mesmo mínimo do Restaurant_Status_Service para manter consistência
        $min_products = 5;
        if ( class_exists( '\\VC\\Services\\Restaurant_Status_Service' ) ) {
            $min_products = \VC\Services\Restaurant_Status_Service::MIN_PRODUCTS;
        }

        $query = new \WP_Query( [
            'post_type'      => 'vc_menu_item',
            'posts_per_page' => $min_products,
            'post_status'    => 'any',
            'meta_query'     => [
                [
                    'key'   => '_vc_restaurant_id',
                    'value' => $restaurant_id,
                ],
            ],
            'no_found_rows'  => false, // Precisamos contar para verificar se tem pelo menos MIN_PRODUCTS
        ] );

        $has_items = $query->found_posts >= $min_products;
        wp_reset_postdata();

        return $has_items;
    }

    /**
     * Marca um passo do onboarding como concluído
     * 
     * @param int    $restaurant_id ID do restaurante
     * @param string $step_slug     Slug do passo (ex: 'welcome', 'basic_data', 'address', etc.)
     */
    public static function mark_step_completed( int $restaurant_id, string $step_slug ): void {
        $completed_steps = get_post_meta( $restaurant_id, '_vc_onboarding_completed_steps', true );
        if ( ! is_array( $completed_steps ) ) {
            $completed_steps = [];
        }

        if ( ! in_array( $step_slug, $completed_steps, true ) ) {
            $completed_steps[] = $step_slug;
            update_post_meta( $restaurant_id, '_vc_onboarding_completed_steps', $completed_steps );
        }
    }

    /**
     * Atualiza o passo atual do onboarding
     * 
     * @param int $restaurant_id ID do restaurante
     * @param int $step          Número do passo (1-7)
     */
    public static function update_current_step( int $restaurant_id, int $step ): void {
        if ( $step >= 1 && $step <= 7 ) {
            update_post_meta( $restaurant_id, '_vc_onboarding_step', $step );
        }
    }

    /**
     * Marca o onboarding como completo
     * 
     * @param int $restaurant_id ID do restaurante
     */
    public static function complete_onboarding( int $restaurant_id ): void {
        update_post_meta( $restaurant_id, '_vc_onboarding_completed', '1' );
        update_post_meta( $restaurant_id, '_vc_onboarding_step', 7 );
        delete_post_meta( $restaurant_id, '_vc_onboarding_force_first_visit' );
        
        // Garantir que a loja está ativa
        $restaurant = get_post( $restaurant_id );
        if ( $restaurant && 'vc_restaurant' === $restaurant->post_type ) {
            if ( 'publish' !== $restaurant->post_status ) {
                wp_update_post( [
                    'ID'          => $restaurant_id,
                    'post_status' => 'publish',
                ] );
            }
        }
    }

    /**
     * Restaura o estado de primeira visita para o restaurante.
     *
     * @param int $restaurant_id ID do restaurante
     */
    public static function reset_to_first_visit( int $restaurant_id ): void {
        $restaurant = get_post( $restaurant_id );
        if ( ! $restaurant || 'vc_restaurant' !== $restaurant->post_type ) {
            return;
        }

        delete_post_meta( $restaurant_id, '_vc_onboarding_completed' );
        delete_post_meta( $restaurant_id, '_vc_onboarding_step' );
        delete_post_meta( $restaurant_id, '_vc_onboarding_completed_steps' );
        update_post_meta( $restaurant_id, '_vc_onboarding_force_first_visit', '1' );
    }

    /**
     * Busca o restaurante do usuário atual
     */
    private static function get_current_restaurant(): ?\WP_Post {
        if ( function_exists( '\\vc_marketplace_current_restaurant' ) ) {
            return \vc_marketplace_current_restaurant();
        }

        $user = wp_get_current_user();
        if ( ! $user || 0 === $user->ID ) {
            return null;
        }

        $meta_id = (int) get_user_meta( $user->ID, 'vc_restaurant_id', true );
        if ( $meta_id ) {
            $restaurant = get_post( $meta_id );
            if ( $restaurant && 'vc_restaurant' === $restaurant->post_type ) {
                return $restaurant;
            }
        }

        $query = new \WP_Query( [
            'post_type'      => 'vc_restaurant',
            'author'         => $user->ID,
            'posts_per_page' => 1,
            'post_status'    => [ 'publish', 'pending', 'draft' ],
            'no_found_rows'  => true,
        ] );

        if ( $query->have_posts() ) {
            $restaurant = $query->posts[0];
            wp_reset_postdata();
            return $restaurant;
        }

        wp_reset_postdata();
        return null;
    }
}

