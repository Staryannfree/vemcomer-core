<?php
/**
 * Restaurant_Status_Service — Serviço para validar status do restaurante
 * @package VemComerCore
 */

namespace VC\Services;

use VC\Utils\Restaurant_Helper;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Serviço para validar status do restaurante
 */
class Restaurant_Status_Service {
    /**
     * Número mínimo de produtos para ativar a loja
     */
    const MIN_PRODUCTS = 5;

    /**
     * Obtém o status completo do restaurante diretamente pelo ID
     * 
     * @param int $restaurant_id ID do restaurante
     * @return array Status do restaurante com:
     *   - active: boolean (se loja está ativa)
     *   - products: int (quantidade de produtos)
     *   - checks: array (min_products, has_whatsapp, has_address, has_hours)
     *   - restaurant_id: int
     *   - reason: string (se não ativo, motivo)
     */
    public static function get_status_for_restaurant( int $restaurant_id ): array {
        if ( $restaurant_id <= 0 ) {
            return [
                'active'        => false,
                'reason'        => 'invalid_restaurant_id',
                'products'      => 0,
                'checks'        => [],
                'restaurant_id' => 0,
            ];
        }

        $restaurant = get_post( $restaurant_id );
        if ( ! $restaurant || 'vc_restaurant' !== $restaurant->post_type ) {
            return [
                'active'        => false,
                'reason'        => 'restaurant_not_found',
                'products'      => 0,
                'checks'        => [],
                'restaurant_id' => 0,
            ];
        }

        return self::calculate_status( $restaurant_id );
    }

    /**
     * Obtém o status completo do restaurante do usuário
     * 
     * @param int $user_id ID do usuário (0 = usuário atual)
     * @return array Status do restaurante com:
     *   - active: boolean (se loja está ativa)
     *   - products: int (quantidade de produtos)
     *   - checks: array (min_products, has_whatsapp, has_address, has_hours)
     *   - restaurant_id: int
     *   - reason: string (se não ativo, motivo)
     */
    public static function get_status_for_user( int $user_id = 0 ): array {
        $restaurant = Restaurant_Helper::get_restaurant_for_user( $user_id );

        if ( ! $restaurant ) {
            return [
                'active'        => false,
                'reason'        => 'no_restaurant',
                'products'      => 0,
                'checks'        => [],
                'restaurant_id' => 0,
            ];
        }

        $restaurant_id = $restaurant->ID;
        return self::calculate_status( $restaurant_id );
    }

    /**
     * Calcula o status do restaurante (lógica compartilhada)
     * 
     * @param int $restaurant_id ID do restaurante
     * @return array Status do restaurante
     */
    private static function calculate_status( int $restaurant_id ): array {

        // 1) Quantidade de produtos
        $products_count = self::count_menu_items( $restaurant_id );

        // 2) Dados básicos
        $whatsapp = get_post_meta( $restaurant_id, 'vc_restaurant_whatsapp', true );
        $address  = get_post_meta( $restaurant_id, 'vc_restaurant_address', true );
        
        // Verificar se tem horários configurados (estruturados ou legado)
        $schedule_json = get_post_meta( $restaurant_id, '_vc_restaurant_schedule', true );
        $hours_legacy  = get_post_meta( $restaurant_id, 'vc_restaurant_open_hours', true );
        $has_hours     = ! empty( $schedule_json ) || ! empty( $hours_legacy );

        $checks = [
            'min_products' => $products_count >= self::MIN_PRODUCTS,
            'has_whatsapp' => ! empty( $whatsapp ),
            'has_address'  => ! empty( $address ),
            'has_hours'    => $has_hours,
        ];

        // Loja está ativa se tiver produtos mínimos, WhatsApp e endereço
        $active = $checks['min_products'] && $checks['has_whatsapp'] && $checks['has_address'];

        return [
            'active'        => $active,
            'products'      => $products_count,
            'checks'        => $checks,
            'restaurant_id' => $restaurant_id,
            'reason'        => $active ? '' : self::get_inactive_reason( $checks, $products_count ),
        ];
    }

    /**
     * Retorna o motivo pelo qual a loja não está ativa
     * 
     * @param array $checks Array de verificações
     * @param int   $products_count Quantidade de produtos
     * @return string Motivo da inatividade
     */
    private static function get_inactive_reason( array $checks, int $products_count ): string {
        if ( ! $checks['min_products'] ) {
            return sprintf(
                __( 'Cadastre pelo menos %d produtos para começar a receber pedidos.', 'vemcomer' ),
                self::MIN_PRODUCTS
            );
        }

        if ( ! $checks['has_whatsapp'] ) {
            return __( 'Configure o WhatsApp do restaurante.', 'vemcomer' );
        }

        if ( ! $checks['has_address'] ) {
            return __( 'Configure o endereço do restaurante.', 'vemcomer' );
        }

        return __( 'Configure os dados básicos do restaurante.', 'vemcomer' );
    }

    /**
     * Conta produtos do restaurante
     * 
     * @param int $restaurant_id ID do restaurante
     * @return int Quantidade de produtos
     */
    private static function count_menu_items( int $restaurant_id ): int {
        if ( $restaurant_id <= 0 ) {
            return 0;
        }

        $query = new WP_Query( [
            'post_type'      => 'vc_menu_item',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'no_found_rows'  => false,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'   => '_vc_restaurant_id',
                    'value' => $restaurant_id,
                ],
            ],
        ] );

        $count = $query->found_posts;
        wp_reset_postdata();
        return $count;
    }
}

