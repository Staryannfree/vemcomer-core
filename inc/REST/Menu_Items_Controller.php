<?php
/**
 * Menu_Items_Controller — REST endpoints para itens do cardápio
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Model\CPT_MenuItem;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Menu_Items_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
    }

    public function routes(): void {
        // GET: Lista itens do cardápio (público)
        register_rest_route( 'vemcomer/v1', '/menu-items', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_menu_items' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'featured'  => [
                    'required'          => false,
                    'validate_callback' => function( $param ) {
                        return in_array( $param, [ 'true', 'false', '1', '0', '' ], true );
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'restaurant_id' => [
                    'required'          => false,
                    'validate_callback' => 'is_numeric',
                    'sanitize_callback' => 'absint',
                ],
                'per_page' => [
                    'required'          => false,
                    'default'           => 10,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param ) && $param > 0 && $param <= 50;
                    },
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );
    }

    /**
     * GET /wp-json/vemcomer/v1/menu-items
     * Lista itens do cardápio (pratos do dia se featured=true)
     */
    public function get_menu_items( WP_REST_Request $request ): WP_REST_Response {
        $featured      = $request->get_param( 'featured' );
        $restaurant_id = $request->get_param( 'restaurant_id' );
        $per_page      = (int) $request->get_param( 'per_page' ) ?: 10;

        $args = [
            'post_type'      => CPT_MenuItem::SLUG,
            'posts_per_page' => $per_page,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        $meta_query = [];

        // Filtrar por featured (pratos do dia)
        if ( $featured === 'true' || $featured === '1' ) {
            $meta_query[] = [
                'key'   => '_vc_menu_item_featured',
                'value' => '1',
            ];
        }

        // Filtrar por restaurante
        if ( $restaurant_id > 0 ) {
            $meta_query[] = [
                'key'   => '_vc_restaurant_id',
                'value' => (string) $restaurant_id,
            ];
        }

        if ( ! empty( $meta_query ) ) {
            if ( count( $meta_query ) > 1 ) {
                $meta_query['relation'] = 'AND';
            }
            $args['meta_query'] = $meta_query;
        }

        $query = new WP_Query( $args );

        $items = [];
        foreach ( $query->posts as $post ) {
            $restaurant_id = (int) get_post_meta( $post->ID, '_vc_restaurant_id', true );
            $restaurant    = $restaurant_id > 0 ? get_post( $restaurant_id ) : null;
            
            $image_id = get_post_thumbnail_id( $post->ID );
            $price    = get_post_meta( $post->ID, '_vc_price', true );
            
            // Determinar badge (featured, promoção, etc)
            $is_featured = (bool) get_post_meta( $post->ID, '_vc_menu_item_featured', true );
            $badge = '';
            if ( $is_featured ) {
                $badge = 'DESTAQUE';
            } elseif ( $price ) {
                // Verificar se tem desconto (pode ser implementado depois)
                $badge = '';
            }
            
            $items[] = [
                'id'            => $post->ID,
                'name'          => get_the_title( $post ),
                'description'  => wp_trim_words( $post->post_content, 15, '...' ),
                'restaurant'    => $restaurant ? get_the_title( $restaurant ) : '',
                'restaurant_id' => $restaurant_id,
                'restaurant_slug' => $restaurant ? $restaurant->post_name : null, // Adicionar slug para URLs
                'price'         => $price ? 'R$ ' . number_format( (float) $price, 2, ',', '.' ) : '',
                'image'         => $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : null,
                'badge'         => $badge,
                'is_featured'   => $is_featured,
            ];
        }

        return new WP_REST_Response( $items, 200 );
    }
}

