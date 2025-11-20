<?php
/**
 * REST API – Lista de restaurantes com filtros
 * Rota: /vemcomer/v1/restaurants
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'rest_api_init', function() {
    register_rest_route( 'vemcomer/v1', '/restaurants', [
        'methods'  => WP_REST_Server::READABLE,
        'callback' => 'vc_rest_api_get_restaurants',
        'permission_callback' => '__return_true', // leitura pública
        'args' => [
            'search'    => [ 'type' => 'string', 'required' => false ],
            'cuisine'   => [ 'type' => 'string', 'required' => false ],
            'location'  => [ 'type' => 'string', 'required' => false ],
            'delivery'  => [ 'type' => 'boolean', 'required' => false ],
            'page'      => [ 'type' => 'integer', 'required' => false, 'default' => 1 ],
            'per_page'  => [ 'type' => 'integer', 'required' => false, 'default' => 10 ],
        ],
    ]);
});

function vc_rest_api_get_restaurants( WP_REST_Request $req ) : WP_REST_Response {
    $page     = max( 1, (int) $req->get_param('page') );
    $per_page = max( 1, min( 50, (int) $req->get_param('per_page') ) );
    $search   = sanitize_text_field( (string) $req->get_param('search') );
    $cuisine  = sanitize_title( (string) $req->get_param('cuisine') );
    $location = sanitize_title( (string) $req->get_param('location') );
    $delivery = $req->get_param('delivery');

    $tax_query = [];
    if ( $cuisine ) {
        $tax_query[] = [
            'taxonomy' => 'vc_cuisine',
            'field'    => 'slug',
            'terms'    => [ $cuisine ],
        ];
    }
    if ( $location ) {
        $tax_query[] = [
            'taxonomy' => 'vc_location',
            'field'    => 'slug',
            'terms'    => [ $location ],
        ];
    }
    if ( count( $tax_query ) > 1 ) {
        $tax_query['relation'] = 'AND';
    }

    $meta_query = [];
    if ( ! is_null( $delivery ) ) {
        $meta_query[] = [
            'key'   => 'vc_restaurant_delivery',
            'value' => $delivery ? '1' : '0',
        ];
    }

    \VC\Logging\log_event( 'Legacy REST restaurants query', [
        'page'     => $page,
        'per_page' => $per_page,
        'search'   => $search,
        'cuisine'  => $cuisine,
        'location' => $location,
        'delivery' => $delivery,
    ], 'debug' );

    $q = new WP_Query([
        'post_type'      => 'vc_restaurant',
        's'              => $search ?: '',
        'tax_query'      => $tax_query ?: '',
        'meta_query'     => $meta_query ?: '',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'no_found_rows'  => false,
    ]);

    $items = [];
    foreach ( $q->posts as $post ) {
        $pid = $post->ID;
        $items[] = [
            'id'          => $pid,
            'title'       => get_the_title( $pid ),
            'excerpt'     => get_the_excerpt( $pid ),
            'thumbnail'   => get_the_post_thumbnail_url( $pid, 'medium' ),
            'cuisines'    => wp_get_post_terms( $pid, 'vc_cuisine', [ 'fields' => 'all' ] ),
            'locations'   => wp_get_post_terms( $pid, 'vc_location', [ 'fields' => 'all' ] ),
                'meta'        => [
                    'cnpj'       => get_post_meta( $pid, 'vc_restaurant_cnpj', true ),
                    'whatsapp'   => get_post_meta( $pid, 'vc_restaurant_whatsapp', true ),
                    'site'       => get_post_meta( $pid, 'vc_restaurant_site', true ),
                    'open_hours' => get_post_meta( $pid, 'vc_restaurant_open_hours', true ),
                    'delivery'   => get_post_meta( $pid, 'vc_restaurant_delivery', true ) === '1',
                    'address'    => get_post_meta( $pid, 'vc_restaurant_address', true ),
                    'lat'        => get_post_meta( $pid, 'vc_restaurant_lat', true ),
                    'lng'        => get_post_meta( $pid, 'vc_restaurant_lng', true ),
                ],
            'link'        => get_permalink( $pid ),
        ];
    }

    $total     = (int) $q->found_posts;
    $total_pg  = (int) ceil( $total / $per_page );

    $res = new WP_REST_Response( $items );
    $res->header( 'X-WP-Total', $total );
    $res->header( 'X-WP-TotalPages', $total_pg );

    \VC\Logging\log_event( 'Legacy REST restaurants response', [ 'count' => count( $items ), 'total' => $total ], 'debug' );

    return $res;
}
