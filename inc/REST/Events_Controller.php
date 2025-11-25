<?php
/**
 * Events_Controller — REST endpoints para eventos gastronômicos
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Model\CPT_Event;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Events_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
    }

    public function routes(): void {
        // GET: Lista eventos (público)
        register_rest_route( 'vemcomer/v1', '/events', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_events' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'featured'  => [
                    'required'          => false,
                    'validate_callback' => function( $param ) {
                        return in_array( $param, [ 'true', 'false', '1', '0', '' ], true );
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'date'     => [
                    'required'          => false,
                    'validate_callback' => function( $param ) {
                        return empty( $param ) || strtotime( $param ) !== false;
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
     * GET /wp-json/vemcomer/v1/events
     * Lista eventos gastronômicos
     */
    public function get_events( WP_REST_Request $request ): WP_REST_Response {
        $featured      = $request->get_param( 'featured' );
        $date          = $request->get_param( 'date' );
        $restaurant_id = $request->get_param( 'restaurant_id' );
        $per_page      = (int) $request->get_param( 'per_page' ) ?: 10;

        $args = [
            'post_type'      => CPT_Event::SLUG,
            'posts_per_page' => $per_page,
            'post_status'    => 'publish',
            'orderby'        => 'meta_value',
            'meta_key'       => '_vc_event_date',
            'order'          => 'ASC',
        ];

        $meta_query = [];

        // Filtrar por featured
        if ( $featured === 'true' || $featured === '1' ) {
            $meta_query[] = [
                'key'   => '_vc_event_is_featured',
                'value' => '1',
            ];
        }

        // Filtrar por restaurante
        if ( $restaurant_id > 0 ) {
            $meta_query[] = [
                'key'   => '_vc_event_restaurant_id',
                'value' => (string) $restaurant_id,
            ];
        }

        // Filtrar por data (eventos de hoje ou futuros)
        if ( $date ) {
            $args['meta_query'][] = [
                'key'     => '_vc_event_date',
                'value'   => $date,
                'compare' => '>=',
            ];
        } else {
            // Por padrão, mostrar apenas eventos futuros ou de hoje
            $today = date( 'Y-m-d' );
            $args['meta_query'][] = [
                'key'     => '_vc_event_date',
                'value'   => $today,
                'compare' => '>=',
            ];
        }

        if ( ! empty( $meta_query ) ) {
            if ( count( $meta_query ) > 1 ) {
                $meta_query['relation'] = 'AND';
            }
            $args['meta_query'] = array_merge( $args['meta_query'] ?? [], $meta_query );
        }

        $query = new WP_Query( $args );

        $events = [];
        foreach ( $query->posts as $post ) {
            $restaurant_id = (int) get_post_meta( $post->ID, '_vc_event_restaurant_id', true );
            $restaurant    = $restaurant_id > 0 ? get_post( $restaurant_id ) : null;
            
            $event_date = get_post_meta( $post->ID, '_vc_event_date', true );
            $event_time = get_post_meta( $post->ID, '_vc_event_time', true );
            
            // Formatar data
            $date_obj = $event_date ? date_create( $event_date ) : null;
            $day = $date_obj ? $date_obj->format( 'd' ) : '';
            $month = $date_obj ? strtoupper( $date_obj->format( 'M' ) ) : '';
            
            $image_id = get_post_thumbnail_id( $post->ID );
            
            $events[] = [
                'id'          => $post->ID,
                'title'       => get_the_title( $post ),
                'description' => wp_trim_words( $post->post_content, 20, '...' ),
                'restaurant'  => $restaurant ? get_the_title( $restaurant ) : '',
                'restaurant_id' => $restaurant_id,
                'date'        => [
                    'day'   => $day,
                    'month' => $month,
                    'full'  => $event_date,
                ],
                'time'        => $event_time ?: '',
                'location'    => (string) get_post_meta( $post->ID, '_vc_event_location', true ),
                'price'       => (string) get_post_meta( $post->ID, '_vc_event_price', true ),
                'image'       => $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : null,
                'is_live'     => get_post_meta( $post->ID, '_vc_event_status', true ) === 'ongoing',
                'is_featured' => (bool) get_post_meta( $post->ID, '_vc_event_is_featured', true ),
            ];
        }

        return new WP_REST_Response( $events, 200 );
    }
}

