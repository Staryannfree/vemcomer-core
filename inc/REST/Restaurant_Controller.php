<?php
/**
 * Restaurant_Controller — REST endpoints públicos para restaurantes e itens
 * Filtros suportados em GET /wp-json/vemcomer/v1/restaurants:
 *   - cuisine (slug da taxonomia vc_cuisine)
 *   - delivery (true|false) — meta _vc_has_delivery
 *   - is_open (true|false) — meta _vc_is_open
 *   - per_page (1..50)
 *   - search (texto)
 *   - orderby (title|date) e order (asc|desc)
 *
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Model\CPT_MenuItem;
use VC\Model\CPT_Restaurant;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Restaurant_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
    }

    public function routes(): void {
        register_rest_route( 'vemcomer/v1', '/restaurants', [
            'methods'  => 'GET',
            'callback' => [ $this, 'get_restaurants' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'vemcomer/v1', '/restaurants/(?P<id>\d+)/menu-items', [
            'methods'  => 'GET',
            'callback' => [ $this, 'get_menu_items' ],
            'permission_callback' => '__return_true',
            'args' => [ 'id' => [ 'validate_callback' => 'is_numeric' ] ],
        ] );
    }

    public function get_restaurants( WP_REST_Request $request ) {
        $per_page = (int) $request->get_param( 'per_page' );
        $per_page = $per_page > 0 ? min( $per_page, 50 ) : 10;

        $args = [
            'post_type'      => CPT_Restaurant::SLUG,
            'posts_per_page' => $per_page,
            'no_found_rows'  => true,
            'post_status'    => 'publish',
        ];

        // Busca livre
        $search = (string) $request->get_param( 'search' );
        if ( $search ) { $args['s'] = $search; }

        // Ordenação
        $orderby = (string) $request->get_param( 'orderby' );
        $order   = strtoupper( (string) $request->get_param( 'order' ) );
        if ( in_array( $orderby, [ 'title', 'date' ], true ) ) { $args['orderby'] = $orderby; }
        if ( in_array( $order, [ 'ASC', 'DESC' ], true ) ) { $args['order'] = $order; }

        // Taxonomia: cozinha
        $cuisine = (string) $request->get_param( 'cuisine' );
        if ( $cuisine ) {
            $args['tax_query'][] = [
                'taxonomy' => CPT_Restaurant::TAX_CUISINE,
                'field'    => 'slug',
                'terms'    => array_map( 'sanitize_title', array_map( 'trim', explode( ',', $cuisine ) ) ),
            ];
        }

        // Metas: delivery / is_open
        $meta = [];
        $delivery = $request->get_param( 'delivery' );
        if ( null !== $delivery ) {
            $meta[] = [ 'key' => '_vc_has_delivery', 'value' => ( filter_var( $delivery, FILTER_VALIDATE_BOOLEAN ) ? '1' : '0' ) ];
        }
        $is_open = $request->get_param( 'is_open' );
        if ( null !== $is_open ) {
            $meta[] = [ 'key' => '_vc_is_open', 'value' => ( filter_var( $is_open, FILTER_VALIDATE_BOOLEAN ) ? '1' : '0' ) ];
        }
        if ( $meta ) { $args['meta_query'] = $meta; }

        log_event( 'REST restaurants query', [ 'args' => $args ], 'debug' );

        $q = new WP_Query( $args );

        $items = [];
        foreach ( $q->posts as $p ) {
            $terms = wp_get_object_terms( $p->ID, CPT_Restaurant::TAX_CUISINE, [ 'fields' => 'slugs' ] );
            $items[] = [
                'id'        => $p->ID,
                'title'     => get_the_title( $p ),
                'address'   => (string) get_post_meta( $p->ID, '_vc_address', true ),
                'phone'     => (string) get_post_meta( $p->ID, '_vc_phone', true ),
                'has_delivery' => (bool) get_post_meta( $p->ID, '_vc_has_delivery', true ),
                'is_open'   => (bool) get_post_meta( $p->ID, '_vc_is_open', true ),
                'cuisines'  => array_values( array_map( 'strval', (array) $terms ) ),
            ];
        }

        return new WP_REST_Response( $items, 200 );
    }

    public function get_menu_items( WP_REST_Request $request ) {
        $rid = (int) $request->get_param( 'id' );
        if ( ! $rid ) {
            log_event( 'REST menu items missing id', [], 'warning' );
            return new WP_REST_Response( [], 200 );
        }

        $q = new WP_Query([
            'post_type'      => CPT_MenuItem::SLUG,
            'posts_per_page' => 100,
            'no_found_rows'  => true,
            'post_status'    => 'publish',
            'meta_query'     => [ [ 'key' => '_vc_restaurant_id', 'value' => (string) $rid ] ],
        ]);

        $items = [];
        foreach ( $q->posts as $p ) {
            $items[] = [
                'id'          => $p->ID,
                'title'       => get_the_title( $p ),
                'price'       => (string) get_post_meta( $p->ID, '_vc_price', true ),
                'prep_time'   => (int) get_post_meta( $p->ID, '_vc_prep_time', true ),
                'is_available'=> (bool) get_post_meta( $p->ID, '_vc_is_available', true ),
            ];
        }

        log_event( 'REST menu items query', [ 'restaurant_id' => $rid, 'count' => count( $items ) ], 'debug' );

        return new WP_REST_Response( $items, 200 );
    }
}
