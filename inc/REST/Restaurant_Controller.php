<?php
/**
 * Restaurant_Controller — REST endpoints públicos para restaurantes e itens
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Model\CPT_Restaurant;
use VC\Model\CPT_MenuItem;
use WP_Query;
use WP_REST_Request;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Restaurant_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
    }

    public function routes(): void {
        register_rest_route( 'vemcomer/v1', '/restaurants', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_restaurants' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'vemcomer/v1', '/restaurants/(?P<id>\\d+)/menu-items', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_menu_items' ],
            'permission_callback' => '__return_true',
            'args'                => [ 'id' => [ 'validate_callback' => 'is_numeric' ] ],
        ] );
    }

    public function get_restaurants( WP_REST_Request $request ) {
        $q = new WP_Query([
            'post_type'      => CPT_Restaurant::SLUG,
            'posts_per_page' => 50,
            'no_found_rows'  => true,
            'post_status'    => 'publish',
        ]);
        $items = [];
        foreach ( $q->posts as $p ) {
            $items[] = [
                'id'            => $p->ID,
                'title'         => get_the_title( $p ),
                'address'       => get_post_meta( $p->ID, '_vc_address', true ),
                'phone'         => get_post_meta( $p->ID, '_vc_phone', true ),
                'whatsapp'      => get_post_meta( $p->ID, '_vc_whatsapp', true ),
                'min_order'     => get_post_meta( $p->ID, '_vc_min_order', true ),
                'delivery_time' => get_post_meta( $p->ID, '_vc_delivery_time', true ),
                'opening_hours' => get_post_meta( $p->ID, '_vc_opening_hours', true ),
                'is_open'       => (bool) get_post_meta( $p->ID, '_vc_is_open', true ),
                'cuisines'      => wp_get_post_terms( $p->ID, CPT_Restaurant::TAX_CUISINE, [ 'fields' => 'names' ] ),
            ];
        }
        return rest_ensure_response( $items );
    }

    public function get_menu_items( WP_REST_Request $request ) {
        $rid = (int) $request->get_param( 'id' );
        if ( $rid <= 0 ) {
            return new WP_Error( 'vc_invalid_restaurant', __( 'Restaurante inválido.', 'vemcomer' ), [ 'status' => 400 ] );
        }
        $q = new WP_Query([
            'post_type'      => CPT_MenuItem::SLUG,
            'posts_per_page' => 100,
            'no_found_rows'  => true,
            'post_status'    => 'publish',
            'meta_query'     => [ [ 'key' => '_vc_restaurant_id', 'value' => $rid, 'compare' => '=' ] ],
        ]);
        $items = [];
        foreach ( $q->posts as $p ) {
            $items[] = [
                'id'          => $p->ID,
                'title'       => get_the_title( $p ),
                'description' => wp_strip_all_tags( get_post_field( 'post_content', $p ) ),
                'price'       => get_post_meta( $p->ID, '_vc_price', true ),
                'prep_time'   => get_post_meta( $p->ID, '_vc_prep_time', true ),
                'available'   => (bool) get_post_meta( $p->ID, '_vc_is_available', true ),
                'categories'  => wp_get_post_terms( $p->ID, CPT_MenuItem::TAX_CATEGORY, [ 'fields' => 'names' ] ),
            ];
        }
        return rest_ensure_response( $items );
    }
}
