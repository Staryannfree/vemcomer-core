<?php
/**
 * Restaurant_Helper — Helper central para descobrir o restaurante de um lojista
 * @package VemComerCore
 */

namespace VC\Utils;

use WP_Post;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper central para descobrir o restaurante de um lojista.
 * 
 * Regras:
 * 1) Filter hook (para override externo)
 * 2) usermeta 'vc_restaurant_id'
 * 3) fallback: restaurante onde ele é post_author
 *    (e nesse caso, já corrige o usermeta)
 */
class Restaurant_Helper {
    /**
     * Retorna o post do restaurante do usuário
     * 
     * @param int $user_id ID do usuário (0 = usuário atual)
     * @return WP_Post|null Post do restaurante ou null se não encontrado
     */
    public static function get_restaurant_for_user( int $user_id = 0 ): ?WP_Post {
        if ( $user_id === 0 ) {
            $user_id = get_current_user_id();
        }

        if ( $user_id <= 0 ) {
            return null;
        }

        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return null;
        }

        // 1) Filtro – permite override em temas/plugins
        $filtered_id = (int) apply_filters( 'vemcomer/restaurant_id_for_user', 0, $user );
        if ( $filtered_id > 0 ) {
            $post = get_post( $filtered_id );
            if ( $post instanceof WP_Post && $post->post_type === 'vc_restaurant' ) {
                return $post;
            }
        }

        // 2) usermeta (fonte oficial)
        $meta_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );
        if ( $meta_id > 0 ) {
            $post = get_post( $meta_id );
            if ( $post instanceof WP_Post && $post->post_type === 'vc_restaurant' ) {
                return $post;
            }
        }

        // 3) fallback: restaurante onde ele é autor
        $q = new WP_Query( [
            'post_type'      => 'vc_restaurant',
            'posts_per_page' => 1,
            'post_status'    => [ 'publish', 'pending', 'draft' ],
            'author'         => $user_id,
            'no_found_rows'  => true,
        ] );

        if ( $q->have_posts() ) {
            /** @var WP_Post $restaurant */
            $restaurant = $q->posts[0];
            wp_reset_postdata();

            if ( $restaurant instanceof WP_Post ) {
                // auto-corrige o usermeta pra próxima vez
                update_user_meta( $user_id, 'vc_restaurant_id', $restaurant->ID );
                return $restaurant;
            }
        }

        wp_reset_postdata();
        return null;
    }

    /**
     * Retorna apenas o ID do restaurante do usuário
     * 
     * @param int $user_id ID do usuário (0 = usuário atual)
     * @return int ID do restaurante ou 0 se não encontrado
     */
    public static function get_restaurant_id_for_user( int $user_id = 0 ): int {
        $restaurant = self::get_restaurant_for_user( $user_id );
        return $restaurant ? (int) $restaurant->ID : 0;
    }

    /**
     * Anexa um restaurante a um produto (item do cardápio)
     * 
     * Atualiza o meta oficial `_vc_restaurant_id` e mantém o legado
     * `_vc_menu_item_restaurant` em sincronia para compatibilidade.
     * 
     * @param int $product_id ID do produto (vc_menu_item)
     * @param int $restaurant_id ID do restaurante
     * @return void
     */
    public static function attach_restaurant_to_product( int $product_id, int $restaurant_id ): void {
        if ( $product_id <= 0 || $restaurant_id <= 0 ) {
            return;
        }

        // Meta oficial
        update_post_meta( $product_id, '_vc_restaurant_id', $restaurant_id );

        // LEGADO: manter em sincronia, mas você pode remover isso no futuro
        update_post_meta( $product_id, '_vc_menu_item_restaurant', $restaurant_id );
    }
}

